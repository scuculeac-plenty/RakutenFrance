<?php

namespace RakutenFrance\Catalogue\Controllers;

use Carbon\Carbon;
use Exception;
use Plenty\Modules\Catalog\Contracts\CatalogExportRepositoryContract;
use Plenty\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Plenty\Modules\Catalog\Contracts\TemplateContainerContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Catalogue\Builders\CatalogueBuilder;
use RakutenFrance\Catalogue\Helpers\CatalogueBuilderHelper;
use RakutenFrance\Catalogue\Helpers\CatalogueExportHelper;
use RakutenFrance\Catalogue\Services\CatalogueCsvService;
use RakutenFrance\Catalogue\Services\CatalogueXmlService;
use RakutenFrance\Catalogue\TemplateProviders\GenericTemplateProvider;
use RakutenFrance\Configuration\PluginConfiguration;

class CatalogueController extends Controller
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
        parent::__construct();
        $this->catalogBuilder = $catalogueBuilder;
        $this->catalogRepositoryContract = $catalogRepositoryContract;
        $this->templateContainerContract = $templateContainerContract;
    }

    /**
     * Returns raw catalog export without custom builder
     *
     * @param Request $request
     *
     * @return array|string[]
     */
    public function raw(Request $request): array
    {
        $alias = $request->get('alias');
        if (!$alias) {
            return ['QUERY parameter missing' => 'alias'];
        }
        $timestamp = $request->get('timestamp') ?: null;

        /** @var CatalogueExportHelper $catalogueExportHelper */
        $catalogueExportHelper = pluginApp(CatalogueExportHelper::class);
        $catalog = $catalogueExportHelper->catalogByAlias($alias);

        $resultArray = [];

        /** @var CatalogExportRepositoryContract $catalogExportRepository */
        $catalogExportRepository = pluginApp(CatalogExportRepositoryContract::class);
        $exportService = $catalogExportRepository->exportById($catalog['id']);
        $exportService->setUpdatedSince($timestamp ? Carbon::createFromTimestamp($timestamp) : Carbon::now()->subDay());
        $catalogExportResult = $exportService->getResult();

        foreach ($catalogExportResult as $page) {
            $resultArray = array_merge($resultArray, $page);
        }

        return [
            'alias' => $alias,
            'payload' => $resultArray
        ];
    }

    /**
     * Returns template of the catalog that is being used by alias
     *
     * @param Request $request
     *
     * @return array|string[]
     */
    public function template(Request $request): array
    {
        $alias = $request->get('alias');

        if (!$alias) {
            return ['QUERY parameter missing' => 'alias'];
        }
        /** @var CatalogueBuilderHelper $catalogueExportHelper */
        $catalogueExportHelper = pluginApp(CatalogueBuilderHelper::class);
        $template = $catalogueExportHelper->getTemplateByName($alias);

        return [
            'alias' => $alias,
            'template' => $template
        ];
    }

    /**
     * Create new catalog template by alias
     *
     * @param Request $request
     *
     * @return array[]|string[]
     * @throws Exception
     */
    public function new(Request $request): array
    {
        $alias = $request->get('alias');
        $name = $request->get('name');

        if (!$alias) {
            return ['QUERY parameter missing' => 'alias'];
        }

        if (!$name) {
            return ['QUERY parameter missing' => 'name'];
        }

        $catalog = $this->catalogBuilder->build(
            $alias
        );

        $template = $this->templateContainerContract->register(
            $alias,
            PluginConfiguration::PLUGIN_NAME,
            GenericTemplateProvider::class
        );

        $catalogCreate = $this->catalogRepositoryContract->create([
            'name' => $name,
            'template' => $template->getIdentifier()
        ]);

        return [
            'builder' => [
                'catalog' => $catalog,
                'template' => $template->toArray(),
                'create' => $catalogCreate->toArray()
            ]
        ];
    }

    /**
     * Generates custom catalogue
     *
     * @param Request $request
     *
     * @return string
     */
    public function generate(Request $request): string
    {
        $alias = $request->get('alias');
        if (!$alias) {
            return 'No QUERY:alias provided.';
        }

        $type = $request->get('type');
        if (!$type) {
            return 'No QUERY:type provided. Supported types: [csv,xml]';
        }

        $page = $request->get('page');
        if (!$page) {
            return 'No QUERY:page provided.';
        }

        $validate = (bool)$request->get('validate');
        $timestamp = $request->get('timestamp') ?: null;

        switch ($type) {
            case 'csv':
            {
                /** @var CatalogueCsvService $catalogueEanService */
                $catalogueEanService = pluginApp(CatalogueCsvService::class);

                return $catalogueEanService->generate($alias, $page, $validate, $timestamp);
            }
            case 'xml':
            {
                /** @var CatalogueXmlService $CatalogueXmlService */
                $CatalogueXmlService = pluginApp(CatalogueXmlService::class);

                return $CatalogueXmlService->generate($alias, $page, $validate, $timestamp);
            }
        }

        return 'Nothing generate';
    }
}
