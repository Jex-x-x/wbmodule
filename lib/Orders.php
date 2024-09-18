<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Sale\TradingPlatformTable;

// 02.11.2023
class Orders {
    use Exception; // trait

    protected const TRADING_PLATFORM_CODE = 'wildberries';
    protected const OPTION_STATUSES_SUFFIX = 'status_';

    protected $main;
    protected $moduleId;
    protected $wrappers;
    protected $Product;
    protected $PackageRatio;

    protected $xmlIdPrefix = 'WB';
    protected $maxDuring = 30;
    protected $notLinkedProductId = 99999999;

    protected $siteId;
    protected $baseCurrency;
    protected $tradingPlatformCode;
    protected $deliveryServiceId;
    protected $paymentSystemId;
    protected $optionsOfCompletedStatuses = [];
    protected $userDescriptionTemplate;
    protected $templateFields = [
        '#SHIPMENT_ID#' => 'posting_number',
        '#CREATE_DATE#' => 'in_process_at',
    ];
    protected $changedCanBuyFlagProductIds = [];

    public function __construct($objects = [])
    {
        try {
            $this->main = $objects['Main'] ?? new Main();
            $this->moduleId = $this->main->getModuleId();
            $this->wrappers = new Wrappers($objects);

            if (!Loader::IncludeModule('sale')) {
                throw new SystemException("Sale module isn`t installed");
            }
            if (!Loader::IncludeModule('catalog')) {
                throw new SystemException("Catalog module isn`t installed");
            }
            if (!Loader::IncludeModule('iblock')) {
                throw new SystemException("Iblock module isn`t installed");
            }
            if (!Loader::IncludeModule('landing')) {
                throw new SystemException("Landing module isn`t installed");
            }

            $this->Product = $objects['Product'] ?? new Product($objects);
            $this->PackageRatio = $objects['PackageRatio'] ?? new PackageRatio($objects);

            $accountIndex = $this->wrappers->Option->getAccountIndex();
            $this->xmlIdPrefix .= ($accountIndex > 1 ? $accountIndex : '');

            $this->siteId = $this->wrappers->Option->get($this->moduleId, 'siteId');
            $this->baseCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();
            $this->deliveryServiceId = intval($this->wrappers->Option->get($this->moduleId, 'deliveryServiceId'));
            $this->paymentSystemId = intval($this->wrappers->Option->get($this->moduleId, 'paymentSystemId'));
            $this->userDescriptionTemplate = $this->wrappers->Option->get($this->moduleId, 'userDescriptionTemplate');
            $this->allowCanBuyZero = ($this->wrappers->Option->get($this->moduleId, 'allowCanBuyZero') == 'Y');
            $this->verifyAndInstallTradingPlatform();
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    protected function verifyAndInstallTradingPlatform() {
        $result = TradingPlatformTable::getList([
            'filter' => [
                '=CODE' => self::TRADING_PLATFORM_CODE,
            ],
        ]);
        if (!$result->fetch()) {
            TradingPlatformTable::add([
                "CODE" => self::TRADING_PLATFORM_CODE,
                "NAME" => Loc::getMessage(strtoupper($this->moduleId).".TRADING_PLATFORM_NAME"),
                "ACTIVE" => "Y",
            ]);
        }
    }

    /**
     * Добавился параметр $ordersFromTradingPlatform, в котором передаются данные заказов с маркетплейса
     * Запрос к API вынесен в метод getOrders класса Api
     *
     * Вызов цепочки методов производится из класса Agents в методе getOrdersAgents
     *
     * Формат заказов:
     * $ordersFromTradingPlatform = [
     *     [
     *         'posting_number' => '123', // внешний номер заказа
     *         'shipment_date' => '2022-01-28T10:00:00Z', // планируемая дата отгрузки
     *         'products' => [
     *             [
     *                 'offer_id' => '456',
     *                 'name' => 'Product name',
     *                 'price' => 1000,
     *                 'discount_price' => 100, // опционально (если указано, то BASE_PRICE = price - discount_price)
     *                 'quantity' => 1,
     *             ],
     *         ],
     *     ],
     * ];
     */
    public function createNewOrders($ordersFromTradingPlatform)
    {
        $this->createReport('create_orders_log.txt', 'Start creating new orders');

        // проверить нет ли блокировки
        // если нет, поставить блокировку
        // если блокировка есть, прервать работу
        $started = ($this->wrappers->Option->get($this->moduleId, 'createOrdersStarted') == 'Y');
        if ($started) {
            $this->createReport('create_orders_log.txt', 'Creating of orders is blocked');
            $this->wrappers->Option->set($this->moduleId, 'createOrdersStarted', '');
            return;
        } else {
            $this->wrappers->Option->set($this->moduleId, 'createOrdersStarted', 'Y');
        }

        // начало выполнения скрипта по времени
        $startTime = time();

        $this->verifyAndConvertToTrueCharset($ordersFromTradingPlatform);

        foreach ($ordersFromTradingPlatform as $key => $resultOrder) {
            $this->createOrder($resultOrder);

            // Прерываем по таймеру
            if ((time() - $startTime) > $this->maxDuring) break;
        }

        // снимаем блокировку
        $this->wrappers->Option->set($this->moduleId, 'createOrdersStarted', '');
    }

    public function createOrder($resultOrder)
    {
        try {
            $success = false;
            $externalId = $resultOrder['posting_number'];

            $checkIs = $this->checkTheOrderForUniqueness($externalId);
            if ($checkIs) return $success;

            $userId = $this->getUserId();
            $eventResult = $this->callEvent('onBeforeCreateOrder', $resultOrder, $userId);
            if (!$eventResult) return $success;

            $order = $this->wrappers->Order->create($this->siteId, $userId);
            $order->setPersonTypeId(intval($this->wrappers->Option->get($this->moduleId, 'personTypeId')));
            $order->setField('XML_ID', $this->xmlIdPrefix.'_'.$externalId);

            $basket = $this->getBasket($order, $resultOrder['products']);
            $order->setBasket($basket);

            $this->createShipment($order, $basket, $resultOrder);
            $this->createPayment($order, $resultOrder);
            $this->initTradeBinding($order, $externalId);

            $shipmentDateTimestamp = strtotime($resultOrder['shipment_date']);
            $shipmentDate = $shipmentDateTimestamp ? date('d.m.Y', $shipmentDateTimestamp) : '';

            $orderPropertyValuesSetSuccess = $this->setOrderPropertyValues($order, [
                'shipmentDate' => $shipmentDate,
                'externalId' => $externalId,
            ]);
            if (!$orderPropertyValuesSetSuccess) throw new SystemException("Order ${externalId} can`t be created because binding of properties is wrong");

            if (!empty($resultOrder['customer']) && !empty($resultOrder['customer']['name'])) { // механизм не изменен вместе с обновлением класса
                $this->setCustomerData($order, $resultOrder['customer']);
            } else {
                $this->setDefaultCustomerData($order, $userId);
            }
            if (!empty($resultOrder['tracking_number'])) $this->setTrackNumber($order, $resultOrder['tracking_number']);
            $this->setUserDescription($order, $resultOrder);

            $eventResult = $this->callEvent('onBeforeSaveOrder', $resultOrder, $order);
            if (!$eventResult) return $success;

            $result = $order->save();
            if (!$result->isSuccess()) {
                $error = $result->getErrorMessages();
                $this->createReport('error_log.txt', $error);
            } else {
                // success
                $success = true;
                $this->createReport('create_orders_log.txt', 'Creating the order '.$externalId.' is success');
            }

            $this->returnBackChangesProductCanBuyFlags(
                $this->changedCanBuyFlagProductIds
            );

            return $success;
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    protected function verifyAndConvertToTrueCharset(&$array)
    {
        $charset = $this->getSiteCharset();

        if ($charset == 'windows-1251') {
            array_walk_recursive($array, function(&$item, $key) {
                $item = iconv('UTF-8', $charset, $item);
            });
        }
    }

    protected function getSiteCharset()
    {
        $charset = '';

        $result = \CSite::GetList(
            $by = "sort",
            $order="desc",
            ["LID" => $this->siteId]
        );
        if ($site = $result->fetch()) {
            $charset = $site['CHARSET'];
        }

        return $charset;
    }

    protected function getUserId()
    {
        $userId = intval($this->wrappers->Option->get($this->moduleId, 'userId'));
        if (!is_numeric($userId) || !$userId) $userId = 1;

        return $userId;
    }

    protected function getCustomerId()
    {
        $customerId = intval($this->wrappers->Option->get($this->moduleId, 'customerId'));
        if (!is_numeric($customerId) || !$customerId) $customerId = false;

        return $customerId;
    }

    protected function callEvent($eventName, &...$args)
    {
        $event = new Event($this->moduleId, $eventName, $args);
        $event->send();
        $eventResults = $event->getResults();
        $finalResult = true;

        foreach ($eventResults as $eventResult) {
            $eventParameters = $eventResult->getParameters();
            $disallowAction = $eventParameters['disallow'] ?: false;
            if ($disallowAction) $finalResult = false;
        }

        return $finalResult;
    }

    public function checkTheOrderForUniqueness($externalId)
    {
        return $this->getOrderIdByExternalId($externalId) ? true : false;
    }

    protected function getOrderIdByExternalId($externalId)
    {
        $orderId = false;
        $orders = $this->wrappers->Order->getList([
            'select' => ['ID'],
            'filter' => [
                '=XML_ID' => $this->xmlIdPrefix.'_'.$externalId,
            ],
            'order' => ['ID' => 'DESC']
        ]);
        if ($orderInfo = $orders->fetch()) {
            $orderId = $orderInfo['ID'];
        }

        return $orderId;
    }

    protected function getBasket(&$order, $products)
    {
        $basket = \Bitrix\Sale\Basket::create($this->siteId);
        $this->addProductsToBasket($order, $basket, $products);

        return $basket;
    }

    protected function addProductsToBasket(&$order, &$basket, $products)
    {
        foreach ($products as $product) {
            $fields = $this->getProductFields($product);

            if (!$fields['CAN_BUY']) {
                $this->markOrderAsProblematic(
                    $order,
                    $fields,
                    $product
                );
                continue;
            }
            unset($fields['CAN_BUY']);

            $item = $basket->createItem("catalog", $fields['PRODUCT_ID']);
            unset($fields["PRODUCT_ID"]);
            $item->setFields($fields);
        }
    }

    protected function markOrderAsProblematic(&$order, $fields, $product)
    {
        $message = Loc::getMessage(strtoupper($this->moduleId).".ERROR_ADD_PRODUCT_TO_BASKET");
        $message = $this->replaceErrorMessage($message, $fields, $product['offer_id']);
        $code = str_replace('.', "_", $this->moduleId) . '_product_add_error_id_' . $fields['PRODUCT_ID'];
        $this->addOrderError($order, $message, $code);
    }

    /**
     * Добавить маркер ошибки в заказ
     * После вызова этой функции необходимо сохранить заказ: $order->save()
     */
    protected function addOrderError(&$order, $message, $code)
    {
        // подготовка данных для маркера
        $errorResult = new \Bitrix\Main\Error($message, $code);
        $result = new \Bitrix\Sale\Result();
        $result->addWarning($errorResult);

        // проверить нет ли такого маркера
        $markerInfo = \Bitrix\Sale\EntityMarker::loadFromDb(['filter' => [
            'ORDER_ID' => $order->getId(),
            'CODE' => $code,
        ]]);
        $id = $markerInfo['ID'] ?? false;
        if ($id) {
            // удалить старый маркер
            \Bitrix\Sale\EntityMarker::delete($id);
        }
        // добавить новый маркер
        \Bitrix\Sale\EntityMarker::addMarker($order, $order, $result);
    }

    protected function replaceErrorMessage($message, $fields, $offerId)
    {
        $patterns = [];
        $patterns[0] = '/#PRODUCT_ID#/';
        $patterns[1] = '/#OFFER_ID#/';

        $replacements = [];
        $replacements[0] = $fields['PRODUCT_ID'];
        $replacements[1] = is_array($offerId) ? implode(',', $offerId) : $offerId;

        return preg_replace($patterns, $replacements, $message);
    }

    protected function getProductFields($product)
    {
        $detail = $this->getProductDetail($product['offer_id']);
        $canBuy = $this->checkCanBuyProduct($detail);
        $fields = $this->getProductFieldsByDetailInfo($detail, $product);
        $fields['CAN_BUY'] = $canBuy;

        return $fields;
    }

    protected function checkCanBuyProduct($detail)
    {
        $canBuy = true;
        if (
            $detail
            && $detail['quantity_trace'] == 'Y'
            && $detail['can_buy_zero'] == 'N'
            && !$detail['quantity']
        ) {
            $canBuy = false;
        }

        if (
            !$canBuy
            && $this->allowCanBuyZero
        ) {
            $productId = $detail['id'];
            $defaultCanBuyValue = $detail['can_buy_zero_raw'];

            $success = $this->updateProductCanBuyZero($productId, 'Y');
            if ($success) {
                $this->changedCanBuyFlagProductIds[$productId] = $defaultCanBuyValue;
                $canBuy = true;
            }
        }

        return $canBuy;
    }

    protected function updateProductCanBuyZero($productId, $flag)
    {
        return $this->wrappers->CCatalogProduct->Update(
            $productId,
            ['CAN_BUY_ZERO' => $flag]
        );
    }

    protected function returnBackChangesProductCanBuyFlags(
        $changedCanBuyFlagProductIds
    ) {
        foreach ($changedCanBuyFlagProductIds as $productId => $defaultCanBuyValue) {
            $success = $this->updateProductCanBuyZero($productId, $defaultCanBuyValue);
            if (!$success) {
                $this->createReport(
                    'error_log.txt',
                    'error update product CAN_BUY_ZERO flag "'. $defaultCanBuyValue .'" for product_id = '.$productId
                );
            }
        }
        $this->changedCanBuyFlagProductIds = [];
    }

    protected function getProductDetail($offerId)
    {
        return $this->Product->getProductInfoByOfferId($offerId);
    }

    protected function getProductFieldsByDetailInfo($detailInfoAboutProduct, $product)
    {
        if (empty($product['offer_id'])) $product['offer_id'] = $this->notLinkedProductId;
        $product = $this->PackageRatio->calculatePriceAndQuantityWithPackageRatio($detailInfoAboutProduct, $product);

        $discountPrice = $product['discount_price'] ?? 0;
        $finalPrice = $product['price'] - $discountPrice; // механизм не изменен вместе с обновлением класса

        $fields = [
            'PRODUCT_ID' => $detailInfoAboutProduct['id'] ?: $this->notLinkedProductId,
            'NAME' => $product['name'],
            'BASE_PRICE' => $product['price'], // механизм не изменен вместе с обновлением класса
            'DISCOUNT_PRICE' => $discountPrice,
            'PRICE' => $finalPrice, // механизм не изменен вместе с обновлением класса
            'CURRENCY' => $this->baseCurrency,
            'QUANTITY' => $product['quantity'],
            'CUSTOM_PRICE' => 'Y',
            'LID' => $this->siteId,
            /* 'DIMENSIONS' => [
                "WIDTH" => $product['dimensions']['width'],
                "HEIGHT" => $product['dimensions']['height'],
                "LENGTH"=> $product['dimensions']['length'],
            ], */
        ];

        if (!empty($detailInfoAboutProduct['id'])) {
            $fields['PRODUCT_PROVIDER_CLASS'] = '\Bitrix\Catalog\Product\CatalogProvider';

            if (!empty($detailInfoAboutProduct['detail_page_url'])) {
                $fields['DETAIL_PAGE_URL'] = $detailInfoAboutProduct['detail_page_url'];
            }
        }

        $eventResult = $this->callEvent('onAfteGetProductFields', $fields);

        return $fields;
    }

    protected function createShipment(&$order, &$basket, $resultOrder)
    {
        $shipmentCollection = $order->getShipmentCollection();
        $deliveryServiceId = $resultOrder['deliveryServiceId'] ?: $this->deliveryServiceId;
        $shipment = $shipmentCollection->createItem(
            \Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryServiceId)
        );

        $this->setDeliveryNameWithParent($order, $shipment, $deliveryServiceId);

        $shipmentItemCollection = $shipment->getShipmentItemCollection();

        foreach ($basket as $basketItem) {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
        }
    }

    protected function setDeliveryNameWithParent(&$order, &$shipment, $deliveryServiceId)
    {
        $shipmentFields = [];
        $deliveryService = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryServiceId);
        if (!$deliveryService) return;

        if ($deliveryService->isProfile()) {
            $shipmentFields['DELIVERY_NAME'] = $deliveryService->getNameWithParent();
        }
        if (empty($shipmentFields)) return;

        $setFieldsResult = $shipment->setFields($shipmentFields);
        if(!$setFieldsResult->isSuccess()) {
            $this->createReport('error_log.txt', $setFieldsResult->getErrors());
        }
    }

    protected function createPayment(&$order, $resultOrder)
    {
        $paymentCollection = $order->getPaymentCollection();
        $paymentSystemId = $resultOrder['paymentSystemId'] ?: $this->paymentSystemId;
        $payment = $paymentCollection->createItem(
            \Bitrix\Sale\PaySystem\Manager::getObjectById($paymentSystemId)
        );
        $payment->setField("SUM", $order->getPrice());
        $payment->setField("CURRENCY", $order->getCurrency());
    }

    protected function initTradeBinding(&$order, $externalId)
    {
        $platform = \Bitrix\Sale\TradingPlatform\Landing\Landing::getInstanceByCode(self::TRADING_PLATFORM_CODE);
        if (!$platform->isInstalled()) return;

        $collection = $order->getTradeBindingCollection();
        $collection->createItem($platform);
        foreach ($collection as $item) {
            $item->setField('EXTERNAL_ORDER_ID', $externalId);
        }
    }

    protected function payTheOrder(&$order, $paid = true)
    {
        $paymentCollection = $order->getPaymentCollection();
        foreach ($paymentCollection as $payment) {
            $payment->setPaid($paid ? "Y" : 'N');
        }
    }

    protected function shipTheOrder(&$order, $ship = true)
    {
        $shipmentCollection = $order->getShipmentCollection();
        foreach ($shipmentCollection as $shipment) {
            if ($shipment->isSystem()) continue;
            if ($ship) {
                $shipment->allowDelivery();
            } else {
                $shipment->disallowDelivery();
            }
            $shipment->setField('DEDUCTED', $ship ? 'Y' : 'N');
        }
    }

    protected function cancelledTheOrder(&$order)
    {
        $this->payTheOrder($order, false);
        $this->shipTheOrder($order, false);

        $order->setField("CANCELED", "Y");
    }

    protected function setOrderPropertyValues(&$order, $orderInfo)
    {
        $success = true;
        $externalId = $orderInfo['externalId'] ?? '';
        $shipmentDate = $orderInfo['shipmentDate'] ?? '';

        $propertyCollection = $order->getPropertyCollection();


	$utmmedium = $propertyCollection->getItemByOrderPropertyId(111);
    	$utmmedium->setValue("wb");


        $externalIdProp = $propertyCollection->getItemByOrderPropertyId(intval($this->wrappers->Option->get($this->moduleId, 'propertyOfExternalOrderNumber')));
        if ($externalIdProp && $externalId) {
            $externalIdProp->setValue($externalId);
        } else {
            $success = false;
        }

        $shipmentDateProp = $propertyCollection->getItemByOrderPropertyId(intval($this->wrappers->Option->get($this->moduleId, 'propertyOfShipmentDate')));
        if ($shipmentDateProp) $shipmentDateProp->setValue($shipmentDate);

        return $success;
    }

    protected function setCustomerData(&$order, $customerData)
    {
        $phone = $customerData['phone'] ?? '';
        $email = $customerData['customer_email'] ?? '';
        $fullName = $customerData['name'] ?? '';
        $zipCode = $customerData['address']['zip_code'] ?? '';
        $city = $customerData['address']['city'] ?? '';
        $address = $customerData['address']['address_tail'] ?? '';

        $propertyCollection = $order->getPropertyCollection();

        $phoneProp = $propertyCollection->getPhone();
        if ($phoneProp) $phoneProp->setValue($phone);

        $emailProp = $propertyCollection->getUserEmail();
        if ($emailProp) $emailProp->setValue($email);

        $userProp = $propertyCollection->getProfileName();
        if ($userProp) $userProp->setValue($fullName);

        $zipProp = $propertyCollection->getItemByOrderPropertyCode('ZIP');
        if (!$zipProp) $zipProp = $propertyCollection->getDeliveryLocationZip();
        if ($zipProp && !empty($zipCode)) $zipProp->setValue(intval($zipCode));

        $cityProp = $propertyCollection->getItemByOrderPropertyCode('CITY');
        if ($cityProp) $cityProp->setValue($city);

        $addressProp = $propertyCollection->getItemByOrderPropertyCode('ADDRESS');
        if (!$addressProp) $addressProp = $propertyCollection->getAddress();
        if ($addressProp) $addressProp->setValue($address);

        $locationCode = $this->getLocation($city);
        if ($locationCode) {
            $locationProp = $propertyCollection->getItemByOrderPropertyCode('LOCATION');
            if (!$locationProp) $locationProp = $propertyCollection->getDeliveryLocation();
            if ($locationProp) $locationProp->setValue($locationCode);
        }
    }

    protected function getLocation($city)
    {
        $query = \Bitrix\Sale\Location\LocationTable::getList(array(
            'filter' => array('NAME.NAME' => $city),
            'select' => array('CODE')
        ));
        if ($locationData = $query->fetch()) {
            return $locationData['CODE'] ?: false;
        }
    }

    protected function setDefaultCustomerData(&$order, $userId)
    {
        [$name, $phone, $email, $address] = $this->getUserData($userId);
        $propertyCollection = $order->getPropertyCollection();

        $phoneProp = $propertyCollection->getPhone();
        if ($phoneProp) $phoneProp->setValue($phone);

        $emailProp = $propertyCollection->getUserEmail();
        if ($emailProp) $emailProp->setValue($email);

        $userProp = $propertyCollection->getProfileName();;
        if ($userProp) $userProp->setValue($name);

        $addressProp = $propertyCollection->getAddress();
        if ($addressProp) $addressProp->setValue($address);
    }

    protected function getUserData($userId)
    {
        $customerId = $this->getCustomerId();

        if ($customerId) {
            $iterator = \CSaleOrderUserPropsValue::GetList(
                [],
                ["USER_PROPS_ID" => $customerId]
            );
            while ($field = $iterator->Fetch()) {
                if ($field['PROP_IS_PROFILE_NAME'] == 'Y') {
                    $name = $field['VALUE'];
                }
                if ($field['PROP_CODE'] == 'PHONE') {
                    $phone = $field['VALUE'];
                }
                if ($field['PROP_IS_EMAIL'] == 'Y') {
                    $email = $field['VALUE'];
                }
                if ($field['PROP_CODE'] == 'ADDRESS') {
                    $address = $field['VALUE'];
                }
            }
        } else {
            $userRes = \CUser::GetByID($userId ?: 1);
            $userFields = $userRes->Fetch();
            $name = ($userFields['NAME'] ?? '').' '.($userFields['LAST_NAME'] ?? '');
            $phone = $userFields['PERSONAL_PHONE'] ?? '';
            $email = $userFields['EMAIL'] ?? '';
            $address = '';
        }

        return [
            $name ?? '',
            $phone ?? '',
            $email ?? '',
            $address ?? '',
        ];
    }

    protected function setTrackNumber(&$order, $trackNumber)
    {
        $propertyCollection = $order->getPropertyCollection();
        $trackNumberProp = $propertyCollection->getItemByOrderPropertyId($this->wrappers->Option->get($this->moduleId, 'propertyOfTrackNumber'));
        if ($trackNumberProp && !empty($trackNumber)) $trackNumberProp->setValue($trackNumber);
    }

    protected function setUserDescription(&$order, $resultOrder)
    {
        $descriptions = [];

        if ($this->userDescriptionTemplate) {
            $descriptions[] = $this->applyTemplate($this->userDescriptionTemplate, $resultOrder);
        }
        $comment = $resultOrder['customer']['address']['comment'] ?? false;
        if ($comment) $descriptions[] = $comment;

        if ($descriptions) $order->setField('USER_DESCRIPTION', implode(', ', $descriptions));
    }

    protected function applyTemplate($template, $resultOrder)
    {
        $result = $template;

        foreach ($this->templateFields as $mark => $field) {
            $value = $resultOrder[$field] ?? '';
            $result = str_replace($mark, $value, $result);
        }

        return $result;
    }

    /**
     * Метод changeOrderStatuses расформирован на 3 части (вызов из класса Agents в методе getStatusesAgent):
     * 1) getOrderIdsToExternalIds в текущем классе - получение id и связанных externalId
     * 2) getStatusByExternalId в классе Api - получение статуса по externalId
     * 3) changeOrderStatus в текущем классе - конвертация и сохранение нового статуса в заказ
     *
     * Вызов цепочки методов произвадится из класса Agents в методе getStatusesAgent
     */
    public function getOrderIdsToExternalIds($flagOfAllOrders = false, $orderId = false)
    {
        $today = date('Y-m-d');
        global $DB;

        $filter = [
            '%=XML_ID' => $this->xmlIdPrefix.'_%',
            ">=DATE_INSERT" => date($DB->DateFormatToPHP(\CSite::GetDateFormat("SHORT")),  strtotime($today.' -'. $this->wrappers->Option->get($this->moduleId, 'numberOfDaysToProcessOrders') .' days')),
        ];

        if ($orderId > 0) {
            $filter['>ID'] = $orderId;
        }

        if ($flagOfAllOrders == false){
            $filter['!=CANCELED'] = 'Y';
        }

        if ($statusesFilter = $this->getFilterByCompletedStatuses()) {
            foreach ($statusesFilter as $keyFilter => $valueFilter) {
                $filter[$keyFilter] = $valueFilter;
            }
        }

        $orders = $this->wrappers->Order->getList([
            'select' => ['ID', 'STATUS_ID', 'XML_ID'],
            'filter' => $filter,
            'order' => ['ID' => 'ASC'],
        ]);
        $orderIdsToExternalIds = [];

        while ($orderResult = $orders->fetch()) {
            if (strpos($orderResult['XML_ID'], $this->xmlIdPrefix.'_') === false) continue;

            $externalId = $this->getExternalId($orderResult['ID']);
            if (!$externalId) continue;
            $orderIdsToExternalIds[$orderResult['ID']] = $externalId;
        }

        return $orderIdsToExternalIds;
    }

    public function changeOrderStatus($orderId, $statusOnTradingPlatform)
    {
        $eventResult = $this->callEvent('onBeforeChangeOrderStatus', $orderId, $statusOnTradingPlatform);
        if (!$eventResult) return;

        $success = false;
        $needSave = false;
        $statusOnBitrix = $this->convertStatusToBitrixSimilar($statusOnTradingPlatform);
        $order = $this->wrappers->Order->load($orderId);

        if ($statusOnBitrix) {
            $oldStatusOnBitrix = $order->getField('STATUS_ID');

            if ($statusOnBitrix !== $oldStatusOnBitrix) {
                // change order status
                $order->setField('STATUS_ID', $statusOnBitrix);
                $needSave = true;
            }
        }

        // change order flags
        $flagIsChanged = $this->setOrderFlags($order, $statusOnTradingPlatform);
        if (!$needSave && $flagIsChanged) $needSave = true;

        if ($needSave) {
            $result = $order->save();
            if ($result->isSuccess()) {
                $success = true;
            } else {
                $this->createReport('error_log.txt', $result->getErrors());
            }
        }

        return $success;
    }

    public function setCompletedStatuses($statuses)
    {
        $this->optionsOfCompletedStatuses = $statuses;
    }

    protected function getFilterByCompletedStatuses()
    {
        $filter = [];

        foreach ($this->optionsOfCompletedStatuses as $option) {
            $status = $this->wrappers->Option->get($this->moduleId, self::OPTION_STATUSES_SUFFIX.$option);
            if ($status && !in_array($status, $filter)) {
                $filter[] = $status;
            }
        }

        $disallowStatuses = $this->getBitrixDisallowStatuses();
        foreach ($disallowStatuses as $status) {
            if ($status == 'nothing') continue;
            $filter[] = $status;
        }

        $filter = array_unique($filter);

        return $filter ? ['!=STATUS_ID' => $filter] : [];
    }

    protected function getBitrixDisallowStatuses()
    {
        $statusesList = $this->wrappers->Option->get($this->moduleId, 'disallowStatuses');

        return explode(",", $statusesList);
    }

    protected function getExternalId($orderId)
    {
        $externalId = '';
        $order = $this->wrappers->Order->load($orderId);
        if ($order) {
            $xmlId = $order->getField("XML_ID");
            $externalId = str_replace($this->xmlIdPrefix.'_', '', $xmlId);
        }

        return $externalId;
    }

    public function convertStatusToBitrixSimilar($statusOnTradingPlatform)
    {
        $statusWithBitrix = $this->wrappers->Option->get($this->moduleId, self::OPTION_STATUSES_SUFFIX.$statusOnTradingPlatform);

        // если соответствие не выбрано
        if (!$statusWithBitrix || $statusWithBitrix == 'nothing') {
            $statusWithBitrix = null;
        }

        return $statusWithBitrix;
    }

    // need - не используется, удалить (проверить)
    public function getBitrixStatusInfo($orderId)
    {
        $order = \Bitrix\Sale\Order::load($orderId);
        if ($order) {
            $oldStatusInBitrix = $order->getField('STATUS_ID');

            $query = \Bitrix\Sale\Internals\OrderChangeTable::getList(
                [
                    'order' => array('DATE_CREATE' => 'DESC', 'ID' => 'DESC'),
                    'filter' => array('ORDER_ID' => $orderId, 'TYPE' => 'ORDER_STATUS_CHANGED'),
                    'select' => array('DATE_MODIFY'),
                ]
            );

            if ($historyItem = $query->fetch()) {
                $date = $historyItem['DATE_MODIFY'];
                $bitrixStatusChangeTime = strtotime($date->toString(new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "Y-m-d HH:i:s"))));
            }
        }

        return [
            'status' => $oldStatusInBitrix ?? false,
            'modifed' => $bitrixStatusChangeTime ?? 0,
        ];
    }

    protected function setOrderFlags(&$order, $statusOnTradingPlatform)
    {
        $paid = false;
        $ship = false;
        $flagIsChanged = false;
        $statusOnTradingPlatform = self::OPTION_STATUSES_SUFFIX.$statusOnTradingPlatform;

        // payment
        if (!$order->isPaid()) {
            $paymentFlagData = $this->wrappers->Option->get($this->moduleId, 'paymentFlag');
            $paymentFlags = explode(",", $paymentFlagData);
            if (in_array($statusOnTradingPlatform, $paymentFlags)) {
                $this->payTheOrder($order);
                $paid = true;
                $flagIsChanged = true;
            }
        }

        // shipment
        if (!$order->isShipped()) {
            $shippingFlagData = $this->wrappers->Option->get($this->moduleId, 'shippingFlag');
            $shippingFlags = explode(",", $shippingFlagData);
            if (in_array($statusOnTradingPlatform, $shippingFlags)) {
                $this->shipTheOrder($order);
                $ship = true;
                $flagIsChanged = true;
            }
        }

        // cancelled
        if (
            !$paid
            && !$ship
            && !$order->isCanceled()
        ) {
            $cancelledFlagData = $this->wrappers->Option->get($this->moduleId, 'cancelledFlag');
            $cancelledFlags = explode(",", $cancelledFlagData);
            if (in_array($statusOnTradingPlatform, $cancelledFlags)) {
                $this->cancelledTheOrder($order);
                $flagIsChanged = true;
            }
        }

        return $flagIsChanged;
    }
}
