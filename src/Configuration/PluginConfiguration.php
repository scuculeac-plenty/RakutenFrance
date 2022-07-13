<?php

namespace RakutenFrance\Configuration;

class PluginConfiguration
{
    // Env
    const PRODUCTION_MODE = true;
    const ENVIRONMENT = self::PRODUCTION_MODE ? 'https://ws.fr.shopping.rakuten.com' : 'https://sandbox.fr.shopping.rakuten.com';

    // Plugin
    const PLUGIN_VERSION = '1.0.11';
    const PLUGIN_NAME = 'RakutenFrance';
    const REFERRER_NAME = 'Rakuten.fr';
    const PLUGIN_KEY = 'hashtagESRakutenFrance';
    const DEFAULT_CURRENCY = 'EUR';
    const CHANNEL = 'HashTagPlentymarket';

    const CANCELLATION_DELIMITER = 'CANCEL:';
}
