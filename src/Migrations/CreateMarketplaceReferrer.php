<?php

namespace RakutenFrance\Migrations;

use Exception;
use Plenty\Modules\Order\Referrer\Contracts\OrderReferrerRepositoryContract;
use Plenty\Modules\Order\Referrer\Models\OrderReferrer;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;

/**
 * Class CreateMarketplaceReferrer
 * @package RakutenFrance\Migrations
 */
class CreateMarketplaceReferrer
{
    use Loggable;

    public function run(): void
    {
        try {
            $checkReferrers = $this->checkReferrers();
            if ($checkReferrers) {
                exit;
            }
            /** @var OrderReferrer $orderReferrerModel */
            $orderReferrerModel = pluginApp(OrderReferrer::class);
            $orderReferrerModel->isEditable = true;
            $orderReferrerModel->backendName = PluginConfiguration::REFERRER_NAME;
            $orderReferrerModel->name = PluginConfiguration::REFERRER_NAME;
            $orderReferrerModel->isFilterable = true;
            pluginApp(OrderReferrerRepositoryContract::class)->create($orderReferrerModel->toArray());
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME . '::log.exception',
                [
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Checks if referrer already exists
     *
     * @return bool
     */
    private function checkReferrers(): bool
    {
        /** @var OrderReferrerRepositoryContract $orderReferrerRepositoryContract */
        $orderReferrerRepositoryContract = pluginApp(OrderReferrerRepositoryContract::class);
        /** @var OrderReferrer[] $referrerList */
        $referrerList = $orderReferrerRepositoryContract->getList();
        foreach ($referrerList as $referrer) {
            if ($referrer->backendName == PluginConfiguration::REFERRER_NAME) {
                return true;
            }
        }

        return false;
    }
}
