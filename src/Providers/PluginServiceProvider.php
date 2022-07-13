<?php

namespace RakutenFrance\Providers;

use Plenty\Modules\Cron\Services\CronContainer;
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Modules\Wizard\Contracts\WizardContainerContract;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\ServiceProvider;
use RakutenFrance\Assistant\AssistantShippingSettings;
use RakutenFrance\Assistant\AssistantWizard;
use RakutenFrance\Catalogue\Assistants\Catalogue;
use RakutenFrance\Catalogue\CatalogBootServiceProvider;
use RakutenFrance\Catalogue\CatalogueRouteServiceProvider;
use RakutenFrance\Catalogue\Constructors\TemplateConstructor;
use RakutenFrance\Crons\HeartbeatCron;
use RakutenFrance\Crons\OrdersImportCron;
use RakutenFrance\Crons\SynchronizeCatalogEanMatching;
use RakutenFrance\Crons\SynchronizeCatalogErrorsCron;
use RakutenFrance\Crons\SynchronizeCatalogUploadFeed;
use RakutenFrance\Crons\SynchronizeFeedInformation;
use RakutenFrance\Crons\SynchronizeMarketplaceOrderStatuses;
use RakutenFrance\Crons\SynchronizeStockWithMarketplace;
use RakutenFrance\Procedures\AcceptOrderEventProcedure;
use RakutenFrance\Procedures\CancelItemProcedure;
use RakutenFrance\Procedures\RefuseOrderEventProcedure;
use RakutenFrance\Procedures\ShippingOrderEventProcedure;

class PluginServiceProvider extends ServiceProvider
{
    use Loggable;

    public function register()
    {
        $this->getApplication()->register(PluginRouteServiceProvider::class);
        $this->getApplication()->register(CatalogBootServiceProvider::class);
        $this->getApplication()->register(CatalogueRouteServiceProvider::class);
    }

    public function boot(
        CronContainer $container,
        WizardContainerContract $wizardContainerContract,
        EventProceduresService $eventProceduresService
    ) {
        $container->add(CronContainer::EVERY_FIFTEEN_MINUTES, OrdersImportCron::class);
        $container->add(CronContainer::EVERY_FIFTEEN_MINUTES, SynchronizeStockWithMarketplace::class);
        $container->add(CronContainer::EVERY_TWENTY_MINUTES, SynchronizeMarketplaceOrderStatuses::class);
        $container->add(CronContainer::DAILY, SynchronizeFeedInformation::class);
        $container->add(CronContainer::HOURLY, SynchronizeCatalogEanMatching::class);
        $container->add(CronContainer::DAILY, SynchronizeCatalogUploadFeed::class);
        $container->add(CronContainer::HOURLY, SynchronizeCatalogErrorsCron::class);
        $container->add(CronContainer::HOURLY, HeartbeatCron::class);

        $this->getApplication()->singleton(TemplateConstructor::class);
        $wizardContainerContract->register(Catalogue::ASSISTANT_KEY, Catalogue::class);
        $wizardContainerContract->register(AssistantWizard::ASSISTANT_KEY, AssistantWizard::class);
        $wizardContainerContract->register(AssistantShippingSettings::ASSISTANT_SHIPPING_KEY, AssistantShippingSettings::class);

        $eventProceduresService->registerProcedure(
            'RakutenFrance',
            ProcedureEntry::EVENT_TYPE_ORDER,
            [
                'de' => 'Rakuten.fr: Auftrag annehmen',
                'en' => 'Rakuten.fr: Accept Order'
            ],
            AcceptOrderEventProcedure::class . '@run'
        );
        $eventProceduresService->registerProcedure(
            'RakutenFrance',
            ProcedureEntry::EVENT_TYPE_ORDER,
            [
                'de' => 'Rakuten.fr: Auftrag ablehnen',
                'en' => 'Rakuten.fr: Refuse Order'
            ],
            RefuseOrderEventProcedure::class . '@run'
        );
        $eventProceduresService->registerProcedure(
            'RakutenFrance',
            ProcedureEntry::EVENT_TYPE_ORDER,
            [
                'de' => 'Rakuten.fr: Versandbestätigung übermitteln',
                'en' => 'Rakuten.fr: Order Shipping Confirmation'
            ],
            ShippingOrderEventProcedure::class . '@run'
        );
        $eventProceduresService->registerProcedure(
            'RakutenFrance',
            ProcedureEntry::EVENT_TYPE_ORDER,
            [
                'de' => 'Rakuten.fr: Auftragspositionen stornieren',
                'en' => 'Rakuten.fr: Cancel order items'
            ],
            CancelItemProcedure::class . '@run'
        );
    }
}
