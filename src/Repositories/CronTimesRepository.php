<?php

namespace RakutenFrance\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Models\CronTimes;

/**
 * Class CronTimesRepository
 * @package JTLffCenterCloud\Repositories
 */
class CronTimesRepository
{
    use Loggable;

    const TYPE_STOCK = 'STOCK';

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
     * Find timestamp by type
     *
     * @param string $type
     * @param int|false $setDefaultTimestamp
     *
     * @return CronTimes
     */
    public function findByType(string $type, $setDefaultTimestamp): CronTimes
    {
        if (!$setDefaultTimestamp) {
            $setDefaultTimestamp = strtotime("-2 days");
        }

        $cronTime = $this->getByType($type)[0];
        if (!$cronTime instanceof CronTimes) {
            $cronTime = $this->save(['type' => $type, 'timestamp' => $setDefaultTimestamp]);
        }

        return $cronTime;
    }

    /**
     * Return information by type
     *
     * @param string $type
     *
     * @return CronTimes[]
     */
    private function getByType(string $type): array
    {
        return $this->database
            ->query(CronTimes::class)
            ->where('type', '=', $type)
            ->get();
    }

    /**
     * Saves catalog
     *
     * @param CronTimes|array $cronTime
     *
     * @return CronTimes
     */
    private function save($cronTime): CronTimes
    {
        if (!$cronTime instanceof CronTimes) {
            $cronTime = pluginApp(CronTimes::class)->set($cronTime);
        }
        $this->database->save($cronTime);

        return $cronTime;
    }

    /**
     * Updates cron times
     *
     * @param CronTimes $cronTime
     *
     * @return void
     */
    public function update(CronTimes $cronTime): void
    {
        $cronTime->timestamp = time();
        $this->database->save($cronTime);
    }
}
