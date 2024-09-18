<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

Loc::loadMessages(__FILE__);
$suffix = 'WBS24.WBAPI';
?>
<style>
    .wbs24_module_instructions {
        display: flex;
    }
    .wbs24_module_instructions a {
        display: block;
        flex: 0 1 33.333%;
        cursor: pointer;
        padding: 30px;
        margin-right: 10px;
        width: 50%;
        text-align: center;
        border-radius: 10px;
        color: #4b6267;
        font-size: 14px;
        font-weight: 700;
        text-shadow: 0 1px #fff;
        background-color: #e0e8ea;
        box-shadow: 0px 2px 2px 0px rgba(0, 0, 0, 0.25);
        text-decoration: none;
    }
    .wbs24_module_instructions a:last-child {
        margin-right: 0;
    }
    .wbs24_module_instructions a:hover {
        background-color: #c2d0d3;
    }
</style>

<div class="wbs24_module_instructions">
    <a href="https://wbs24.ru/info/instructions/wb-api/ustanovka-modulya-wb-api/" target="_blank"><?=Loc::getMessage($suffix.'.MODULE_INSTALLATION_INSTRUCTION')?></a>
    <a href="https://wbs24.ru/info/instructions/wb-api/nastroyka-modulya-wb-api/" target="_blank"><?=Loc::getMessage($suffix.'.MODULE_SETTING_INSTRUCTION')?></a>
    <a href="https://wbs24.ru/info/instructions/wb-api/rasprostranennye-trudnosti-wb-api/" target="_blank"><?=Loc::getMessage($suffix.'.MODULE_COMMON_DIFFICULTIES_INSTRUCTION')?></a>
</div>

