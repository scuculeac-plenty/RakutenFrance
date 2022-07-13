<?php

namespace RakutenFrance\Catalogue;

use Plenty\Modules\Catalog\Contracts\TemplateContainerContract;
use Plenty\Plugin\ServiceProvider;
use RakutenFrance\Catalogue\Database\Repositories\CatalogRepository;
use RakutenFrance\Catalogue\TemplateProviders\GenericTemplateProvider;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;

/**
 * Class CatalogBootServiceProvider
 * @package RakutenFrance\Catalogue
 */
class CatalogBootServiceProvider extends ServiceProvider
{
    use Loggable;

    /**
     * @param TemplateContainerContract $container
     * @param CatalogRepository $catalogRepository
     */
    public function boot(TemplateContainerContract $container, CatalogRepository $catalogRepository)
    {
        foreach ($catalogRepository->get() as $template) {
            $container->register(
                $template->alias,
                PluginConfiguration::PLUGIN_NAME,
                GenericTemplateProvider::class
            );
        }
    }
}
