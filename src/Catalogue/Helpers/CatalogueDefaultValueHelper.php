<?php

namespace RakutenFrance\Catalogue\Helpers;

/**
 * Class CatalogueDefaultValueHelper
 *
 * @package Catalogue\Helpers
 */
class CatalogueDefaultValueHelper
{
    /**
     * @var
     */
    private $sources = [];

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationId(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-id",
            "type" => "variation",
            "key" => "id",
            "id" => null
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationSkus(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "sku-sku",
            "type" => "sku",
            "key" => "sku",
            "id" => null
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationActive(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-isActive",
            "type" => "variation",
            "key" => "isActive",
            "id" => null
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationNumber(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-number",
            "type" => "variation",
            "key" => "number",
            "id" => null,

        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationPurchasePrice(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-purchasePrice",
            "type" => "variation",
            "key" => "purchasePrice",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationIsVisibleIfNetStockIsPositive(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-isVisibleIfNetStockIsPositive",
            "type" => "variation",
            "key" => "isVisibleIfNetStockIsPositive",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationMinimumOrderQuantity(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-minimumOrderQuantity",
            "type" => "variation",
            "key" => "minimumOrderQuantity",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationMaximumOrderQuantity(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-maximumOrderQuantity",
            "type" => "variation",
            "key" => "maximumOrderQuantity",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationIntervalOrderQuantity(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-intervalOrderQuantity",
            "type" => "variation",
            "key" => "intervalOrderQuantity",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationReleasedAt(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-releasedAt",
            "type" => "variation",
            "key" => "releasedAt",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationModel(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-model",
            "type" => "variation",
            "key" => "model",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationWidthMM(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-widthMM",
            "type" => "variation",
            "key" => "widthMM",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationHeightMM(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-heightMM",
            "type" => "variation",
            "key" => "heightMM",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationLengthMM(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-lengthMM",
            "type" => "variation",
            "key" => "lengthMM",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationWeightNetG(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-weightNetG",
            "type" => "variation",
            "key" => "weightNetG",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param      $value
     * @param bool $isCombined
     *
     * @return $this
     */
    public function ownValue($value, bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "type" => "own-value",
            "value" => $value,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationUnitsContained(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-unitsContained",
            "type" => "variation",
            "key" => "unitsContained",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationContentValue(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-amount",
            "type" => "unit",
            "key" => "content",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationPackingUnit(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-packingUnits",
            "type" => "variation",
            "key" => "packingUnits",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool $isCombined
     *
     * @return $this
     */
    public function variationUnit(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "variation-unit",
            "type" => "unit",
            "key" => "unitOfMeasurement",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param bool   $isCombined
     *
     * @return $this
     */
    public function itemId(bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "item-id",
            "type" => "item",
            "key" => "id",
            "id" => null,
        ];

        return $this;
    }

    /**
     * @param string $lang
     * @param bool   $isCombined
     *
     * @return $this
     */
    public function itemTextName1(string $lang = 'de', bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "itemText-name1",
            "type" => "text",
            "key" => "name1",
            "id" => null,
            "lang" => $lang
        ];

        return $this;
    }

    /**
     * @param string $lang
     * @param bool   $isCombined
     *
     * @return $this
     */
    public function itemTextName2(string $lang = 'de', bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "itemText-name2",
            "type" => "text",
            "key" => "name2",
            "id" => null,
            "lang" => $lang
        ];

        return $this;
    }

    /**
     * @param string $lang
     * @param bool   $isCombined
     *
     * @return $this
     */
    public function itemTextPreview(string $lang = 'de', bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "itemText-shortDescription",
            "type" => "text",
            "key" => "shortDescription",
            "id" => null,
            "lang" => $lang
        ];

        return $this;
    }

    /**
     * @param string $lang
     * @param bool   $isCombined
     *
     * @return $this
     */
    public function itemTextMetaKeywords(string $lang = 'de', bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "itemText-metaKeywords",
            "type" => "text",
            "key" => "keywords",
            "id" => null,
            "lang" => $lang
        ];

        return $this;
    }

    /**
     * @param string $lang
     * @param bool   $isCombined
     *
     * @return $this
     */
    public function itemTextMetaDescription(string $lang = 'de', bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "itemText-metaDescription",
            "type" => "text",
            "key" => "metaDescription",
            "id" => null,
            "lang" => $lang
        ];

        return $this;
    }

    /**
     * @param string $lang
     * @param bool   $isCombined
     *
     * @return $this
     */
    public function itemTextDescription(string $lang = 'de', bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "itemText-description",
            "type" => "text",
            "key" => "description",
            "id" => null,
            "lang" => $lang
        ];

        return $this;
    }

    /**
     * @param string $lang
     * @param bool   $isCombined
     *
     * @return $this
     */
    public function itemTextTechnicalData(string $lang = 'de', bool $isCombined = false): CatalogueDefaultValueHelper
    {
        $this->sources[] = [
            "isCombined" => $isCombined,
            "value" => null,
            "fieldId" => "itemText-technicalData",
            "type" => "text",
            "key" => "technicalData",
            "id" => null,
            "lang" => $lang
        ];

        return $this;
    }

    /**
     * Get sources and clears
     *
     * @return array
     */
    public function get(): array
    {
        $sources = $this->sources;
        $this->sources = [];
        return $sources;
    }
}
