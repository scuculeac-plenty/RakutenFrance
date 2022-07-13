<?php

namespace RakutenFrance\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;
use RakutenFrance\Configuration\PluginConfiguration;

/**
 * Class CronTimes
 *
 * @property int $id
 * @property string $type
 * @property int $timestamp
 */
class CronTimes extends Model
{
    public $id = 0;
    public $type = '';
    public $timestamp = 0;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return PluginConfiguration::PLUGIN_NAME . '::CronTimes';
    }

    /**
     * Sets Cron Times model
     *
     * @param $cronTimes
     *
     * @return $this
     */
    public function set($cronTimes): CronTimes
    {
        $this->id = $cronTimes['id'] ?? $this->id;
        $this->type = $cronTimes['type'] ?? $this->type;
        $this->timestamp = $cronTimes['timestamp'] ?? $this->timestamp;

        return $this;
    }
}
