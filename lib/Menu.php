<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Localization\Loc;

/**
 * Класс для вывода административного меню
 */
class Menu
{
    protected $main;
    protected $Profiles;
    protected $moduleId;

    public function __construct($objects = [])
    {
        $this->main = $objects['Main'] ?? new Main();
        $this->Profiles = $objects['Profiles'] ?? new Profiles();
        $this->moduleId = $this->main->getModuleId();
    }

    public static function OnBuildGlobalMenuHandler(&$aGlobalMenu, &$aModuleMenu) {
        if (!isset($GLOBALS['USER']) || !$GLOBALS['USER']->IsAdmin()) return;

        $menu = new Menu();
        $menu->build($aGlobalMenu, $aModuleMenu);
    }

    public function build(&$aGlobalMenu, &$aModuleMenu)
    {
        $GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/css/".$this->moduleId."/styles.css");

        $suffix = strtoupper($this->moduleId);
        $menuKey = 'global_menu_wbs24';
        $subMenuKey = 'wb';
        $subMenuIcon = 'catalog';
        $profiles = $this->Profiles->getList();

        // главный узел
        if (!isset($aGlobalMenu[$menuKey])) {
            $aGlobalMenu[$menuKey] = [
                'menu_id' => 'wbs24_head',
                'text' => Loc::getMessage($suffix.".WEB_SYMPHONY"),
                'title' => Loc::getMessage($suffix.".WEB_SYMPHONY"),
                'sort' => 450,
                'items_id' => $menuKey,
                'items' => [],
            ];
        }

        // подраздел
        if (!isset($aGlobalMenu[$menuKey][$subMenuKey])) {
            $aGlobalMenu[$menuKey]['items'][$subMenuKey] = [
                'sort' => 10,
                'text' => Loc::getMessage($suffix.".WB_TITLE"),
                'title' => Loc::getMessage($suffix.".WB_TITLE"),
                'icon' => $subMenuIcon,
                'page_icon' => $subMenuIcon,
                'items_id' => $subMenuKey.'_options',
                'items' => [],
            ];
        }

        $modulePrefix = str_replace(".", "_", $this->moduleId);

        // пункты меню подраздела
        // аккаунты
        foreach ($profiles as $profileId) {
            $menuItems = [
                [
                    'sort' => 10,
                    'url' => '/bitrix/admin/'.$modulePrefix.'_profile_main.php?'
                        .'profile_id='.$profileId
                        .'&mid='.$this->moduleId
                        .'&options_type=base'
                    ,
                    'text' => Loc::getMessage($suffix.".ACCOUNT_MAIN_SETTINGS"),
                    'title' => Loc::getMessage($suffix.".ACCOUNT_MAIN_SETTINGS"),
                    'icon' => '',
                    'page_icon' => '',
                    'items_id' => 'profile_'.$profileId.'_main',
                ],
                [
                    'sort' => 20,
                    'url' => '/bitrix/admin/'.$modulePrefix.'_profile_order.php?'
                        .'profile_id='.$profileId
                        .'&mid='.$this->moduleId
                        .'&options_type=order'
                    ,
                    'text' => Loc::getMessage($suffix.".ACCOUNT_ORDER_SETTINGS"),
                    'title' => Loc::getMessage($suffix.".ACCOUNT_ORDER_SETTINGS"),
                    'icon' => '',
                    'page_icon' => '',
                    'items_id' => 'profile_'.$profileId.'_order',
                ],
                [
                    'sort' => 30,
                    'url' => '/bitrix/admin/'.$modulePrefix.'_profile_price.php?'
                        .'profile_id='.$profileId
                        .'&mid='.$this->moduleId
                        .'&options_type=price'
                    ,
                    'text' => Loc::getMessage($suffix.".ACCOUNT_PRICE_SETTINGS"),
                    'title' => Loc::getMessage($suffix.".ACCOUNT_PRICE_SETTINGS"),
                    'icon' => '',
                    'page_icon' => '',
                    'items_id' => 'profile_'.$profileId.'_price',
                ],
                [
                    'sort' => 30,
                    'url' => '/bitrix/admin/'.$modulePrefix.'_profile_stock.php?'
                    .'profile_id='.$profileId
                    .'&mid='.$this->moduleId
                    .'&options_type=stock'
                ,
                    'text' => Loc::getMessage($suffix.".ACCOUNT_STOCK_SETTINGS"),
                    'title' => Loc::getMessage($suffix.".ACCOUNT_STOCK_SETTINGS"),
                    'icon' => '',
                    'page_icon' => '',
                    'items_id' => 'profile_'.$profileId.'_stock',
                ],
            ];

            $aGlobalMenu[$menuKey]['items'][$subMenuKey]['items'][] = [
                'sort' => (100 + $accountId * 10),
                'text' => Loc::getMessage($suffix.".ACCOUNT").' ['.$profileId.']',
                'title' => Loc::getMessage($suffix.".ACCOUNT").' ['.$profileId.']',
                'icon' => '',
                'page_icon' => '',
                'items_id' => 'profile_'.$profileId,
                'items' => $menuItems,
            ];
        }

        // Права доступа
        $aGlobalMenu[$menuKey]['items'][$subMenuKey]['items'][] = [
            'sort' => 0,
            'url' => '/bitrix/admin/'.$modulePrefix.'_group_rights.php?'
                .'&mid='.$this->moduleId
                .'&options_type=group_rights'
            ,
            'text' => Loc::getMessage($suffix.".GROUP_RIGHTS"),
            'title' => Loc::getMessage($suffix.".GROUP_RIGHTS"),
            'icon' => '',
            'page_icon' => '',
            'items_id' => 'group_rights',
        ];

        // Инструкция
        $aGlobalMenu[$menuKey]['items'][$subMenuKey]['items'][] = [
            'sort' => 0,
            'url' => '/bitrix/admin/'.$modulePrefix.'_instruction.php',
            'text' => Loc::getMessage($suffix.".INSTRUCTION"),
            'title' => Loc::getMessage($suffix.".INSTRUCTION"),
            'icon' => '',
            'page_icon' => '',
            'items_id' => 'instruction',
        ];
    }
}
