<?php

namespace RakutenFrance\Catalogue\Assistants;

use Plenty\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Plenty\Modules\Catalog\Contracts\TemplateContainerContract;
use Plenty\Modules\Wizard\Contracts\WizardSettingsHandler;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Catalogue\Builders\CatalogueBuilder;
use RakutenFrance\Catalogue\Database\Migrations\CreateRakutenEANCatalog;
use RakutenFrance\Catalogue\TemplateProviders\GenericTemplateProvider;
use RakutenFrance\Configuration\PluginConfiguration;

class CatalogueHandler implements WizardSettingsHandler
{
    use Loggable;

    private $catalogBuilder;
    private $catalogRepositoryContract;
    private $templateContainerContract;

    public function __construct(
        CatalogueBuilder $catalogueBuilder,
        CatalogRepositoryContract $catalogRepositoryContract,
        TemplateContainerContract $templateContainerContract
    ) {
        $this->catalogBuilder = $catalogueBuilder;
        $this->catalogRepositoryContract = $catalogRepositoryContract;
        $this->templateContainerContract = $templateContainerContract;
    }

    /**
     * @throws \Exception
     */
    public function handle(array $parameters): bool
    {
        $catalog = $this->catalogBuilder->build(
            $parameters['data'][Catalogue::VALUE_CATALOG_ALIAS]
        );

        if (!$catalog) {
            return false;
        }

        $template = $this->templateContainerContract->register(
            $parameters['data'][Catalogue::VALUE_CATALOG_ALIAS],
            PluginConfiguration::PLUGIN_NAME,
            GenericTemplateProvider::class
        );

        $this->catalogRepositoryContract->create([
            'name' => $parameters['data'][Catalogue::VALUE_CATALOG_NAME],
            'template' => $template->getIdentifier()
        ]);

        return true;
    }
}
