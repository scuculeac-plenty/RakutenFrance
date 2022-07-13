<?php

namespace RakutenFrance\Catalogue\Database\Migrations;

use Exception;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use RakutenFrance\Catalogue\Database\Models\CatalogHistory;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;

class CreateCatalogHistoryTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        try {
            $migrate->createTable(CatalogHistory::class);

            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME . '::log.migration',
                'CatalogHistory Table created'
            );
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.exception',
                [
                    'message' => $e->getMessage()
                ]
            );
        }
    }
}
