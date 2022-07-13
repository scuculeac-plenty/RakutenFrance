<?php

namespace RakutenFrance\Crons;

use DateTime;
use Exception;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\API\MarketplaceClient;
use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Cron\Contracts\CronHandler;
use RakutenFrance\Helpers\PluginSettingsHelper;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use RakutenFrance\Catalogue\Database\Repositories\CatalogHistoryRepository;
use Plenty\Modules\Item\Variation\Contracts\VariationSearchRepositoryContract;
use Plenty\Modules\Item\VariationSku\Contracts\VariationSkuRepositoryContract;

class SynchronizeFeedInformation extends CronHandler
{
    use Loggable;

    const IMPORTS_TO_DELETE = [self::ERROR, self::CANCELLED, self::FILE_IS_CORRUPTED, self::FILE_IS_IDENTICAL];
    const ERROR = 'Erreur';
    const CANCELLED = 'Annulé';
    const FILE_IS_CORRUPTED = 'Aucune ligne n’a été chargée';
    const FILE_IS_IDENTICAL = 'Fichier précédent identique, pas de traitement';

    const IMPORTS_TO_WAIT = [self::PROCESSING, self::PENDING, self::IN_PROGRESS];
    const PROCESSING = 'Reçu';
    const PENDING = 'En attente';
    const IN_PROGRESS = 'M.à.j. en cours';
    const REPORT_TO_PROCESS = [self::REPORT_PROCESSED,self::REPORT_PROCESS_FAILED];
    const REPORT_PROCESSED = 'PROCESSED';
    const REPORT_PROCESS_FAILED = 'PROCESS_FAILED';

    const SUCCESS = 'Traité';
    /**
     * @var CatalogHistoryRepository
     */
    private $catalogHistoryRepository;
    /**
     * @var MarketplaceClient
     */
    private $marketplaceClient;
    /**
     * @var VariationSkuRepositoryContract
     */
    private $variationSkuRepositoryContract;
    /**
     * @var array
     */
    private $settings;

    private $variationSearchRepository;
    private $variationRepository;

    /**
     * SynchronizeFeedInformation constructor.
     *
     * @param CatalogHistoryRepository          $catalogHistoryRepository
     * @param MarketplaceClient                 $marketplaceClient
     * @param VariationSkuRepositoryContract    $variationSkuRepositoryContract
     * @param PluginSettingsHelper              $pluginSettingsHelper
     * @param VariationSearchRepositoryContract $variationSearchRepositoryContract
     * @param VariationRepositoryContract       $variationRepositoryContract
     */
    public function __construct(
        CatalogHistoryRepository $catalogHistoryRepository,
        MarketplaceClient $marketplaceClient,
        VariationSkuRepositoryContract $variationSkuRepositoryContract,
        PluginSettingsHelper $pluginSettingsHelper,
        VariationSearchRepositoryContract $variationSearchRepositoryContract,
        VariationRepositoryContract $variationRepositoryContract
    ) {
        $this->catalogHistoryRepository = $catalogHistoryRepository;
        $this->marketplaceClient = $marketplaceClient;
        $this->variationSkuRepositoryContract = $variationSkuRepositoryContract;
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->variationSearchRepository = $variationSearchRepositoryContract;
        $this->variationRepository = $variationRepositoryContract;
    }

    public function handle()
    {
        try {
            $this->cleanUp();
            $this->searchAndAssign();
        } catch (Exception $exception) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.exception',
                $exception->getMessage()
            );
        }
    }

    /**
     * Cleans catalog history if limit is reached
     *
     * @return void
     */
    private function cleanUp(): void
    {
        $catalogHistory = $this->catalogHistoryRepository->get();
        foreach ($catalogHistory as $catalog) {
            $this->catalogHistoryRepository->updateLimitOrDelete($catalog);
        }
    }

    /**
     * Search and assigns SKU by barcodes
     *
     * @return void
     * @throws Exception
     */
    private function searchAndAssign(): void
    {
        $nextToken = '';
        do {
            list($nextToken, $exports) = $this->marketplaceClient->getExports($nextToken);
            foreach ($exports as $itemList) {
                foreach ($itemList as $item) {
                    $this->getLogger(__METHOD__)->debug(PluginConfiguration::PLUGIN_NAME.'::log.rakutenItem', [
                        'item' => $item
                    ]);

                    $pid = $item['productsummary']['productid'];
                    $aid = $item['advertid'];

                    $variation = $this->findVariationBySKU($item['sku']);
                    if (!empty($variation)) {
                        $this->getLogger(__METHOD__)->debug(PluginConfiguration::PLUGIN_NAME.'::log.variationFoundBySku', [
                            'variation' => $variation,
                            'sku' => $item['sku']
                        ]);
                        continue;
                    }

                    $variation = $this->findVariationById((int)$item['sku']);
                    if (!empty($variation)) {
                        $this->getLogger(__METHOD__)->debug(PluginConfiguration::PLUGIN_NAME.'::log.variationFoundById', [
                            'variation' => $variation,
                            'id' => $item['sku']
                        ]);

                        $this->addSKU($variation['id'], $item['sku'], "PID: $pid, AID: $aid");
                        continue;
                    }

                    $variation = $this->findVariationByNumber($item['sku']);
                    if (!empty($variation)) {
                        $this->getLogger(__METHOD__)->debug(PluginConfiguration::PLUGIN_NAME.'::log.variationFoundByNumber', [
                            'variation' => $variation,
                            'number' => $item['sku']
                        ]);

                        $this->addSKU($variation['id'], $item['sku'], "PID: $pid, AID: $aid");
                        continue;
                    }

                    if (isset($item['productsummary']['barcode'])) {
                        $variation = $this->findVariationByBarcode($item['productsummary']['barcode']);
                        if (!empty($variation)) {
                            $this->getLogger(__METHOD__)->debug(PluginConfiguration::PLUGIN_NAME.'::log.variationFoundByBarcode', [
                                'variation' => $variation,
                                'barcode' => $item['productsummary']['barcode']
                            ]);

                            $this->addSKU($variation['id'], $item['sku'], "PID: $pid, AID: $aid");
                            continue;
                        }
                    }
                }
            }
        } while (!empty($nextToken));
    }

    private function findVariationByNumber(string $variationNumber)
    {
        $this->variationSearchRepository->setFilters([
            'numberExact' => $variationNumber,
            'referrerId' => $this->settings[PluginSettingsHelper::REFERRER_ID],
        ]);
        $result = $this->variationSearchRepository->search();
        $this->variationSearchRepository->clearFilters();
        $entires = $result->getResult();

        return $entires[0];
    }

    private function findVariationById(int $variationId)
    {
        try {
            $variation = $this->variationRepository->findById($variationId);

            return $variation->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function addSKU(int $variationId, string $sku, string $additionalInformation = '')
    {
        try {
            $this->variationSkuRepositoryContract->create(
                [
                    'variationId' => $variationId,
                    'marketId' => (int)$this->settings[PluginSettingsHelper::REFERRER_ID],
                    'accountId' => (int)$this->settings[PluginSettingsHelper::PLENTY_ACCOUNT_ID],
                    'initialSku' => $sku,
                    'sku' => $sku,
                    'parentSku' => "",
                    'isActive' => false,
                    'exportedAt' => date(DateTime::W3C, time()),
                    'status' => "ACTIVE",
                    'additionalInformation' => $additionalInformation,
                ]
            );
        } catch (ValidationException $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'error' => $e->getMessageBag()
            ]);
        }
    }

    private function findVariationByBarcode(string $barcode)
    {
        $this->variationSearchRepository->setFilters([
            'barcode' => $barcode,
            'referrerId' => $this->settings[PluginSettingsHelper::REFERRER_ID],
        ]);
        $result = $this->variationSearchRepository->search();
        $this->variationSearchRepository->clearFilters();
        $entires = $result->getResult();

        return $entires[0];
    }

    private function findVariationBySKU(string $sku)
    {
        $searchResult = $this->variationSkuRepositoryContract->search(
            [
                'marketId' => $this->settings[PluginSettingsHelper::REFERRER_ID],
                'sku' => $sku,
            ]
        );

        return $searchResult[0];
    }
}
