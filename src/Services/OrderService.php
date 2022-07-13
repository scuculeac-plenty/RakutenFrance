<?php

namespace RakutenFrance\Services;

use Exception;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Account\Address\Models\AddressRelationType;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Models\OrderType;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use RakutenFrance\Builders\OrderBuilder;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;

class OrderService
{
    use Loggable;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepositoryContract;

    /**
     * @var AuthHelper
     */
    private $authHelper;

    public function __construct(
        OrderRepositoryContract $orderRepositoryContract,
        AuthHelper $authHelper
    ) {
        $this->orderRepositoryContract = $orderRepositoryContract;
        $this->authHelper = $authHelper;
    }

    /**
     * Create new Order in plentymarkets.
     *
     * @param string $externalOrderId
     * @param array $orderItems
     * @param int $deliveryAddressId
     * @param int $billingAddressId
     * @param string $mop
     *
     * @return Order|null
     */
    public function placeOrder(
        string $externalOrderId,
        array $orderItems,
        int $deliveryAddressId,
        int $billingAddressId,
        string $mop
    ) {
        try {
            /** @var OrderBuilder $orderBuilder */
            $orderBuilder = pluginApp(OrderBuilder::class);

            $orderBuilder = $orderBuilder->prepare(OrderType::TYPE_SALES_ORDER)
                ->withOrderItems($orderItems)
                ->withAddressId($billingAddressId, AddressRelationType::BILLING_ADDRESS)
                ->withAddressId($deliveryAddressId, AddressRelationType::DELIVERY_ADDRESS)
                ->withOrderProperty(OrderPropertyType::PAYMENT_METHOD, $mop)
                ->withOrderProperty(OrderPropertyType::EXTERNAL_ORDER_ID, $externalOrderId);


            return $this->authHelper->processUnguarded(
                function () use ($orderBuilder) {
                    return $this->orderRepositoryContract->createOrder($orderBuilder->done());
                }
            );
        } catch (ValidationException $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $e->getMessageBag(),
            ]);
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $e->getMessage(),
            ]);
        }
        return null;
    }

    /**
     * Get Order by external Order ID.
     *
     * @param string $externalOrderId
     *
     * @return Order|null
     */
    public function findOrderByExternalOrderId(string $externalOrderId)
    {
        try {
            return $this->authHelper->processUnguarded(
                function () use ($externalOrderId) {
                    return $this->orderRepositoryContract->findOrderByExternalOrderId($externalOrderId);
                }
            );
        } catch (Exception $e) {
            return null;
        }
    }

    public function addOrderNote(int $orderId, array $data, int $accountId)
    {
        $this->authHelper->processUnguarded(
            function () use ($orderId, $data, $accountId) {
                $commentRepo = pluginApp(CommentRepositoryContract::class);
                $data = [
                    'text' =>
                        $data['text'] .
                        $data['value'] .
                        ' added by ' .
                        PluginConfiguration::PLUGIN_NAME .
                        ' plugin',
                    'referenceType' => 'order',
                    'isVisibleForContact' => false,
                    'referenceValue' => $orderId,
                    'userId' => $accountId,
                ];
                $commentRepo->createComment($data);
            }
        );
    }

    /**
     * Gets plentymarket orders by referrer
     *
     * @param $referrerId
     *
     * @return Order[]
     */
    public function getOrders(int $referrerId): array
    {
        $orders = [];
        $dateFrom = strtotime("-1 weeks");
        $dateTo = strtotime("now");
        $this->orderRepositoryContract->setFilters([
            'referrerId' => $referrerId,
            'updatedAtFrom' => date("c", $dateFrom),
            'updatedAtTo' => date("c", $dateTo),
            'statusFrom' => 1,
            'statusTo' => 4.9
        ]);

        $page = 0;
        $isLastPage = false;
        while (!$isLastPage) {
            $order = $this->orderRepositoryContract->searchOrders($page, 50);
            foreach ($order->getResult()['entries'] as $order) {
                $order[] = $order;
            }
            $isLastPage = $order->isLastPage();
            ++$page;
        }

        return $orders;
    }
}
