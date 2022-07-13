<?php

namespace RakutenFrance\Catalogue\Database\Repositories;

use Carbon\Carbon;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use RakutenFrance\Catalogue\Database\Models\CatalogHistory;
use Plenty\Plugin\Log\Loggable;

/**
 * Class CatalogHistoryRepository
 *
 * @package RakutenFrance\Catalogue\Database\Repositories
 */
class CatalogHistoryRepository
{
    use Loggable;

    const TYPE_STOCK = 'Stock';
    const TYPE_CATALOG = 'Catalog';
    const TYPE_EAN = 'EAN';

    private $database;

    /**
     * CatalogHistoryRepository constructor.
     *
     * @param DataBase $database
     */
    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Saves catalog
     *
     * @param CatalogHistory|array $catalogHistory
     *
     * @return CatalogHistory
     */
    public function save($catalogHistory): CatalogHistory
    {
        if (!$catalogHistory instanceof CatalogHistory) {
            $catalogHistory = pluginApp(CatalogHistory::class)->set($catalogHistory);
        }
        $this->database->save($catalogHistory);

        return $catalogHistory;
    }

    /**
     * Update catalog
     *
     * @param CatalogHistory $catalogHistory
     *
     * @return CatalogHistory
     */
    public function update(CatalogHistory $catalogHistory): CatalogHistory
    {
        $this->database->save($catalogHistory);

        return $catalogHistory;
    }

    /**
     * Updates timesFailedToProcess or delete if limit reached
     *
     * @param CatalogHistory $catalogHistory
     *
     * @return void
     */
    public function updateLimitOrDelete(CatalogHistory $catalogHistory): void
    {
        if ($catalogHistory->timesFailedToProcess > 4) { //6 Iterations
            $this->deleteById($catalogHistory->id);
        } else {
            $catalogHistory->timesFailedToProcess += 1;
            $this->database->save($catalogHistory);
        }
    }

    /**
     * Delete by id
     *
     * @param int $catalogHistoryId
     *
     * @return bool
     */
    public function deleteById(int $catalogHistoryId): bool
    {
        return $this->database
            ->query(CatalogHistory::class)
            ->where('id', '=', $catalogHistoryId)
            ->delete();
    }

    /**
     * Return information by type
     *
     * @param string $type
     *
     * @return CatalogHistory[]
     */
    public function getByType(string $type): array
    {
        return $this->database
            ->query(CatalogHistory::class)
            ->where('type', '=', $type)
            ->get();
    }

    /**
     * Return information by type and alias for update since
     *
     * @param string $type
     * @param string $alias
     *
     * @return Carbon
     */
    public function getLastUpload(string $type, string $alias): Carbon
    {
        /** @var CatalogHistory $catalogHistory */
        $catalogHistory = $this->database
            ->query(CatalogHistory::class)
            ->where('type', '=', $type)
            ->where('alias', '=', $alias)
            ->orderBy('id', 'desc')
            ->get()[0];

        /** @var Carbon $carbon */
        $carbon = pluginApp(Carbon::class);
        if ($catalogHistory instanceof CatalogHistory) {
            return $carbon->setTimeFromTimeString($catalogHistory->lastUpload);
        }

        return $carbon->now()->subDays(1);
    }

    /**
     * Returns CatalogHistory model.
     *
     * @return CatalogHistory[]
     */
    public function get(): array
    {
        return $this->database
            ->query(CatalogHistory::class)
            ->get();
    }

    /**
     * Purge CatalogHistory model.
     *
     * @return bool
     */
    public function purge(): bool
    {
        return $this->database
            ->query(CatalogHistory::class)
            ->delete();
    }
}
