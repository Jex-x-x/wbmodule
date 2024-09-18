<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);
// Action
$action = $request->getQuery("ACTION");
$userId = $request->getQuery('user_id');

$data = [
    'action' => $action,
    'user_id' => $userId,
];

if (Loader::includeModule('wbs24.wbapi')) {
    $main = new Main();
    if (!$main->checkRights()) die();

    $controller = new OptionController();
    $json = $controller->action($data);

    echo $json;
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
