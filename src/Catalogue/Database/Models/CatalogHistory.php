<?php

namespace RakutenFrance\Catalogue\Database\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;
use RakutenFrance\Configuration\PluginConfiguration;

/**
 * Class CatalogHistory
 *
 * @property int $id
 * @property string $alias
 * @property int $importId
 * @property string $type
 * @property string $lastUpload
 * @property array $additionalInfo
 * @property int $timesFailedToProcess
 */
class CatalogHistory extends Model
{
    public $id = 0;
    public $alias = '';
    public $importId = 0;
    public $type = '';
    public $lastUpload = '';
    public $additionalInfo = [];
    public $timesFailedToProcess = 0;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return PluginConfiguration::PLUGIN_NAME . '::CatalogHistory';
    }

    /**
     * @param array|CatalogHistory $catalogHistory
     *
     * @return CatalogHistory
     */
    public function set($catalogHistory): CatalogHistory
    {
        $this->id = $catalogHistory['id'] ?? $catalogHistory->id ?? $this->id;
        $this->alias = $catalogHistory['alias'] ?? $catalogHistory->alias ?? $this->alias;
        $this->importId = $catalogHistory['importId'] ?? $catalogHistory->importId ?? $this->importId;
        $this->type = $catalogHistory['type'] ?? $catalogHistory->type ?? $this->type;
        $this->lastUpload = $catalogHistory['lastUpload'] ?? $catalogHistory->lastUpload ?? $this->lastUpload;
        $this->additionalInfo = $catalogHistory['additionalInfo'] ?? $catalogHistory->additionalInfo ?? $this->additionalInfo;
        $this->timesFailedToProcess = $catalogHistory['timesFailedToProcess'] ?? $catalogHistory->timesFailedToProcess ?? $this->timesFailedToProcess;

        return $this;
    }
}
