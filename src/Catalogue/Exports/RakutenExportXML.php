<?php

namespace RakutenFrance\Catalogue\Exports;

use Illuminate\Support\Collection;
use Plenty\Modules\Catalog\Contracts\CatalogExportRepositoryContract;
use Plenty\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Plenty\Modules\Catalog\Contracts\TemplateContainerContract;
use Plenty\Modules\Catalog\Models\CatalogExportResult;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\API\Api;
use RakutenFrance\Assistant\AssistantWizard;
use RakutenFrance\Catalogue\Constructors\TemplateConstructor;
use RakutenFrance\Catalogue\Converters\LazyConverter;
use RakutenFrance\Catalogue\Database\Repositories\CatalogHistoryRepository;
use RakutenFrance\Catalogue\Helpers\CatalogueExportHelper;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Repositories\CatalogErrorsRepository;
use XMLWriter;

class RakutenExportXML extends CatalogueExportHelper
{
    use Loggable;

    const ITEMS_PER_PAGE = 500;
    const ITEMS_PER_EXPORT = 5000; // 5 Per export 4 + 1

    private $settings;
    private $catalogErrorsRepository;
    /**
     * @var XMLWriter
     */
    private $xmlWriter;
    /**
     * @var int
     */
    private $itemsConstructed = 0;
    /**
     * @var int
     */
    private $totalItemsConstructed = 0;
    /**
     * @var int
     */
    private $page = 0;
    /**
     * @var int
     */
    private $starTime = 0;

    public function __construct(
        CatalogRepositoryContract $catalogRepository,
        CatalogExportRepositoryContract $catalogExportRepository,
        TemplateContainerContract $templateContainer,
        CatalogErrorsRepository $catalogErrorsRepository,
        PluginSettingsHelper $settings
    ) {
        parent::__construct($catalogRepository, $catalogExportRepository, $templateContainer);
        $this->catalogErrorsRepository = $catalogErrorsRepository;
        $this->settings = $settings->getSettings();
    }

    /**
     * Export catalog XML
     *
     * @param string $alias
     *
     * @return int
     */
    public function export(string $alias): int
    {
        $this->starTime = time();
        $this->xmlWriter = pluginApp(XMLWriter::class);
        $this->xmlWriter->openMemory();
        $this->xmlWriter->startDocument('1.0', 'UTF-8');
        $this->xmlWriter->startElement('items');

        $catalogMap = $this->getMappingByAlias($alias);
        $requiredFields = $this->getCatalogRequiredFieldsList($catalogMap);

        $catalog = $this->catalogByAlias($alias);
        if (!$catalog) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.noAliasCatalog',
                ['alias' => $alias]
            );
            return -1;
        }

        $load = pluginApp(TemplateConstructor::class)->load($catalog['templateName']);
        if (!$load) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.noTemplateCatalog',
                ['template' => $catalog['templateName']]
            );
            return -2;
        }
        $catalogService = $this->exportCatalogById($catalog['id']);
        $catalogService->setItemsPerPage(self::ITEMS_PER_PAGE);
        /** @var CatalogHistoryRepository $catalogHistoryRepository */
        $catalogHistoryRepository = pluginApp(CatalogHistoryRepository::class);
        $lastUpload = $catalogHistoryRepository->getLastUpload(
            CatalogHistoryRepository::TYPE_CATALOG,
            $alias
        );
        $catalogService->setUpdatedSince($lastUpload);
        $catalogResult = $catalogService->getResult();

        if (!$catalogResult instanceof CatalogExportResult) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.noCatalogExportResult', []);
            return -3;
        }

        /** @var LazyConverter $lazyConverter */
        $lazyConverter = pluginApp(LazyConverter::class);
        $lazyLoader = $lazyConverter->fromCatalogExportResult($catalogResult)->getSourceCollection();

        $lazyLoader->each(function ($chuck) use ($catalogMap, $requiredFields, $alias) {
            foreach ($chuck as $variation) {
                $variationId = $variation['variationId'];
                if (!$variationId) {
                    $this->getLogger(__METHOD__)->error(
                        PluginConfiguration::PLUGIN_NAME . '::log.noVariationId',
                        ['alias' => $alias]
                    );
                    continue;
                }
                if ($this->itemsConstructed >= self::ITEMS_PER_EXPORT) {
                    $this->prepareForUpload($alias);
                }
                $checkRequiredFields = $this->isValidVariation($requiredFields, $variation);
                if (!$checkRequiredFields['valid']) {
                    $this->catalogErrorsRepository->createOrUpdate(
                        $variationId,
                        $alias,
                        self::TYPE_CATALOG,
                        $checkRequiredFields['errors']
                    );
                    continue;
                }
                $this->catalogErrorsRepository->deleteByVariationIdAndMethod($variationId, self::TYPE_CATALOG);

                /** Building logic goes here */
                $this->buildItem($alias, $catalogMap, $variation);
            }
        });

        if ($this->itemsConstructed > 0) {
            $this->prepareForUpload($alias);
        }

        return $this->totalItemsConstructed;
    }

    /**
     * Prepare document start and end for each upload
     *
     * @param string $alias
     */
    private function prepareForUpload(string $alias): void
    {
        /** Ends the document for upload */
        $this->xmlWriter->endElement(); //END Items
        $this->xmlWriter->endDocument(); //END Document

        $this->uploadCatalog($alias);

        /** Restarts the document for upload */
        $this->starTime = time();
        $this->itemsConstructed = 0;
        $this->xmlWriter->flush();
        $this->xmlWriter->startDocument('1.0', 'UTF-8');
        $this->xmlWriter->startElement('items');
    }

    /**
     * Uploads a catalog
     *
     * @param string $alias
     *
     * @return void
     */
    private function uploadCatalog(string $alias): void
    {
        $timeElapsed = time() - $this->starTime;

        $this->getLogger(__METHOD__)->debug(
            PluginConfiguration::PLUGIN_NAME . '::log.catalogUpload',
            [$alias, $this->itemsConstructed, $this->totalItemsConstructed]
        );

        /** @var LibraryCallContract $libraryCallContract */
        $libraryCallContract = pluginApp(LibraryCallContract::class);
        $upload = $libraryCallContract->call(
            PluginConfiguration::PLUGIN_NAME . '::uploadCatalogFile',
            [
                'file_name' => 'Catalog_' . $alias . '_' . uniqid() . '_' . time() . '.xml',
                'file_content' => $this->xmlWriter->outputMemory(),
                'username' => $this->settings[AssistantWizard::VALUE_RAKUTEN_USERNAME],
                'access_key' => $this->settings[AssistantWizard::VALUE_RAKUTEN_TOKEN],
                'environment' => PluginConfiguration::ENVIRONMENT,
                'version' => Api::MARKETPLACE_FEED_UPLOAD_VERSION,
                'channel' => PluginConfiguration::CHANNEL,
            ]
        );

        if ($upload['message']['response']['importid']) {
            /** @var CatalogHistoryRepository $catalogHistoryRepository */
            $catalogHistoryRepository = pluginApp(CatalogHistoryRepository::class);
            $catalogHistoryRepository->save(
                [
                    'alias' => $alias,
                    'importId' => $upload['message']['response']['importid'],
                    'type' => CatalogHistoryRepository::TYPE_CATALOG,
                    'lastUpload' => date("Y-m-d H:i:s"),
                    'additionalInfo' => [
                        'timeElapsed' => $timeElapsed,
                        'catalog_page' => $this->page,
                        'itemsConstructed' => $this->itemsConstructed,
                        'totalItemsConstructed' => $this->totalItemsConstructed
                    ]
                ]
            );
            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME . '::catalogueExport.catalogExportUploaded',
                [
                    'Alias' => $alias,
                    'Information' => $upload
                ]
            );
        } else {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::catalogueExport.catalogGenerationFailed',
                [
                    'Alias' => $alias,
                    'Information' => $upload
                ]
            );
        }
    }

    /**
     * Builds XML item
     *
     * @param string $alias
     * @param array  $catalogMap
     * @param array  $variation
     */
    private function buildItem(string $alias, array $catalogMap, array $variation)
    {
        $this->xmlWriter->startElement('item');
        $this->addElement('alias', $alias);
        $this->xmlWriter->startElement('attributes');
        foreach ($catalogMap as $mapKey => $values) {
            $valuesForMapping = array_intersect_key($values, array_flip(array_keys($variation)));
            $this->xmlWriter->startElement($mapKey);
            foreach ($valuesForMapping as $valueKey => $value) {
                if ($variation[$valueKey] ?? null) {
                    if (is_array($variation[$valueKey])) {
                        $this->addAttribute(
                            $value['label'],
                            $valueKey,
                            $this->implodeByKey(
                                'url',
                                $this->checkImages($variation[$valueKey]['variation'] ?: $variation[$valueKey]['all'])
                            )
                        );
                    } else {
                        $this->addAttribute(
                            $value['label'],
                            $valueKey,
                            $value['unit'] ? $variation[$valueKey] : $variation[$valueKey] . ' ' . $variation[$value['unit']]
                        );
                    }
                }
            }
            $this->xmlWriter->endElement();//END MAP
        }
        $this->xmlWriter->endElement(); //END attributes
        $this->xmlWriter->endElement(); //END Item
        $this->itemsConstructed += 1;
        $this->totalItemsConstructed += 1;
    }

    /**
     * Adds element to XML
     *
     * @param $name
     * @param $text
     */
    private function addElement($name, $text)
    {
        $this->xmlWriter->startElement($name);
        $this->xmlWriter->text($text);
        $this->xmlWriter->endElement();
    }

    /**
     * Adds attribute to XML
     *
     * @param $label
     * @param $key
     * @param $value
     */
    private function addAttribute($label, $key, $value)
    {
        $this->xmlWriter->startElement('attribute');
        $this->addElement('label', $label);
        $this->addElement('key', $key);
        $this->addElement('value', $value);
        $this->xmlWriter->endElement();
    }

    /**
     * Images check
     *
     * @param array $images
     *
     * @return array
     */
    private function checkImages(array $images): array
    {
        $imagesFiltered = [];
        usort($images, function ($a, $b) {
            return strnatcmp($a['position'], $b['position']);
        });
        $referrerId = $this->settings[PluginSettingsHelper::REFERRER_ID];
        foreach ($images as $image) {
            $markets = $image['availabilities']['market'];
            foreach ($markets as $market) {
                if ($market == -1 || $market == $referrerId) {
                    $imagesFiltered[] = $image;
                    continue 2;
                }
            }

            /** @phpstan-ignore-next-line */
            $marketplaceCollection = Collection::make($image['availabilities'])->where('type', '=', 'marketplace');
            $check = $marketplaceCollection->whereIn('value', [$referrerId, -1])->isNotEmpty();
            if ($check) {
                $imagesFiltered[] = $image;
            }
        }

        return $imagesFiltered;
    }
}
