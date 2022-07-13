<?php

namespace RakutenFrance\Catalogue\Helpers;

use Illuminate\Support\Collection;
use Plenty\Modules\Property\V2\Contracts\PropertyGroupRepositoryContract as PropertyGroupRepositoryContractV2;
use Plenty\Modules\Property\V2\Contracts\PropertyRepositoryContract as PropertyRepositoryContractV2;
use Plenty\Modules\Property\V2\Contracts\PropertySelectionRepositoryContract as PropertySelectionRepositoryContractV2;
use Plenty\Modules\Property\V2\Models\Property;
use Plenty\Modules\Property\V2\Models\PropertyGroup;
use Plenty\Modules\Property\V2\Models\PropertyGroupName;
use Plenty\Modules\Property\V2\Models\PropertyName;
use Plenty\Modules\Property\V2\Models\PropertySelection;
use Plenty\Modules\Property\V2\Models\PropertySelectionName;
use Plenty\Modules\Wizard\Contracts\WizardDataRepositoryContract;
use RakutenFrance\Assistant\AssistantWizard;
use RakutenFrance\Configuration\PluginConfiguration;

/**
 * Class CatalogPropertySelectionHelper
 *
 * @package RakutenFrance\Catalogue\Helpers
 */
class CatalogPropertySelectionHelper
{
    const CATALOG_PROPERTY = 'propertyId';

    /**
     * @var WizardDataRepositoryContract
     */
    private $wizardDataRepositoryContract;
    /**
     * @var PropertyGroupRepositoryContractV2
     */
    private $propertyGroupRepositoryContractV2;
    /**
     * @var PropertyRepositoryContractV2
     */
    private $propertyRepositoryContractV2;
    /**
     * @var PropertySelectionRepositoryContractV2
     */
    private $propertySelectionRepositoryContractV2;

    /**
     * CatalogPropertySelectionHelper constructor.
     *
     * @param WizardDataRepositoryContract          $wizardDataRepositoryContract
     * @param PropertyGroupRepositoryContractV2     $propertyGroupRepositoryContractV2
     * @param PropertyRepositoryContractV2          $propertyRepositoryContractV2
     * @param PropertySelectionRepositoryContractV2 $propertySelectionRepositoryContractV2
     */
    public function __construct(
        WizardDataRepositoryContract $wizardDataRepositoryContract,
        PropertyGroupRepositoryContractV2 $propertyGroupRepositoryContractV2,
        PropertyRepositoryContractV2 $propertyRepositoryContractV2,
        PropertySelectionRepositoryContractV2 $propertySelectionRepositoryContractV2
    ) {
        $this->wizardDataRepositoryContract = $wizardDataRepositoryContract;
        $this->propertyGroupRepositoryContractV2 = $propertyGroupRepositoryContractV2;
        $this->propertyRepositoryContractV2 = $propertyRepositoryContractV2;
        $this->propertySelectionRepositoryContractV2 = $propertySelectionRepositoryContractV2;
    }

    /**
     * Handle property creation by a specific value
     *
     * @param mixed       $catalogValue
     * @param string|null $realCatalogName
     *
     * @return int
     */
    public function handleProperty($catalogValue, string $realCatalogName = null): int
    {
        $propertyId = $this->checkProperty();
        return $this->checkPropertySelectionValues($propertyId, $catalogValue, $realCatalogName ?? $catalogValue);
    }

    /**
     * Check if property is saved at the assistant
     *
     * @return int
     */
    private function checkProperty(): int
    {
        $wizard = $this->wizardDataRepositoryContract->findByWizardKey(AssistantWizard::ASSISTANT_KEY)->data->catalog;
        if (!$wizard[CatalogPropertySelectionHelper::CATALOG_PROPERTY]) {
            return $this->createProperty();
        }

        return (int)$wizard[CatalogPropertySelectionHelper::CATALOG_PROPERTY];
    }

    /**
     * Create property
     *
     * @return int
     */
    private function createProperty(): int
    {
        $propertyNames = [];
        foreach (['en', 'fr', 'de'] as $lang) {
            /** @var PropertyName $propertyName */
            $propertyName = pluginApp(PropertyName::class);
            $propertyName->lang = $lang;
            $propertyName->name = 'Catalog' . ': ' . PluginConfiguration::PLUGIN_NAME;
            $propertyName->description = 'Catalog' . ': ' . PluginConfiguration::PLUGIN_NAME;
            $propertyNames[] = $propertyName->toArray();
        }

        $propertyGroupNames = [];
        foreach (['en', 'fr', 'de'] as $lang) {
            /** @var PropertyGroupName $propertyGroupName */
            $propertyGroupName = pluginApp(PropertyGroupName::class);
            $propertyGroupName->lang = $lang;
            $propertyGroupName->name = 'Catalog group' . ': ' . PluginConfiguration::PLUGIN_NAME;
            ;
            $propertyGroupName->description = 'Catalog group' . ': ' . PluginConfiguration::PLUGIN_NAME;
            $propertyGroupNames[] = $propertyGroupName->toArray();
        }
        /** @var PropertyGroup $propertyGroup */
        $propertyGroup = pluginApp(PropertyGroup::class);
        $propertyGroup->position = 1;
        $propertyGroup->names = $propertyGroupNames;
        $propertyGroup = $this->propertyGroupRepositoryContractV2->create($propertyGroup->toArray());

        /** @var Property $property */
        $property = pluginApp(Property::class);
        $property->cast = Property::CAST_SELECTION;
        $property->type = 'item';
        $property->groups = [$propertyGroup->toArray()];
        $property->position = 1;
        $property->names = $propertyNames;

        $property = $this->propertyRepositoryContractV2->create($property->toArray());

        $this->wizardDataRepositoryContract->finalize(
            AssistantWizard::ASSISTANT_KEY,
            'catalog',
            ['propertyId' => $property->id]
        );

        return $property->id;
    }

    /**
     * Check property selection values
     *
     * @param $propertyId
     * @param $catalogValue
     * @param $realCatalogName
     *
     * @return int
     */
    private function checkPropertySelectionValues($propertyId, $catalogValue, $realCatalogName): int
    {
        $page = 1;
        do {
            $selections = [];
            /** @var PropertySelection[] $searchByPropertyId */
            $searchByPropertyId = $this->propertySelectionRepositoryContractV2->searchByPropertyId(
                $propertyId,
                ['names'],
                1000,
                $page++
            );
            foreach ($searchByPropertyId as $propertySelection) {
                /** @phpstan-ignore-next-line */
                $selections[Collection::make($propertySelection->names)->first()->name] = $propertySelection->id;
            }

            if (array_key_exists($catalogValue, $selections)) {
                return $selections[$catalogValue];
            }
        } while ($searchByPropertyId);

        return $this->createPropertySelectionValues($propertyId, $catalogValue, $realCatalogName);
    }

    /**
     * Create property selection value
     *
     * @param $propertyId
     * @param $name
     * @param $realCatalogName
     *
     * @return int
     */
    private function createPropertySelectionValues($propertyId, $name, $realCatalogName): int
    {
        $names = [];
        /** @var PropertySelectionName $propertyName */
        $propertyName = pluginApp(PropertySelectionName::class);
        $propertyName->name = $name;
        $propertyName->lang = 'en';
        $propertyName->description = "Catalog alias: $realCatalogName";
        $names[] = $propertyName->toArray();

        /** @var PropertySelectionName $propertyName */
        $propertyName = pluginApp(PropertySelectionName::class);
        $propertyName->name = $name;
        $propertyName->lang = 'de';
        $propertyName->description = "Catalog alias: $realCatalogName";
        $names[] = $propertyName->toArray();

        /** @var PropertySelectionName $propertyName */
        $propertyName = pluginApp(PropertySelectionName::class);
        $propertyName->name = $name;
        $propertyName->lang = 'fr';
        $propertyName->description = "Catalog alias: $realCatalogName";
        $names[] = $propertyName->toArray();

        /** @var PropertySelection $propertySelection */
        $propertySelection = pluginApp(PropertySelection::class);

        $propertySelection->propertyId = $propertyId;
        $propertySelection->position = 0;
        $propertySelection->names = $names;
        $propertySelectionValue = $this->propertySelectionRepositoryContractV2->create($propertySelection->toArray());

        return $propertySelectionValue->id;
    }
}
