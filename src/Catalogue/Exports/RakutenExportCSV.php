<?php

namespace RakutenFrance\Catalogue\Exports;

use Illuminate\Support\Collection;
use Plenty\Modules\Catalog\Contracts\CatalogExportRepositoryContract;
use Plenty\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Plenty\Modules\Catalog\Contracts\TemplateContainerContract;
use Plenty\Modules\Catalog\Models\CatalogExportResult;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Catalogue\Constructors\TemplateConstructor;
use RakutenFrance\Catalogue\Converters\LazyConverter;
use RakutenFrance\Catalogue\Database\Migrations\CreateRakutenEANCatalog;
use RakutenFrance\Catalogue\Helpers\CatalogueExportHelper;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Repositories\CatalogErrorsRepository;

class RakutenExportCSV extends CatalogueExportHelper
{
    use Loggable;

    private $CSV = '';
    private $settings;
    private $itemsConstructed;
    private $catalogErrorsRepository;

    public function __construct(
        CatalogRepositoryContract $catalogRepository,
        CatalogExportRepositoryContract $catalogExportRepository,
        TemplateContainerContract $templateContainer,
        CatalogErrorsRepository $catalogErrorsRepository
    ) {
        parent::__construct($catalogRepository, $catalogExportRepository, $templateContainer);
        $this->catalogErrorsRepository = $catalogErrorsRepository;
    }

    /**
     * Exports catalog as CSV
     *
     * @return string|false
     */
    public function export()
    {
        $this->itemsConstructed = 0;
        $catalogMap = $this->getMappingByAlias(CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG);

        $catalog = $this->catalogByAlias(CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG);
        if (!$catalog) {
            return false;
        }

        $load = pluginApp(TemplateConstructor::class)->load($catalog['templateName']);
        if (!$load) {
            return false;
        }

        $this->settings = pluginApp(PluginSettingsHelper::class)->getSettings();
        if (!$this->settings) {
            return false;
        }

        $catalogService = $this->exportCatalogById($catalog['id']);
        $catalogResult = $catalogService->getResult();
        if (!$catalogResult instanceof CatalogExportResult) {
            return false;
        }
        $requiredFields = $this->getCatalogRequiredFieldsList($catalogMap);

        /** @var LazyConverter $lazyConverter */
        $lazyConverter = pluginApp(LazyConverter::class);
        $lazyLoader = $lazyConverter->fromCatalogExportResult($catalogResult)->getSourceCollection();

        $lazyLoader->each(function ($chuck) use ($catalogMap, $requiredFields) {
            foreach ($chuck as $variation) {
                /** Building logic goes here */
                $variationId = $variation['variationId'];
                if (!$variationId) {
                    $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.noVariationId',
                        ['alias' => CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG]
                    );
                    continue;
                }
                $checkRequiredFields = $this->isValidVariation($requiredFields, $variation);
                if (!$checkRequiredFields['valid']) {
                    $this->catalogErrorsRepository->createOrUpdate(
                        $variationId,
                        CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG,
                        self::TYPE_CATALOG,
                        $checkRequiredFields['errors']
                    );
                    continue;
                }
                $this->catalogErrorsRepository->deleteByVariationIdAndMethod($variationId, self::TYPE_CATALOG);

                /** Building logic goes here */
                $this->buildItem($catalogMap, $variation);
            }
        });

        return $this->itemsConstructed > 0 ? $this->CSV : false;
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
        $this->itemsConstructed += 1;
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

            $marketplaceCollection = Collection::make($image['availabilities'])->where('type', '=', 'marketplace');
            $check = $marketplaceCollection->whereIn('value', [$referrerId, -1])->isNotEmpty();
            if ($check) {
                $imagesFiltered[] = $image;
            }
        }

        return $imagesFiltered;
    }
}
