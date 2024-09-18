<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);
$accountIndex = intval($request["account_index"] ?? 1);

if (Loader::includeModule('wbs24.wbapi')) {
    $curlExecObject = new CurlExec($accountIndex);
    $curlExecObject->getOrders();
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
