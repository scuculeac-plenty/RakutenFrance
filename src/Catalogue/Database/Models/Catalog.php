<?php

namespace RakutenFrance\Catalogue\Database\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;
use RakutenFrance\Configuration\PluginConfiguration;

/**
 * Class Catalog
 *
 * @property int $id
 * @property string $alias
 */
class Catalog extends Model
{
    public $id = 0;
    public $alias = '';

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return PluginConfiguration::PLUGIN_NAME . '::Catalog';
    }

    /**
     * @param Catalog|array $catalog
     *
     * @return Catalog
     */
    public function set($catalog): Catalog
    {
        $this->alias = $catalog['alias'] ?? $catalog->alias ?? $this->alias;

        return $this;
    }
}
