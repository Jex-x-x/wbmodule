<?php
namespace Wbs24\Wbapi;

class ProductStocks
{
    protected $main;
    protected $moduleId;
    protected $wrappers;
    protected $Product;

    protected $minimalStocks;
    protected $barcodeAsOfferId;

    public function __construct($objects = [])
    {
        $this->main = $objects['Main'] ?? new Main();
        $this->moduleId = $this->main->getModuleId();
        $this->wrappers = new Wrappers($objects);
        $this->Product = $objects['Product'] ?? new Product($objects);

        $this->minimalStocks = intval($this->wrappers->Option->get($this->moduleId, 'stocks_minimal'));
        $this->barcodeAsOfferId = ($this->wrappers->Option->get($this->moduleId, 'barcodeAsOfferId') == 'Y');

        $this->whereStocksLocated = $this->wrappers->Option->get(
            $this->moduleId, 'stockType'
        );
        $this->productStockPropCode = $this->wrappers->Option->get(
            $this->moduleId,
            'productStockProperty'
        );
        $this->offerStockPropCode = $this->wrappers->Option->get(
            $this->moduleId,
            'offerStockProperty'
        );

        // обнуление остатков у ненайденных в Битрикс товаров
        $this->resetStock = ($this->wrappers->Option->get($this->moduleId, 'resetStock') == 'Y');
    }

    public function getStocks($productsInMarketplace)
    {
        $barcodesToProductsInfo = $this->getBarcodesToProductsInfo($productsInMarketplace);
        //$productIds = $this->getProductIds($barcodesToProductsInfo);
        $productIdsToTypes = $this->getProductIdsToTypes($barcodesToProductsInfo);
        $productIdsToQuantity = $this->getProductIdsToQuantityV2($productIdsToTypes);
        $stocks = $this->getDifferentStocks($productsInMarketplace, $barcodesToProductsInfo, $productIdsToQuantity);

        return $stocks;
    }

    protected function getBarcodesToProductsInfo($productsInMarketplace)
    {
        $barcodesToProductsInfo = [];
        foreach ($productsInMarketplace as $product) {
            $offerId = $this->barcodeAsOfferId ? $product['barcode'] : $product['article'];
            if (!$offerId) continue;

            $productInfo = $this->Product->getProductInfoByOfferId($offerId);
            if ($productInfo) {
                $barcodesToProductsInfo[$product['barcode']] = [
                    'id' => $productInfo['id'],
                    'package_ratio_value' => $productInfo['package_ratio_value'],
                    'product_type' => $productInfo['product_type'],
                ];
            }
        }

        return $barcodesToProductsInfo;
    }

    protected function getProductIds($barcodesToProductsInfo)
    {
        $productIds = [];

        foreach ($barcodesToProductsInfo as $info) {
            $productIds[] = $info['id'];
        }

        return $productIds;
    }

    protected function getProductIdsToTypes($barcodesToProductsInfo)
    {
        $productIdsToTypes = [];

        foreach ($barcodesToProductsInfo as $info) {
            $productIdsToTypes[$info['id']] = $info['product_type'];
        }

        return $productIdsToTypes;
    }

    // получение только доступного кол-ва
    protected function getProductIdsToQuantity($productIds)
    {
        $productIdsToQuantity = [];

        if ($productIds) {
            $res = $this->wrappers->CIBlockElement->GetList(
                [],
                ['ID' => $productIds],
                false,
                false,
                ['ID', 'IBLOCK_ID', 'QUANTITY']
            );
            while ($product = $res->Fetch()) {
                $quantity = intval($product['QUANTITY']);
                if ($quantity < $this->minimalStocks) $quantity = 0;
                $productIdsToQuantity[$product['ID']] = $quantity;
            }
        }

        return $productIdsToQuantity;
    }

    protected function getProductIdsToQuantityV2($productIdsToTypes)
    {
        switch ($this->whereStocksLocated) {
            case '':
            case 'catalog_quantity':
                $productIdsToQuantity = $this->getAvailableStocks(
                    array_keys($productIdsToTypes)
                );
                break;
            case 'stocks_from_property':
                $productIdsToQuantity = $this->getPropertyStocks(
                    $productIdsToTypes
                );
                break;
            default:
                $warehouseId = (int) $this->whereStocksLocated;
                $productIdsToQuantity = $this->getWarehouseStocks(
                    array_keys($productIdsToTypes),
                    $warehouseId
                );
                break;
        }

        return $productIdsToQuantity;
    }

    protected function getAvailableStocks($productIds)
    {
        return $this->getProductIdsToQuantity($productIds);
    }

    protected function getPropertyStocks($productIdsToTypes)
    {
        $productIds = [];
        $offerIds = [];

        foreach ($productIdsToTypes as $productId => $type) {
            switch ($type) {
                case 1:
                case 2:
                case 3:
                    $productIds[] = $productId;
                    break;
                case 4:
                    $offerIds[] = $productId;
                    break;
            }
        }

        // простые товары
        $productIdsToQuantity = [];
        if ($productIds) {
            $productIdsToQuantity = $this->getProductQuantityFromProps($this->productStockPropCode, $productIds);
        }

        // торговые предложения
        $offerIdsToQuantity = [];
        if ($offerIds) {
            $offerIdsToQuantity = $this->getProductQuantityFromProps($this->offerStockPropCode, $offerIds);
        }

        return $productIdsToQuantity + $offerIdsToQuantity;
    }

    protected function getProductQuantityFromProps($propertyCode, $ids)
    {
        if (
            !$propertyCode
            || $propertyCode == 'nothing'
        ) return [];

        $productIdsToQuantity = [];

        $propertyCode = strtoupper($propertyCode);
        $select = [
            'ID',
            'IBLOCK_ID',
            'PROPERTY_'.strtoupper($propertyCode),
        ];
        $filter = ['ID' => $ids];
        $source = 'stocks';
        $res = $this->Product->get($select, $filter, $source);
        while ($product = $res->Fetch()) {
            $quantity = intval($product['PROPERTY_'.$propertyCode.'_VALUE']) ?? 0;
            if ($quantity < $this->minimalStocks) $quantity = 0;
            $productIdsToQuantity[$product['ID']] = $quantity;
        }

        return $productIdsToQuantity;
    }

    protected function getWarehouseStocks($productIds, $warehouseId)
    {
        $productIdsToQuantity = [];

        $select = ['ID', 'IBLOCK_ID', 'STORE_AMOUNT_'.$warehouseId];
        $filter = ['ID' => $productIds];
        $source = 'stocks';
        $res = $this->Product->get($select, $filter, $source);

        while ($product = $res->Fetch()) {
            $quantity = intval($product['STORE_AMOUNT_'.$warehouseId]) ?? 0;
            if ($quantity < $this->minimalStocks) $quantity = 0;
            $productIdsToQuantity[$product['ID']] = $quantity;
        }

        return $productIdsToQuantity;
    }

    protected function getDifferentStocks($productsInMarketplace, $barcodesToProductsInfo, $productIdsToQuantity)
    {
        $differentStocks = [];

        foreach ($productsInMarketplace as $product) {
            $quantityInMarketplace = $product['stock'] ?? false;
            $barcode = $product['barcode'] ?? false;
            $productId = $barcodesToProductsInfo[$barcode]['id'] ?? false;
            $ratio = $barcodesToProductsInfo[$barcode]['package_ratio_value'] ?? 1;
            $quantityInCatalog = $productIdsToQuantity[$productId] ?? false;
            $warehouseId = $product['warehouseId'] ?? false;

            if (
                !$productId
                && $barcode
                && $warehouseId
                && $this->resetStock
            ) {
                $differentStocks[] = [
                    'barcode' => $barcode,
                    'stock' => 0,
                    'warehouseId' => $warehouseId,
                ];
            }

            if (
                $quantityInMarketplace === false
                || $barcode === false
                || $quantityInCatalog === false
                || $warehouseId === false
            ) continue;

            $quantityInCatalogWithRatio = floor($quantityInCatalog / $ratio); // остаток с учетом коэффициента упаковки
            if ($quantityInMarketplace !== $quantityInCatalogWithRatio) {
                $differentStocks[] = [
                    'barcode' => $barcode,
                    'stock' => intval($quantityInCatalogWithRatio), // новый остаток из каталога сайта
                    'warehouseId' => $warehouseId,
                ];
            }
        }

        return $differentStocks;
    }
}
