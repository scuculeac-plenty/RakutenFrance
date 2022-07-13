<?php

namespace RakutenFrance\Crons;

use Exception;
use Plenty\Modules\Cron\Contracts\CronHandler;
use RakutenFrance\Catalogue\Database\Migrations\CreateRakutenEANCatalog;
use RakutenFrance\Catalogue\Database\Repositories\CatalogRepository;
use RakutenFrance\Catalogue\Exports\RakutenExportXML;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Helpers\PluginSettingsHelper;

/**
 * Class SynchronizeCatalogUploadFeed
 *
 * @package RakutenFrance\Crons
 */
class SynchronizeCatalogUploadFeed extends CronHandler
{
    use Loggable;

    /**
     * Catalogs to ignore
     */
    const IGNORE_CATALOGS = [CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG];

    /**
     * @var array
     */
    private $settings;
    /**
     * @var CatalogRepository
     */
    private $catalogRepository;

    /**
     * SynchronizeCatalogUploadFeed constructor.
     *
     * @param PluginSettingsHelper $pluginSettingsHelper
     * @param CatalogRepository    $catalogRepository
     */
    public function __construct(
        PluginSettingsHelper $pluginSettingsHelper,
        CatalogRepository $catalogRepository
    ) {
        $this->settings = $pluginSettingsHelper->getSettings();
        $this->catalogRepository = $catalogRepository;
    }

    public function handle()
    {
        $this->getLogger(__METHOD__)->error("Catalogue export started", 'started');
        try {
            $catalogLists = $this->catalogRepository->get();
            if ($this->settings[PluginSettingsHelper::JOB_SYNCHRONIZE_FEED_TO_MARKETPLACE] != true) {
                $this->getLogger(__METHOD__)->error("Toggle off", 'Im here');
                return;
            }
            foreach ($catalogLists as $catalog) {
                if (in_array($catalog->alias, self::IGNORE_CATALOGS)) {
                    continue;
                }

                /** @var RakutenExportXML $rakutenExportXML */
                $rakutenExportXML = pluginApp(RakutenExportXML::class);
                $totalItemsConstructed = $rakutenExportXML->export($catalog->alias);
                $this->getLogger(__METHOD__)->debug(
                    PluginConfiguration::PLUGIN_NAME . '::catalogueExport.catalogInformation',
                    ['alias' => $catalog->alias, 'total' => $totalItemsConstructed]
                );
                $this->getLogger(__METHOD__)->error("Catalogue export ended", 'end');
            }
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', $e->getMessage());
        }
    }
}
