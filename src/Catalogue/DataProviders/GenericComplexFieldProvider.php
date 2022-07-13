<?php

namespace RakutenFrance\Catalogue\DataProviders;

use Exception;
use Illuminate\Support\Collection;
use Plenty\Modules\Catalog\Containers\CatalogMappingValueContainer;
use Plenty\Modules\Catalog\Contracts\CatalogMappingValueProviderContract;
use Plenty\Modules\Catalog\Models\CatalogMappingValue;

class GenericComplexFieldProvider implements CatalogMappingValueProviderContract
{
    protected $fields;

    /**
     * @param array $fields
     */
    public function set(array $fields): void
    {
        /** @phpstan-ignore-next-line */
        $this->fields = Collection::make($fields)->mapWithKeys(function ($item) {
            return [
                $item['value'] => [
                    'label' => $item['label'],
                    'value' => $item['value']
                ]
            ];
        })->all();
    }

    /**
     * @throws Exception
     */
    public function getValueById(string $id): CatalogMappingValue
    {
        if (!isset($this->fields[$id])) {
            throw new Exception('Field does not exist.', 404);
        }

        return pluginApp(CatalogMappingValue::class, [
            $this->fields[$id]['value'],
            $this->fields[$id]['label'],
            $this->fields[$id]['value']
        ]);
    }

    public function getValuesByParentId(string $parentId = null): CatalogMappingValueContainer
    {
        $mappingValueContainer = pluginApp(CatalogMappingValueContainer::class);

        foreach ($this->fields as $id => $field) {
            if ($field['parentId'] != $parentId) {
                continue;
            }

            $mappingValue = pluginApp(CatalogMappingValue::class, [
                $field['value'],
                $field['label'],
                $field['value'],
            ]);

            $mappingValueContainer->addMappingValue($mappingValue);
        }

        return $mappingValueContainer;
    }

    /**
     * @param array $params
     *
     * @return CatalogMappingValueContainer
     */
    public function getValues(array $params = []): CatalogMappingValueContainer
    {
        return pluginApp(CatalogMappingValueContainer::class);
    }
}
