<?php

namespace RakutenFrance\Catalogue;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\ApiRouter;
use RakutenFrance\Catalogue\Controllers\CatalogueController;
use RakutenFrance\Configuration\PluginConfiguration;

class CatalogueRouteServiceProvider extends RouteServiceProvider
{
    /**
     * Registers catalogue routes for any related catalogue issues.
     *
     * @param ApiRouter $apiRouter
     */
    public function map(
        ApiRouter $apiRouter
    ) {
        $apiRouter->version(
            ['v1'],
            ['middleware' => 'oauth'],
            function ($apiRouter) {
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME . '/catalog/template', CatalogueController::class . '@template');
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME . '/catalog/template/new', CatalogueController::class . '@new');
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME . '/catalog/export/raw', CatalogueController::class . '@raw');
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME . '/catalog/export/generate', CatalogueController::class . '@generate');
            }
        );
    }
}
