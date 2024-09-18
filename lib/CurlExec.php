<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;

class CurlExec {
    use Accounts; // trait

    protected $main;
    protected $moduleId;
    protected $wrappers;
    protected $accountIndex;

    protected $OrdersStack;
    protected $Orders;
    protected $ApiController;

    protected $allowCombineOrders;

    public function __construct($accountIndex = 1, $objects = [])
    {
        $this->main = $objects['Main'] ?? new Main();
        $this->moduleId = $this->main->getModuleId();
        $this->wrappers = new Wrappers($objects);
        $this->accountIndex = $accountIndex;

        $dependencies = $this->getDependencies($accountIndex);

        $this->OrdersStack = $objects['OrdersStack'] ?? new OrdersStack($accountIndex);
        $this->OrdersCovertedStack = $objects['OrdersStack'] ?? new Model\OrdersCovertedStack($accountIndex);
        $this->Orders = $objects['Orders'] ?? new Orders\Combine($dependencies);
        $this->ApiController = $objects['ApiController'] ?? new Api\Controller();

        $this->allowCombineOrders = ($this->wrappers->Option->get($this->moduleId, 'allowCombineOrders') == 'Y');
    }

    public function getOrders()
    {
        if ($this->allowCombineOrders) {
            // Получить обьединенные заказы
            $orders = $this->OrdersCovertedStack->getOrders();
        } else {
            // Получить одиночные заказы
            $orders = $this->OrdersStack->getOrders();
        }

        if ($orders) {
            $this->Orders->createNewOrders($orders);
        }
    }

    public function getStatuses($lastOrderId = 0)
    {
        ini_set('display_errors', 'Off');

        $orderIdsToExternalIds = $this->Orders->getOrderIdsToExternalIds(true, $lastOrderId);


        $maxDuring = 30;
        $step = 50;
        $counter = 0;
		$startTime = time();
        $lastOrderId = '';

        $externalIds = [];
        foreach ($orderIdsToExternalIds as $orderId => $externalId) {
            $externalIds[] = (int)$externalId;
            $counter++;
            if ($counter >= 50) {
                if ($externalId != end($orderIdsToExternalIds)) $lastOrderId = $orderId;
                break;
            }
        }

        $externalIdsToStatus = [];
        if ($externalIds) {
            $externalIdsToStatus = $this->ApiController->action([
                'action' => 'get_statuses',
                'account_index' => $this->accountIndex,
                'order_ids' => $externalIds,
            ]);
        }

        if ($externalIdsToStatus) {
            foreach ($orderIdsToExternalIds as $orderId => $externalId) {
                $statusOnTradingPlatform = $externalIdsToStatus[$externalId] ?? false;
                if ($statusOnTradingPlatform) {
                    $this->Orders->changeOrderStatus($orderId, $statusOnTradingPlatform);
                }

                if ((time() - $startTime) > $maxDuring) {
                    $lastOrderId = $orderId;
                    break;
                }
            }
        }

        return $lastOrderId;
    }
}
