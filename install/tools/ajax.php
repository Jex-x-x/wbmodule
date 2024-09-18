<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;
use Wbs24\Wbapi\Api\Controller;

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);
// Action
$action = $request->getQuery("ACTION");
$postingNumber = $request->getQuery("posting_number");
$accountIndex = $request->getQuery("account_index");
$orderId = $request->getQuery("order_id");
// collect_order
$onePackage = $request->getQuery("one_package");
$packages = $request->getQuery("packages");
$packages = json_decode($packages, true);
// create_act
$deliveryMethodId = $request->getQuery("delivery_method_id");
// get_act
$actCreateId = $request->getQuery("act_сreate_id");

// выключатель кнопок для следующих перезагрузок заказа
$disabled = $request->getQuery("disabled");

$data = [
    'action' => $action,
    'order_id' => $orderId,
    'packages' => $packages,
    'one_package' => $onePackage,
    'posting_number' => $postingNumber,
    'account_index' => $accountIndex ?: 1,
    'delivery_method_id' => $deliveryMethodId,
    'act_сreate_id' => $actCreateId,
    'disabled' => $disabled,
];

if (Loader::includeModule('wbs24.wbapi')) {
    $main = new Main();
    if (!$main->checkRights()) die();

    $controller = new Controller();
    $result = $controller->action($data);

    echo $result; // тут может вернуться не только success, но и например ссылка на pdf
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
