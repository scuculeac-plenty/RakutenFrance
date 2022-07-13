<?php

namespace RakutenFrance\Controllers;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Crons\OrdersImportCron;
use RakutenFrance\Crons\SynchronizeCatalogEanMatching;
use RakutenFrance\Crons\SynchronizeCatalogErrorsCron;
use RakutenFrance\Crons\SynchronizeCatalogUploadFeed;
use RakutenFrance\Crons\SynchronizeFeedInformation;
use RakutenFrance\Crons\SynchronizeMarketplaceOrderStatuses;
use RakutenFrance\Crons\SynchronizeStockWithMarketplace;

class TestController extends Controller
{
    use Loggable;

    public function reset(Request $request, Response $response, Migrate $migrate)
    {
        $model = $request->get('model');
        $migrate->deleteTable('RakutenFrance\\Models\\' . $model);
        $migrate->createTable('RakutenFrance\\Models\\' . $model);

        return $response->json('Ok');
    }

    public function show(Request $request, Response $response)
    {
        return $response->json(pluginApp(DataBase::class)->query('RakutenFrance\\Models\\' . $request->get('model'))->get());
    }

    public function catalog(Request $request, Response $response)
    {
        return $response->json(pluginApp(DataBase::class)->query('RakutenFrance\\Catalogue\\Database\\Models\\' . $request->get('model'))->get());
    }

    public function catalogReset(Request $request, Response $response, Migrate $migrate)
    {
        $model = $request->get('model');
        $migrate->deleteTable('RakutenFrance\\Catalogue\\Database\\Models\\' . $model);
        $migrate->createTable('RakutenFrance\\Catalogue\\Database\\Models\\' . $model);
        $response->json('Ok');
    }

    public function catalogMigration(Request $request, Response $response)
    {
        $model = $request->get('model');
        $class = "RakutenFrance\\Catalogue\\Database\\Migrations\\$model";
        $migration = pluginApp($class);
        if ($migration instanceof $class) {
            $migration->run();
        }

        $response->json('Ok');
    }

    public function cron(Request $request)
    {
        $model = $request->get('model');
        switch ($model) {
            case 'orders':
                return pluginApp(OrdersImportCron::class)->handle();
            case 'stockSync':
                return pluginApp(SynchronizeStockWithMarketplace::class)->handle();
            case 'exportAll':
                return pluginApp(SynchronizeCatalogUploadFeed::class)->handle();
            case 'fileEAN':
                return pluginApp(SynchronizeCatalogEanMatching::class)->handle();
            case 'fileSync':
                return pluginApp(SynchronizeFeedInformation::class)->handle();
            case 'orderStatus':
                return pluginApp(SynchronizeMarketplaceOrderStatuses::class)->handle();
            case 'catalogErrors':
                return pluginApp(SynchronizeCatalogErrorsCron::class)->handle();
        }
        return 'no cron found';
    }
}
