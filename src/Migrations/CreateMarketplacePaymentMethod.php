<?php

namespace RakutenFrance\Migrations;

use Exception;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;

class CreateMarketplacePaymentMethod
{
    use Loggable;

    public function run(): void
    {
        try {
            $paymentMethodData = [
                'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                'paymentKey' => PluginConfiguration::PLUGIN_NAME,
                'name' => PluginConfiguration::PLUGIN_NAME,
            ];
            pluginApp(PaymentMethodRepositoryContract::class)->createPaymentMethod($paymentMethodData);
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME . '::log.exception', [
                'message' => $e->getMessage()
            ]);
        }
    }
}
