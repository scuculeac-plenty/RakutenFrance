<?php

namespace RakutenFrance\Procedures;

use Exception;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Services\OrderService;

class CancelItemProcedure
{
    use Loggable;

    private $apiClient;
    private $orderService;
    private $settings;

    public function __construct(
        MarketplaceClient $apiClient,
        OrderService $orderService,
        PluginSettingsHelper $pluginSettingsHelper
    ) {
        $this->apiClient = $apiClient;
        $this->orderService = $orderService;
        $this->settings = $pluginSettingsHelper->getSettings();
    }

    public function run(EventProceduresTriggered $eventTriggered)
    {
        try {
            $order = $eventTriggered->getOrder();

            foreach ($order->orderItems as $orderItem) {
                $externalItemId = $orderItem->properties->where(
                    "typeId",
                    "=",
                    OrderPropertyType::EXTERNAL_ITEM_ID
                )->first()->value;

                if (!$externalItemId) {
                    continue;
                }

                $cancelReason = $this->settings[PluginSettingsHelper::CANCEL_TEXT];
                $canceledItem = $this->apiClient->cancelItem(
                    $externalItemId,
                    $cancelReason
                ) ?? "Must be accepted before cancelling";
                $this->orderService->addOrderNote(
                    $order->id,
                    ['text' => "Cancel item status: $canceledItem"],
                    $this->settings[PluginSettingsHelper::PLENTY_ACCOUNT_ID]
                );
            }
        } catch (Exception $exception) {
            $this->getLogger(__FUNCTION__)->error(
                PluginConfiguration::PLUGIN_NAME . "::log.cancelOrdersError",
                $exception->getMessage()
            );
        }
    }
}
