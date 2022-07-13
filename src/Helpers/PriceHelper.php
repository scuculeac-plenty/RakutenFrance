<?php

namespace RakutenFrance\Helpers;

use Plenty\Modules\Item\SalesPrice\Contracts\SalesPriceSearchRepositoryContract;
use Plenty\Modules\Item\SalesPrice\Models\SalesPriceSearchRequest;
use Plenty\Modules\Item\SalesPrice\Models\SalesPriceSearchResponse;

/**
 * Class PriceHelper
 * @package RakutenFrance\Helpers
 */
class PriceHelper
{
    /**
     * @var SalesPriceSearchRepositoryContract
     */
    private $salesPriceSearchRepositoryContract;

    /**
     * PriceHelper constructor.
     * @param SalesPriceSearchRepositoryContract $salesPriceSearchRepositoryContract
     */
    public function __construct(
        SalesPriceSearchRepositoryContract $salesPriceSearchRepositoryContract
    ) {
        $this->salesPriceSearchRepositoryContract = $salesPriceSearchRepositoryContract;
    }

    /**
     * Gets variation price
     *
     * @param $variationId
     * @param $settings
     *
     * @return SalesPriceSearchResponse
     */
    public function getPrice($variationId, $settings)
    {
        /**
         * SalesPriceSearchRequest $salesPriceSearchRequest
         */
        $salesPriceSearchRequest = pluginApp(SalesPriceSearchRequest::class);
        if ($salesPriceSearchRequest instanceof SalesPriceSearchRequest) {
            $salesPriceSearchRequest->variationId = $variationId;
            $salesPriceSearchRequest->plentyId = $settings[PluginSettingsHelper::APPLICATION_ID];
            $salesPriceSearchRequest->referrerId = $settings[PluginSettingsHelper::REFERRER_ID];
            //$salesPriceSearchRequest->type = 'default';
        }
        return $this->salesPriceSearchRepositoryContract->search($salesPriceSearchRequest);
    }
}
