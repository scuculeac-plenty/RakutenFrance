<?php

namespace RakutenFrance\Catalogue\Helpers;

use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Plugin\Storage\Contracts\StorageRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Catalogue\Database\Repositories\CatalogRepository;
use RakutenFrance\Configuration\PluginConfiguration;

/**
 * Class Catalogue
 *
 * @package RakutenFrance\Catalogue\Builders
 */
class CatalogueBuilderHelper
{
    use Loggable;

    const CATALOG_BOOLEAN = 'boolean';
    const CATALOG_STRING = 'string';
    const CATALOG_INTEGER = 'integer';

    const CATALOG_NAME = 'catalogName';
    const CATALOG_BASE_KEYS = 'catalogBase';
    const CATALOG_BY_KEY = 'catalogKey';
    const CATALOG_NESTED = 'catalogNested';
    const CATALOG_MAP = 'catalogMap';
    const CATALOG_FILTERS = 'catalogFilters';
    const CATALOG_SETTINGS = 'catalogSettings';
    /**
     * @var string
     */
    private $catalogName = '';
    /**
     * @var array
     */
    private $catalogBase = [];
    /**
     * @var array
     */
    private $catalogByKey = [];
    /**
     * @var array
     */
    private $catalogNested = [];
    /**
     * @var array
     */
    private $catalogMap = [];
    /**
     * @var array
     */
    private $catalogSettings = [];
    /**
     * @var array
     */
    private $catalogFilters = [];

    /**
     * @var CatalogRepository
     */
    private $catalogRepository;
    /**
     * @var StorageRepositoryContract
     */
    private $storageRepositoryContract;

    /**
     * @var AuthHelper
     */
    private $authHelper;

    /**
     * @var CatalogueDefaultValueHelper
     */
    protected $defaultValueHelper;

    /**
     * CatalogueBuilder constructor.
     *
     * @param CatalogRepository           $catalogRepository
     * @param StorageRepositoryContract   $storageRepositoryContract
     * @param AuthHelper                  $authHelper
     * @param CatalogueDefaultValueHelper $defaultValueHelper
     */
    public function __construct(
        CatalogRepository $catalogRepository,
        StorageRepositoryContract $storageRepositoryContract,
        AuthHelper $authHelper,
        CatalogueDefaultValueHelper $defaultValueHelper
    ) {
        $this->catalogRepository = $catalogRepository;
        $this->storageRepositoryContract = $storageRepositoryContract;
        $this->authHelper = $authHelper;
        $this->defaultValueHelper = $defaultValueHelper;
    }

    /**
     * Gets template by name
     *
     * @param string $templateName
     *
     * @return array
     */
    public function getTemplateByName(string $templateName): array
    {
        return $this->authHelper->processUnguarded(
            function () use ($templateName) {
                return json_decode($this->storageRepositoryContract->getObject(
                    PluginConfiguration::PLUGIN_NAME,
                    "$templateName.json"
                )->body, true);
            }
        );
    }

    /**
     * Save catalog to load at boot method
     *
     * @param string $alias
     */
    protected function saveCatalogName(string $alias)
    {
        $this->catalogName = $alias;
        $this->catalogRepository->save([
            'alias' => $alias,
        ]);
    }

    /**
     * Save catalog to a storage file
     *
     * @return bool
     */
    protected function saveCatalog(): bool
    {
        if (!$this->catalogName) {
            $this->getLogger(__METHOD__)
                ->error(PluginConfiguration::PLUGIN_NAME . '::log.catalogNotSaved', $this->catalogName);
            return false;
        }
        $this->deleteCatalogJsonIfExists();

        $catalog = [
            self::CATALOG_NAME => $this->catalogName,
            self::CATALOG_BASE_KEYS => $this->catalogBase,
            self::CATALOG_BY_KEY => $this->catalogByKey,
            self::CATALOG_NESTED => $this->catalogNested,
            self::CATALOG_MAP => $this->catalogMap,
            self::CATALOG_FILTERS => $this->catalogFilters,
            self::CATALOG_SETTINGS => $this->catalogSettings,
        ];

        $wasUploaded = $this->authHelper->processUnguarded(
            function () use ($catalog) {
                return $this->storageRepositoryContract->uploadObject(
                    PluginConfiguration::PLUGIN_NAME,
                    "$this->catalogName.json",
                    json_encode($catalog)
                );
            }
        );

        return $wasUploaded->key ? true : false;
    }

    /**
     * Check if catalog was exists and delete before creating new one
     *
     * @return bool
     */
    private function deleteCatalogJsonIfExists(): bool
    {
        return $this->authHelper->processUnguarded(
            function () {
                $doesExist = $this->storageRepositoryContract->doesObjectExist(
                    PluginConfiguration::PLUGIN_NAME,
                    "$this->catalogName.json"
                );
                if ($doesExist) {
                    $this->storageRepositoryContract->deleteObject(
                        PluginConfiguration::PLUGIN_NAME,
                        "$this->catalogName.json"
                    );
                }

                return $doesExist;
            }
        );
    }

    /**
     * Add to catalog by key with identifier and parameters
     *
     * @param string $identifier
     * @param string $label
     * @param array  $baseStructure
     */
    protected function addToCatalogBase(string $identifier, string $label, array $baseStructure)
    {
        $this->catalogBase[$identifier] = ['label' => $label, 'values' => $baseStructure];
    }

    /**
     * Add to catalog base key with identifier and parameters
     *
     * @param string $identifier
     * @param string $label
     * @param bool   $required
     * @param array  $byKeyStructure
     * @param bool   $isArray
     * @param bool   $isLocked
     * @param array  $default
     */
    protected function addToCatalogByKey(
        string $identifier,
        string $label,
        bool $required,
        array $byKeyStructure,
        bool $isArray = false,
        bool $isLocked = false,
        array $default = []
    ) {
        $this->catalogByKey[$identifier] = [
            'label' => $label,
            'required' => $required,
            'values' => $byKeyStructure,
            'isArray' => $isArray,
            'isLocked' => $isLocked,
            'default' => $default,

        ];
    }

    /**
     * Basic catalog key structure
     *
     * @param string $value
     * @param string $label
     *
     * @return array
     */
    protected function byKeyStructure(string $value, string $label): array
    {
        return [
            'value' => $value,
            'label' => $label,
        ];
    }

    /**
     * Basic catalog base key structure
     *
     * @param string      $key
     * @param string      $label
     * @param bool        $required
     * @param string|null $dataType
     * @param bool|null   $isLocked
     * @param bool|null   $isArray
     * @param array|null  $default
     *
     * @return array
     */
    protected function baseStructure(
        string $key,
        string $label,
        bool $required,
        string $dataType = null,
        bool $isLocked = null,
        bool $isArray = null,
        array $default = null
    ): array {
        $base = [
            'key' => $key,
            'label' => $label,
            'required' => $required,
            'isLocked' => $isLocked,
            'isArray' => $isArray,
            'default' => $default
        ];

        if ($dataType) {
            $base['meta'] = [
                'dataType' => $dataType
            ];
        }

        return $base;
    }

    /**
     * Add to catalog map for reconstruct, as values required for construction like labels,keys etc.
     *
     * @param string $category
     * @param string $identifier
     * @param array  $valuesForReconstruction
     */
    protected function addToCatalogMap(string $category, string $identifier, array $valuesForReconstruction)
    {
        $this->catalogMap[$category][$identifier] = $valuesForReconstruction;
    }

    protected function addSettings(array $settings)
    {
        $this->catalogSettings = $settings;
    }

    protected function addFilters(array $filters)
    {
        $this->catalogFilters = $filters;
    }
}
