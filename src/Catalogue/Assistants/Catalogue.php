<?php

namespace RakutenFrance\Catalogue\Assistants;

use Plenty\Modules\Wizard\Services\WizardProvider;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Assistant\Folders\BasicSettingsFolder;
use RakutenFrance\Catalogue\Database\Migrations\CreateRakutenEANCatalog;
use RakutenFrance\Configuration\PluginConfiguration;

class Catalogue extends WizardProvider
{
    const ASSISTANT_KEY = PluginConfiguration::PLUGIN_NAME . '_assistant_catalogue';
    const VALUE_CATALOG_NAME = 'catalogName';
    const VALUE_CATALOG_ALIAS = 'catalogAlias';

    /**
     * @return array
     */
    protected function structure(): array
    {
        return [
            'key' => self::ASSISTANT_KEY,
            'iconPath' => '',
            'title' => 'catalogue.title',
            'topics' => [],
            'settingsHandlerClass' => CatalogueHandler::class,
            'actionHandlerClass' => CatalogueActionHandler::class,
            'createOptionIdTitle' => 'catalogue.createOptionIdTitle',
            'createOptionIdCardLabel' => 'catalogue.createOptionIdCardLabel',
            'priority' => 499,
            'shortDescription' => 'catalogue.shortDescription',
            'relevance' => 'essential',
            'keywords' => ['rakutenFranceSettings', 'marketplace'],
            'translationNamespace' => PluginConfiguration::PLUGIN_NAME,
            'steps' => [
                'step1' => [
                    'title' => 'catalogue.step1Title',
                    'description' => 'catalogue.step1Description',
                    'showFullDescription' => true,
                    'sections' => [
                        [
                            'title' => 'catalogue.step1Section1Title',
                            'description' => 'catalogue.step1Section1Description',
                            'form' => [
                                'createEanCatalog' => [
                                    'type' => 'button',
                                    'options' => [
                                        'icon' => 'icon-ticket_create',
                                        'name' => 'catalogue.step1Section1Name',
                                        'action' => 'createEanCatalog'
                                    ],
                                ],
                            ],
                        ],
                        [
                            'title' => 'catalogue.step1Section2Title',
                            'description' => 'catalogue.step1Section2Description',
                            'form' => [
                                'createCategoryCatalog' => [
                                    'type' => 'button',
                                    'options' => [
                                        'icon' => 'icon-ticket_create',
                                        'name' => 'catalogue.step1Section2Name',
                                        'action' => 'createCategoryCatalog'
                                    ],
                                ],
                            ],
                        ],
                    ]
                ],
                'step2' => [
                    'title' => 'catalogue.step2Title',
                    'description' => 'catalogue.step2Description',
                    'sections' => [
                        [
                            'title' => 'catalogue.step2Section1Title',
                            'description' => 'catalogue.step2Section1Description',
                            'form' => [
                                'catalogName' => [
                                    'type' => 'text',
                                    'options' => [
                                        'name' => 'catalogue.step2Section1Name',
                                        'required' => true,
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'catalogue.step2Section2Title',
                            'description' => 'catalogue.step2Section2Description',
                            'form' =>
                                [
                                    'catalogAlias' => [
                                        'type' => 'suggestion',
                                        'options' => [
                                            'name' => 'catalogue.step2Section2Name',
                                            'listBoxValues' => $this->getProductTypeList(),
                                            'required' => true,
                                        ]
                                    ]
                                ]
                        ],
                    ]
                ],
                'step3' => [
                    'title' => 'catalogue.step3Title',
                    'description' => 'catalogue.step3Description',
                    'showFullDescription' => true,
                    'sections' => []
                ],
            ]
        ];
    }

    /**
     * Gets template list
     *
     * @return array
     */
    public function getProductTypeList(): array
    {
        /** @var MarketplaceClient $productTypes */
        $productTypes = pluginApp(MarketplaceClient::class)->getAliases()['producttypetemplate'];
        $catalogueList = [];
        foreach ($productTypes as $catalog) { /** @phpstan-ignore-line */
            $catalogueList[] = [
                'caption' => $catalog['label'] . " [" . $catalog['alias'] . "]",
                'value' => $catalog['alias'],
            ];
        }

        return $catalogueList;
    }
}
