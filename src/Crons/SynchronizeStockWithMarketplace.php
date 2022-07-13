<?php

namespace RakutenFrance\Crons;

use DateTime;
use Exception;
use Plenty\Modules\Cron\Contracts\CronHandler;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\StockManagement\Stock\Contracts\StockRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use Plenty\Repositories\Models\PaginatedResult;
use RakutenFrance\API\Api;
use RakutenFrance\Catalogue\Database\Repositories\CatalogHistoryRepository;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Helpers\PriceHelper;
use RakutenFrance\Repositories\CronTimesRepository;

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
     * @var CatalogHistoryRepository
     */
    private $catalogHistoryRepository;
    /**
     * @var CronTimesRepository
     */
    private $cronTimesRepository;

    /**
     * SynchronizeStockWithMarketplace constructor.
     *
     * @param VariationRepositoryContract $variationRepositoryContract
     * @param PluginSettingsHelper        $pluginSettingsHelper
     * @param PriceHelper                 $priceHelper
     * @param LibraryCallContract         $libraryCallContract
     * @param CronTimesRepository         $cronTimesRepository
     */
    public function __construct(
        VariationRepositoryContract $variationRepositoryContract,
        PluginSettingsHelper $pluginSettingsHelper,
        PriceHelper $priceHelper,
        LibraryCallContract $libraryCallContract,
        CronTimesRepository $cronTimesRepository
    ) {
        $this->variationRepositoryContract = $variationRepositoryContract;
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->priceHelper = $priceHelper;
        $this->libraryCallContract = $libraryCallContract;
        $this->cronTimesRepository = $cronTimesRepository;
    }

    public function handle()
    {
        if ($this->settings[PluginSettingsHelper::JOB_SYNCHRONIZE_STOCK_WITH_MARKETPLACE] != true) {
            return;
        }
        $cronTime = $this->cronTimesRepository->findByType(CronTimesRepository::TYPE_STOCK, strtotime("-30 minutes"));
        $stockToUpdate = $this->getVariationsFromStockRepository($cronTime->timestamp, $this->settings);
        $csv = $this->generateCSV($stockToUpdate);
        if (!empty($csv)) {
            $this->uploadFile($csv, $this->settings);
        }
        $this->cronTimesRepository->update($cronTime);
    }

    /**
     * Gets stock variations from repository
     *
     * @param int   $timestamp
     * @param array $settings
     *
     * @return array
     */
    public function getVariationsFromStockRepository(int $timestamp, array $settings): array
    {
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

            /** @var  PaginatedResult $variationsByWarehouseType */
            foreach ($variationsByWarehouseType->getResult() as $stock) {
                $variationInfo = $this->variationInformation(
                    $stock,
                    $settings
                );
                if (!$variationInfo) {
                    continue;
                }
                $variations[] = $variationInfo;
            }
        }
        $stockRepositoryContract->clearFilters();
        $stockRepositoryContract->clearCriteria();

        return $variations;
    }

    /**
     * Gets variation information
     *
     * @param       $stock
     * @param array $settings
     *
     * @return array
     */
    private function variationInformation($stock, array $settings): array
    {
        try {
            $variation = $this->variationRepositoryContract->findById($stock->variationId);
        } catch (Exception $exception) {
            return [];
        }

        $variationSkus = $variation->variationSkus->where(
            "marketId",
            "=",
            $settings[PluginSettingsHelper::REFERRER_ID]
        )->first();
        if (!$variationSkus) {
            return [];
        }
        $price = $this->priceHelper->getPrice($stock['variationId'], $settings);

        return [
            'variation_sku' => $variationSkus->sku,
            'stockNet' => $stock->stockNet,
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
}
