<?php

namespace RakutenFrance\Catalogue\Helpers;

use Plenty\Modules\Catalog\Contracts\CatalogExportRepositoryContract;
use Plenty\Modules\Catalog\Contracts\CatalogExportServiceContract;
use Plenty\Modules\Catalog\Contracts\CatalogRepositoryContract;
use Plenty\Modules\Catalog\Contracts\TemplateContainerContract;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable;

/**
 * Class Export
 *
 * @package RakutenFrance\Catalogue\Export
 */
class CatalogueExportHelper
{
    use Loggable;
    const TYPE_CATALOG = 'Catalog';
    const TYPE_RAKUTEN = 'Rakuten';

    /**
     * @var CatalogRepositoryContract
     */
    private $catalogRepository;
    /**
     * @var CatalogExportRepositoryContract
     */
    private $catalogExportRepository;
    /**
     * @var TemplateContainerContract
     */
    private $templateContainer;

    /**
     * Export constructor.
     *
     * @param CatalogRepositoryContract $catalogRepository
     * @param CatalogExportRepositoryContract $catalogExportRepository
     * @param TemplateContainerContract $templateContainer
     */
    public function __construct(
        CatalogRepositoryContract $catalogRepository,
        CatalogExportRepositoryContract $catalogExportRepository,
        TemplateContainerContract $templateContainer
    ) {
        $this->catalogRepository = $catalogRepository;
        $this->catalogExportRepository = $catalogExportRepository;
        $this->templateContainer = $templateContainer;
    }

    /**
     * Export catalogs list by alias
     *
     * @param string $alias
     *
     * @return array
     */
    public function catalogByAlias(string $alias): array
    {
        $page = 1;
        $this->catalogRepository->setFilters([
            'type' => PluginConfiguration::PLUGIN_NAME,
            'active' => true
        ]);
        do {
            $catalogs = $this->catalogRepository->all($page, 25);
            foreach ($catalogs->getResult() as $catalog) {
                $template = $this->templateContainer->getTemplate($catalog['template']);
                if ($template->getName() == $alias) {
                    return ['id' => $catalog['id'], 'templateName' => $template->getName()];
                }
            }
            $page++;
        } while (!$catalogs->isLastPage());

        return [];
    }

    /**
     * Catalogs result
     *
     * @param string $catalogId
     *
     * @return CatalogExportServiceContract
     */
    public function exportCatalogById(string $catalogId): CatalogExportServiceContract
    {
        return $this->catalogExportRepository->exportById($catalogId);
    }

    /**
     * Gets catalog export mappings by alias
     *
     * @param string $alias
     *
     * @return array
     */
    public function getMappingByAlias(string $alias): array
    {
        return pluginApp(CatalogueBuilderHelper::class)->getTemplateByName($alias)[CatalogueBuilderHelper::CATALOG_MAP];
    }

    /**
     * Gets only mapping required fields
     *
     * @param array $catalogMap
     *
     * @return array
     */
    public function getCatalogRequiredFieldsList(array $catalogMap): array
    {
        $required = [];
        foreach ($catalogMap as $catalog) {
            foreach ($catalog as $fieldKey => $fields) {
                if ($fields['required']) {
                    array_push($required, $fieldKey);
                }
            }
        }

        return $required;
    }

    /**
     * Check if required fields missing
     *
     * @param array $requiredFields
     * @param array $variation
     *
     * @return array
     */
    public function isValidVariation(array $requiredFields, array $variation): array
    {
        $errors = [];
        $valid = true;
        if (!$variation) {
            return ['valid' => false, 'errors' => [['Catalog' => 'Variation failed.', 'variation' => $variation]]];
        }

        foreach ($requiredFields as $required) {
            if (@$variation[$required]) {
                continue;
            } else {
                $errors[] = ["Issue" => "{$required} field must not be empty."];
                $valid = false;
            }
        }

        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }

    /**
     * Implodes array values by key
     *
     * @param string $byKey
     * @param array $list
     *
     * @return string
     */
    public function implodeByKey(string $byKey, array $list): string
    {
        $values = array_map(function ($array) use ($byKey) {
            return $array[$byKey];
        }, $list);

        return implode('|', $values);
    }
}
