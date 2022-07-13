<?php

namespace RakutenFrance\Crons;

use Plenty\Plugin\Application;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Cron\Contracts\CronHandler;
use RakutenFrance\Configuration\PluginConfiguration;

class HeartbeatCron extends CronHandler
{
    use Loggable;

    const URL = 'https://invoices.hashtages.eu/api/usage';
    const SECRET = '3NbXPeS6ksxz';

    /**
     * Get license information from API
     */
    public function handle()
    {
        $application = pluginApp(Application::class);

        $data = [
            'pluginName' => PluginConfiguration::PLUGIN_NAME,
            'pluginVersion' => PluginConfiguration::PLUGIN_VERSION,
            'plentyId' => $application->getPlentyId(),
        ];

        $payload = json_encode($data);
        $signature = \hash_hmac('sha256', $payload, self::SECRET);

        $header = [
            'Content-type: application/json',
            'hashtages-secret: '.$signature
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        curl_exec($ch);
        curl_close($ch);
    }
}
