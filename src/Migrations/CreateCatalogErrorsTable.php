<?php

namespace RakutenFrance\Migrations;

use Exception;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Models\CatalogErrors;

class CreateCatalogErrorsTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        try {
            $migrate->createTable(CatalogErrors::class);

            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME . '::log.migration',
                'Catalog Errors Table created'
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
