<?php

namespace RakutenFrance\Catalogue\Database\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use RakutenFrance\Catalogue\Database\Models\Catalog;
use Plenty\Plugin\Log\Loggable;

/**
 * Class CatalogRepository.
 */
class CatalogRepository
{
    use Loggable;

    /**
     * @var DataBase
     */
    private $database;

    /**
     * CatalogRepository constructor.
     *
     * @param DataBase $database
     */
    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Saves settings
     *
     * @param Catalog|array $catalog
     *
     * @return Catalog
     */
    public function save($catalog): Catalog
    {
        if (!$catalog instanceof Catalog) {
            $catalog = pluginApp(Catalog::class)->set($catalog);
        }
        $this->deleteByAlias($catalog->alias);
        $this->database->save($catalog);

        return $catalog;
    }

    /**
     * Delete catalog by alias
     *
     * @param $alias
     *
     * @return bool
     */
    public function deleteByAlias(string $alias): bool
    {
        return $this->database
            ->query(Catalog::class)
            ->where('alias', '=', $alias)
            ->delete();
    }

    /**
     * Returns Catalog model.
     *
     * @return Catalog[]
     */
    public function get(): array
    {
        return $this->database
            ->query(Catalog::class)
            ->get();
    }
}
