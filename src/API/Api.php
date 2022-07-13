<?php

namespace RakutenFrance\API;

use Exception;
use Plenty\Plugin\Translation\Translator;
use RakutenFrance\Assistant\AssistantWizard;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Helpers\PluginSettingsHelper;

abstract class Api
{
    use Loggable;

    const API_URL = PluginConfiguration::ENVIRONMENT;

    //API Versions
    const MARKETPLACE_ORDERS_VERSION = '2017-08-07';
    const MARKETPLACE_ITEMS_VERSION = '2021-04-08';
    const MARKETPLACE_CATEGORIES_VERSION = '2011-10-11';
    const MARKETPLACE_ALIAS_VERSION = '2015-06-30';
    const MARKETPLACE_ALIAS_PROPERTIES_VERSION = '2017-10-04';
    const MARKETPLACE_SHIPPING_VERSION = '2016-05-09';
    const MARKETPLACE_STOCK_VERSION = '2010-09-20';
    const MARKETPLACE_SHIPPING_VERSION_2 = '2017-09-12';
    const MARKETPLACE_BILLING_INFO_VERSION = '2016-03-16';
    const MARKETPLACE_CANCEL_ITEM_VERSION = '2011-02-02';
    const MARKETPLACE_FEED_UPLOAD_VERSION = '2015-02-02';
    const IMPORT_REPORT_VERSION = '2017-02-10';
    const EXPORT_VERSION = '2018-06-29';

    protected $settings;

    public function __construct(PluginSettingsHelper $pluginSettingsHelper)
    {
        $this->settings = $pluginSettingsHelper->getSettings();
    }

    protected function call(string $action, string $endpoint, string $version, array $additionalData = [])
    {
        if (empty($this->settings)) {
            /** @var Translator $translator */
            $translator = pluginApp(Translator::class);
            throw new Exception($translator->trans(PluginConfiguration::PLUGIN_NAME . '::log.settingsNotFound'));
        }

        $url = $this->setUrl($action, $endpoint, $version, $additionalData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);

        if (curl_error($ch)) {
            throw new Exception(curl_error($ch));
        }

        $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $data = json_decode($json, true);

        $this->getLogger(__METHOD__)->debug(PluginConfiguration::PLUGIN_NAME . '::log.apiResponse', [
            'data' => $data,
            'curlInfo' => curl_getinfo($ch),
        ]);

        curl_close($ch);

        return $data['response'] ?? $data;
    }

    protected function setUrl(string $action, string $endpoint, string $version, array $additionalData)
    {
        $url = self::API_URL . '/' . $endpoint . '?action=' . $action;
        $url .= '&login=' . $this->settings[AssistantWizard::VALUE_RAKUTEN_USERNAME];
        $url .= '&pwd=' . $this->settings[AssistantWizard::VALUE_RAKUTEN_TOKEN];
        $url .= '&version=' . $version;
        $url .= '&channel=' . PluginConfiguration::CHANNEL;

        return $additionalData ? $url . '&' . http_build_query($additionalData, '', '&') : $url;
    }
}
