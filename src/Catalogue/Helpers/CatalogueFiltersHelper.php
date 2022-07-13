<?php

namespace RakutenFrance\Catalogue\Helpers;

/**
 * Class CatalogueFiltersHelper
 * @package RakutenFrance\Catalogue\Helpers
 */
class CatalogueFiltersHelper
{
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';
    const STATUS_ERROR = 'ERROR';
    const STATUS_SENT = 'SENT';

    const ITEM_HAS_IDS = 'item.hasIds';
    const VARIATION_ACTIVE = 'variationBase.isActive';
    const VARIATION_IS_VISIBLE_FOR_MARKETPLACE = 'variationMarket.isVisibleForMarket';
    const VARIATION_IS_VISIBLE_FOR_ATLEAST_ONE_MARKETPLACE = 'variationMarket.isVisibleForAtLeastOneMarket';
    const VARIATION_PROPERTY_HAS_SELECTION = 'variationProperty.hasSelection';
    const VARIATION_HAS_SKU = 'variationSku.hasSku';

    private $filters = [];

    /**
     * Filters settings
     *
     * @return array
     */
    public function done(): array
    {
        return $this->filters;
    }

    /**
     * Filters for only active variations
     *
     * @return CatalogueFiltersHelper
     */
    public function setVariationIsActive(): CatalogueFiltersHelper
    {
        $this->filters[] = [
            'name' => self::VARIATION_ACTIVE,
            'params' => [
                'active' => true
            ]
        ];

        return $this;
    }

    /**
     * Filters specific item ids
     *
     * @param array $itemIds
     *
     * @return CatalogueFiltersHelper
     */
    public function setItemHasIds(array $itemIds): CatalogueFiltersHelper
    {
        $this->filters[] = [
            'name' => self::ITEM_HAS_IDS,
            'params' => [
                'itemIds' => $itemIds
            ]
        ];

        return $this;
    }

    /**
     * Only variations that are linked to the specified marketplace
     *
     * @param int $marketId
     *
     * @return CatalogueFiltersHelper
     */
    public function setVariationMarketIsVisibleForMarket(int $marketId): CatalogueFiltersHelper
    {
        $this->filters[] = [
            'name' => self::VARIATION_IS_VISIBLE_FOR_MARKETPLACE,
            'params' => [
                'marketId' => $marketId
            ]
        ];

        return $this;
    }

    /**
     * Only variations that are linked to at least on of the specified marketplaces
     *
     * @param array $marketIds
     *
     * @return CatalogueFiltersHelper
     */
    public function setVariationMarketIsVisibleForAtLeastOneMarket(array $marketIds): CatalogueFiltersHelper
    {
        $this->filters[] = [
            'name' => self::VARIATION_IS_VISIBLE_FOR_ATLEAST_ONE_MARKETPLACE,
            'params' => [
                'marketIds' => $marketIds
            ]
        ];

        return $this;
    }

    /**
     * Only variations that are linked to a specific property selection
     *
     * @param int $propertySelectionId
     *
     * @return CatalogueFiltersHelper
     */
    public function setVariationPropertyHasSelection(int $propertySelectionId): CatalogueFiltersHelper
    {
        $this->filters[] = [
            'name' => self::VARIATION_PROPERTY_HAS_SELECTION,
            'params' => [
                'propertySelectionId' => $propertySelectionId
            ]
        ];

        return $this;
    }

    /**
     * Only variations that are linked to an SKU that matches the provided data
     *
     * @param int $referrerId
     * @param int $accountId
     * @param string $status
     *
     * @return CatalogueFiltersHelper
     */
    public function setVariationSkuHasSku(int $referrerId, int $accountId, string $status): CatalogueFiltersHelper
    {
        $this->filters[] = [
            'name' => self::VARIATION_HAS_SKU,
            'params' => [
                'referrerId' => $referrerId,
                'accountId' => $accountId,
                'status' => $status
            ]
        ];

        return $this;
    }
}
