<?php

namespace RakutenFrance\Crons;

use Exception;
use Plenty\Modules\Cron\Contracts\CronHandler;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\API\Api;
use RakutenFrance\Catalogue\Database\Migrations\CreateRakutenEANCatalog;
use RakutenFrance\Catalogue\Database\Repositories\CatalogHistoryRepository;
use RakutenFrance\Catalogue\Exports\RakutenExportCSV;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Helpers\PluginSettingsHelper;

/**
 * Class SynchronizeCatalogEanMatching
 *
 * @package RakutenFrance\Crons
 */
class SynchronizeCatalogEanMatching extends CronHandler
{
    use Loggable;

    /**
     * @var array
     */
    private $settings;
    /**
     * @var LibraryCallContract
     */
    private $libraryCallContract;
    /**
     * @var RakutenExportCSV
     */
    private $rakutenExportCSV;
    /**
     * @var CatalogHistoryRepository
     */
    private $catalogHistoryRepository;

    /**
     * SynchronizeCatalogEanMatching constructor.
     *
     * @param PluginSettingsHelper     $pluginSettingsHelper
     * @param LibraryCallContract      $libraryCallContract
     * @param RakutenExportCSV         $rakutenExportCSV
     * @param CatalogHistoryRepository $catalogHistoryRepository
     */
    public function __construct(
        PluginSettingsHelper $pluginSettingsHelper,
        LibraryCallContract $libraryCallContract,
        RakutenExportCSV $rakutenExportCSV,
        CatalogHistoryRepository $catalogHistoryRepository
    ) {
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->libraryCallContract = $libraryCallContract;
        $this->rakutenExportCSV = $rakutenExportCSV;
        $this->catalogHistoryRepository = $catalogHistoryRepository;
    }

    public function handle()
    {
        try {
            if ($this->settings[PluginSettingsHelper::JOB_SYNCHRONIZE_EAN_MATCHING_FILE] != true) {
                return;
            }

            $this->uploadCatalog($this->settings);
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', $e->getMessage());
        }
    }

    /**
     *  Upload Ean catalog
     *
     * @param $settings
     */
    private function uploadCatalog(array $settings)
    {
        $fileName = 'FULLADVERT_' . uniqid() . '_' . time() . '.csv';
        $catalogContent = $this->rakutenExportCSV->export();
        if ($catalogContent) {
            $upload = $this->libraryCallContract->call(
                PluginConfiguration::PLUGIN_NAME . '::uploadEanFile',
                [
                    'file_name' => $fileName,
                    'file_content' => $catalogContent,
                    'username' => $settings[PluginSettingsHelper::USERNAME],
                    'access_key' => $settings[PluginSettingsHelper::TOKEN],
                    'environment' => PluginConfiguration::ENVIRONMENT,
                    'stock_version' => Api::MARKETPLACE_STOCK_VERSION,
                    'profile_id' => $settings[PluginSettingsHelper::PROFILE_ID],
                    'mapping_alias' => 'FULLADVERT',
                    'channel' => PluginConfiguration::CHANNEL,
                ]
            );

            if ($upload['success'] == true) {
                $this->getLogger(__METHOD__)->debug(
                    PluginConfiguration::PLUGIN_NAME . '::catalogue.catalogExportUploaded',
                    [
                        'Catalog' => CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG,
                        'Information' => $upload
                    ]
                );
                $importId = (int)$upload['message']['response']['importid'];
                if ($importId) {
                    $this->catalogHistoryRepository->save(
                        [
                            'alias' => CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG,
                            'importId' => $importId,
                            'type' => CatalogHistoryRepository::TYPE_EAN,
                            'lastUpload' => date('Y-m-d H:i:s'),
                            'additionalInfo' => []
                        ]
                    );
                }
            } else {
                $this->getLogger(__METHOD__)->error(
                    PluginConfiguration::PLUGIN_NAME . '::catalogue.catalogExportFailed',
                    [
                        'Catalog' => CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG,
                        'Information' => $upload
                    ]
                );
            }
        } else {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::catalogue.catalogGenerationFailed',
                [
                    'Catalog' => CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG,
                    'Export' => $catalogContent
                ]
            );
        }
    }
}
