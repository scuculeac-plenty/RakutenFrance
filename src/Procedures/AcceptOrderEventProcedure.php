<?php

namespace RakutenFrance\Procedures;

use Exception;
use Illuminate\Support\Collection;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\AddressOption;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Contracts\OrderItemRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Models\OrderItem;
use Plenty\Modules\Order\Models\OrderItemType;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\Iso2ToUnCode;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Services\OrderService;
use RakutenFrance\Services\PaymentService;

/**
 * Class AcceptOrderEventProcedure
 *
 * @package RakutenFrance\Procedures
 */
class AcceptOrderEventProcedure
{
    use Loggable;

    /**
     * @var MarketplaceClient
     */
    private $apiClient;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var array
     */
    private $settings;
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepositoryContact;
    /**
     * @var AddressRepositoryContract
     */
    private $addressRepositoryContract;
    /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepositoryContract;
    /**
     * @var PaymentService
     */
    private $paymentService;
    /**
     * @var OrderItemRepositoryContract
     */
    private $orderItemRepositoryContract;
    /**
     * @var CountryRepositoryContract
     */
    private $countryRepositoryContract;

    /**
     * AcceptOrderEventProcedure constructor.
     *
     * @param MarketplaceClient           $apiClient
     * @param OrderService                $orderService
     * @param PluginSettingsHelper        $pluginSettingsHelper
     * @param OrderRepositoryContract     $orderRepositoryContract
     * @param AddressRepositoryContract   $addressRepositoryContract
     * @param PaymentRepositoryContract   $paymentRepositoryContract
     * @param PaymentService              $paymentService
     * @param OrderItemRepositoryContract $orderItemRepositoryContract
     * @param CountryRepositoryContract   $countryRepositoryContract
     */
    public function __construct(
        MarketplaceClient $apiClient,
        OrderService $orderService,
        PluginSettingsHelper $pluginSettingsHelper,
        OrderRepositoryContract $orderRepositoryContract,
        AddressRepositoryContract $addressRepositoryContract,
        PaymentRepositoryContract $paymentRepositoryContract,
        PaymentService $paymentService,
        OrderItemRepositoryContract $orderItemRepositoryContract,
        CountryRepositoryContract $countryRepositoryContract
    ) {
        $this->apiClient = $apiClient;
        $this->orderService = $orderService;
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->orderRepositoryContact = $orderRepositoryContract;
        $this->addressRepositoryContract = $addressRepositoryContract;
        $this->paymentRepositoryContract = $paymentRepositoryContract;
        $this->paymentService = $paymentService;
        $this->orderItemRepositoryContract = $orderItemRepositoryContract;
        $this->countryRepositoryContract = $countryRepositoryContract;
    }

    /**
     * @param EventProceduresTriggered $eventTriggered
     */
    public function run(EventProceduresTriggered $eventTriggered)
    {
        try {
            $order = $eventTriggered->getOrder();

            foreach ($order->orderItems as $orderItem) {
                $externalItemId = $orderItem->properties->where(
                    'typeId',
                    '=',
                    OrderPropertyType::EXTERNAL_ITEM_ID
                )->first()->value;

                if (!$externalItemId) {
                    continue;
                }
                $countryId = (int)$order->warehouseSender->warehouse_location;
                $country = $this->countryRepositoryContract->getCountryById($countryId);
                $unCode = Iso2ToUnCode::convert($country->isoCode2);
                if (!$unCode) {
                    continue;
                }
                $acceptOrderItem = $this->apiClient->acceptItem($externalItemId, $unCode);

                $this->getLogger(__FUNCTION__)->debug(PluginConfiguration::PLUGIN_NAME . '::log.acceptOrderItem',
                    [
                        'externalItemId' => $externalItemId,
                        'unCode' => $unCode,
                        'acceptOrderItem' => $acceptOrderItem
                    ]);

                if ($acceptOrderItem !== 'Accepted') {
                    $commentData = [
                        'text' => 'Item was not accepted. ',
                        'value' => "Marketplace item ID: {$externalItemId}; plentymarkets variation ID: {$orderItem->itemVariationId}"
                    ];

                    $this->orderService->addOrderNote(
                        $order->id,
                        $commentData,
                        $this->settings[PluginSettingsHelper::PLENTY_ACCOUNT_ID]
                    );
                }
            }

            $externalOrderId = (string)Collection::make($order->properties)->where(
                'typeId',
                '=',
                OrderPropertyType::EXTERNAL_ORDER_ID
            )->first()->value;

            if (!$externalOrderId) {
                return;
            }

            $billingInfo = $this->apiClient->getBillingInfo($externalOrderId);
            $shippingInfo = $this->apiClient->getShippingInfo($externalOrderId);

            $this->getLogger(__FUNCTION__)->debug(PluginConfiguration::PLUGIN_NAME . '::log.billAndShipInfo',
                [
                    'billingInfo' => $billingInfo,
                    'shippingInfo' => $billingInfo,
                ]);

            if ($billingInfo['billinginformation']) {
                $this->updateBillingInformation($billingInfo, $externalOrderId, $order);
            }

            if ($shippingInfo['shippinginformation']) {
                $this->updateDeliveryOptions($shippingInfo, $order);
            }
        } catch (ValidationException $validationException) {
            $this->getLogger(__FUNCTION__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $validationException->getMessageBag()
            ]);
        } catch (Exception $exception) {
            $this->getLogger(__FUNCTION__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $exception->getMessage()
            ]);
        }
    }

    /**
     * Updates billing information
     *
     * @param        $billingInfo
     * @param string $externalOrderId
     * @param Order  $order
     *
     * @return void
     */
    private function updateBillingInformation($billingInfo, string $externalOrderId, Order $order): void
    {
        $billingItems = $billingInfo['billinginformation']['items']['item'];
        if (!is_array($billingItems[0])) {
            $billingItems = [$billingItems];
        }

        $updatedOrder = $this->updateOrderItems($billingItems, $order);

        if ($updatedOrder instanceof Order) {
            $this->checkPayment($billingItems, $externalOrderId, $updatedOrder);
        }
    }

    /**
     * Gets Order items to update
     *
     * @param       $billingItems
     * @param Order $order
     *
     * @return Order|null
     */
    private function updateOrderItems($billingItems, Order $order)
    {
        $shippingCostAmount = 0.0;
        foreach ($billingItems as $item) {
            if (empty($item['shippingsaleprice']['amount']) || (float)$item['shippingsaleprice']['amount'] == 0) {
                continue;
            }

            $shippingCostAmount += (float)$item['shippingsaleprice']['amount'];
        }

        /** @var OrderItem $shippingCostsItem */
        $shippingCostsItem = &$order->orderItems->where('typeId', '=', OrderItemType::TYPE_SHIPPING_COSTS)->first();
        $shippingCostsItem->systemAmount->priceOriginalGross = $shippingCostAmount;

        $updatedOrder = $this->orderRepositoryContact->updateOrder($order->toArray(), $order->id);

        return $updatedOrder ?? null;
    }

    /**
     * Checks payment and update it
     *
     * @param        $billingItems
     * @param string $externalOrderId
     * @param Order  $order
     *
     * @return void
     */
    private function checkPayment($billingItems, string $externalOrderId, Order $order): void
    {
        $amount = 0.0;
        foreach ($billingItems as $item) {
            $amount = $amount + (float)$item['shippingsaleprice']['amount'];
        }

        $payment = $this->paymentService->placePayment(
            $amount,
            date("j/m/Y-H:i"),
            $externalOrderId,
            PluginConfiguration::DEFAULT_CURRENCY,
            $this->settings[PluginSettingsHelper::METHOD_OF_PAYMENT_ID]
        );

        if ($payment instanceof Payment) {
            $this->paymentService->addPaymentOrderRelation($payment, $order);
        }
    }

    /**
     * Updates delivery options
     *
     * @param       $shopingInfo
     * @param Order $order
     *
     * @return void
     */
    public function updateDeliveryOptions($shopingInfo, Order $order): void
    {
        $deliveryAddress = $order->deliveryAddress;

        if ($shopingInfo['shippinginformation']['purchasebuyeremail']) {
            /** @var AddressOption $option5 */
            $option5 = $deliveryAddress->options->where('typeId', '=', AddressOption::TYPE_EMAIL)->first();
            if ($option5->id) {
                $this->addressRepositoryContract->updateAddressOption([
                    'typeId' => AddressOption::TYPE_EMAIL,
                    'value' => $shopingInfo['shippinginformation']['purchasebuyeremail']
                ], $option5->id);
            } else {
                $this->addressRepositoryContract->createAddressOptions([
                    'typeId' => AddressOption::TYPE_EMAIL,
                    'value' => $shopingInfo['shippinginformation']['purchasebuyeremail']
                ], $deliveryAddress->id);
            }
        }

        if ($shopingInfo['shippinginformation']['billingaddress']['phonenumber1']) {
            /** @var AddressOption $option4 */
            $option4 = $deliveryAddress->options->where('typeId', '=', AddressOption::TYPE_TELEPHONE)->first();
            if ($option4->id) {
                $this->addressRepositoryContract->updateAddressOption([
                    'typeId' => AddressOption::TYPE_TELEPHONE,
                    'value' => $shopingInfo['shippinginformation']['billingaddress']['phonenumber1']
                ], $option4->id);
            } else {
                $this->addressRepositoryContract->createAddressOptions([
                    'typeId' => AddressOption::TYPE_TELEPHONE,
                    'value' => $shopingInfo['shippinginformation']['billingaddress']['phonenumber1']
                ], $deliveryAddress->id);
            }
        }
    }
}
