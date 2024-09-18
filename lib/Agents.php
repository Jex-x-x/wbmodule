<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;
use Wbs24\Wbapi\{
    Api,
    Controller\Main as MainController,
    Model\OrdersCovertedStack
};

class Agents {
    use Exception; // trait
    use Accounts; // trait

    protected $main;
    protected $moduleId;
    protected $wrappers;

    protected $postfixAgentNames = [
        '::getOrdersAgent();',
        '::ordersCurlExecAgent();',
        '::cleanOrdersStack();',
        '::statusesCurlExecAgent(0);',
    ];

    public function __construct($objects = [])
    {
        try {
            $this->main = $objects['Main'] ?? new Main();
            $this->moduleId = $this->main->getModuleId();
            $this->wrappers = new Wrappers($objects);
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function updateAgents(array $accounts = [1])
    {
        // проверка условий, если какие то важные настройки не выбраны, агенты создаваться не будут
        $this->wrappers->CAgent->RemoveModuleAgents($this->moduleId);

        foreach ($accounts as $accountIndex) {
            $this->wrappers->Option->setPrefix($accountIndex);
            if (!$this->agentCheckOn()) continue;
            if (empty($this->wrappers->Option->get($this->moduleId, 'apiKey'))) continue;
            if (
                empty($this->wrappers->Option->get($this->moduleId, 'scheduler_interval'))
                || !is_numeric($this->wrappers->Option->get($this->moduleId, 'scheduler_interval'))
            ) continue;

            $valuesShouldNotBeNothing = [
                'isHttps',
                'siteId',
                'personTypeId',
                'deliveryServiceId',
                'paymentSystemId',
                'propertyOfExternalOrderNumber',
                'skuPropertyForProducts',
                'skuPropertyForProductOffers',
            ];
            $valueIsNothing = false;
            foreach ($valuesShouldNotBeNothing as $value) {
                if ($this->wrappers->Option->get($this->moduleId, $value) == 'nothing') {
                    $valueIsNothing = true;
                    break;
                }
            }
            if ($valueIsNothing) continue;

            foreach ($this->postfixAgentNames as $postfixAgentName) {
                if ($accountIndex > 1) {
                    $postfixAgentName = str_replace("(0)", "(0, ${accountIndex})", $postfixAgentName);
                    $postfixAgentName = str_replace("()", "(${accountIndex})", $postfixAgentName);
                }

                $this->updateAgent($postfixAgentName);
            }
        }
    }

    public function updateAgent(string $postfixAgentName) {
        $agentName = $this->getNameAgent($postfixAgentName);
        $interval = intval($this->wrappers->Option->get($this->moduleId, "scheduler_interval"));
        $this->addAgent($agentName, $interval);
    }

    public function addAgent($agentName, $interval)
    {
        if ($interval) {
            $interval *= 60;

            $this->wrappers->CAgent->AddAgent(
                $agentName,
                $this->moduleId,
                "N",
                $interval
            );
        }
    }

    public function getNameAgent(string $postfixAgentName): string
    {
        return "\\".__CLASS__.$postfixAgentName;
    }

    protected function agentCheckOn(): bool
    {
        return $this->wrappers->Option->get($this->moduleId, "scheduler_is_on") == "Y" ? true : false;
    }

    public static function getOrdersAgent($accountIndex = 1)
    {
        $agents = new Agents();
        $dependencies = $agents->getDependencies($accountIndex);

        $controllerObject = new Api\Controller();
        $ordersObject = new Orders($dependencies);
        $ordersStackObject = new OrdersStack($accountIndex);
        $MainController = new MainController($accountIndex);


        // api request
        $orders = $controllerObject->action([
            'action' => 'get_orders',
            'account_index' => $accountIndex,
        ]);

        $ordersToTemporaryStack = [];
        foreach ($orders as $order) {
            if (!$ordersObject->checkTheOrderForUniqueness($order['posting_number'])) {
                $ordersToTemporaryStack[] = $order;
            }
        }

        if (!empty($ordersToTemporaryStack)) {
            $ordersStackObject->addOrdersToStack($ordersToTemporaryStack);
            $MainController->process([
                'action' => 'setConvertedOrders',
                'accountIndex' => $accountIndex,
                'orders' => $ordersToTemporaryStack
            ]);
        }

        return '\\'.__CLASS__.'::'.__FUNCTION__.'('.$accountIndex.');';
    }

    public static function ordersCurlExecAgent($accountIndex = 1)
    {
        try {
            $agents = new Agents();
            $agents->setAccount($accountIndex);

            $domainName = $agents->getDomainName();
            $isHttps = $agents->checkIsHttps();
            $url =
                'http'.($isHttps ? 's' : '')
                .'://'.$domainName
                .'/bitrix/tools/wbs24.wbapi/orderscurlexec.php?account_index='.$accountIndex
            ;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 45);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            if ($error) {
                $result = 'cURL Error: ' . $error;
            }
        } catch (SystemException $exception) {
            $agents->exceptionHandler($exception);
        }

        return '\\'.__CLASS__.'::'.__FUNCTION__.'('.$accountIndex.');';
    }

    public static function cleanOrdersStack($accountIndex = 1)
    {
        $agents = new Agents();
        $dependencies = $agents->getDependencies($accountIndex);

        $ordersObject = new Orders\Combine($dependencies);
        $ordersStackObject = new OrdersStack($accountIndex);

        $OrdersCovertedStack = new OrdersCovertedStack($accountIndex);

        $flagOfAllOrders = true;

        // Очистка основного стека временных заказов
        $orderIdsToExternalIds = $ordersObject->getOrderIdsToExternalIds($flagOfAllOrders);
        $externalIds = array_values($orderIdsToExternalIds);
        $combinedExternalIds = $ordersObject->getCombinedExternalIds($externalIds);
        $allExternalIds = array_unique(array_merge($externalIds, $combinedExternalIds));
        $ordersStackObject->cleanStack($allExternalIds);

        // Очистка стека временных заказов с группированными заказами
        $OrdersCovertedStack->cleanStack($externalIds);

        return '\\'.__CLASS__.'::'.__FUNCTION__.'('.$accountIndex.');';
    }

    public static function statusesCurlExecAgent($lastOrderId = 0, $accountIndex = 1)
    {
        try {
            $agents = new Agents();
            $agents->setAccount($accountIndex);

            $domainName = $agents->getDomainName();
            $isHttps = $agents->checkIsHttps();
            $url =
                'http'.($isHttps ? 's' : '')
                .'://'.$domainName
                .'/bitrix/tools/wbs24.wbapi/statusescurlexec.php?last_order_id='.$lastOrderId.'&account_index='.$accountIndex
            ;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 45);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            if ($error) {
                $result = 'cURL Error: ' . $error;
            }

            if ($result) {
                if (is_numeric($result)) {
                    $lastOrderId = $result;
                } else {
                    $agents->createReport('error_log.txt', 'request: '.$result);
                }
            } else {
                $lastOrderId = 0;
            }
        } catch (SystemException $exception) {
            $agents->exceptionHandler($exception);
        }

        return '\\'.__CLASS__.'::'.__FUNCTION__.'('.$lastOrderId.', '.$accountIndex.');';
    }

    public function getDomainName()
    {
        $query = \Bitrix\Main\SiteTable::getList();
        while ($site = $query->Fetch()) {
            if ($site['LID'] == $this->wrappers->Option->get($this->moduleId, 'siteId')) {
                $domainName = $site['SERVER_NAME'];
            }
        }

        return $domainName;
    }

    public function checkIsHttps()
    {
        return $this->wrappers->Option->get($this->moduleId, "isHttps") == "Y" ? true : false;
    }
}
