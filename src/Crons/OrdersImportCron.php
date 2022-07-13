<?php

namespace RakutenFrance\Crons;

use Exception;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Cron\Contracts\CronHandler;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Services\AddressService;
use RakutenFrance\Services\ItemService;
use RakutenFrance\Services\OrderService;
use RakutenFrance\Services\PaymentService;

class OrdersImportCron extends CronHandler
{
    use Loggable;

    /** @var array */
    private $settings;
    /** @var MarketplaceClient */
    private $apiClient;
    /** @var OrderService */
    private $orderService;
    /** @var AddressService */
    private $addressService;
    /** @var ItemService */
    private $itemService;
    /** @var PaymentService */
    private $paymentService;

    public function __construct(
        PluginSettingsHelper $pluginSettingsHelper,
        MarketplaceClient $apiClient,
        OrderService $orderService,
        AddressService $addressService,
        ItemService $itemService,
        PaymentService $paymentService
    ) {
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->apiClient = $apiClient;
        $this->orderService = $orderService;
        $this->addressService = $addressService;
        $this->itemService = $itemService;
        $this->paymentService = $paymentService;
    }

    public function handle()
    {
        try {
            if ($this->settings[PluginSettingsHelper::JOB_SYNCHRONIZE_MARKETPLACE_ORDERS] !== true) {
                $this->getLogger(__METHOD__)->info(PluginConfiguration::PLUGIN_NAME . '::log.ordersImportOff', [
                    'message' => 'Order synchronization is turner off.',
                ]);

                return;
            }

            $marketplaceOrders = $this->apiClient->getOrders();

            $this->getLogger(__METHOD__)->debug(PluginConfiguration::PLUGIN_NAME . '::log.ordersImportCron', [
                'marketplaceOrders' => $marketplaceOrders,
            ]);

            if (empty($marketplaceOrders)) {
                $this->getLogger(__METHOD__)->info(PluginConfiguration::PLUGIN_NAME . '::log.ordersImportNoOrders', [
                    'message' => 'No orders in marketplace.',
                ]);

                return;
            }

            foreach ($marketplaceOrders as $marketplaceOrder) {
                $this->importOrder($marketplaceOrder);
            }
        } catch (ValidationException $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $e->getMessageBag(),
            ]);
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Import new marketplace order to plentyMarkets.
     *
     * @param array $marketplaceOrder
     *
     * @return void
     */
    private function importOrder(array $marketplaceOrder)
    {
        $plentyOrder = $this->orderService->findOrderByExternalOrderId((string)$marketplaceOrder['purchaseid']);
        if (!empty($plentyOrder)) {
            $this->getLogger(__METHOD__)->info(PluginConfiguration::PLUGIN_NAME . '::log.ordersImportOrderExists', [
                'message' => 'This order already exists in plentymarkets',
                'orderId' => $plentyOrder->id,
                'purchaseId' => $marketplaceOrder['purchaseid'],
            ]);

            return;
        }

        $marketplaceCustomerBillingAddress = $marketplaceOrder['deliveryinformation']['billingaddress'];
        $marketplaceCustomerDeliveryAddress = $marketplaceOrder['deliveryinformation']['deliveryaddress'];

        $customerAddress = $marketplaceCustomerBillingAddress ?? $marketplaceCustomerDeliveryAddress;

        list($billingAddress, $deliveryAddress) = $this->createAddresses(
            $customerAddress,
            $marketplaceOrder['deliveryinformation']['purchasebuyeremail']
        );
        if (empty($billingAddress) || empty($deliveryAddress)) {
            return;
        }
        $country = $this->addressService->getCountryId($customerAddress['country'], $customerAddress['countryalpha2']);
        $orderItems = $this->itemService->createItemOrderLines(
            $marketplaceOrder['items']['item'],
            $this->settings['referrerId'],
            $country ? $country->id : 10
        );

        $order = $this->orderService->placeOrder(
            $marketplaceOrder['purchaseid'],
            $orderItems['items'],
            $deliveryAddress->id,
            $billingAddress->id,
            (string)$this->settings[PluginSettingsHelper::METHOD_OF_PAYMENT_ID],
            $country ? $country->isoCode2 : 'FR'
        );

        $payment = null;
        if ($order instanceof Order) {
            if ($orderItems['missingItems']) {
                foreach ($orderItems['missingItems'] as $missingItem) {
                    $this->orderService->addOrderNote(
                        $order->id,
                        $missingItem,
                        $this->settings[PluginSettingsHelper::PLENTY_ACCOUNT_ID]
                    );
                }
            }

            $payment = $this->createPayment(
                $marketplaceOrder['purchaseid'],
                $marketplaceOrder['purchasedate'],
                $marketplaceOrder['items']['item']
            );

            if ($payment instanceof Payment) {
                // creating relation between payment and order
                $this->paymentService->addPaymentOrderRelation($payment, $order);
            }
        }

        $this->getLogger(__METHOD__)->debug(PluginConfiguration::PLUGIN_NAME . '::log.importOrder', [
            'marketplaceOrder' => $marketplaceOrder,
            'orderItems' => $orderItems,
            'payment' => $payment,
            'order' => $order,
            'billingAddress' => $billingAddress,
            'deliveryAddress' => $deliveryAddress,
        ]);
    }

    /**
     * Create plentyMarkets billing and delivery Addresses.
     *
     * @param        $customerAddress
     * @param string $email
     *
     * @return array
     */
    private function createAddresses($customerAddress, $email): array
    {
        if (empty($customerAddress)) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.ordersImportEmptyAddress', [
                'message' => 'Address could not be created',
                'marketplaceCustomerAddress' => $customerAddress,
            ]);

            return [];
        }

        $addressData = [
            'address1' => $customerAddress['address1'],
            'city' => $customerAddress['city'],
            'zipCode' => $customerAddress['zipcode'],
            'country' => $customerAddress['country'],
            'countryalpha2' => $customerAddress['countryalpha2'],
            'firstName' => $customerAddress['firstname'],
            'lastName' => $customerAddress['lastname'],
            'email' => $email,
            'phone' => $customerAddress['phonenumber1'],
        ];

        $billingAddress = $this->addressService->placeAddress($addressData);
        $deliveryAddress = $this->addressService->placeAddress($addressData);

        if (empty($billingAddress) || empty($deliveryAddress)) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.ordersImportEmptyAddress', [
                'message' => 'Address could not be created',
                'marketplaceCustomerAddress' => $customerAddress,
                'addressData' => $addressData,
                'deliveryAddress' => $deliveryAddress,
                'billingAddress' => $billingAddress,
            ]);

            return [];
        }

        return [$billingAddress, $deliveryAddress];
    }

    /**
     * Create plentyMarkets payment.
     *
     * @param string $transactionId
     * @param string $date
     * @param array $marketplaceItems
     *
     * @return Payment|null
     */
    private function createPayment(string $transactionId, string $date, array $marketplaceItems)
    {
        $amount = 0.0;
        $currency = PluginConfiguration::DEFAULT_CURRENCY;
        foreach ($marketplaceItems as $item) {
            $currency = $item['advertpricelisted']['currency'];
            $amount += (float)$item['advertpricelisted']['amount'];
        }

        return $this->paymentService->placePayment(
            $amount,
            $date,
            $transactionId,
            $currency,
            $this->settings[PluginSettingsHelper::METHOD_OF_PAYMENT_ID]
        );
    }
}
