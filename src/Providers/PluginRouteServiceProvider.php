<?php

namespace RakutenFrance\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\ApiRouter;
use Plenty\Plugin\Routing\Router;
use RakutenFrance\Configuration\PluginConfiguration;
use RakutenFrance\Controllers\CatalogErrorsController;
use RakutenFrance\Controllers\CatalogHistoryController;
use RakutenFrance\Controllers\TestController;
use RakutenFrance\Repositories\CatalogErrorsRepository;

/**
 * Class PluginRouteServiceProvider
 * @package RakutenFrance\Providers
 */
class PluginRouteServiceProvider extends RouteServiceProvider
{
    /**
     * @param ApiRouter $apiRouter
     */
    public function map(ApiRouter $apiRouter)
    {
        $apiRouter->version(
            ['v1'],
            ['middleware' => 'oauth'],
            function ($apiRouter) {
                //Basic
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME . '/show', TestController::class . '@show');
                $apiRouter->post(PluginConfiguration::PLUGIN_NAME . '/reset', TestController::class . '@reset');
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME . '/cron', TestController::class . '@cron');
                //Catalog
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME . '/catalog/show', TestController::class . '@catalog');
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME . '/catalog/migration', TestController::class . '@catalogMigration');
                $apiRouter->delete(PluginConfiguration::PLUGIN_NAME . '/catalog/reset', TestController::class . '@catalogReset');
                //Catalog UI
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME.'/catalog-errors', CatalogErrorsController::class.'@index');
            }
        );
    }
}
