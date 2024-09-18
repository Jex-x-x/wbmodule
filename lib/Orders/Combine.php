<?php
namespace Wbs24\Wbapi\Orders;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Orders;
use Wbs24\Wbapi\Db;

/**
 * DEPRECATED
 * Класс устарел и почти все функции из него больше не используются
 * Однако подключение класса Orders в некоторых местах еще идет через этот класс и некотоыре функции еще используются
 */
class Combine extends Orders
{
    protected $OrdersDirectory;

    public function __construct($objects = [])
    {
        parent::__construct($objects);

        $accountIndex = $this->wrappers->Option->getAccountIndex();
        $this->OrdersDirectory = $objects['OrdersDirectory'] ?? new Directory($accountIndex, $objects);
        $this->Db = $objects['Db'] ?? new Db($objects); // только для fixBasketElement - возможно лучше потом переделать
    }

    /*
    DEPRECATED
    public function updateAndCreateOrders($ordersFromTradingPlatform)
    {
        $this->createReport('create_orders_log.txt', 'Start creating new orders');

        $startTime = time();

        foreach ($ordersFromTradingPlatform as $resultOrder) {
            if ($parentExternalId = $this->getParentExternalId($resultOrder)) {
                // если такой заказ уже есть
                if ($this->OrdersDirectory->get([
                    'external_id' => $resultOrder['posting_number'],
                ])) continue;

                // комбинировать
                // запись в справочник
                $resultOrder['parent_external_id'] = $parentExternalId;
                $this->OrdersDirectory->add($resultOrder);
                // добавление товара в заказ
                $this->combineOrder($resultOrder, $parentExternalId);
            } else {
                // запись в справочник
                $this->OrdersDirectory->add($resultOrder);
                // создать новый
                $this->createOrder($resultOrder);
            }

            if ((time() - $startTime) > $this->maxDuring) break;
        }
    }
    */

    /*
    DEPRECATED
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

            // проверка корзины
            [
                'basketDiffProducts' => $basketDiffProducts,
                'offerIdsToProductIds' => $offerIdsToProductIds,
            ] = $this->getBasketDifference($basket, $externalId);

            if (!empty($basketDiffProducts)) {
                // добавить ошибку в заказ
                $errorMessage = $this->getErrorBasketMessage($basketDiffProducts, $offerIdsToProductIds);
                $this->addOrderError($order, $errorMessage, 'basket_is_wrong');
            }

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
    */

    public function getCombinedExternalIds($parentExternalIds)
    {
        $combinedExternalIds = [];
        foreach ($parentExternalIds as $externalId) {
            $result = $this->OrdersDirectory->get([
                'parent_external_id' => $externalId,
            ]);
            foreach ($result as $entry) {
                $combinedId = $entry['external_id'];
                if ($combinedId != $externalId) {
                    $combinedExternalIds[] = $combinedId;
                }
            }
        }

        return $combinedExternalIds;
    }

    protected function getParentExternalId($resultOrder)
    {
        $result = $this->OrdersDirectory->get([
            'order_group_id' => $resultOrder['order_group_id'],
        ]);

        $parentExternalId = $result[0]['parent_external_id'] ?? false;

        return $parentExternalId;
    }

    /*
    DEPRECATED
    protected function combineOrder($resultOrder, $parentExternalId)
    {
        $success = false;
        $orderId = $this->getOrderIdByExternalId($parentExternalId);
        $order = $this->wrappers->Order->load($orderId);

        // добавить новый товар в заказ
        $basket = $order->getBasket();
        $this->updateBasket($order, $basket, $resultOrder['products']);
        $basket->refresh();

        // проверка корзины
        [
            'basketDiffProducts' => $basketDiffProducts,
            'offerIdsToProductIds' => $offerIdsToProductIds,
        ] = $this->getBasketDifference($basket, $parentExternalId);

        $result = $order->save();
        if (!$result->isSuccess()) {
            $error = $result->getErrorMessages();
            $this->createReport('error_log.txt', $error);
        } else {
            // success
            $success = true;
            $this->createReport('create_orders_log.txt', 'Updating the order '.$parentExternalId.' is success');
        }

        // если была ошибка при проверке корзины
        if (!empty($basketDiffProducts)) {
            // попытка исправить
            $this->fixBasketElement($orderId, $basketDiffProducts, $offerIdsToProductIds);

            // повторная проверка (с повторной загрузкой сущности заказа)
            $order = $this->wrappers->Order->load($orderId);
            $basket = $order->getBasket();
            [
                'basketDiffProducts' => $basketDiffProducts,
                'offerIdsToProductIds' => $offerIdsToProductIds,
            ] = $this->getBasketDifference($basket, $parentExternalId);

            if (!empty($basketDiffProducts)) {
                // добавить ошибку в заказ
                $errorMessage = $this->getErrorBasketMessage($basketDiffProducts, $offerIdsToProductIds);
                $this->addOrderError($order, $errorMessage, 'basket_is_wrong');

                // повторное сохранение заказа
                $result = $order->save();
                if (!$result->isSuccess()) {
                    $error = $result->getErrorMessages();
                    $this->createReport('error_log.txt', $error);
                }
            } else {
                // success
                $success = true;
                $this->createReport('create_orders_log.txt', 'Add error message to the order '.$parentExternalId);
            }
        }

        return $success;
    }
    */

    /*
    DEPRECATED
    protected function updateBasket(&$order, &$basket, $products)
    {
        $newProducts = [];
        foreach ($products as $product) {
            $fields = $this->getProductFields($product);
            $productId = $fields['PRODUCT_ID'];
            $basketItem = $this->getBasketItemByProductId($basket, $productId);
            if ($basketItem) {
                $this->updateBasketItem($basket, $basketItem, $product);
            } else {
                $newProducts[] = $product;
            }
        }

        if ($newProducts) {
            $this->addProductsToBasket($order, $basket, $newProducts);
        }

        $fuserId = $basket->getFUserId();
        if (!$fuserId) $basket->setFUserId(\Bitrix\Sale\Fuser::getId());
    }
    */

    /*
    DEPRECATED
    protected function getBasketItemByProductId(&$basket, $productId)
    {
        if ($this->notLinkedProductId == $productId) return;

        $foundBasketItem = null;
        foreach ($basket as $basketItem) {
            if ($basketItem->getProductId() == $productId) {
                $foundBasketItem = $basketItem;
                break;
            }
        }

        return $foundBasketItem;
    }
    */

    /*
    DEPRECATED
    protected function updateBasketItem(&$basket, &$basketItem, $product)
    {
        $currentQuantity = $basketItem->getQuantity();
        $addQuatity = $product['quantity'] ?? 0;
        $newQuantity = $currentQuantity + $addQuatity;
        $basketItem->setField('QUANTITY', $newQuantity);
    }
    */

    /**
     * Найти товары которых не хватает в корзине
     */

    /*
    DEPRECATED
    protected function getBasketDifference(&$basket, $parentExternalId)
    {
        $basketProducts = [];
        $mpProducts = $this->getProductsFromOrdersDirectory($parentExternalId);
        $offerIdsToProductIds = [];

        // получить список позиций из корзины заказа
        foreach ($basket as $item) {
            $productId = $item->getProductId();
            if ($productId == $this->notLinkedProductId) continue;
            $offerId = $this->Product->getOfferIdByProductId($productId);
            if (!$offerId) continue;
            $offerIdsToProductIds[$offerId] = $productId;
            $quantity = $item->getQuantity();
            $basketProducts[$offerId] = intval($quantity);
        }

        // сравнение с данными из справочника заказов
        $basketDiffProducts = $this->getNotEnoughProdicts($mpProducts, $basketProducts);

        return [
            'basketDiffProducts' => $basketDiffProducts,
            'offerIdsToProductIds' => $offerIdsToProductIds,
        ];
    }
    */

    /**
     * Сравнение двух списков товаров. Первый список эталонный - взят из справочника заказов.
     * Возвращает массив с элементами вида [offer_id => quantity],
     * где quantity - количество товара, которого не хватает в втором параметре
     */
    protected function getNotEnoughProdicts($mpProducts, $basketProducts)
    {
        $diff = array_diff_assoc($mpProducts, $basketProducts);
        foreach ($diff as $offerId => $quantity) {
            $quanInBasket = $basketProducts[$offerId] ?? 0;
            $deltaQuan = $quantity - $quanInBasket;
            $diff[$offerId] = $deltaQuan;
        }

        return $diff;
    }

    /**
     * Получить массив товаров из справочника заказов (формат элемента: [offer_id => quantity])
     */

    /*
    DEPRECATED
    protected function getProductsFromOrdersDirectory($parentExternalId)
    {
        $mpProducts = [];
        $orderInfo = $this->OrdersDirectory->get([
            'parent_external_id' => $parentExternalId,
        ]);

        foreach ($orderInfo as $item) {
            $offerId = $item['offer_id'] ?? false;
            if (!$offerId) continue; // если offer_id не сохранен, то не добавляем запись в проверочный массив
            if (isset($mpProducts[$offerId])) {
                $mpProducts[$offerId] += 1;
            } else {
                $mpProducts[$offerId] = 1;
            }
        }

        return $mpProducts;
    }
    */

    /**
     * Функция прямого фикса элемента корзины (в обход API) - возможно лучше отказаться в будущем
     */

    /*
    DEPRECATED
    protected function fixBasketElement($orderId, $basketDiffProducts, $offerIdsToProductIds)
    {
        foreach ($basketDiffProducts as $offerId => $quantity) {
            $productId = $offerIdsToProductIds[$offerId] ?? false;
            if (!$productId) continue;
            $this->Db->query(
                'UPDATE `b_sale_basket` SET `QUANTITY` = `QUANTITY` + '.$quantity.' WHERE `ORDER_ID` = '.$orderId.' AND `PRODUCT_ID` = '.$productId
            );
        }
    }
    */

    /**
     * Получить текст ошибки корзины
     */

    /*
    DEPRECATED
    protected function getErrorBasketMessage($basketDiffProducts, $offerIdsToProductIds)
    {
        $suffix = strtoupper($this->moduleId);
        $errorMessage = Loc::getMessage($suffix.".BASKET_IS_WRONG");
        $firstEl = true;

        foreach ($basketDiffProducts as $offerId => $quantity) {
            $errorMessage .=
                ($firstEl ? '' : '; ')
                .(isset($offerIdsToProductIds[$offerId]) ? 'ID = '.$offerIdsToProductIds[$offerId].', ' : '')
                .'OFFER_ID = '.$offerId.', '
                .$quantity.' '.Loc::getMessage($suffix.".PCS")
            ;
            $firstEl = false;
        }

        return $errorMessage;
    }
    */
}
