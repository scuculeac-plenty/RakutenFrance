<?php

namespace RakutenFrance\Catalogue\Assistants;

use Plenty\Modules\Wizard\Contracts\WizardActionHandler;
use RakutenFrance\Catalogue\Builders\CatalogueBuilder;
use RakutenFrance\Catalogue\Database\Migrations\CreateRakutenEANCatalog;
use RakutenFrance\Catalogue\Database\Repositories\CatalogRepository;

class CatalogueActionHandler implements WizardActionHandler
{
    const IGNORE_CATALOGS = [CreateRakutenEANCatalog::RAKUTEN_EAN_CATALOG];

    /**
     * Creation ean matching catalog
     *
     * @return void
     */
    public function createEanCatalog(): void
    {
        /** @var CreateRakutenEANCatalog $createRakutenEANCatalog */
        $createRakutenEANCatalog = pluginApp(CreateRakutenEANCatalog::class);
        $createRakutenEANCatalog->run();
    }

    /**
     * Creation ean matching catalog
     *
     * @return void
     * @throws \Exception
     */
    public function createCategoryCatalog(): void
    {
        /** @var CatalogRepository $catalogRepository */
        $catalogRepository = pluginApp(CatalogRepository::class);

        foreach ($catalogRepository->get() as $catalog) {
            if (in_array($catalog->alias, self::IGNORE_CATALOGS)) {
                continue;
            }
            /** @var CatalogueBuilder $catalogueBuilder */
            $catalogueBuilder = pluginApp(CatalogueBuilder::class);
            $catalogueBuilder->build($catalog->alias);
        }
    }
}
