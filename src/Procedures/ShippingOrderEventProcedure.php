<?php

namespace RakutenFrance\Procedures;

use Exception;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\OrderItemType;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Shipping\Contracts\ParcelServicePresetRepositoryContract;
use Plenty\Modules\Order\Shipping\ParcelService\Models\ParcelServicePreset;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Wizard\Contracts\WizardDataRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Assistant\AssistantShippingSettings;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Services\OrderService;
use XMLWriter;
use Illuminate\Support\Collection;

class ShippingOrderEventProcedure
{
    use Loggable;

    const DEFAULT = 'Autre';
    private $apiClient;
    private $orderService;
    private $authHelper;
    private $settings;

    public function __construct(
        MarketplaceClient $apiClient,
        OrderService $orderService,
        PluginSettingsHelper $pluginSettingsHelper,
        AuthHelper $authHelper
    ) {
        $this->apiClient = $apiClient;
        $this->orderService = $orderService;
        $this->authHelper = $authHelper;
        $this->settings = $pluginSettingsHelper->getSettings();
    }

    public function run(EventProceduresTriggered $eventTriggered, LibraryCallContract $libCall)
    {
        try {
            $order = $eventTriggered->getOrder();
            $parcelServicePresetRepositoryContract = pluginApp(ParcelServicePresetRepositoryContract::class);
            $orderRepositoryContract = pluginApp(OrderRepositoryContract::class);

            list($packagesNumbers, $parcelName) = $this->authHelper->processUnguarded(
                function () use (
                    $order,
                    $parcelServicePresetRepositoryContract,
                    $orderRepositoryContract
                ) {
                    /** @var  ParcelServicePreset $parcel */
                    $parcel = $parcelServicePresetRepositoryContract->getPresetById($order->shippingProfileId);
                    return [
                        $orderRepositoryContract->getPackageNumbers($order->id),
                        $parcel->id
                    ];
                }
            );

            $externalOrderValue = Collection::make($order->properties)->where(
                'typeId',
                '=',
                OrderPropertyType::EXTERNAL_ORDER_ID
            )->first()->value;

            if ($externalOrderValue) {
                $request['purchaseid'] = $externalOrderValue;
            }

            $orderItemsId = [];
            foreach ($order->orderItems as $orderItem) {
                if ($orderItem->typeId != OrderItemType::TYPE_VARIATION &&
                    $orderItem->typeId != OrderItemType::TYPE_UNASSIGEND_VARIATION &&
                    $orderItem->typeId != OrderItemType::TYPE_ITEM_BUNDLE) {
                    continue;
                }

                $externalItemValue = $orderItem->properties->where(
                    'typeId',
                    '=',
                    OrderPropertyType::EXTERNAL_ITEM_ID
                )->first()->value;

                if ($externalItemValue) {
                    $orderItemsId[] = $externalItemValue;
                }
            }

            $validShippingProfile = $this->validateShippingProfile($parcelName);

            if ($validShippingProfile == "Autre") {
                $request['trackingurl'] = true;
            }

            $request['transporter'] = $validShippingProfile;
            $XMLRequest = $this->generateShippingRequest($request, $orderItemsId, $packagesNumbers);

            $fileName = $this->settings[PluginSettingsHelper::APPLICATION_ID] . "_" . uniqid() . "_request_" . time() . ".xml";

            $uploadFileToServer = $libCall->call(
                PluginConfiguration::PLUGIN_NAME . '::uploadXmlFile',
                [
                    "username" => $this->settings[PluginSettingsHelper::USERNAME],
                    "access_key" => $this->settings[PluginSettingsHelper::TOKEN],
                    "file_content" => $XMLRequest,
                    "file_name" => $fileName,
                    "order_id" => $order->id,
                    "environment" => PluginConfiguration::ENVIRONMENT,
                    "shipping_version" => MarketplaceClient::MARKETPLACE_SHIPPING_VERSION,
                    "channel" => PluginConfiguration::CHANNEL
                ]
            );

            if ($uploadFileToServer["success"]) {
                $this->orderService->addOrderNote(
                    $order->id,
                    ["text" => $uploadFileToServer["message"]],
                    $this->settings[PluginSettingsHelper::PLENTY_ACCOUNT_ID]
                );
            } else {
                $this->orderService->addOrderNote(
                    $order->id,
                    ["text" => $uploadFileToServer["message"]],
                    $this->settings[PluginSettingsHelper::PLENTY_ACCOUNT_ID]
                );
                $this->getLogger(__FUNCTION__)->error(
                    PluginConfiguration::PLUGIN_NAME . "::log.shippingConfirmationError",
                    $uploadFileToServer
                );
            }
        } catch (Exception $e) {
            $this->getLogger(__FUNCTION__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Shipping profile validation
     *
     * @param $shippingProfile
     *
     * @return string
     */
    private function validateShippingProfile($shippingProfile): string
    {
        /** @var WizardDataRepositoryContract $wizardDataRepositoryContract */
        $wizardDataRepositoryContract = pluginApp(WizardDataRepositoryContract::class);
        $shippingMatching = $wizardDataRepositoryContract->findByWizardKey(
            AssistantShippingSettings::ASSISTANT_SHIPPING_KEY
        )->data->default;

        return $shippingMatching[$shippingProfile] ?? self::DEFAULT;
    }

    /**
     * @param array $requestData
     * @param       $orderItems
     * @param       $packagesNumbers
     *
     * @return mixed
     */
    public function generateShippingRequest(
        array $requestData,
        $orderItems,
        $packagesNumbers
    ) {
        $writer = pluginApp(XMLWriter::class);
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);
        $writer->setIndentString(" ");
        $writer->startElement('items');
        foreach ($orderItems as $key => $orderItem) {
            $writer->startElement('item');
            $writer->writeElement('purchaseid', $requestData['purchaseid']);
            $writer->writeElement('itemid', $orderItem);
            $writer->writeElement('transporter', $requestData['transporter']);
            $writer->writeElement(
                'trackingnumber',
                $this->checkNormalTracking($requestData['transporter']) ? 'yes' :
                    $packagesNumbers[$key] ?: $packagesNumbers[0] ?: "No tracking number"
            );
            if ($requestData['trackingurl']) {
                $writer->writeElement('trackingurl', "http://montransporteur.com?trackingNumber=$orderItem");
            }
            $writer->endElement();
        }
        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private function checkNormalTracking(string $shippingProfile): bool
    {
        switch ($shippingProfile) {
            case "Chronopost":
            case "Mondial Relay prépayé":
            case "Click and Collect":
            case "Retrait chez le vendeur":
            case "So Colissimo":
                return true;
            default:
                return false;
        }
    }
}
