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
        return [
            [
                'caption' => 'Colis Prive',
                'value' => 'Colis Prive',
            ],
            [
                'caption' => 'So Colissimo',
                'value' => 'So Colissimo',
            ],
            [
                'caption' => 'Colissimo',
                'value' => 'Colissimo',
            ],
            [
                'caption' => 'Geodis',
                'value' => 'Geodis',
            ],
            [
                'caption' => 'WeDo Logistics',
                'value' => 'WeDo Logistics',
            ],
            [
                'caption' => '4PX',
                'value' => '4PX',
            ],
            [
                'caption' => 'Autre',
                'value' => 'Autre',
            ],
            [
                'caption' => 'DPD',
                'value' => 'DPD',
            ],
            [
                'caption' => 'Mondial Relay',
                'value' => 'Mondial Relay',
            ],
            [
                'caption' => 'Mondial Relay prépayé',
                'value' => 'Mondial Relay prépayé',
            ],
            [
                'caption' => 'Chronopost',
                'value' => 'Chronopost',
            ],
            [
                'caption' => 'TNT',
                'value' => 'TNT',
            ],
            [
                'caption' => 'UPS',
                'value' => 'UPS',
            ],
            [
                'caption' => 'Fedex',
                'value' => 'Fedex',
            ],
            [
                'caption' => 'Tatex',
                'value' => 'Tatex',
            ],
            [
                'caption' => 'GLS',
                'value' => 'GLS',
            ],
            [
                'caption' => 'DHL',
                'value' => 'DHL',
            ],
            [
                'caption' => 'France Express',
                'value' => 'France Express',
            ],
            [
                'caption' => 'Kiala',
                'value' => 'Kiala',
            ],
            [
                'caption' => 'Cubyn',
                'value' => 'Cubyn',
            ],
            [
                'caption' => 'DPD Germany',
                'value' => 'DPD Germany',
            ],
            [
                'caption' => 'DPD UK',
                'value' => 'DPD UK',
            ],
            [
                'caption' => 'B2C Europe',
                'value' => 'B2C Europe',
            ],
            [
                'caption' => 'TrackYourParcel',
                'value' => 'TrackYourParcel',
            ],
            [
                'caption' => 'Yun Express',
                'value' => 'Yun Express',
            ],
            [
                'caption' => 'China EMS',
                'value' => 'China EMS',
            ],
            [
                'caption' => 'Swiss Post',
                'value' => 'Swiss Post',
            ],
            [
                'caption' => 'Courrier Suivi',
                'value' => 'Courrier Suivi',
            ],
            [
                'caption' => 'PostNL International',
                'value' => 'PostNL International',
            ],
            [
                'caption' => 'Royal Mail',
                'value' => 'Royal Mail',
            ],
            [
                'caption' => 'CNE Express',
                'value' => 'CNE Express',
            ],
            [
                'caption' => 'S.F. Express',
                'value' => 'S.F. Express',
            ],
            [
                'caption' => 'Singapore Post',
                'value' => 'Singapore Post',
            ],
            [
                'caption' => 'Hong Kong Post',
                'value' => 'Hong Kong Post',
            ],
            [
                'caption' => 'Bpost',
                'value' => 'Bpost',
            ],
            [
                'caption' => 'Autre',
                'value' => 'Autre',
            ],
        ];
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
