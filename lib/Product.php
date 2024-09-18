<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;

// 14.02.2023
class Product
{
    use Exception; // trait

    protected const OPTION_PRODUCT_OFFER_ID = 'skuPropertyForProducts';
    protected const OPTION_SUB_PRODUCT_OFFER_ID = 'skuPropertyForProductOffers';

    protected $main;
    protected $moduleId;
    protected $wrappers;
    protected $PackageRatio;

    protected $optionProductOfferId;
    protected $optionSubProductOfferId;
    protected $allTradeCatalogs;
    protected $allProperties = [];

    public function __construct($objects = [])
    {
        $this->main = $objects['Main'] ?? new Main();
        $this->moduleId = $this->main->getModuleId();
        $this->wrappers = new Wrappers($objects);
        $this->PackageRatio = $objects['PackageRatio'] ?? new PackageRatio($objects);

        $this->optionProductOfferId = $this->wrappers->Option->get($this->moduleId, self::OPTION_PRODUCT_OFFER_ID);
        $this->optionSubProductOfferId = $this->wrappers->Option->get($this->moduleId, self::OPTION_SUB_PRODUCT_OFFER_ID);
    }

    public function getProductIdByOfferId($offerId)
    {
        $productInfo = $this->getProductInfoByOfferId($offerId);

        return $productInfo['id'] ?: false;
    }

    public function getProductInfoByOfferId($offerId)
    {
        $allTradeCatalogs = $this->getAllTradeCatalogs();

        // поиск по инфоблокам простых товаров
        $productInfo = [];
        foreach ($allTradeCatalogs['product_iblock'] as $tradeCatalog) {
            $productInfo = $this->getDetailedInformationAboutProduct($this->optionProductOfferId, $offerId, $tradeCatalog);
            if ($productInfo) break;
        }

        // поиск по инфоблокам ТП
        if (!$productInfo) {
            foreach ($allTradeCatalogs['offers_iblock'] as $tradeCatalog) {
                $productInfo = $this->getDetailedInformationAboutProductOffers($this->optionSubProductOfferId, $offerId, $tradeCatalog);
                if ($productInfo) break;
            }
        }

        return $productInfo;
    }

    public function get($select, $filter, $source = '')
    {
        $queryResult = $this->wrappers->CIBlockElement->GetList(
            [],
            $filter,
            false,
            false,
            $select
        );
        return $queryResult;
    }

    public function getOfferIdByProductId($productId)
    {
        $select = [
            'ID',
            'IBLOCK_ID',
            'TYPE',
        ];
        $filter = [
            'ID' => $productId,
        ];
        $offerId = false;

        if ($this->optionProductOfferId) $select[] = 'PROPERTY_'.$this->optionProductOfferId;
        if (
            $this->optionSubProductOfferId
            && $this->optionProductOfferId != $this->optionSubProductOfferId
        ) {
            $select[] = 'PROPERTY_'.$this->optionSubProductOfferId;
        }

        $res = $this->get($select, $filter);
        if ($res) {
            $fields = $res->Fetch();
            $type = $fields['TYPE'] ?? false;
            if ($type == 1 || $type == 2) {
                $offerId = $fields['PROPERTY_'.$this->optionProductOfferId.'_VALUE'] ?? false;
            }
            if ($type == 4) {
                $offerId = $fields['PROPERTY_'.$this->optionSubProductOfferId.'_VALUE'] ?? false;
            }
        }

        return $offerId;
    }

    protected function callEvent($eventName, &...$args)
    {
        $event = new Event($this->moduleId, $eventName, $args);
        $event->send();
    }

    protected function getAllTradeCatalogs()
    {
        if ($this->allTradeCatalogs) return $this->allTradeCatalogs;

        $query = $this->wrappers->CIBlock->GetList(
            [],
            [
                'SITE_ID' => $this->siteId,
                'ACTIVE' => 'Y',
            ]
        );
        $tradeCatalogs = [
            'product_iblock' => [],
            'offers_iblock' => [],
        ];
        while ($iblock = $query->Fetch()) {
            $iblockInfo = $this->wrappers->CCatalog->GetByIDExt($iblock['ID']);
            if ($iblockInfo['CATALOG_TYPE'] == 'X') {
                $tradeCatalogs['product_iblock'][] = $iblockInfo['PRODUCT_IBLOCK_ID'];
                $tradeCatalogs['offers_iblock'][] = $iblockInfo['OFFERS_IBLOCK_ID'];
            } elseif ($iblockInfo['CATALOG_TYPE'] == 'D') {
                if (!in_array($iblockInfo['IBLOCK_ID'], $tradeCatalogs['product_iblock'])) {
                    $tradeCatalogs['product_iblock'][] = $iblockInfo['IBLOCK_ID'];
                }
            }
        }

        $this->allTradeCatalogs = $tradeCatalogs;

        return $tradeCatalogs;
    }

    protected function getDetailInfoForMultiOfferIds($func, $param)
    {
        [$propertyOnBitrix, $offerIds, $tradeCatalog] = $param;
        $detailInfo = [];

        foreach ($offerIds as $offerId) {
            $detailInfo = $this->$func($propertyOnBitrix, $offerId, $tradeCatalog);
            if ($detailInfo) break;
        }

        return $detailInfo;
    }

    protected function getDetailedInformationAboutProductOffers($propertyOnBitrix, $offerId, $tradeCatalog)
    {
        try {
            if (is_array($offerId)) {
                return $this->getDetailInfoForMultiOfferIds(__FUNCTION__, [
                    $propertyOnBitrix,
                    $offerId,
                    $tradeCatalog
                ]);
            }

            $filter = $this->getFilter($propertyOnBitrix, $offerId, $tradeCatalog);
            if (!$filter) return [];

            $select = [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'DETAIL_PAGE_URL',
                'QUANTITY',
                'CAN_BUY_ZERO',
                'QUANTITY_TRACE',
                'TYPE'
            ];
            $select = $this->PackageRatio->getSelectForOffer($select);

            $query = $this->get($select, $filter);

            $detailInfoAboutProductOffer = [];
            if ($offerData = $query->GetNext()) {
                if ($offerData['ID']) {
                    $parentProduct = $this->wrappers->CCatalogSKU->GetProductInfo($offerData['ID']);
                    if (is_array($parentProduct)) {
                        $queryById = $this->wrappers->CIBlockElement->GetByID($parentProduct['ID']);
                        if ($resultProduct = $queryById->GetNext()) {
                            if ($resultProduct['ACTIVE'] == 'Y') {
                                $detailInfoAboutProductOffer['id'] = $offerData['ID'];
                                $detailInfoAboutProductOffer['detail_page_url'] = $offerData['DETAIL_PAGE_URL'];

                                $detailInfoAboutProductOffer['quantity_trace'] = $offerData['QUANTITY_TRACE'];
                                $detailInfoAboutProductOffer['can_buy_zero'] = $offerData['CAN_BUY_ZERO'];
                                $detailInfoAboutProductOffer['quantity'] = (int) $offerData['QUANTITY'];

                                $detailInfoAboutProductOffer['product_type'] = $offerData['TYPE'];
                                $detailInfoAboutProductOffer['iblock_id'] = $offerData['IBLOCK_ID'];
                                $detailInfoAboutProductOffer['parent_product_id'] = $parentProduct['ID'];
                                $detailInfoAboutProductOffer['parent_iblock_id'] = $parentProduct['IBLOCK_ID'];

                                if ($this->PackageRatio->checkPropertyExistsForOffer($offerData)) {
                                    $detailInfoAboutProductOffer['package_ratio_value'] = $this->PackageRatio->getPropertyValueForOffer($offerData);
                                } else {
                                    $packageRatioProductPropCode = $this->PackageRatio->getPackageRatioProductPropCode();
                                    $productData = $this->getParentPackageProductProp($resultProduct, $packageRatioProductPropCode);
                                    if ($this->PackageRatio->checkPropertyExistsForProduct($productData)) {
                                        $detailInfoAboutProductOffer['package_ratio_value'] = $this->PackageRatio->getPropertyValueForProduct($productData);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }

        return $detailInfoAboutProductOffer;
    }

    protected function getParentPackageProductProp($baseProduct, $packageRatioPropCode)
    {
        $productData = [];
        if (!$packageRatioPropCode) return $productData;

        $prop = $this->wrappers->CIBlockElement->GetProperty(
            $baseProduct['IBLOCK_ID'],
            $baseProduct['ID'],
            ["sort" => "asc"],
            ["CODE"=> $packageRatioPropCode]
        );

        if ($packageRatioProp = $prop->Fetch()) {
            $packageRatioPropValue = $packageRatioProp['VALUE'];
            $productData['PROPERTY_' . strtoupper($packageRatioPropCode) . '_VALUE'] = $packageRatioPropValue;
        }

        return $productData;
    }

    protected function getDetailedInformationAboutProduct($propertyOnBitrix = null, $offerId = null, $tradeCatalog)
    {
        try {
            if (is_array($offerId)) {
                return $this->getDetailInfoForMultiOfferIds(__FUNCTION__, [
                    $propertyOnBitrix,
                    $offerId,
                    $tradeCatalog
                ]);
            }

            $filter = $this->getFilter($propertyOnBitrix, $offerId, $tradeCatalog);
            if (!$filter) return [];

            $select = [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'DETAIL_PAGE_URL',
                'QUANTITY',
                'CAN_BUY_ZERO',
                'QUANTITY_TRACE',
                'TYPE'
            ];
            $select = $this->PackageRatio->getSelectForProduct($select);

            $query = $this->get($select, $filter);

            $detailInfoAboutProduct = [];
            if ($product = $query->GetNext()) {
                $detailInfoAboutProduct['id'] = $product['ID'];
                $detailInfoAboutProduct['detail_page_url'] = $product['DETAIL_PAGE_URL'];

                $detailInfoAboutProduct['quantity_trace'] = $product['QUANTITY_TRACE'];
                $detailInfoAboutProduct['can_buy_zero'] = $product['CAN_BUY_ZERO'];
                $detailInfoAboutProduct['quantity'] = (int) $product['QUANTITY'];

                $detailInfoAboutProduct['product_type'] = $product['TYPE'];
                $detailInfoAboutProduct['iblock_id'] = $product['IBLOCK_ID'];
                if ($this->PackageRatio->checkPropertyExistsForProduct($product)) {
                    $detailInfoAboutProduct['package_ratio_value'] = $this->PackageRatio->getPropertyValueForProduct($product);
                }
            }
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }

        return $detailInfoAboutProduct;
    }

    protected function getFilter($propertyOnBitrix, $offerId, $tradeCatalog)
    {
        $filter = [];
        $code = strtoupper($propertyOnBitrix);

        if ($code == 'ID') {
            $filter = [
                'ID' => $offerId,
            ];
        } elseif ($code == 'XML_ID') {
            $filter = [
                'XML_ID' => $offerId,
            ];
        } else {
            $propertyExists = $this->checkIblockPropertyExists($code, $tradeCatalog);
            if ($propertyExists) {
                $suffix = $this->getFilterPropertySuffix($code, $tradeCatalog);
                $filter = [
                    'PROPERTY_'.$code.$suffix => $offerId,
                ];
            }
        }
        if ($filter) {
            $filter['IBLOCK_ID'] = $tradeCatalog;
            $filter['ACTIVE'] = 'Y';
        }

        return $filter;
    }

    protected function checkIblockPropertyExists($propertyOfIblock, $iblockId)
    {
        $iblockProperties = $this->getAllowedIblockProperties($iblockId);

        return array_key_exists($propertyOfIblock, $iblockProperties);
    }

    protected function getAllowedIblockProperties($iblockId)
    {
        $allowedTypes = ['S', 'L'];
        $properties = [];

        $allProperties = $this->getAllIblockProperties($iblockId);
        foreach ($allProperties as $code => $property) {
            if (!in_array($property['PROPERTY_TYPE'], $allowedTypes)) continue;
            $properties[strtoupper($code)] = $property['NAME'];
        }

        return $properties;
    }

    protected function getFilterPropertySuffix($code, $iblockId)
    {
        $suffix = '';

        $allProperties = $this->getAllIblockProperties($iblockId);
        $type = $allProperties[$code]['PROPERTY_TYPE'] ?? false;
        if ($type == 'L') $suffix = '_VALUE';

        return $suffix;
    }

    protected function getAllIblockProperties($iblockId)
    {
        if (isset($this->allProperties[$iblockId])) return $this->allProperties[$iblockId];

        $properties = [];
        $res = $this->wrappers->CIBlockProperty->GetList([], [
            'IBLOCK_ID' => $iblockId,
        ]);
        while ($property = $res->Fetch()) {
            $properties[$property['CODE']] = $property;
        }

        $this->allProperties[$iblockId] = $properties;

        return $properties;
    }
}
