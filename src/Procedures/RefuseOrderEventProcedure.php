<?php

namespace RakutenFrance\Procedures;

use Exception;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\Iso2ToUnCode;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Services\OrderService;

class RefuseOrderEventProcedure
{
    use Loggable;

    private $apiClient;
    private $orderService;
    private $settings;
    private $countryRepositoryContract;

    public function __construct(
        MarketplaceClient $apiClient,
        OrderService $orderService,
        PluginSettingsHelper $pluginSettingsHelper,
        CountryRepositoryContract $countryRepositoryContract
    ) {
        $this->apiClient = $apiClient;
        $this->orderService = $orderService;
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->countryRepositoryContract = $countryRepositoryContract;
    }

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
                $uncode = Iso2ToUnCode::convert($country->isoCode2);
                if (!$uncode) {
                    continue;
                }

                $acceptOrderItem = $this->apiClient->refuseItem($externalItemId, $uncode);

                if ($acceptOrderItem !== 'Refused') {
                    $commentData = [
                        'text' => 'Item was not refused. ',
                        'value' => "Marketplace item ID: {$externalItemId}; plentymarkets variation ID: {$orderItem->itemVariationId}"
                    ];
                    $this->orderService->addOrderNote($order->id, $commentData, $this->settings['plentyAccountId']);
                }
            }
        } catch (Exception $e) {
            $this->getLogger(__FUNCTION__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
