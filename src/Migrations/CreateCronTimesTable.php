<?php

namespace RakutenFrance\Migrations;

use Exception;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Models\CronTimes;

class CreateCronTimesTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        try {
            $migrate->createTable(CronTimes::class);

            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME . '::log.migration',
                'Cron Times Table created'
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
