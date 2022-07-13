<?php

namespace RakutenFrance\Catalogue\TemplateProviders;

use Plenty\Modules\Catalog\Containers\Filters\CatalogFilterBuilderContainer;
use Plenty\Modules\Catalog\Containers\TemplateGroupContainer;
use Plenty\Modules\Catalog\Contracts\CatalogMutatorContract;
use Plenty\Modules\Catalog\Contracts\TemplateContract;
use Plenty\Modules\Catalog\Models\ComplexTemplateField;
use Plenty\Modules\Catalog\Models\SimpleTemplateField;
use Plenty\Modules\Catalog\Models\TemplateGroup;
use Plenty\Modules\Catalog\Services\Converter\Containers\ResultConverterContainer;
use Plenty\Modules\Catalog\Services\Converter\ResultConverters\Defaults\CSVResultConverter;
use Plenty\Modules\Catalog\Templates\Providers\AbstractGroupedTemplateProvider;
use Plenty\Modules\Item\Catalog\ExportTypes\Variation\Filters\Builders\VariationFilterBuilderFactory;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Catalogue\Constructors\TemplateConstructor;
use RakutenFrance\Catalogue\DataProviders\GenericComplexFieldProvider;
use RakutenFrance\Catalogue\Helpers\CatalogueFiltersHelper;
use RakutenFrance\Catalogue\Mutators\GenericMutator;

/**
 * Class GenericTemplateProvider.
 */
class GenericTemplateProvider extends AbstractGroupedTemplateProvider
{
    use Loggable;

    /**
     * @var TemplateContract
     */
    protected $templateContract;

    /**
     * @var TemplateConstructor
     */
    private $templateConstructor;
    /**
     * @var array
     */
    private $genericTemplateProvider;

    /**
     * GenericTemplateProvider constructor.
     *
     * @param TemplateConstructor $templateConstructor
     * @param TemplateContract    $template
     */
    public function __construct(
        TemplateConstructor $templateConstructor,
        TemplateContract $template
    ) {
        $this->templateContract = $template;
        $this->templateConstructor = $templateConstructor;
        $this->genericTemplateProvider = $this->templateConstructor->getMappings($this->templateContract->getName());
    }

    public function getTemplateGroupContainer(): TemplateGroupContainer
    {
        $mappings = $this->genericTemplateProvider[TemplateConstructor::MAPPINGS];

        /** @var TemplateGroupContainer $templateGroupContainer */
        $templateGroupContainer = pluginApp(TemplateGroupContainer::class);

        if ($mappings[TemplateConstructor::MAPPINGS_BASE]) {
            foreach ($mappings[TemplateConstructor::MAPPINGS_BASE] as $baseKey => $catalogBase) {
                /** @var TemplateGroup $simpleGroup */
                $simpleGroup = pluginApp(TemplateGroup::class,
                    [
                        "identifier" => $baseKey,
                        "label" => ucfirst($catalogBase['label'])
                    ]);

                foreach ($catalogBase['values'] as $base) {
                    $simpleTemplateField = pluginApp(SimpleTemplateField::class, [
                        $base['key'],
                        $base['key'],
                        $base['label'],
                        $base['required'] ?? false,
                        @$base['isLocked'] ?? false,
                        @$base['isArray'] ?? false,
                        [],
                        @$base['default'] ?? []
                    ]);
                    $simpleGroup->addGroupField($simpleTemplateField);
                }
                $templateGroupContainer->addGroup($simpleGroup);
            }
        }
        if ($mappings[TemplateConstructor::MAPPINGS_KEY]) {
            foreach ($mappings[TemplateConstructor::MAPPINGS_KEY] as $byKey => $catalogKey) {
                /** @var TemplateGroup $complexGroup */
                $complexGroup = pluginApp(TemplateGroup::class,
                    [
                        "identifier" => $byKey,
                        "label" => ucfirst($catalogKey['label'])
                    ]);

                /** @var GenericComplexFieldProvider $genericComplexFieldProvider */
                $genericComplexFieldProvider = pluginApp(GenericComplexFieldProvider::class);
                $genericComplexFieldProvider->set($catalogKey['values']);
                /** @var ComplexTemplateField $fields */
                $fields = pluginApp(ComplexTemplateField::class, [
                    $byKey,
                    $byKey,
                    ucfirst($catalogKey['label']),
                    $genericComplexFieldProvider,
                    @$catalogKey['required'] ?? false,
                    @$catalogKey['isLocked'] ?? false,
                    @$catalogKey['isArray'] ?? false,
                    [],
                    @$catalogKey['default'] ?? []
                ]);
                $complexGroup->addGroupField($fields);
                $templateGroupContainer->addGroup($complexGroup);
            }
        }

        return $templateGroupContainer;
    }

    public function getFilterContainer(): CatalogFilterBuilderContainer
    {
        /** @var CatalogFilterBuilderContainer $container */
        $container = pluginApp(CatalogFilterBuilderContainer::class);
        foreach ($this->genericTemplateProvider[TemplateConstructor::FILTERS] as $filter) {
            switch ($filter['name']) {
                case CatalogueFiltersHelper::VARIATION_ACTIvE:
                    /** @var VariationFilterBuilderFactory $filterBuilderFactory */
                    $filterBuilderFactory = pluginApp(VariationFilterBuilderFactory::class);
                    $variationIsActiveFilter = $filterBuilderFactory->variationIsActive();
                    $variationIsActiveFilter->setShouldBeActive($filter['params']['active']);
                    $container->addFilterBuilder($variationIsActiveFilter);
                    break;
                case CatalogueFiltersHelper::VARIATION_IS_VISIBLE_FOR_MARKETPLACE:
                    /** @var VariationFilterBuilderFactory $filterBuilderFactory */
                    $filterBuilderFactory = pluginApp(VariationFilterBuilderFactory::class);
                    $variationIsVisibleForAtLeastOneMarketFilter = $filterBuilderFactory->variationIsVisibleForAtLeastOneMarket();
                    $variationIsVisibleForAtLeastOneMarketFilter->setMarketIds((float)$filter['params']['marketId']);
                    $container->addFilterBuilder($variationIsVisibleForAtLeastOneMarketFilter);
                    break;
                case CatalogueFiltersHelper::VARIATION_PROPERTY_HAS_SELECTION:
                    /** @var VariationFilterBuilderFactory $filterBuilderFactory */
                    $filterBuilderFactory = pluginApp(VariationFilterBuilderFactory::class);
                    $variationHasAtLeastOnePropertySelectionFilter = $filterBuilderFactory->variationHasAtLeastOnePropertySelection();
                    $variationHasAtLeastOnePropertySelectionFilter->setPropertySelectionIds($filter['params']['propertySelectionId']);
                    $container->addFilterBuilder($variationHasAtLeastOnePropertySelectionFilter);
                    break;
            }
        }

        return $container;
    }

    public function getCustomFilterContainer(): CatalogFilterBuilderContainer
    {
        /** @var CatalogFilterBuilderContainer $container */
        return pluginApp(CatalogFilterBuilderContainer::class);
    }

    public function isPreviewable(): bool
    {
        return true;
    }

    public function getPostMutator(): CatalogMutatorContract
    {
        return pluginApp(GenericMutator::class);
    }

    public function getResultConverterContainer(): ResultConverterContainer
    {
        /** @var ResultConverterContainer $container */
        $container = pluginApp(ResultConverterContainer::class);
        /** @var CSVResultConverter $csvConverter */
        $csvConverter = pluginApp(CSVResultConverter::class);
        $container->addResultConverter($csvConverter);

        return $container;
    }
}

