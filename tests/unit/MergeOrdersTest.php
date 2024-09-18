<?php
namespace Wbs24\Wbapi;

class MergeOrdersTest extends BitrixTestCase
{
    public function testMergeOrders()
    {
        // Исходные данные
        $array1 = [
            'posting_number' => 13833711,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => [6665956397512],
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $array2 = [
            'posting_number' => 13833712,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => [6665956397512],
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $orders = [$array1, $array2];
        $expectedResult = [
            [
                'posting_number' => 13833711,
                'order_group_id' => 'f884001e44e511edb8780242ac120002',
                'status' => NULL,
                'in_process_at' => '2024-07-15 19:44:43+03:00',
                'shipment_date' => NULL,
                'tracking_number' => NULL,
                'group_external_ids' => [
                    13833712
                ],
                'customer' => [
                    'phone' => '+79990000001',
                    'customer_email' => NULL,
                    'name' => 'Тест Тестович',
                    'address' => [
                        'city' => 'Город',
                        'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                        'comment' => NULL,
                    ],
                ],
                'products' => [
                    [
                        'offer_id' => [6665956397512],
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 2,
                    ],
                ],
            ],
        ];

        $object = new Controller\Main();
        $method = $this->getMethod('Wbs24\\Wbapi\\Controller\\Main', 'mergeOrders');

        // Вызов тестируемой функции
        $result = $method->invokeArgs($object, [$orders]);

        // Проверка результата
        $this->assertEquals($expectedResult, $result);
    }

    public function testMergeOrdersV2()
    {
        // Исходные данные
        $array1 = [
            'posting_number' => 13833711,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => [6665956397512],
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $array2 = [
            'posting_number' => 13833712,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => [6665956397512],
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $array3 = [
            'posting_number' => 13833713,
            'order_group_id' => 'f884001e44e511edb8780242ac120003',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => [6665956397512],
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $orders = [$array1, $array2, $array3];
        $expectedResult = [
            [
                'posting_number' => 13833711,
                'order_group_id' => 'f884001e44e511edb8780242ac120002',
                'status' => NULL,
                'in_process_at' => '2024-07-15 19:44:43+03:00',
                'shipment_date' => NULL,
                'tracking_number' => NULL,
                'group_external_ids' => [
                    13833712
                ],
                'customer' => [
                    'phone' => '+79990000001',
                    'customer_email' => NULL,
                    'name' => 'Тест Тестович',
                    'address' => [
                        'city' => 'Город',
                        'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                        'comment' => NULL,
                    ],
                ],
                'products' => [
                    [
                        'offer_id' => [6665956397512],
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 2,
                    ],
                ],
            ],
            [
                'posting_number' => 13833713,
                'order_group_id' => 'f884001e44e511edb8780242ac120003',
                'status' => NULL,
                'in_process_at' => '2024-07-15 19:44:43+03:00',
                'shipment_date' => NULL,
                'tracking_number' => NULL,
                'customer' => [
                    'phone' => '+79990000001',
                    'customer_email' => NULL,
                    'name' => 'Тест Тестович',
                    'address' => [
                        'city' => 'Город',
                        'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                        'comment' => NULL,
                    ],
                ],
                'products' => [
                    [
                        'offer_id' => [6665956397512],
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 1,
                    ],
                ],
            ]
        ];

        $object = new Controller\Main();
        $method = $this->getMethod('Wbs24\\Wbapi\\Controller\\Main', 'mergeOrders');

        // Вызов тестируемой функции
        $result = $method->invokeArgs($object, [$orders]);

        // Проверка результата
        $this->assertEquals($expectedResult, $result);
    }

    public function testMergeOrdersV3()
    {
        // Исходные данные
        $array1 = [
            'posting_number' => 13833711,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => '6665956397514',
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
                [
                    'offer_id' => '6665956397512',
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $array2 = [
            'posting_number' => 13833712,
            'order_group_id' => 'f884001e44e511edb8780242ac120003',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => [6665956397512],
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $array3 = [
            'posting_number' => 13833713,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => '6665956397514',
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $orders = [$array1, $array2, $array3];
        $expectedResult = [
            [
                'posting_number' => 13833711,
                'order_group_id' => 'f884001e44e511edb8780242ac120002',
                'status' => NULL,
                'in_process_at' => '2024-07-15 19:44:43+03:00',
                'shipment_date' => NULL,
                'tracking_number' => NULL,
                'group_external_ids' => [
                    13833713
                ],
                'customer' => [
                    'phone' => '+79990000001',
                    'customer_email' => NULL,
                    'name' => 'Тест Тестович',
                    'address' => [
                        'city' => 'Город',
                        'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                        'comment' => NULL,
                    ],
                ],
                'products' => [
                    [
                        'offer_id' => '6665956397514',
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 2,
                    ],
                    [
                        'offer_id' => '6665956397512',
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 1,
                    ],
                ],
            ],
            [
               'posting_number' => 13833712,
                'order_group_id' => 'f884001e44e511edb8780242ac120003',
                'status' => NULL,
                'in_process_at' => '2024-07-15 19:44:43+03:00',
                'shipment_date' => NULL,
                'tracking_number' => NULL,
                'customer' => [
                    'phone' => '+79990000001',
                    'customer_email' => NULL,
                    'name' => 'Тест Тестович',
                    'address' => [
                        'city' => 'Город',
                        'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                        'comment' => NULL,
                    ],
                ],
                'products' => [
                    [
                        'offer_id' => [6665956397512],
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 1,
                    ],
                ],
            ]
        ];

        $object = new Controller\Main();
        $method = $this->getMethod('Wbs24\\Wbapi\\Controller\\Main', 'mergeOrders');

        // Вызов тестируемой функции
        $result = $method->invokeArgs($object, [$orders]);

        print_r($result);

        // Проверка результата
        $this->assertEquals($expectedResult, $result);
    }

    public function testMergeOrdersV4()
    {
        // Исходные данные
        $array1 = [
            'posting_number' => 13833711,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => '6665956397514',
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
                [
                    'offer_id' => '6665956397512',
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $array2 = [
            'posting_number' => 13833712,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => '6665956397512',
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $array3 = [
            'posting_number' => 13833713,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => '6665956397514',
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $orders = [$array1, $array2, $array3];
        $expectedResult = [
            [
                'posting_number' => 13833711,
                'order_group_id' => 'f884001e44e511edb8780242ac120002',
                'status' => NULL,
                'in_process_at' => '2024-07-15 19:44:43+03:00',
                'shipment_date' => NULL,
                'tracking_number' => NULL,
                'group_external_ids' => [
                    13833712,
                    13833713
                ],
                'customer' => [
                    'phone' => '+79990000001',
                    'customer_email' => NULL,
                    'name' => 'Тест Тестович',
                    'address' => [
                        'city' => 'Город',
                        'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                        'comment' => NULL,
                    ],
                ],
                'products' => [
                    [
                        'offer_id' => '6665956397514',
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 2,
                    ],
                    [
                        'offer_id' => '6665956397512',
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 2,
                    ],
                ],
            ],
        ];

        $object = new Controller\Main();
        $method = $this->getMethod('Wbs24\\Wbapi\\Controller\\Main', 'mergeOrders');

        // Вызов тестируемой функции
        $result = $method->invokeArgs($object, [$orders]);

        // Проверка результата
        $this->assertEquals($expectedResult, $result);
    }

    public function testMergeOrdersV5()
    {
        // Исходные данные
        $array1 = [
            'posting_number' => 13833711,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => [6665956397514, 6665956397513],
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
                [
                    'offer_id' => '6665956397512',
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $array2 = [
            'posting_number' => 13833712,
            'order_group_id' => 'f884001e44e511edb8780242ac120002',
            'status' => NULL,
            'in_process_at' => '2024-07-15 19:44:43+03:00',
            'shipment_date' => NULL,
            'tracking_number' => NULL,
            'customer' => [
                'phone' => '+79990000001',
                'customer_email' => NULL,
                'name' => 'Тест Тестович',
                'address' => [
                    'city' => 'Город',
                    'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                    'comment' => NULL,
                ],
            ],
            'products' => [
                [
                    'offer_id' => [6665956397513],
                    'name' => 'Product',
                    'price' => 100,
                    'currency' => 643,
                    'discount_price' => 0,
                    'quantity' => 1,
                ],
            ],
        ];

        $orders = [$array1, $array2];
        $expectedResult = [
            [
                'posting_number' => 13833711,
                'order_group_id' => 'f884001e44e511edb8780242ac120002',
                'status' => NULL,
                'in_process_at' => '2024-07-15 19:44:43+03:00',
                'shipment_date' => NULL,
                'tracking_number' => NULL,
                'group_external_ids' => [
                    13833712,
                ],
                'customer' => [
                    'phone' => '+79990000001',
                    'customer_email' => NULL,
                    'name' => 'Тест Тестович',
                    'address' => [
                        'city' => 'Город',
                        'address_tail' => 'Челябинская область, Челябинск, Город, 51-я улица Арабкира, 10А, 42,  3',
                        'comment' => NULL,
                    ],
                ],
                'products' => [
                    [
                        'offer_id' => [6665956397514, 6665956397513],
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 1,
                    ],
                    [
                        'offer_id' => '6665956397512',
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 1,
                    ],
                    [
                        'offer_id' => [6665956397513],
                        'name' => 'Product',
                        'price' => 100,
                        'currency' => 643,
                        'discount_price' => 0,
                        'quantity' => 1,
                    ],
                ],
            ],
        ];

        $object = new Controller\Main();
        $method = $this->getMethod('Wbs24\\Wbapi\\Controller\\Main', 'mergeOrders');

        // Вызов тестируемой функции
        $result = $method->invokeArgs($object, [$orders]);

        // Проверка результата
        $this->assertEquals($expectedResult, $result);
    }
}
