<?php

namespace RakutenFrance\Catalogue\Converters;

use Plenty\Modules\Catalog\Services\Collections\CatalogLazyCollection;
use Plenty\Modules\Catalog\Services\Converter\ResultConverters\BaseResultConverter;
use Plenty\Modules\Catalog\Services\FileHandlers\ResourceHandler;

class LazyConverter extends BaseResultConverter
{
    const CHUNK_SIZE = 50;

    public function getLabel(): string
    {
        return 'generic';
    }

    public function getKey(): string
    {
        return 'GENERIC';
    }

    protected function convertToMarketplace(CatalogLazyCollection $collection)
    {
    }
    protected function convertToDownload(CatalogLazyCollection $collection, ResourceHandler $resourceHandler)
    {
    }
}
