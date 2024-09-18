<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class ProductStocksTest extends BitrixTestCase
{
    public function testGetStocks()
    {
        // входные параметры
        $productsInMarketplace = [
            [
                'chrtId' => 205384680,
                'barcode' => '2039893700627',
                'stock' => 1,
                'warehouseId' => 11111,
            ],
        ];

        // результат для проверки
        $expectedResult = [
            [
                'barcode' => '2039893700627',
                'stock' => 15,
                'warehouseId' => 11111,
            ],
        ];

        // заглушки
        Loader::includeModule('iblock');

        // для конструктора
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->will($this->returnCallback(
                function ($moduleId, $option) {
                    $optionsResults = [
                        'siteId' => 's1',
                        'skuPropertyForProducts' => 'ARTNUMBER',
                        'skuPropertyForProductOffers' => 'DEMOPROP',
                        'barcodeAsOfferId' => 'Y',
                    ];

                    return $optionsResults[$option] ?? '';
                }
            ));

        // для getAllTradeCatalogs()
        $CIBlockResultForCIBlockStub = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'ID' => 1,
            ],
            [
                'ID' => 2,
            ],
            false,
        ];
        $CIBlockResultForCIBlockStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $CIBlockStub = $this->createMock(Wrappers\CIBlock::class);
        $CIBlockStub->method('GetList')
            ->willReturn($CIBlockResultForCIBlockStub);

        $CCatalogStub = $this->createMock(Wrappers\CCatalog::class);
        $CCatalogStub->method('GetByIDExt')
            ->will($this->onConsecutiveCalls(...[
                [
                    'CATALOG_TYPE' => 'X',
                    'PRODUCT_IBLOCK_ID' => 1,
                    'OFFERS_IBLOCK_ID' => 2,
                ],
                [
                    'CATALOG_TYPE' => '',
                ],
            ]));

        // для getDetailedInformationAboutProduct()
        $CIBlockResultForCIBlockPropertyStub = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'PROPERTY_TYPE' => 'S',
                'CODE' => 'ARTNUMBER',
                'NAME' => 'Article',
            ],
            false,
        ];
        $CIBlockResultForCIBlockPropertyStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $CIBlockPropertyStub = $this->createMock(Wrappers\CIBlockProperty::class);
        $CIBlockPropertyStub->method('GetList')
            ->willReturn($CIBlockResultForCIBlockPropertyStub);

        $CIBlockResultStub1 = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'ID' => 55555,
                'DETAIL_PAGE_URL' => 'http://test.ru/catalog/55555/',
            ],
            false,
        ];
        $CIBlockResultStub1->method('GetNext')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        // для getProductIdsToQuantity()
        $CIBlockResultStub2 = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'ID' => 55555,
                'QUANTITY' => 15,
            ],
            false,
        ];
        $CIBlockResultStub2->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        // для всех функций
        $CIBlockElementStub = $this->createMock(Wrappers\CIBlockElement::class);
        $CIBlockElementStub->expects($this->exactly(2))
            ->method('GetList')
            ->will($this->onConsecutiveCalls(...[$CIBlockResultStub1, $CIBlockResultStub2]));

        // вычисление результата
        // вызов protected метода
        $object = new ProductStocks([
            'Option' => $OptionStub,
            'CIBlock' => $CIBlockStub,
            'CCatalog' => $CCatalogStub,
            'CIBlockProperty' => $CIBlockPropertyStub,
            'CIBlockElement' => $CIBlockElementStub,
        ]);
        $result = $object->getStocks($productsInMarketplace);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
