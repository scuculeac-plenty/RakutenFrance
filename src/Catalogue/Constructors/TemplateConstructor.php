<?php

namespace RakutenFrance\Catalogue\Constructors;

use RakutenFrance\Catalogue\DataProviders\GenericBaseDataProvider;
use RakutenFrance\Catalogue\DataProviders\GenericKeyDataProvider;
use RakutenFrance\Catalogue\Helpers\CatalogueBuilderHelper;
use Plenty\Plugin\Log\Loggable;

/**
 * Class TemplateConstructor
 *
 * @package RakutenFrance\Catalogue\Constructors
 */
class TemplateConstructor
{
    use Loggable;

    const MAPPINGS = 'mappings';
    const MAPPINGS_BASE = 'mapping_base';
    const MAPPINGS_KEY = 'mapping_key';
    const SETTINGS = 'settings';
    const FILTERS = 'filters';

    const FIELD_TO_IGNORE = ['aid', 'pid'];
    /**
     * @var
     */
    private $catalogBase;
    /**
     * @var
     */
    private $catalogByKey;
    /**
     * @var
     */
    private $catalogSettings = [];
    /**
     * @var
     */
    private $catalogFilters = [];
    /**
     * @var CatalogueBuilderHelper
     */
    private $catalogueBuilderHelper;

    /**
     * @param CatalogueBuilderHelper $catalogueBuilderHelper
     */
    public function __construct(CatalogueBuilderHelper $catalogueBuilderHelper)
    {
        $this->catalogueBuilderHelper = $catalogueBuilderHelper;
    }

    /**
     * @param string $templateName
     *
     * @return array
     */
    public function getMappings(string $templateName): array
    {
        $load = $this->load($templateName);

        if (!$load) {
            return [];
        }

        return [
            self::MAPPINGS => [
                self::MAPPINGS_BASE => $this->checkBases(),
                self::MAPPINGS_KEY => $this->checkKeys()
            ],
            self::SETTINGS => $this->catalogSettings,
            self::FILTERS => $this->catalogFilters
        ];
    }

    /**
     * Loads template catalog
     *
     * @param string $templateName
     *
     * @return bool
     */
    public function load(string $templateName): bool
    {
        $template = $this->catalogueBuilderHelper->getTemplateByName($templateName);

        if (!$template) {
            return false;
        }

        $this->catalogBase = $template[CatalogueBuilderHelper::CATALOG_BASE_KEYS];
        $this->catalogByKey = $template[CatalogueBuilderHelper::CATALOG_BY_KEY];
        $this->catalogSettings = $template[CatalogueBuilderHelper::CATALOG_SETTINGS];
        $this->catalogFilters = $template[CatalogueBuilderHelper::CATALOG_FILTERS];

        return true;
    }

    /**
     * Unset unwanted by key values from showing inside catalog
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function unsetByKey(string $identifier): bool
    {
        if (in_array($identifier, self::FIELD_TO_IGNORE)) {
            return true;
        }

        return false;
    }

    /**
     * Returns catalog base keys values
     *
     * @return array
     */
    public function checkBases(): array
    {
        if(!self::FIELD_TO_IGNORE){
            return $this->catalogBase;
        }

        foreach ($this->catalogBase as $baseKey => $base){
            foreach ($this->catalogBase[$baseKey]['values'] as $key => $value) {
                if (in_array($value['key'], self::FIELD_TO_IGNORE)) {
                    unset($this->catalogBase[$baseKey]['values'][$key]);
                }
            }
        }

        return array_values($this->catalogBase);
    }

    /**
     * Returns catalog by key values
     *
     * @return array
     */
    public function checkKeys(): array
    {
        return $this->catalogByKey;
    }
}
