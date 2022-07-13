<?php

namespace RakutenFrance\Crons;

use Exception;
use Plenty\Modules\Cron\Contracts\CronHandler;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Item\Variation\Models\Variation;
use Plenty\Modules\Item\VariationSku\Contracts\VariationSkuRepositoryContract;
use RakutenFrance\API\MarketplaceClient;
use RakutenFrance\Catalogue\Database\Models\CatalogHistory;
use RakutenFrance\Catalogue\Database\Repositories\CatalogHistoryRepository;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Repositories\CatalogErrorsRepository;

class SynchronizeCatalogErrorsCron extends CronHandler
{
    const METHOD_REPORT = 'Report';
    const METHOD_GENERIC = 'Generic';
    private $catalogHistoryRepository;
    private $catalogErrorsRepository;
    private $marketplaceClient;
    private $variationSkuRepositoryContract;
    private $settings;
    private $variationSearchRepository;
    private $variationRepository;

    public function __construct(
        CatalogHistoryRepository $catalogHistoryRepository,
        CatalogErrorsRepository $catalogErrorsRepository,
        MarketplaceClient $marketplaceClient,
        VariationSkuRepositoryContract $variationSkuRepositoryContract,
        PluginSettingsHelper $pluginSettingsHelper,
        VariationSearchRepositoryContract $variationSearchRepository,
        VariationRepositoryContract $variationRepository
    ) {
        $this->catalogHistoryRepository = $catalogHistoryRepository;
        $this->catalogErrorsRepository = $catalogErrorsRepository;
        $this->marketplaceClient = $marketplaceClient;
        $this->variationSkuRepositoryContract = $variationSkuRepositoryContract;
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->variationSearchRepository = $variationSearchRepository;
        $this->variationRepository = $variationRepository;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        $catalogHistory = $this->catalogHistoryRepository->get();
        foreach ($catalogHistory as $history) {
            if (@$history->additionalInfo['completed']) {
                continue;
            }
            if (!$history->importId) {
                continue;
            }
            switch ($history->type) {
                case CatalogHistoryRepository::TYPE_EAN:
                {
                    $this->processEan($history);
                    break;
                }
                case CatalogHistoryRepository::TYPE_CATALOG:
                {
                    $this->processAlias($history);
                    break;
                }
            }
        }
    }

    /**
     * @param CatalogHistory $catalogHistory
     *
     * @throws Exception
     */
    private function processEan(CatalogHistory $catalogHistory): void
    {
        $data = $this->marketplaceClient->getReportByImportId($catalogHistory->importId);
        if (!in_array(@$data['processstatus'], SynchronizeFeedInformation::REPORT_TO_PROCESS)) {
            return;
        }

        $this->catalogErrorsRepository->purgeByFormatAndMethod($catalogHistory->alias, self::METHOD_REPORT);
        foreach (@$data['errorlist']['error'] as $product) {
            if (!@$product['data']) {
                continue;
            }
            $parseCSV = str_getcsv($product['data'], ';');
            $isFound = $this->find($parseCSV[0]);
            if (!$isFound) {
                continue;
            }
            $this->catalogErrorsRepository->createOrUpdate(
                $isFound,
                $catalogHistory->alias,
                self::METHOD_REPORT,
                [['error' => trim($product['errorreport'])]]
            );
        }
        $catalogHistory->additionalInfo = $catalogHistory->additionalInfo + ['completed' => true];
        $this->catalogHistoryRepository->update($catalogHistory);
    }

    /**
     * @param CatalogHistory $catalogHistory
     *
     * @throws Exception
     */
    private function processAlias(CatalogHistory $catalogHistory): void
    {
        $request = $this->marketplaceClient->getGenericByImportId($catalogHistory->importId);
        if (@$request['metaData']['status'] != SynchronizeFeedInformation::SUCCESS) {
            return;
        }

        $this->catalogErrorsRepository->purgeByFormatAndMethod($catalogHistory->alias, self::METHOD_GENERIC);
        foreach ($request['data'] as $product) {
            if (@$product['errors']) {
                $isFound = $this->find($product['sku']);
                if ($isFound && @$product['errors']['error']) {
                    $this->catalogErrorsRepository->createOrUpdate(
                        $isFound,
                        $catalogHistory->alias,
                        self::METHOD_GENERIC,
                        is_array($product['errors']['error']) ? $product['errors']['error'] : [$product['errors']['error']]
                    );
                }
            }
        }
        $catalogHistory->additionalInfo = $catalogHistory->additionalInfo + ['completed' => true];
        $this->catalogHistoryRepository->update($catalogHistory);
    }

    /**
     * @param $sku
     *
     * @return int|null
     */
    private function find($sku)
    {
        $findVariationBySKU = @$this->findVariationBySKU((string)$sku);
        if ($findVariationBySKU) {
            return $findVariationBySKU;
        }
        $findVariationById = @$this->findVariationById((int)$sku);
        if ($findVariationById) {
            return $findVariationById;
        }
        $findVariationByNumber = @$this->findVariationByNumber((string)$sku);
        if ($findVariationByNumber) {
            return $findVariationByNumber;
        }
        return null;
    }

    /**
     * @param string $sku
     *
     * @return int|null
     */
    private function findVariationBySKU(string $sku)
    {
        try {
            $searchResult = $this->variationSkuRepositoryContract->search(
                [
                    'marketId' => $this->settings[PluginSettingsHelper::REFERRER_ID],
                    'sku' => $sku,
                ]
            );
            /** @var Variation[] $searchResult */
            if (@$searchResult[0]) { /** @phpstan-ignore-line */
                return $searchResult[0]->variationId; /** @phpstan-ignore-line */
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param int $variationId
     *
     * @return int|null
     */
    private function findVariationById(int $variationId)
    {
        try {
            $variation = $this->variationRepository->findById($variationId);

            return $variation->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $variationNumber
     *
     * @return int|null
     */
    private function findVariationByNumber(string $variationNumber)
    {
        try {
            $this->variationSearchRepository->setFilters([
                'numberExact' => $variationNumber,
                'referrerId' => $this->settings[PluginSettingsHelper::REFERRER_ID],
            ]);
            $result = $this->variationSearchRepository->search();
            $this->variationSearchRepository->clearFilters();
            $entities = $result->getResult();

            if (@$entities[0]) {
                return (int)$entities[0]['id'];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
