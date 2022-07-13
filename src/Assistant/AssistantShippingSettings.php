<?php

namespace RakutenFrance\Assistant;

use Plenty\Modules\Order\Shipping\Contracts\ParcelServicePresetRepositoryContract;
use Plenty\Modules\Wizard\Services\WizardProvider;
use RakutenFrance\Assistant\Handlers\AssistantShippingHandler;
use RakutenFrance\Configuration\PluginConfiguration;

class AssistantShippingSettings extends WizardProvider
{
    const ASSISTANT_SHIPPING_KEY = 'rakuten_france_assistant_shipping';

    protected function structure(): array
    {
        return [
            'key' => self::ASSISTANT_SHIPPING_KEY,
            'iconPath' => '',
            'title' => 'assistant.ShippingTitle',
            'topics' => ['RakutenFrance', 'marketplace'],
            'settingsHandlerClass' => AssistantShippingHandler::class,
            'createOptionIdTitle' => 'assistant.ShippingCreateOptionIdTitle',
            'createOptionIdCardLabel' => 'assistant.ShippingCreateOptionIdCardLabel',
            'priority' => 499,
            'shortDescription' => 'assistant.ShippingShortDescription',
            'relevance' => 'essential',
            'keywords' => ['RakutenFrance', 'marketplace'],
            'translationNamespace' => PluginConfiguration::PLUGIN_NAME,
            'steps' => [
                'step1' => [
                    'title' => 'assistant.ShippingStepTitle',
                    'description' => 'assistant.ShippingStepDescription',
                    'sections' => [
                        [
                            'title' => 'assistant.ShippingStep1Title',
                            'description' => 'assistant.ShippingStep1Description',
                            'form' =>
                                $this->getShippingMatchingForm()
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array|array[]
     */
    private function getCarrierCodeList(): array
    {
        return array(
            array(
                'caption' => 'Colis Prive',
                'value' => 'Colis Prive',
            ),
            array(
                'caption' => 'So Colissimo',
                'value' => 'So Colissimo',
            ),
            array(
                'caption' => 'Colissimo',
                'value' => 'Colissimo',
            ),
            array(
                'caption' => 'Geodis',
                'value' => 'Geodis',
            ),
            array(
                'caption' => 'WeDo Logistics',
                'value' => 'WeDo Logistics',
            ),
            array(
                'caption' => '4PX',
                'value' => '4PX',
            ),
            array(
                'caption' => 'Autre',
                'value' => 'Autre',
            ),
            array(
                'caption' => 'DPD',
                'value' => 'DPD',
            ),
            array(
                'caption' => 'Mondial Relay',
                'value' => 'Mondial Relay',
            ),
            array(
                'caption' => 'Mondial Relay prépayé',
                'value' => 'Mondial Relay prépayé',
            ),
            array(
                'caption' => 'Chronopost',
                'value' => 'Chronopost',
            ),
            array(
                'caption' => 'TNT',
                'value' => 'TNT',
            ),
            array(
                'caption' => 'UPS',
                'value' => 'UPS',
            ),
            array(
                'caption' => 'Fedex',
                'value' => 'Fedex',
            ),
            array(
                'caption' => 'Tatex',
                'value' => 'Tatex',
            ),
            array(
                'caption' => 'GLS',
                'value' => 'GLS',
            ),
            array(
                'caption' => 'DHL',
                'value' => 'DHL',
            ),
            array(
                'caption' => 'France Express',
                'value' => 'France Express',
            ),
            array(
                'caption' => 'Kiala',
                'value' => 'Kiala',
            ),
            array(
                'caption' => 'Cubyn',
                'value' => 'Cubyn',
            ),
            array(
                'caption' => 'DPD Germany',
                'value' => 'DPD Germany',
            ),
            array(
                'caption' => 'DPD UK',
                'value' => 'DPD UK',
            ),
            array(
                'caption' => 'B2C Europe',
                'value' => 'B2C Europe',
            ),
            array(
                'caption' => 'TrackYourParcel',
                'value' => 'TrackYourParcel',
            ),
            array(
                'caption' => 'Yun Express',
                'value' => 'Yun Express',
            ),
            array(
                'caption' => 'China EMS',
                'value' => 'China EMS',
            ),
            array(
                'caption' => 'Swiss Post',
                'value' => 'Swiss Post',
            ),
            array(
                'caption' => 'Courrier Suivi',
                'value' => 'Courrier Suivi',
            ),
            array(
                'caption' => 'PostNL International',
                'value' => 'PostNL International',
            ),
            array(
                'caption' => 'Royal Mail',
                'value' => 'Royal Mail',
            ),
            array(
                'caption' => 'CNE Express',
                'value' => 'CNE Express',
            ),
            array(
                'caption' => 'S.F. Express',
                'value' => 'S.F. Express',
            ),
            array(
                'caption' => 'Singapore Post',
                'value' => 'Singapore Post',
            ),
            array(
                'caption' => 'Hong Kong Post',
                'value' => 'Hong Kong Post',
            ),
            array(
                'caption' => 'Bpost',
                'value' => 'Bpost',
            ),
            array(
                'caption' => 'Autre',
                'value' => 'Autre',
            ),
        );
    }

    /**
     * @return array|array[]
     */
    public function getShippingMatchingForm(): array
    {
        $codes = $this->getCarrierCodeList();

        $form = [];
        foreach ($this->getShippingProfiles() as $shippingProfile) {
            $form[$shippingProfile['value']] = [
                'type' => 'select',
                'options' => [
                    'name' => $shippingProfile['caption'],
                    'listBoxValues' =>
                        $codes
                ]
            ];
        }

        return $form;
    }

    /**
     * getting shipping profiles
     *
     * @return array
     */
    private function getShippingProfiles(): array
    {
        $parcelServicePresetRRepositoryContract = pluginApp(ParcelServicePresetRepositoryContract::class);
        $shipping = $parcelServicePresetRRepositoryContract->getPresetList(
            [
                'id',
                'parcelServiceId',
                'backendName'
            ],
            null,
            null,
            []
        );

        $shippingList = [];
        foreach ($shipping as $value) {
            $shippingList[] = [
                'caption' => $value->backendName,
                'value' => $value->id
            ];
        }

        return $shippingList;
    }
}
