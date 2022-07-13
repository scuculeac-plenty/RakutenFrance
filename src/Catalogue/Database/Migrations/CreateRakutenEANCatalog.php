<?php

namespace RakutenFrance\Catalogue\Database\Migrations;

use Exception;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Catalogue\Helpers\CatalogPropertySelectionHelper;
use RakutenFrance\Catalogue\Helpers\CatalogueBuilderHelper;
use RakutenFrance\Catalogue\Helpers\CatalogueFiltersHelper;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\PluginSettingsHelper;

class CreateRakutenEANCatalog extends CatalogueBuilderHelper
{
    use Loggable;

    const RAKUTEN_EAN_CATALOG = PluginConfiguration::REFERRER_NAME . ' EAN matching';

    public function run(): void
    {
        try {
            $baseStructure = [];
            $byKeyStructure = [];
            $this->saveCatalogName(self::RAKUTEN_EAN_CATALOG);
            $baseStructure[] = $this->baseStructure(
                'variationId',
                'VariationId',
                false,
                null,
                true,
                false,
                $this->defaultValueHelper->variationId()->get()
            );
            $baseStructure[] = $this->baseStructure('sku', 'Sku', true);
            $baseStructure[] = $this->baseStructure('barcode', 'Barcode', false);
            $baseStructure[] = $this->baseStructure('price', 'Price', true);
            $baseStructure[] = $this->baseStructure('quantity', 'Quantity', false);
            $baseStructure[] = $this->baseStructure('listing_comment', 'Listing comment', false);
            $baseStructure[] = $this->baseStructure('url_images', 'URL images', false);
            $this->addToCatalogBase('general', 'General', $baseStructure);

            $byKeyStructure['condition'][] = $this->byKeyStructure('N', 'Neuf');
            $byKeyStructure['condition'][] = $this->byKeyStructure('CN', 'Comme neuf');
            $byKeyStructure['condition'][] = $this->byKeyStructure('TBE', 'Très bon état');
            $byKeyStructure['condition'][] = $this->byKeyStructure('BE', 'Bon état');
            $byKeyStructure['condition'][] = $this->byKeyStructure('EC', 'état correct');
            $this->addToCatalogByKey('condition', 'Condition', true, $byKeyStructure['condition']);

            $byKeyStructure['refurbished'][] = $this->byKeyStructure('1', 'Yes');
            $byKeyStructure['refurbished'][] = $this->byKeyStructure('0', 'No');
            $this->addToCatalogByKey('refurbished', 'Refurbished', false, $byKeyStructure['refurbished']);

            $this->addToCatalogMap('general', 'sku', ['required' => true]);
            $this->addToCatalogMap('general', 'barcode', ['required' => false]);
            $this->addToCatalogMap('general', 'price', ['required' => true]);
            $this->addToCatalogMap('general', 'quantity', ['required' => false]);
            $this->addToCatalogMap('general', 'condition', ['required' => true]);
            $this->addToCatalogMap('general', 'listing_comment', ['required' => false]);
            $this->addToCatalogMap('general', 'refurbished', ['required' => false]);
            $this->addToCatalogMap('general', 'url_images', ['required' => false]);

            /** @var CatalogPropertySelectionHelper $catalogPropertySelection */
            $catalogPropertySelection = pluginApp(CatalogPropertySelectionHelper::class);
            $propertySelectionId = $catalogPropertySelection->handleProperty(self::RAKUTEN_EAN_CATALOG);

            /** @var PluginSettingsHelper $settings */
            $settings = pluginApp(PluginSettingsHelper::class)->getSettings();

            /** @var CatalogueFiltersHelper $catalogFilters */
            $catalogFilters = pluginApp(CatalogueFiltersHelper::class);
            $filters = $catalogFilters
                ->setVariationIsActive()
                ->setVariationMarketIsVisibleForMarket($settings[PluginSettingsHelper::REFERRER_ID])
                ->setVariationPropertyHasSelection($propertySelectionId)
                ->done();

            $this->addFilters($filters);

            $this->saveCatalog();
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.exception',
                [
                    'message' => $e->getMessage()
                ]
            );
        }
    }
}
