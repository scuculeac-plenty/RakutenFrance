<?php

namespace RakutenFrance\Crons;

use DateTime;
use Exception;
use RakutenFrance\API\Api;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Helpers\PriceHelper;
use Plenty\Modules\Cron\Contracts\CronHandler;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Repositories\CronTimesRepository;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Modules\StockManagement\Stock\Models\Stock;
use Plenty\Modules\Item\VariationStock\Models\VariationStock;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\StockManagement\Warehouse\Models\Warehouse;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use Plenty\Modules\StockManagement\Stock\Contracts\StockRepositoryContract;
use Plenty\Modules\Item\VariationStock\Contracts\VariationStockRepositoryContract;
use Plenty\Modules\StockManagement\Warehouse\Contracts\WarehouseRepositoryContract;

/**
 * Class SynchronizeStockWithMarketplace
 *
 * @package RakutenFrance\Crons
 */
class SynchronizeStockWithMarketplace extends CronHandler
{
    use Loggable;

    /**
     * @var VariationRepositoryContract
     */
    private $variationRepositoryContract;
    /**
     * @var array
     */
    private $settings;
    /**
     * @var PriceHelper
     */
    private $priceHelper;
    /**
     * @var LibraryCallContract
     */
    private $libraryCallContract;
    /**
     * @var CronTimesRepository
     */
    private $cronTimesRepository;
    /**
     * @var WarehouseRepositoryContract
     */
    private $warehouseRepositoryContract;
    /**
     * @var VariationStockRepositoryContract
     */
    private $variationStockRepositoryContract;
    /**
     * @var array
     */
    private $warehouses;

    /**
     * SynchronizeStockWithMarketplace constructor.
     *
     * @param VariationRepositoryContract $variationRepositoryContract
     * @param PluginSettingsHelper $pluginSettingsHelper
     * @param PriceHelper $priceHelper
     * @param LibraryCallContract $libraryCallContract
     * @param CronTimesRepository $cronTimesRepository
     * @param WarehouseRepositoryContract $warehouseRepositoryContract
     * @param VariationStockRepositoryContract $variationStockRepositoryContract
     */
    public function __construct(
        VariationRepositoryContract $variationRepositoryContract,
        PluginSettingsHelper $pluginSettingsHelper,
        PriceHelper $priceHelper,
        LibraryCallContract $libraryCallContract,
        CronTimesRepository $cronTimesRepository,
        WarehouseRepositoryContract $warehouseRepositoryContract,
        VariationStockRepositoryContract $variationStockRepositoryContract
    ) {
        $this->variationRepositoryContract = $variationRepositoryContract;
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->priceHelper = $priceHelper;
        $this->libraryCallContract = $libraryCallContract;
        $this->cronTimesRepository = $cronTimesRepository;
        $this->warehouseRepositoryContract = $warehouseRepositoryContract;
        $this->variationStockRepositoryContract = $variationStockRepositoryContract;
    }

    public function handle(): void
    {
        if ($this->settings[PluginSettingsHelper::JOB_SYNCHRONIZE_STOCK_WITH_MARKETPLACE] !== true) {
            return;
        }
        $this->cacheWarehouses();
        $cronTime = $this->cronTimesRepository->findByType(CronTimesRepository::TYPE_STOCK, strtotime('-30 minutes'));
        $stockToUpdate = $this->getVariationsFromStockRepository($cronTime->timestamp);
        $csv = $this->generateCSV($stockToUpdate);
        if ($csv) {
            $this->uploadFile($csv, $this->settings);
        }
        $this->cronTimesRepository->update($cronTime);
    }

    /**
     * Gets stock variations from repository
     *
     * @param int $timestamp
     *
     * @return array
     */
    public function getVariationsFromStockRepository(int $timestamp): array
    {
        /** @var StockRepositoryContract $stockRepositoryContract */
        $stockRepositoryContract = pluginApp(StockRepositoryContract::class);
        $stockRepositoryContract->setFilters([
            'updatedAtFrom' => date(DateTime::W3C, $timestamp)
        ]);

        $variationSearchParams = [
            'page' => 1,
            'itemsPerPage' => 100,
        ];

        $variations = [];
        $isLastPage = false;
        while (!$isLastPage) {
            $variationsByWarehouseType = $stockRepositoryContract->listStockByWarehouseType(
                'sales',
                ['*'],
                $variationSearchParams['page'],
                $variationSearchParams['itemsPerPage']
            );

            $isLastPage = $variationsByWarehouseType->isLastPage();
            ++$variationSearchParams['page'];

            foreach ($variationsByWarehouseType->getResult() as $stock) {
                $variation = $this->variationInformation($stock);
                if (!$variation) {
                    continue;
                }
                $variations[] = $variation;
            }
        }
        $stockRepositoryContract->clearFilters();
        $stockRepositoryContract->clearCriteria();

        return $variations;
    }

    /**
     * Gets variation information
     *
     * @param Stock $stock
     * @return array
     */
    private function variationInformation(Stock $stock): array
    {
        try {
            $variation = $this->variationRepositoryContract->findById($stock->variationId);
        } catch (Exception $exception) {
            return [];
        }

        $variationSkus = $variation->variationSkus->where(
            "marketId",
            "=",
            $this->settings[PluginSettingsHelper::REFERRER_ID]
        )->first();
        if (!$variationSkus) {
            return [];
        }
        $price = $this->priceHelper->getPrice($stock->variationId, $this->settings);

        $physicalStock = 0;
        $variationStocks = $this->variationStockRepositoryContract->listStockByWarehouse($variation->id, ['*']);
        foreach ($variationStocks as $variationStock) {
            /** @var VariationStock $variationStock */
            $warehouseAccess = @$this->warehouses[$variationStock->warehouseId] ?: false;
            if (!$warehouseAccess) {
                continue;
            }
            $physicalStock += $variationStock->netStock;
        }

        return [
            'variation_sku' => $variationSkus->sku,
            'stockNet' => $physicalStock,
            'price' => $price->price > 0 ? $price->price : 0.0,
        ];
    }

    /**
     * Generate CSV
     *
     * @param array $variations
     *
     * @return string
     */
    private function generateCSV(array $variations): string
    {
        $CSV = '';
        foreach ($variations as $variation) {
            $variationId = $variation['variation_sku'];
            $price = $variation['price'];
            $stockNet = $variation['stockNet'];

            $CSV .= "$variationId;$price;$stockNet\n";
        }

        return $CSV;
    }

    /**
     * Upload CSV file
     *
     * @param string $CSV
     * @param array  $settings
     *
     * @return void
     */
    private function uploadFile(string $CSV, array $settings): void
    {
        $fileName = 'PRICEQUANTITY_' . uniqid() . '_' . time() . '.csv';
        $upload = $this->libraryCallContract->call(
            PluginConfiguration::PLUGIN_NAME . '::uploadStockSyncFile',
            [
                'file_name' => $fileName,
                'file_content' => $CSV,
                'username' => $settings[PluginSettingsHelper::USERNAME],
                'access_key' => $settings[PluginSettingsHelper::TOKEN],
                'environment' => PluginConfiguration::ENVIRONMENT,
                'stock_version' => Api::MARKETPLACE_STOCK_VERSION,
                'profile_id' => $settings[PluginSettingsHelper::PROFILE_STOCK_ID],
                'mapping_alias' => 'PRICEQUANTITY',
                'channel' => PluginConfiguration::CHANNEL
            ]
        );

        if ($upload['success'] == true) {
            if ($upload['message']['error']) {
                $this->getLogger(__METHOD__)->error(
                    PluginConfiguration::PLUGIN_NAME . '::log.uploadStockFailed',
                    [
                        'Information' => $upload['message']
                    ]
                );
            } else {
                $this->getLogger(__METHOD__)->info(
                    PluginConfiguration::PLUGIN_NAME . '::log.uploadStockSuccess',
                    [
                        'Information' => $upload['message']
                    ]
                );
            }
        } else {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.uploadStockFailed',
                [
                    'Information' => $upload['message']
                ]
            );
        }
    }

    /**
     * @return void
     */
    private function cacheWarehouses(): void
    {
        $warehouses = $this->warehouseRepositoryContract->all();
        /** @var Warehouse $warehouse */
        foreach ($warehouses as $warehouse) {
            if (in_array($this->settings[PluginSettingsHelper::REFERRER_ID], $warehouse->allocationReferrerIds) || in_array(-1, $warehouse->allocationReferrerIds)) {
                $this->warehouses[$warehouse->id] = true;
                continue;
            }

            $this->warehouses[$warehouse->id] = false;
        }
    }
}
