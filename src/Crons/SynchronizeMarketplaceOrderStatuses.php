<?php

namespace RakutenFrance\Crons;

use Exception;
use Plenty\Modules\Cron\Contracts\CronHandler;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Services\OrderService;

/**
 * Class SynchronizeMarketplaceOrderStatuses
 * @package RakutenFrance\Crons
 */
class SynchronizeMarketplaceOrderStatuses extends CronHandler
{
    use Loggable;

    /**
     * @var MarketplaceClient
     */
    private $marketplaceClient;
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepositoryContract;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var array
     */
    private $settings;


    /**
     * SynchronizeMarketplaceOrderStatuses constructor.
     * @param MarketplaceClient $marketplaceClient
     * @param OrderRepositoryContract $orderRepositoryContract
     * @param OrderService $orderService
     * @param PluginSettingsHelper $pluginSettingsHelper
     */
    public function __construct(
        MarketplaceClient $marketplaceClient,
        OrderRepositoryContract $orderRepositoryContract,
        OrderService $orderService,
        PluginSettingsHelper $pluginSettingsHelper
    ) {
        $this->marketplaceClient = $marketplaceClient;
        $this->orderRepositoryContract = $orderRepositoryContract;
        $this->orderService = $orderService;
        $this->settings = $pluginSettingsHelper->getSettings();
    }

    public function handle()
    {
        try {
            if ($this->settings[PluginSettingsHelper::JOB_SYNCHRONIZE_MARKETPLACE_ORDER_STATUSES] != true) {
                return;
            }

            $orders = $this->orderService->getOrders($this->settings[PluginSettingsHelper::REFERRER_ID]);
            $currentSales = $this->marketplaceClient->getCurrentSales();
            $this->digestCurrentSales($currentSales);

            foreach ($orders as $order) {
                foreach ($order->orderItems as $orderItem) {
                    $property = (int)$orderItem->properties->where(
                        'typeId',
                        '=',
                        OrderPropertyType::EXTERNAL_ITEM_ID
                    )->first()->value;

                    if ($property) {
                        $itemInfo = $this->marketplaceClient->getItemInfo($property);

                        if ($itemInfo->item->itemstatus == 'CANCELLED') {
                            $this->orderRepositoryContract->completeOrder($order['id'], ['statusId' => 8.1]);

                            $this->orderService->addOrderNote($order->id, [
                                "text" => "Item with ID : $property  was cancelled by customer",
                                "value" => ""
                            ], $this->settings[PluginSettingsHelper::PLENTY_ACCOUNT_ID]);
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.orderStatusError',
                $exception->getMessage()
            );
        }
    }

    /**
     * Check items with current sales
     *
     * @param $currentSales
     *
     * @return void
     */
    private function digestCurrentSales($currentSales): void
    {
        foreach ($currentSales as $currentSale) {
            foreach ($currentSale->items->item as $item) {
                if ($item->itemstatus === 'ON_HOLD') {
                    $order = $this->orderService->findOrderByExternalOrderId($currentSale->purchaseid);
                    $this->orderRepositoryContract->completeOrder($order->id, ['statusId' => 4]);
                    $this->orderService->addOrderNote(
                        $order->id,
                        [
                            "text" => "Item with ID : $item->itemid  was set to ON_HOLD",
                            "value" => ""
                        ],
                        $this->settings[PluginSettingsHelper::PLENTY_ACCOUNT_ID]
                    );
                }
            }
        }
    }
}
