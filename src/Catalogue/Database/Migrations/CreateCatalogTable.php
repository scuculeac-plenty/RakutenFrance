<?php

namespace RakutenFrance\Catalogue\Database\Migrations;

use Exception;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use RakutenFrance\Catalogue\Database\Models\Catalog;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;

class CreateCatalogTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        try {
            $migrate->createTable(Catalog::class);

            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME . '::log.migration',
                'CatalogTable created'
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
