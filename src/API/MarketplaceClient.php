<?php

namespace RakutenFrance\API;

use Exception;
use Plenty\Plugin\Log\Loggable;

class MarketplaceClient extends Api
{
    use Loggable;

    //Actions
    const CATEGORY_MAP = 'categorymap';
    const PRODUCT_TYPES = 'producttypes';
    const GET_SHIPPING_INFORMATION = 'getshippinginformation';
    const GET_BILLING_INFORMATION = 'getbillinginformation';
    const GET_CURRENT_SALES = 'getcurrentsales';
    const GET_ITEM_INFOS = 'getiteminfos';
    const GET_NEW_SALES = 'getnewsales';
    const GENERIC_IMPORT_REPORT = 'genericimportreport';
    const IMPORT_REPORT = 'importreport';
    const ACCEPT_SALE = 'acceptsale';
    const REFUSE_SALE = 'refusesale';
    const CANCEL_ITEM = 'cancelitem';
    const PRODUCT_TYPE_TEMPLATE = 'producttypetemplate';
    const EXPORT = 'export';

    //Endpoints
    const CATEGORY_MAP_WS = 'categorymap_ws';
    const STOCK_WS = 'stock_ws';
    const SALES_WS = 'sales_ws';

    /**
     * Get categories from API.
     *
     * @return array
     * @throws Exception
     *
     */
    public function getCategories()
    {
        return $this->call(self::CATEGORY_MAP, self::CATEGORY_MAP_WS, self::MARKETPLACE_CATEGORIES_VERSION);
    }

    /**
     * Get aliases from API.
     *
     * @return array
     * @throws Exception
     *
     */
    public function getAliases()
    {
        return $this->call(self::PRODUCT_TYPES, self::STOCK_WS, self::MARKETPLACE_ALIAS_VERSION);
    }

    /**
     * Get exports.
     *
     * @return array
     * @throws Exception
     */
    public function getExports($nextToken = '')
    {
        $additionalInfo = [];
        if (!empty($nextToken)) {
            $additionalInfo = ['nexttoken' => $nextToken];
        }

        $response = $this->call(
            self::EXPORT,
            self::STOCK_WS,
            self::EXPORT_VERSION,
            $additionalInfo
        );

        return [$response['nexttoken'], $response['advertlist']];
    }

    /**
     * Get template by alias type.
     *
     * @param string $aliasType
     *
     * @return array
     * @throws Exception
     */
    public function getProductTypeTemplate($aliasType)
    {
        return $this->call(
            self::PRODUCT_TYPE_TEMPLATE,
            self::STOCK_WS,
            self::MARKETPLACE_ALIAS_PROPERTIES_VERSION,
            ['alias' => $aliasType, 'scope' => 'VALUES']
        );
    }

    /**
     * Get shipping information from API.
     *
     * @param string $purchaseId
     *
     * @return array
     * @throws Exception
     *
     */
    public function getShippingInfo($purchaseId)
    {
        return $this->call(
            self::GET_SHIPPING_INFORMATION,
            self::SALES_WS,
            self::MARKETPLACE_SHIPPING_VERSION_2,
            ['purchaseid' => $purchaseId]
        );
    }

    /**
     * Get billing information from API.
     *
     * @param string $purchaseId
     *
     * @return array
     * @throws Exception
     *
     */
    public function getBillingInfo($purchaseId)
    {
        return $this->call(
            self::GET_BILLING_INFORMATION,
            self::SALES_WS,
            self::MARKETPLACE_BILLING_INFO_VERSION,
            ['purchaseid' => $purchaseId]
        );
    }

    /**
     * Get current sales from API.
     *
     * @return array
     * @throws Exception
     *
     */
    public function getCurrentSales()
    {
        $nextToken = '';
        $sales = [];
        $additionalInfo = [];
        do {
            if (!empty($nextToken)) {
                $additionalInfo = ['nexttoken' => $nextToken];
            }

            $response = $this->call(
                self::GET_CURRENT_SALES,
                self::SALES_WS,
                self::MARKETPLACE_ORDERS_VERSION,
                $additionalInfo
            );
            $nextToken = $response['nexttoken'];
            array_push($sales, $response['sales']['sale']);
        } while (!empty($nextToken));

        return $sales;
    }

    /**
     * Get item information from API.
     *
     * @param int $itemId
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getItemInfo(int $itemId)
    {
        return $this->call(
            self::GET_ITEM_INFOS,
            self::SALES_WS,
            self::MARKETPLACE_BILLING_INFO_VERSION,
            ['itemid' => $itemId]
        );
    }

    /**
     * Get new orders from API.
     *
     * @return array
     * @throws Exception
     *
     */
    public function getOrders(): array
    {
        $response = $this->call(self::GET_NEW_SALES, self::SALES_WS, self::MARKETPLACE_ORDERS_VERSION);

        if (empty($response) || empty($response['sales']['sale'])) {
            return [];
        }

        $sales = $response['sales']['sale'];
        if (!is_array($response['sales']['sale'][0])) {
            $sales = [$response['sales']['sale']];
        }

        foreach ($sales as &$order) {
            if (!is_array($order['items']['item'][0])) {
                $order['items']['item'] = [$order['items']['item']];
            }
        }

        return $sales;
    }

    /**
     * Get import report from API.
     *
     * @param int $importId
     *
     * @return array
     * @throws Exception
     *
     */
    public function getReportByImportId(int $importId)
    {
        return $this->call(
            self::IMPORT_REPORT,
            self::STOCK_WS,
            self::IMPORT_REPORT_VERSION,
            ['fileid' => $importId,]
        );
    }

    /**
     * Get generic import report from API.
     *
     * @param int $importId
     *
     * @return array
     * @throws Exception
     *
     */
    public function getGenericByImportId(int $importId)
    {
        $nextToken = '';
        $metadata = [];
        $data = [];
        $additionalInfo = ['fileid' => $importId];
        do {
            if (!empty($nextToken)) {
                $additionalInfo['nexttoken'] = $nextToken;
            }
            $response = $this->call(
                self::GENERIC_IMPORT_REPORT,
                self::STOCK_WS,
                self::IMPORT_REPORT_VERSION,
                $additionalInfo
            );

            $nextToken = @$response['nexttoken'];
            if (empty($metadata)) {
                $metadata = [
                    'filename' => $response['file']['filename'],
                    'status' => $response['file']['status'],
                    'uploaddate' => $response['file']['uploaddate'],
                    'processdate' => $response['file']['processdate'],
                    'totallines' => $response['file']['totallines'],
                    'processedlines' => $response['file']['processedlines'],
                    'errorlines' => $response['file']['errorlines'],
                    'successrate' => $response['file']['successrate'],
                    'imageprocessstatus' => $response['file']['imageprocessstatus'],
                ];
            }
            foreach ($response['product'] as $product) {
                if (@$product['errors']) {
                    $data[] = $product;
                }
            }
        } while (!empty($nextToken));

        return [
            'metaData' => $metadata,
            'data' => $data
        ];
    }

    /**
     * Accept sale.
     *
     * @param int $itemId
     *
     * @return string
     * @throws Exception
     */
    public function acceptItem($itemId, $iso)
    {
        $response = $this->call(
            self::ACCEPT_SALE,
            self::SALES_WS,
            self::MARKETPLACE_ITEMS_VERSION,
            [
                'itemid' => $itemId,
                'shippingfromcountry'=> $iso
            ]
        );

        return $response['status'];
    }

    /**
     * Refuse item.
     *
     * @param int $itemId
     *
     * @return string
     * @throws Exception
     */
    public function refuseItem($itemId, $iso)
    {
        $response = $this->call(
            self::REFUSE_SALE,
            self::SALES_WS,
            self::MARKETPLACE_ITEMS_VERSION,
            [
                'itemid' => $itemId,
                'shippingfromcountry'=> $iso
            ]
        );

        return $response['status'];
    }

    /**
     * Cancel item.
     *
     * @param int $itemId
     * @param string $cancelReason
     *
     * @return array|string
     * @throws Exception
     */
    public function cancelItem($itemId, string $cancelReason)
    {
        $response = $this->call(
            self::CANCEL_ITEM,
            self::SALES_WS,
            self::MARKETPLACE_CANCEL_ITEM_VERSION,
            ['itemid' => $itemId, 'comment' => $cancelReason]
        );

        return $response['status'];
    }
}
