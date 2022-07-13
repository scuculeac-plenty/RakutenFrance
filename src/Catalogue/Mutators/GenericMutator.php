<?php
namespace RakutenFrance\Catalogue\Mutators;

use Plenty\Modules\Catalog\Contracts\CatalogMutatorContract;

class GenericMutator implements CatalogMutatorContract
{
    public function mutate($item)
    {
        return $item;
    }
}
