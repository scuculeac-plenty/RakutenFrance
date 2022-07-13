<?php

namespace RakutenFrance\Catalogue\Services;

use XMLWriter;
use Carbon\Carbon;
use Plenty\Plugin\Log\Loggable;
use Illuminate\Support\Collection;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Catalogue\Converters\LazyConverter;
use Plenty\Modules\Catalog\Models\CatalogExportResult;
use RakutenFrance\Catalogue\Helpers\CatalogueExportHelper;
use RakutenFrance\Catalogue\Constructors\TemplateConstructor;
use Plenty\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Plenty\Modules\Catalog\Contracts\TemplateContainerContract;
use Plenty\Modules\Catalog\Contracts\CatalogExportRepositoryContract;

class CatalogueXmlService extends CatalogueExportHelper
{
    use Loggable;

    private $settings;
    /**
     * @var XMLWriter
     */
    private $xmlWriter;

    public function __construct(
        CatalogRepositoryContract $catalogRepository,
        CatalogExportRepositoryContract $catalogExportRepository,
        TemplateContainerContract $templateContainer,
        PluginSettingsHelper $settings
    ) {
        parent::__construct($catalogRepository, $catalogExportRepository, $templateContainer);
        $this->settings = $settings->getSettings();
    }

    /**
     * Export catalog XML
     *
     * @param string   $alias
     * @param int      $page
     * @param bool     $validate
     * @param int|null $timestamp
     *
     * @return array|string
     */
    public function generate(
        string $alias,
        int $page,
        bool $validate = false,
        int $timestamp = null
    ) {
        $this->xmlWriter = pluginApp(XMLWriter::class);
        $this->xmlWriter->openMemory();
        $this->xmlWriter->startDocument('1.0', 'UTF-8');
        $this->xmlWriter->startElement('items');

        $catalogMap = $this->getMappingByAlias($alias);
        $requiredFields = $this->getCatalogRequiredFieldsList($catalogMap);

        $catalog = $this->catalogByAlias($alias);
        if (!$catalog) {
            return ['err' => ['No catalog by alias', ['alias' => $alias]]];
        }

        $load = pluginApp(TemplateConstructor::class)->load($catalog['templateName']);
        if (!$load) {
            return ['err' => ['No catalog by template', ['template' => $catalog['templateName']]]];
        }
        $catalogService = $this->exportCatalogById($catalog['id']);
        $catalogService->setPage($page);
        $catalogService->setItemsPerPage(500);
        /** @phpstan-ignore-next-line */
        $catalogService->setUpdatedSince($timestamp ? Carbon::createFromTimestamp($timestamp) : Carbon::now()->subDay());
        $catalogResult = $catalogService->getResult();

        if (!$catalogResult instanceof CatalogExportResult) {
            return ['err' => 'Catalog export have no results.'];
        }

        /** @var LazyConverter $lazyConverter */
        $lazyConverter = pluginApp(LazyConverter::class);
        $lazyLoader = $lazyConverter->fromCatalogExportResult($catalogResult)->getSourceCollection();
        $lazyLoader->each(function ($chuck) use ($catalogMap, $alias, $requiredFields, $validate) {
            foreach ($chuck as $variation) {
                /** Building logic goes here */
                $checkRequiredFields = $this->isValidVariation($requiredFields, $variation);
                if (!$checkRequiredFields['valid'] && $validate) {
                    continue;
                }
                $this->buildItem($alias, $catalogMap, $variation);
            }
        });

        /** Ends the document */
        $this->xmlWriter->endElement(); //END Items
        $this->xmlWriter->endDocument(); //END Document

        return $this->xmlWriter->outputMemory() ?: 'No items constructed';
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
