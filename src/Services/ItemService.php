<?php

namespace RakutenFrance\Services;

use Exception;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use RakutenFrance\Builders\ItemsBuilder;
use Plenty\Modules\Order\Models\OrderItemType;
use RakutenFrance\Configuration\PluginConfiguration;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Item\VariationSku\Contracts\VariationSkuRepositoryContract;

class ItemService
{
    use Loggable;

    private $variationSkuRepository;
    private $variationRepository;

    public function __construct(
        VariationSkuRepositoryContract $variationSkuRepository,
        VariationRepositoryContract $variationRepository
    ) {
        $this->variationSkuRepository = $variationSkuRepository;
        $this->variationRepository = $variationRepository;
    }

    public function newItemOrderLine(
        int $orderItemType,
        array $marketplaceItem,
        int $variationId,
        float $referrerId,
        int $countryId,
        int $variationVatId
    ): array {
        /** @var ItemsBuilder $itemsBuilder */
        $itemsBuilder = pluginApp(ItemsBuilder::class);

        return $itemsBuilder->withType($orderItemType)
            ->withItemInformation($marketplaceItem['headline'])
            ->withVariationId($variationId)
            ->withVatField($variationVatId)
            ->withCountryVatId($countryId)
            ->withAmount($marketplaceItem['advertpricelisted']['currency'], $marketplaceItem['advertpricelisted']['amount'])
            ->withItemProperty(OrderPropertyType::EXTERNAL_ITEM_ID, $marketplaceItem['itemid'])
            ->withReferrer($referrerId)
            ->done();
    }

    public function createItemOrderLines(array $marketplaceItems, float $referrerId, int $countryId): array
    {
        try {
            $itemArray = [];
            $missingItemsArray = [];
            foreach ($marketplaceItems as $marketplaceItem) {
                if (empty($marketplaceItem['sku'])) {
                    $marketplaceItem['sku'] = $marketplaceItem['itemid'];
                }

                $variation = $this->getVariation($marketplaceItem['sku'], $referrerId);
                $itemType = OrderItemType::TYPE_VARIATION;
                $variationId = $variation->id;
                $variationVatId = $variation->vatId ?? 0;

                if (empty($variationId)) {
                    $this->getLogger(__METHOD__)->info(PluginConfiguration::PLUGIN_NAME.'::log.itemSkuMissing', [
                        'SKU' => $marketplaceItem['sku'],
                    ]);

                    $itemType = OrderItemType::TYPE_UNASSIGEND_VARIATION;
                    $variationId = -2;
                    $variationVatId = 0;

                    array_push($missingItemsArray, [
                        'value' => $marketplaceItem['sku'],
                        'text' => 'SKU not found ',
                    ]);
                }

                $generatedItem = $this->newItemOrderLine(
                    $itemType,
                    $marketplaceItem,
                    (int) $variationId,
                    $referrerId,
                    $countryId,
                    $variationVatId
                );
                array_push($itemArray, $generatedItem);
            }

            return [
                'items' => $itemArray,
                'missingItems' => $missingItemsArray,
            ];
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error(PluginConfiguration::PLUGIN_NAME.'::log.exception', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return [];
        }
    }

    public function getVariationIdBySKU(string $sku, float $referrerId)
    {
        try {
            $variation = $this->variationSkuRepository->search([
                'sku' => $sku,
                'marketId' => $referrerId,
            ]);

            if (!empty($variation[0]->variationId)) {
                return $variation[0]->variationId;
            }
        } catch (Exception $e) {
            return;
        }
        return;
    }

    private function getVariation(string $sku, float $referrerId)
    {
        try {
            return $this->variationRepository->findById((int) $sku);
        } catch (\Exception $e) {
            $variationId = $this->getVariationIdBySKU($sku, $referrerId);

            if (!empty($variationId)) {
                return $this->variationRepository->findById($variationId);
            }
        }
    }
}
