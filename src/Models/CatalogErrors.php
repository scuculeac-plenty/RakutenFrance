<?php

namespace RakutenFrance\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;
use RakutenFrance\Configuration\PluginConfiguration;

/**
 * Class CatalogErrors
 *
 * @property int    $id
 * @property int    $variationId
 * @property string $format
 * @property string $method
 * @property array  $errors
 */
class CatalogErrors extends Model
{
    public $id = 0;
    public $variationId = 0;
    public $format = '';
    public $method = '';
    public $errors = '';

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return PluginConfiguration::PLUGIN_NAME . '::CatalogErrors';
    }

    public function set($array)
    {
        $this->variationId = $array['variationId'] ?? '';
        $this->format = $array['format'] ?? '';
        $this->method = $array['method'] ?? '';
        $this->errors = $array['errors'] ?? '';

        return $this;
    }
}
