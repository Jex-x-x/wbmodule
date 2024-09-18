<?php
namespace Wbs24\Wbapi\Prices;

use Bitrix\Main\{
    Loader,
    SystemException
};
use Wbs24\Wbapi\{
    Main,
    Exception,
    Wrappers,
    Product,
    Formula
};

class Helper
{
    use Exception;

    protected $main;
    protected $moduleId;
    protected $wrappers;

    public function __construct(array $objects = [])
    {
        try {
            if (!Loader::IncludeModule('catalog')) {
                throw new SystemException("Catalog module isn`t installed");
            }
            if (!Loader::IncludeModule('iblock')) {
                throw new SystemException("Iblock module isn`t installed");
            }
            $this->main = $objects['Main'] ?? new Main();
            $this->moduleId = $this->main->getModuleId();
            $this->wrappers = new Wrappers($objects);
            $this->Product = $objects['Product'] ?? new Product();
            $this->Formula = $objects['Formula'] ?? new Formula();

            $this->productPriceFormula = $this->wrappers->Option->get(
                $this->moduleId,
                'productPrice'
            );
            $this->offerPriceFormula = $this->wrappers->Option->get(
                $this->moduleId,
                'offerPrice'
            );
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function getFormulaTemplates()
    {
        return [
            'price' => [
                'PRODUCT' => $this->productPriceFormula,
                'OFFER' => $this->offerPriceFormula,
            ],
        ];
    }

    public function getPricesByProductId(int $productId, $productInfo): array
    {
        $formulaTemplates = $this->getFormulaTemplates();
        $calculatePrices = [];
        switch ((int) $productInfo['product_type']) {
            case 1:
            case 2:
            case 3:
                foreach ($formulaTemplates as $priceType => $formulaTemplates) {
                    $calculatePrices[$priceType] = $this->calculateProductPrice(
                        [
                            'productId' => $productId,
                            'iblockId' => $productInfo['iblock_id'],
                            'formula' => $formulaTemplates['PRODUCT']
                        ]
                    );
                }
                break;
            case 4:
                foreach ($formulaTemplates as $priceType => $formulaTemplates) {
                    $calculatePrices[$priceType] = $this->calculateOfferPrice(
                        [
                            'productId' => $productId,
                            'parentProductId' => $productInfo['parent_product_id'],
                            'iblockId' => $productInfo['iblock_id'],
                            'parentIblockId' => $productInfo['parent_iblock_id'],
                            'formula' => $formulaTemplates['OFFER']
                        ]
                    );
                }
                break;
        }

        return [
            'price' => round($calculatePrices['price']) ?? 0,
        ];
    }

    protected function calculateProductPrice($needInfo)
    {
        $productId = $needInfo['productId'];
        $iblockId = $needInfo['iblockId'];
        $formula = $needInfo['formula'];

        if (
            !$productId
            || !$iblockId
            || !$formula
        ) {
            return 0;
        }

        // Получить айди свойств из меток
        $propertyIds = $this->getPropertyOrPriceIdsFromTemplate(
            $formula,
            'PROPERTY_'
        );
        // Получить типы цен из меток
        $priceTypeIds = $this->getPropertyOrPriceIdsFromTemplate(
            $formula,
            'PRICE_'
        );
        // Получить свойства простого товара
        $propertyIdsToValues = [];
        if ($propertyIds) {
            $propertyIdsToValues = $this->getPropertyIdsToValues(
                $productId,
                $iblockId,
                $propertyIds
            );
        }
        // Получить типы цен
        $priceTypeIdsToValues = [];
        if ($priceTypeIds) {
            $priceTypeIdsToValues = $this->getPriceTypeIdsToValues(
                $productId,
                $priceTypeIds
            );
        }
        // Сопоставляем метки к полученным значениям
        $marksToValues = $this->compareMarksToValues(
            $priceTypeIdsToValues,
            $propertyIdsToValues,
        );

        // если меток вообще нет возвращаем 0
        if (empty($marksToValues)) return 0;

        // Отправялем на калькуляцию
        return $this->calcByFormula($formula, $marksToValues);
    }

    protected function calculateOfferPrice($needInfo)
    {
        $productId = $needInfo['productId'];
        $parentProductId = $needInfo['parentProductId'];
        $iblockId = $needInfo['iblockId'];
        $parentIblockId = $needInfo['parentIblockId'];
        $formula = $needInfo['formula'];

        if (
            !$productId
            || !$parentProductId
            || !$iblockId
            || !$parentIblockId
            || !$formula
        ) {
            return 0;
        }

        // Получить айди свойств из меток
        $offerPropertyIds = $this->getPropertyOrPriceIdsFromTemplate(
            $formula,
            'OFFER_PROPERTY_'
        );
        $propertyIds = $this->getPropertyOrPriceIdsFromTemplate(
            $formula,
            'PROPERTY_'
        );
        $priceTypeIds = $this->getPropertyOrPriceIdsFromTemplate(
            $formula,
            'PRICE_'
        );

        // Получить свойства торгового предложения
        $offerPropertyIdsToValues = [];
        if ($offerPropertyIds) {
            $offerPropertyIdsToValues = $this->getPropertyIdsToValues(
                $productId,
                $iblockId,
                $offerPropertyIds
            );
        }
        // Получить свойства родительского товара
        $propertyIdsToValues = [];
        if ($propertyIds) {
            $propertyIdsToValues = $this->getPropertyIdsToValues(
                $parentProductId,
                $parentIblockId,
                $propertyIds
            );
        }

        // Получить типы цен
        $priceTypeIdsToValues = [];
        if ($priceTypeIds) {
            $priceTypeIdsToValues = $this->getPriceTypeIdsToValues(
                $productId,
                $priceTypeIds
            );
        }

        // Сопоставляем метки к полученным значениям
        $marksToValues = $this->compareMarksToValues(
            $priceTypeIdsToValues,
            $propertyIdsToValues,
            $offerPropertyIdsToValues,
        );

        // если меток вообще нет возвращаем 0
        if (empty($marksToValues)) return 0;

        // Отправялем на калькуляцию
        return $this->calcByFormula($formula, $marksToValues);
    }

    protected function compareMarksToValues(
        $priceTypeIdsToValues,
        $propertyIdsToValues,
        $offerPropertyIdsToValues = []
    ) {
        $marksToValues = [];

        foreach ($priceTypeIdsToValues as $priceTypeId => $priceValue) {
            $marksToValues['PRICE_'.$priceTypeId] = $priceValue;
        }
        foreach ($propertyIdsToValues as $propertyId => $propertyValue) {
            $marksToValues['PROPERTY_'.$propertyId] = $propertyValue;
        }
        if ($offerPropertyIdsToValues) {
            foreach ($offerPropertyIdsToValues as $propertyId => $propertyValue) {
                $marksToValues['OFFER_PROPERTY_'.$propertyId] = $propertyValue;
            }
        }

        return $marksToValues;
    }

    protected function getPropertyIdsToValues($productId, $iblockId, $propertyIds)
    {
        $propertyIdsToValues = [];
        $findProperties = [];

        $iterator = \CIBlockElement::GetPropertyValues(
            $iblockId,
            ['ID' => $productId],
            false,
            ['ID' => $propertyIds]
        );
        if ($row = $iterator->Fetch()) {
            $findProperties = $row;
        }

        foreach ($propertyIds as $propertyId) {
            $propertyIdsToValues[$propertyId] = (int) $findProperties[$propertyId] ?? 0;
        }

        return $propertyIdsToValues;
    }

    protected function getPriceTypeIdsToValues($productId, $priceTypeIds)
    {
        $priceTypeIdsToValues = [];
        $findPrices = [];

        $preparedPriceTypeIds = [];
        foreach ($priceTypeIds as $priceTypeId) {
            $preparedPriceTypeIds[] = 'PRICE_'.$priceTypeId;
        }

        $select = ['ID', 'IBLOCK_ID'];
        $select = array_merge($select, $preparedPriceTypeIds);
        $filter = ['ID' => $productId];
        $source = 'prices';
        $resultQuery = $this->Product->get($select, $filter, $source);
        if ($priceInfo = $resultQuery->fetch()) {
            $findPrices = $priceInfo;
        }
        foreach ($priceTypeIds as $priceTypeId) {
            $priceTypeIdsToValues[$priceTypeId] = (int) $findPrices['PRICE_'.$priceTypeId] ?? 0;
        }

        return $priceTypeIdsToValues;
    }

    protected function getPropertyOrPriceIdsFromTemplate($template, $prefix)
    {
        preg_match_all('/\{'.$prefix.'(\d+)\}/', $template, $matches);
        $findIds = $matches[1] ?? [];

        return $findIds;
    }

    protected function calcByFormula($formula, $marks)
    {
        $allowedMarks = array_keys($marks);
        $this->Formula->setMarks($allowedMarks);
        $this->Formula->setFormula($formula);
        $price = $this->Formula->calc($marks);

        return $price;
    }
}
