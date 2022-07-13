<?php

namespace RakutenFrance\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use RakutenFrance\Models\CatalogErrors;

class CatalogErrorsRepository
{
    /**
     * @var DataBase
     */
    private $database;

    /**
     * CatalogErrorsRepository constructor.
     *
     * @param DataBase $database
     */
    public function __construct(DataBase $database)
    {
        $this->database = $database;
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
            ->query(CatalogErrors::class)
            ->where('id', '=', $catalogHistoryId)
            ->delete();
    }

    /**
     * Return information by type
     *
     * @param string $method
     *
     * @return CatalogErrors[]
     */
    public function getByMethod(string $method): array
    {
        return $this->database
            ->query(CatalogErrors::class)
            ->where('method', '=', $method)
            ->get();
    }

    /**
     * Return information by type
     *
     * @param int $variationId
     *
     * @return CatalogErrors[]
     */
    public function findByVariationId(int $variationId): array
    {
        return $this->database
            ->query(CatalogErrors::class)
            ->where('variationId', '=', $variationId)
            ->get();
    }

    /**
     * Return information by type
     *
     * @param string $format
     *
     * @return CatalogErrors[]
     */
    public function getByFormat(string $format): array
    {
        return $this->database
            ->query(CatalogErrors::class)
            ->where('format', '=', $format)
            ->get();
    }

    /**
     * Create or update by variationId
     *
     * @param int    $variationId
     * @param string $format
     * @param string $method
     * @param array  $errors
     *
     * @return void
     */
    public function createOrUpdate(int $variationId, string $format, string $method, array $errors): void
    {
        $save = $this->findByVariationIdAndMethod($variationId, $method);
        if (!$save instanceof CatalogErrors) {
            /** @var CatalogErrors $create */
            $create = pluginApp(CatalogErrors::class);
            $save = $create->set([
                'variationId' => $variationId,
                'format' => $format,
                'method' => $method,
                'errors' => $errors,
            ]);
        } else {
            $save->format = $format;
            $save->method = $method;
            $save->errors = $errors;
        }

        $this->database->save($save);
    }

    /**
     * Return information by type
     *
     * @param int    $variationId
     * @param string $method
     *
     * @return CatalogErrors|null
     */
    public function findByVariationIdAndMethod(int $variationId, string $method)
    {
        return $this->database
            ->query(CatalogErrors::class)
            ->where('variationId', '=', $variationId)
            ->where('method', '=', $method)
            ->get()[0];
    }

    /**
     * Delete by variationId
     *
     * @param int    $variationId
     * @param string $method
     *
     * @return bool
     */
    public function deleteByVariationIdAndMethod(int $variationId, string $method): bool
    {
        return $this->database
            ->query(CatalogErrors::class)
            ->where('variationId', '=', $variationId)
            ->where('method', '=', $method)
            ->delete();
    }

    /**
     * Purge by format
     *
     * @param string $alias
     * @param string $method
     *
     * @return bool
     */
    public function purgeByFormatAndMethod(string $alias, string $method): bool
    {
        return $this->database
            ->query(CatalogErrors::class)
            ->where('format', '=', $alias)
            ->where('method', '=', $method)
            ->delete();
    }

    /**
     * Returns CatalogErrors models.
     *
     * @return CatalogErrors[]
     */
    public function get(): array
    {
        return $this->database
            ->query(CatalogErrors::class)
            ->get();
    }
}
