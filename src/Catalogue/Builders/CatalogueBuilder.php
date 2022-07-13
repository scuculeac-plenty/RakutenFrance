<?php

namespace RakutenFrance\Catalogue\Builders;

use Exception;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Catalogue\Helpers\CatalogPropertySelectionHelper;
use RakutenFrance\Catalogue\Helpers\CatalogueBuilderHelper;
use RakutenFrance\Catalogue\Helpers\CatalogueFiltersHelper;
use RakutenFrance\Helpers\PluginSettingsHelper;

/**
 * Class CatalogueBuilder.
 */
class CatalogueBuilder extends CatalogueBuilderHelper
{
    use Loggable;

    const EXCLUDE_TYPES = ['shipping', 'pickupStores', 'campaings'];
    const EXCLUDE_KEYS = ['zipcode'];
    private $additionalFields = false;

    /**
     * Builds and saves catalog
     *
     * @param string $templateName
     *
     * @return bool
     * @throws Exception
     */
    public function build(string $templateName): bool
    {
        /** @var  MarketplaceClient $marketplaceClient */
        $marketplaceClient = pluginApp(MarketplaceClient::class);
        $template = $marketplaceClient->getProducttypetemplate($templateName)['attributes'];

        $this->saveCatalogName($templateName);
        foreach (array_keys($template) as $key) {
            if (in_array($key, self::EXCLUDE_TYPES)) {
                continue;
            }
            if (!empty($template[$key]['attribute'])) {
                $this->createCatalogSections($key, $template[$key]);
            }
        }

        /** @var CatalogPropertySelectionHelper $catalogPropertySelection */
        $catalogPropertySelection = pluginApp(CatalogPropertySelectionHelper::class);
        $propertySelectionId = $catalogPropertySelection->handleProperty($templateName);

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

        return $this->saveCatalog();
    }

    /**
     * Generating template base keys
     *
     * @param string $name
     * @param array  $template
     */
    private function createCatalogSections(string $name, array $template)
    {
        $baseKeyStructure = [];
        foreach (!empty($template['attribute']['key']) ? $template : $template['attribute'] as $value) {
            if (in_array($value['key'], self::EXCLUDE_KEYS)) {
                continue;
            }

            if (!$this->additionalFields) {
                $baseKeyStructure[] = $this->baseStructure(
                    'variationId',
                    'VariationId',
                    false,
                    null,
                    true,
                    false,
                    $this->defaultValueHelper->variationId()->get()
                );
                $this->additionalFields = true;
            }

            $isRequired = $this->isMandatory($value['mandatory']);

            /** REQUIRED - Maps catalog for recreation */
            $this->addToCatalogMap(
                $name,
                $value['key'],
                [
                    'label' => $value['label'],
                    'required' => $isRequired,
                    'unit' => $value['units'] ? $value['key'] . '_' . 'unit' : null
                ]
            );
            /** Keys by value for units */
            if ($value['units']) {
                $this->addToCatalogByKey(
                    $value['key'] . '_' . 'unit',
                    $value['label'] . '_' . 'unit',
                    $isRequired,
                    $this->createListKeys($value['units'])
                );
            }
            /**  Keys by value for value lists and continue as we do not want to create base catalog structure */
            if ($value['hasvalues'] == 1) {
                $this->addToCatalogByKey(
                    $value['key'],
                    $value['label'],
                    $isRequired,
                    $this->createListKeys($value['valueslist'])
                );
                continue;
            }
            /**  If not keys by value base is created */
            $baseKeyStructure[] = $this->baseStructure(
                $value['key'],
                $value['label'],
                $isRequired,
                $this->getValueType($value['valuetype'])
            );
        }
        /**  Adds base to catalog */
        $this->addToCatalogBase($name, $name, $baseKeyStructure);
    }

    /**
     * Check is field is mandaroty
     *
     * @param $mandatoryField
     *
     * @return bool
     */
    private function isMandatory($mandatoryField): bool
    {
        if ($mandatoryField == 1) {
            return true;
        }
        return false;
    }

    /**
     * Generating keys with value list
     *
     * @param array $valueslist
     *
     * @return array
     */
    private function createListKeys(array $valueslist): array
    {
        $byKeyStructure = [];
        $list = $valueslist['value'] ?: $valueslist['unit'];
        if (!is_array($list)) {
            $list = [$list];
        }

        foreach ($list as $value) {
            $byKeyStructure[] = $this->byKeyStructure(
                $value,
                $value
            );
        }

        return $byKeyStructure;
    }

    /**
     * Rakuten types to catalog type
     *
     * @param string $valueType
     *
     * @return string
     */
    private function getValueType(string $valueType): string
    {
        switch ($valueType) {
            case 'Text':
                return self::CATALOG_STRING;
            case 'Number':
                return self::CATALOG_INTEGER;
            case 'Boolean':
                return self::CATALOG_BOOLEAN;
            default:
                return '';
        }
    }
}
