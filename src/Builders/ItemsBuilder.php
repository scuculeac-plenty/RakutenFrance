<?php

namespace RakutenFrance\Builders;

use Plenty\Plugin\Log\Loggable;

class ItemsBuilder
{
    use Loggable;

    private $orderItem;

    public function __construct()
    {
        $this->orderItem = [];
    }

    /**
     * Add order item type.
     *
     * @param int $type
     *
     * @return ItemsBuilder
     */
    public function withType(int $type): ItemsBuilder
    {
        $this->orderItem['typeId'] = $type;

        return $this;
    }

    /**
     * Add order item name.
     *
     * @param string $itemName
     *
     * @return ItemsBuilder
     */
    public function withItemInformation(string $itemName): ItemsBuilder
    {
        $this->orderItem['quantity'] = 1; // Rakuten.fr don't have quantity on their webservice response and it's allways 1
        $this->orderItem['orderItemName'] = $itemName;

        return $this;
    }

    /**
     * Add order item country VAT ID.
     *
     * @param int $countryVatId
     *
     * @return ItemsBuilder
     */
    public function withCountryVatId(int $countryVatId): ItemsBuilder
    {
        $this->orderItem['countryVatId'] = $countryVatId;

        return $this;
    }

    /**
     * Add order item price and currency.
     *
     * @param string $currency
     * @param float  $amount
     *
     * @return ItemsBuilder
     */
    public function withAmount(string $currency, float $amount): ItemsBuilder
    {
        if ($this->orderItem['amounts'] === null) {
            $this->orderItem['amounts'] = [];
        }

        $orderAmount = [
            'currency' => $currency,
            'priceOriginalGross' => $amount,
        ];

        array_push($this->orderItem['amounts'], $orderAmount);

        return $this;
    }

    /**
     * Add order item property.
     *
     * @param int    $type
     * @param string $value
     *
     * @return ItemsBuilder
     */
    public function withItemProperty(int $type, string $value): ItemsBuilder
    {
        if ($this->orderItem['properties'] === null) {
            $this->orderItem['properties'] = [];
        }

        $option = [
            'typeId' => $type,
            'value' => $value,
        ];

        array_push($this->orderItem['properties'], $option);

        return $this;
    }

    /**
     * Add order item referrer ID.
     *
     * @param float $referrerId
     *
     * @return ItemsBuilder
     */
    public function withReferrer(float $referrerId): ItemsBuilder
    {
        $this->orderItem['referrerId'] = $referrerId;

        return $this;
    }

    /**
     * Add order item variation ID.
     *
     * @param int $variationId
     *
     * @return ItemsBuilder
     */
    public function withVariationId(int $variationId): ItemsBuilder
    {
        $this->orderItem['itemVariationId'] = $variationId;

        return $this;
    }

    /**
     * Add order item variation VAT ID.
     *
     * @param int $variationVatId
     *
     * @return ItemsBuilder
     */
    public function withVatField(int $variationVatId): ItemsBuilder
    {
        $this->orderItem['vatField'] = $variationVatId;

        return $this;
    }

    public function withVatRate(int $vatRate): ItemsBuilder
    {
        $this->orderItem['vatRate'] = $vatRate;

        return $this;
    }

    /**
     * Return generated order item array.
     *
     * @return array
     */
    public function done(): array
    {
        $orderItem = $this->orderItem;
        $this->orderItem = [];

        return $orderItem;
    }
}
