<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;

// 14.02.2023
class PackageRatio
{
    use Exception;

    protected const OPTION_PRODUCT_PROPERTY = 'packageProductRatio';
    protected const OPTION_OFFER_PROPERTY = 'packageOfferRatio';

    public function __construct($objects = [])
    {
        try {
            $this->main = $objects['Main'] ?? new Main();
            $this->moduleId = $this->main->getModuleId();
            $this->wrappers = new Wrappers($objects);

            $this->optionPackageProductPropCode = $this->wrappers->Option->get($this->moduleId, self::OPTION_PRODUCT_PROPERTY);
            $this->optionPackageOfferPropCode = $this->wrappers->Option->get($this->moduleId, self::OPTION_OFFER_PROPERTY);
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function getPackageRatioProductPropCode()
    {
        $packageProductPropCode = false;
        if ($this->optionPackageProductPropCode != 'nothing') {
            $packageProductPropCode = strtoupper($this->optionPackageProductPropCode);
        }

        return $packageProductPropCode;
    }

    public function getSelectForProduct($select)
    {
        if ($this->optionPackageProductPropCode != 'nothing') {
            $select[] = 'PROPERTY_' . strtoupper($this->optionPackageProductPropCode);
        }

        return $select;
    }

    public function getSelectForOffer($select)
    {
        if ($this->optionPackageOfferPropCode != 'nothing') {
            $select[] = 'PROPERTY_' . strtoupper($this->optionPackageOfferPropCode);
        }

        return $select;
    }

    // Проверка существования свойства
    public function checkPropertyExistsForProduct($response)
    {
        $propertyExists = $this->checkPropertyExists($response, $this->optionPackageProductPropCode);

        return $propertyExists;
    }

    public function checkPropertyExistsForOffer($response)
    {
        $propertyExists = $this->checkPropertyExists($response, $this->optionPackageOfferPropCode);

        return $propertyExists;
    }

    public function checkPropertyExists($response, $propCode)
    {
        $propertyExists = false;

        if (array_key_exists('PROPERTY_' . strtoupper($propCode) . '_VALUE', $response)) {
            $propertyExists = true;
        }

        return $propertyExists;
    }

    // Получение значения свойства
    public function getPropertyValueForProduct($response)
    {
        $propValue = $this->getPropertyValue($response, $this->optionPackageProductPropCode);

        return $propValue;
    }

    public function getPropertyValueForOffer($response)
    {
        $propValue = $this->getPropertyValue($response, $this->optionPackageOfferPropCode);

        return $propValue;
    }

    public function getPropertyValue($response, $propCode)
    {
        $packageRatioPropValue = (int) $response['PROPERTY_' . strtoupper($propCode) . '_VALUE'];

        if (
            !$packageRatioPropValue
            || $packageRatioPropValue < 0
        ) {
            $packageRatioPropValue = 1;
        }

        return $packageRatioPropValue;
    }

    // рассчет цены и кол-ва
    public function calculatePriceAndQuantityWithPackageRatio($detailInfoAboutProduct, $product)
    {
        $packageRatioValue = $detailInfoAboutProduct['package_ratio_value'] ?? false;

        if ($packageRatioValue) {
            $product['price'] = $this->getPriceWithPackagingRatio($product['price'], $packageRatioValue);
            $product['discount_price'] = $this->getPriceWithPackagingRatio($product['discount_price'], $packageRatioValue);
            $product['quantity'] = $this->getStockWithPackagingRatio($product['quantity'], $packageRatioValue);
        }

        return $product;
    }

    public function getPriceWithPackagingRatio($price, $packageRatioValue)
    {
        $finalPrice = $price / $packageRatioValue;

        return $finalPrice;
    }

    public function getStockWithPackagingRatio($quantity, $packageRatioValue)
    {
        $finalStock = $quantity * $packageRatioValue;

        return $finalStock;
    }
}
