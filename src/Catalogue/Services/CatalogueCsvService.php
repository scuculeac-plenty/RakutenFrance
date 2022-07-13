<?php

namespace RakutenFrance\Catalogue\Services;

use Carbon\Carbon;
use Plenty\Plugin\Log\Loggable;
use Illuminate\Support\Collection;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Catalogue\Converters\LazyConverter;
use Plenty\Modules\Catalog\Models\CatalogExportResult;
use RakutenFrance\Catalogue\Helpers\CatalogueExportHelper;
use RakutenFrance\Catalogue\Constructors\TemplateConstructor;

class CatalogueCsvService extends CatalogueExportHelper
{
    use Loggable;

    private $CSV = '';
    private $settings;

    /**
     * Exports catalog as CSV
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
        int $page = 1,
        bool $validate = false,
        int $timestamp = null
    ) {
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

        $this->settings = pluginApp(PluginSettingsHelper::class)->getSettings();
        if (!$this->settings) {
            return ['err' => 'Unable to get the settings.'];
        }

        $catalogService = $this->exportCatalogById($catalog['id']);
        /** @phpstan-ignore-next-line */
        $catalogService->setUpdatedSince($timestamp ? Carbon::createFromTimestamp($timestamp) : Carbon::now()->subDay());
        $catalogService->setPage($page);
        $catalogService->setItemsPerPage(500);
        $catalogResult = $catalogService->getResult();
        if (!$catalogResult instanceof CatalogExportResult) {
            return ['err' => 'Catalog export have no results.'];
        }

        /** @var LazyConverter $lazyConverter */
        $lazyConverter = pluginApp(LazyConverter::class);
        $lazyLoader = $lazyConverter->fromCatalogExportResult($catalogResult)->getSourceCollection();
        $lazyLoader->each(function ($chuck) use ($catalogMap, $requiredFields, $validate) {
            foreach ($chuck as $variation) {
                $checkRequiredFields = $this->isValidVariation($requiredFields, $variation);
                if (!$checkRequiredFields['valid'] && $validate) {
                    continue;
                }
                /** Building logic goes here */
                $this->buildItem($catalogMap, $variation);
            }
        });

        return $this->CSV ?: 'No items constructed';
    }

    /**
     * Builds CSV item
     *
     * @param array $catalogMap
     * @param array $variation
     */
    private function buildItem(array $catalogMap, array $variation)
    {
        foreach ($catalogMap as $values) {
            foreach ($values as $valueKey => $value) {
                if (is_array($variation[$valueKey])) {
                    $this->CSV .= $this->implodeByKey(
                        'url',
                        $this->checkImages($variation[$valueKey]['variation'] ?: $variation[$valueKey]['all'])
                    );
                } else {
                    $this->CSV .= $variation[$valueKey] . ';';
                }
            }

            $this->CSV .= "\n";
        }
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
