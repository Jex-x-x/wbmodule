<?php
namespace Wbs24\Wbapi\Controller;

use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\{
    Controller as AbstractController,
    Model\OrdersCovertedStack,
    Orders\Directory
};

/**
 * Главный контроллер действий
 */
class Main extends AbstractController
{
    public function getAllowedActions(): array
    {
        return [
            'setConvertedOrders',
        ];
    }

    # action - выполняет действие по конвертации очердеи для записи обьедеиненных сборочных заданий
    protected function setConvertedOrders(array $param, array $objects = []): array
    {
        // header params
        [
            'action' => $action,
            'accountIndex' => $accountIndex,
            'orders' => $orders,
        ] = $param;

        $ordersCovertedStack = $objects['OrdersCovertedStack'] ?? new OrdersCovertedStack($accountIndex);
        $OrdersDirectory = $objects['OrdersDirectory'] ?? new Directory($accountIndex, $objects);

        // отсечь заказы, которые уже были добавлены в временную таблицу обьединенных заказов
        foreach ($orders as $key => $order) {
            if ($OrdersDirectory->get([
                'external_id' => $order['posting_number'],
            ])) {
                unset($orders[$key]);
            };
        }

        $preparedOrders = $this->mergeOrders($orders);
        $ordersCovertedStack->addOrdersToStack($preparedOrders);

        // Добавление в таблицу, содержащую перечень данных о заказах.
        foreach ($preparedOrders as $preparedOrder) {
            $externalId = $preparedOrder['posting_number'];
            $groupedExternalIds = $preparedOrder['group_external_ids'] ?? [];
            $OrdersDirectory->add($preparedOrder);

            foreach ($groupedExternalIds as $groupedExternalId) {
                $saveGroupOrder = [
                    'posting_number' => $groupedExternalId,
                    'parent_external_id' => $externalId,
                    'order_group_id' => $preparedOrder['order_group_id'],
                    'offer_id' => '',
                ];

                $OrdersDirectory->add($saveGroupOrder);
            }
        }

        return ['success' => 'Y'];
    }

    # вспомогательные функции
    protected function mergeOrders($orders) {
        $mergedOrders = [];

        foreach ($orders as $order) {
            $orderGroupId = $order['order_group_id'];

            if (isset($mergedOrders[$orderGroupId])) {
                $mergedOrders[$orderGroupId]['products'] = $this->mergeProducts(
                    $mergedOrders[$orderGroupId]['products'],
                    $order['products']
                );
                $mergedOrders[$orderGroupId]['group_external_ids'][] = $order['posting_number'];
            } else {
                $mergedOrders[$orderGroupId] = $order;
            }
        }

        return array_values($mergedOrders);
    }

    protected function mergeProducts($existingProducts, $newProducts)
    {
        foreach ($newProducts as $newProduct) {
            $found = false;
            foreach ($existingProducts as &$existingProduct) {

                $existingOfferIds = is_array($existingProduct['offer_id'])
                ? $existingProduct['offer_id']
                : [$existingProduct['offer_id']];

                $newOfferIds = is_array($newProduct['offer_id'])
                ? $newProduct['offer_id']
                : [$newProduct['offer_id']];

                $isDifferent = array_diff($existingOfferIds, $newOfferIds);
                if (!$isDifferent) {
                    $existingProduct['quantity'] += $newProduct['quantity'];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $existingProducts[] = $newProduct;
            }
        }

        return $existingProducts;
    }
}
