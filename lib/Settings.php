<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

class Settings {
    use Exception; // trait

    public const DONT_DIE = true;

    protected $main;
    protected $moduleId;
    protected $wrappers;

    protected $lastError;

    protected $cachedProperties;

    public function __construct($objects = [])
    {
        try {
            if (!Loader::IncludeModule('sale')) {
                throw new SystemException("Sale module isn`t installed");
            }
            if (!Loader::IncludeModule('iblock')) {
                throw new SystemException("Iblock module isn`t installed");
            }
            if (!Loader::IncludeModule('catalog')) {
                throw new SystemException("Catalog module isn`t installed");
            }

            $this->main = $objects['Main'] ?? new Main();
            $this->moduleId = $this->main->getModuleId();
            $this->wrappers = new Wrappers($objects, self::DONT_DIE);
            $this->StringTemplate = $objects['StringTemplate'] ?? new StringTemplate();
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception, self::DONT_DIE);
            $this->lastError = $exception->getMessage();
        }
    }

    public function loadJs($accounts)
    {
        echo '<script src="/bitrix/js/'.$this->moduleId.'/admin.js?'.time().'"></script>';
        echo '<script src="/bitrix/js/'.$this->moduleId.'/handlers.js?'.time().'"></script>';
        echo '<script src="/bitrix/js/'.$this->moduleId.'/adminHandlers.js?'.time().'"></script>';
        echo '<script src="/bitrix/js/'.$this->moduleId.'/stringTemplate.js?'.time().'"></script>';
        echo
            '<script>'
            .'{'
                .'const handlers = new Wbs24WbapiHandlers();'
                .'handlers.addHandlersForAccounts('.\CUtil::PhpToJSObject($accounts).');'
            .'}'
            .'</script>'
        ;
        echo
            '<script>'
            .'{'
                .'const adminHandlers = new Wbs24WbapiAdminHandlers();'
                .'adminHandlers.start('.\CUtil::PhpToJSObject($accounts).');'
            .'}'
            .'</script>'
        ;
    }

    public function loadCss()
    {
        global $APPLICATION;

        $APPLICATION->SetAdditionalCSS("/bitrix/css/".$this->moduleId."/style.css?220712");
    }

    public function getLastError()
    {
       return $this->lastError;
    }

    public function getAccounts()
    {
        $accountsAsString = $this->wrappers->Option->get($this->moduleId, '_accounts');
        if (!$accountsAsString) $accountsAsString = '1';
        $accounts = explode(',', $accountsAsString);

        return $accounts;
    }

    public function addAccount()
    {
        $accounts = $this->getAccounts();
        $lastKey = array_key_last($accounts);
        $newAccount = intval($accounts[$lastKey]) + 1;
        $accounts[] = $newAccount;
        $accountsAsString = implode(',', $accounts);
        $this->wrappers->Option->set($this->moduleId, '_accounts', $accountsAsString);

        return $newAccount;
    }

    public function deleteAccount($deleteIndex)
    {
        $accounts = $this->getAccounts();
        foreach ($accounts as $key => $index) {
            if ($index == $deleteIndex) unset($accounts[$key]);
        }
        $accountsAsString = implode(',', $accounts);
        $this->wrappers->Option->set($this->moduleId, '_accounts', $accountsAsString);
    }

    public function getDays() {
        $days = [];
        for ($i = 10; $i < 61; $i+=5) {
            $days[$i] = $i;
        }

        return $days;
    }

    public function getMinutes() {
        $minutes = [];
        for ($i = 1; $i < 61; $i++) {
            $minutes[$i] = $i;
        }

        return $minutes;
    }

    public function getUsers()
    {
        $users = [];
        $users['nothing'] = Loc::getMessage("WBS24.WBAPI.NOT_SELECTED");
        $query = \Bitrix\Main\UserTable::getList(array(
            'select' => ['ID','LOGIN'],
        ));

        while ($user = $query->fetch()) {
            $users[$user['ID']] = $user['LOGIN'];
        }

        return $users;
    }

    public function getCustomerIds($userId) {
        $customers = [];
        $customers[0] = Loc::getMessage("WBS24.WBAPI.NOT_SELECTED");

        if ($userId) {
            $query = \CSaleOrderUserProps::GetList(
                array(
                    "DATE_UPDATE" => "DESC"
                ),
                array(
                    'USER_ID' => $userId
                )
            );
            while ($customer = $query->Fetch()) {
                $customers[$customer['ID']] = $customer['NAME'];
            }
        }

        return $customers;
    }

    public function getSiteId() {
        if ($this->cachedSiteId !== null) return $this->cachedSiteId;
        $siteId = null;
        $sites['nothing'] = Loc::getMessage("WBS24.WBAPI.NOT_SELECTED");
        $query = \Bitrix\Main\SiteTable::getList();
        while ($site = $query->Fetch()) {
            $sites[$site['LID']] = $site['LID'];
        }

        $this->cachedSiteId = $sites;

        return $sites;
    }

    public function getAllOrderStatuses()
    {
        if ($this->lastError) return [];

        $nameStatuses = [];
        $nameStatuses['nothing'] = Loc::getMessage("WBS24.WBAPI.NOT_SELECTED");
        $statuses = \CSaleStatus::GetList(
            [],
            [],
            false,
            false,
            ['ID', 'NAME']
        );

        while($status = $statuses->Fetch()) {
            $nameStatuses[$status['ID']] = '[' . $status['ID'] . '] ' . $status['NAME'];
        }

        return $nameStatuses;
    }

    public function getPersonType()
    {
        if ($this->lastError) return [];
        if ($this->cashedAllTypesOfPerson) return $this->cashedAllTypesOfPerson;

        $allTypesOfPerson = [];

        $resultTypesOfPerson = \CSalePersonType::GetList(['SORT' => 'ASC']/*, ['ACTIVE' => 'Y'] */);

        while ($personData = $resultTypesOfPerson->Fetch()) {
            foreach ($personData['LIDS'] as $lid) {
                $allTypesOfPerson[$lid][$personData['ID']] = $personData['NAME'];
            }
        }

        $this->cashedAllTypesOfPerson = $allTypesOfPerson;

        return $allTypesOfPerson;
    }

    public function getDeliveryService()
    {
        if ($this->lastError) return [];

        $allDeliveryService = [];
        $allDeliveryService['nothing'] = Loc::getMessage("WBS24.WBAPI.NOT_SELECTED");
        $query = \Bitrix\Sale\Delivery\Services\Table::getList(
            [
                'select' => ['ID', 'NAME'],
            ]
        );
        while ($delivery = $query->Fetch()) {
            $allDeliveryService[$delivery['ID']] = $delivery['NAME'];
        }

        return $allDeliveryService;
    }

    public function getPaymentSystem()
    {
        if ($this->lastError) return [];

        $allPaymentSystem = [];
        $allPaymentSystem['nothing'] = Loc::getMessage("WBS24.WBAPI.NOT_SELECTED");
        $query = \Bitrix\Sale\PaySystem\Manager::getList(
            [
                'select'  => ['ID', 'NAME'],
            ]
        );

        while ($paySystem = $query->fetch()) {
            $allPaymentSystem[$paySystem['ID']] = $paySystem['NAME'];
        }

        return $allPaymentSystem;
    }

    public function getPriceTypes($modified = false)
    {
        $priceTypes = [];
        $dbPriceType = \CCatalogGroup::GetList(
            array("SORT" => "ASC"),
        );
        while ($priceInfo = $dbPriceType->Fetch())
        {
            if ($modified) {
                $key = 'PRICE_' . $priceInfo['ID'];
            } else {
                $key = $priceInfo['ID'];
            }

            $priceTypes[$key] = '[' . $priceInfo['ID'] . '] ' . $priceInfo['NAME_LANG'];
        }

        return $priceTypes;
    }

    public function getAllIblocks()
    {
        if ($this->lastError) return [];

        $allIblocks = [];
        $allIblocks['nothing'] = Loc::getMessage("WBS24.WBAPI.NOT_SELECTED");
        $query = \Bitrix\Iblock\IblockTable::getList(
            [
                'select' => ['ID', 'NAME'],
            ]
        );

        while ($iblock = $query->fetch()) {
            $allIblocks[$iblock['ID']] = $iblock['NAME'];
        }

        return $allIblocks;
    }

    function isCurlInstalled()
    {
        return in_array('curl', get_loaded_extensions()) ? true : false;
    }

    public function isHttps()
    {
        return $_SERVER['HTTPS'] == 'on';
    }

    public function getSelectForOrderProperties($payerTypeId, $field, $currentValue)
    {
        $prefix = $this->getPrefix($field);

        $options = [
            [
                'ID' => '',
                'NAME' => Loc::getMessage("WBS24.WBAPI.NOT_SELECTED"),
                'CODE' => 'nothing',
                'VALUE' => 'nothing',
                'PAYER_TYPE_ID' => 'all',
                'FOR_JS' => '',
            ],
        ];
        $allOrderProperties = $this->getAllOrderProperties();
        $propertiesOptions = $this->getAllPropertiesForOrders($allOrderProperties);
        foreach ($propertiesOptions as $key => $option) {
            $propertiesOptions[$key]['VALUE'] = $option['ID'];
            $propertiesOptions[$key]['FOR_JS'] = 'data-filter="'.$option['PAYER_TYPE_ID'].'"';
        }
        $options = array_merge($options, $propertiesOptions);

        $selectCode = $this->getSelect($field, $options, $currentValue);

        $jsCode =
            '<script>'
            .'document.addEventListener("DOMContentLoaded", function () {'
                .'let wb = new Wbs24WbapiAdmin();'
                .'wb.activateOptionsForCurrentValue("'.$field.'", "'.$payerTypeId.'");'
                .'let selectPersonTypeId = document.querySelector("select[name='.$prefix.'personTypeId]");'
                .'let selectSiteId = document.querySelector("select[name='.$prefix.'siteId]");'
                .'if (selectPersonTypeId && selectSiteId) {'
                    .'selectPersonTypeId.addEventListener("change", function (e) {'
                        .'let payerTypeId = e.target.value;'
                        .'wb.activateOptionsForCurrentValue("'.$field.'", payerTypeId);'
                    .'});'
                    .'selectSiteId.addEventListener("change", function (e) {'
                        .'let event = new Event("change");'
                        .'selectPersonTypeId.dispatchEvent(event);'
                    .'});'
                .'}'
            .'});'
            .'</script>'
        ;

        return $selectCode.$jsCode;
    }

    protected function getAllOrderProperties()
    {
        if ($this->lastError) return [];

        $allProprties = [];
        $query = \Bitrix\Sale\Property::getList([
            'select' => ['ID', 'NAME', 'PERSON_TYPE_ID', 'CODE'],
        ]);

        while ($property = $query->fetch()) {
            $allProprties[$property['PERSON_TYPE_ID']][$property['ID']] = ['name' => $property['NAME'], 'code' => $property['CODE']];
        }

        return $allProprties;
    }

    protected function getAllPropertiesForOrders($allOrderProperties)
    {
        foreach ($allOrderProperties as $personTypeId => $orderProperties) {
            foreach ($orderProperties as $propertyId => $propertyInfo) {
                $properties[] = [
                    'ID' => $propertyId,
                    'NAME' => $propertyInfo['name'],
                    'CODE' => $propertyInfo['code'],
                    'PAYER_TYPE_ID' => $personTypeId,
                ];
            }
        }

        return $properties;
    }

    public function getSelectForPayer($siteId, $field, $currentValue)
    {
        $prefix = $this->getPrefix($field);

        $options = [
            [
                'ID' => '',
                'NAME' => Loc::getMessage("WBS24.WBAPI.NOT_SELECTED"),
                'CODE' => 'nothing',
                'VALUE' => 'nothing',
                'SITE_ID' => 'all',
                'FOR_JS' => '',
            ],
        ];
        $allPersonTypes = $this->getPersonType();

        $personTypeOptions = $this->getAllPersonTypeOptions($allPersonTypes);
        foreach ($personTypeOptions as $key => $option) {
            $personTypeOptions[$key]['VALUE'] = $option['ID'];
            $personTypeOptions[$key]['FOR_JS'] = 'data-filter="'.$option['SITE_ID'].'"';
        }
        $options = array_merge($options, $personTypeOptions);

        $selectCode = $this->getSelect($field, $options, $currentValue);

        $jsCode =
            '<script>'
            .'document.addEventListener("DOMContentLoaded", function () {'
                .'let wb = new Wbs24WbapiAdmin();'
                .'wb.activateOptionsForCurrentValue("'.$field.'", "'.$siteId.'");'
                .'let select = document.querySelector("select[name='.$prefix.'siteId]");'
                .'if (select) {'
                    .'select.addEventListener("change", function (e) {'
                        .'let siteId = e.target.value;'
                        .'wb.activateOptionsForCurrentValue("'.$field.'", siteId);'
                    .'});'
                .'}'
            .'});'
            .'</script>'
        ;

        return $selectCode.$jsCode;
    }

    protected function getAllPersonTypeOptions($allPersonTypes)
    {
        foreach ($allPersonTypes as $siteId => $personTypeInfo) {
            foreach ($personTypeInfo as $propertyId => $name) {
                $properties[] = [
                    'ID' => $propertyId,
                    'NAME' => $name,
                    'CODE' => $propertyId,
                    'SITE_ID' => $siteId,
                ];
            }
        }

        return $properties;
    }

    public function getSelectForOfferId($siteId, $field, $currentValue)
    {
        $prefix = $this->getPrefix($field);

        $options = [
            [
                'ID' => '',
                'NAME' => 'ID',
                'CODE' => 'ID',
                'VALUE' => 'ID',
                'SITE_ID' => 'all',
                'FOR_JS' => '',
            ],
            [
                'ID' => '',
                'NAME' => 'XML_ID ['.Loc::getMessage("WBS24.WBAPI.EXTERNAL_ID").']',
                'CODE' => 'XML_ID',
                'VALUE' => 'XML_ID',
                'SITE_ID' => 'all',
                'FOR_JS' => '',
            ],
        ];
        $allInfoBlocks = $this->getAllInfoBlocks();
        if ($field == $prefix.'skuPropertyForProducts') {
            $propertiesOptions = $this->getAllStringIblockProperties($allInfoBlocks);
        }
        if ($field == $prefix.'skuPropertyForProductOffers') {
            $propertiesOptions = $this->getAllStringTradeIblockProperties($allInfoBlocks);
        }
        foreach ($propertiesOptions as $key => $option) {
            $propertiesOptions[$key]['VALUE'] = $option['CODE'];
            $propertiesOptions[$key]['FOR_JS'] = 'data-filter="'.$option['SITE_ID'].'"';
        }
        $options = array_merge($options, $propertiesOptions);

        $selectCode = $this->getSelect($field, $options, $currentValue);

        $jsCode =
            '<script>'
            .'document.addEventListener("DOMContentLoaded", function () {'
                .'let wb = new Wbs24WbapiAdmin();'
                .'wb.activateOptionsForCurrentValue("'.$field.'", "'.$siteId.'");'
                .'let select = document.querySelector("select[name='.$prefix.'siteId]");'
                .'if (select) {'
                    .'select.addEventListener("change", function (e) {'
                        .'let siteId = e.target.value;'
                        .'wb.activateOptionsForCurrentValue("'.$prefix.'skuPropertyForProducts", siteId);'
                        .'wb.activateOptionsForCurrentValue("'.$prefix.'skuPropertyForProductOffers", siteId);'
                    .'});'
                .'}'
            .'});'
            .'</script>'
        ;

        return $selectCode.$jsCode;
    }

    public function getSelectForPackageRatio($siteId, $field, $currentValue)
    {
        $prefix = $this->getPrefix($field);

        $options = [
            [
                'ID' => '',
                'NAME' => Loc::getMessage("WBS24.WBAPI.DEFAULT"),
                'CODE' => 'nothing',
                'VALUE' => 'nothing',
                'SITE_ID' => 'all',
                'FOR_JS' => '',
            ],
        ];
        $allInfoBlocks = $this->getAllInfoBlocks();
        if ($field == $prefix.'packageProductRatio') {
            $propertiesOptions = $this->getAllStringIblockProperties($allInfoBlocks);
        }
        if ($field == $prefix.'packageOfferRatio') {
            $propertiesOptions = $this->getAllStringTradeIblockProperties($allInfoBlocks);
        }
        foreach ($propertiesOptions as $key => $option) {
            $propertiesOptions[$key]['VALUE'] = $option['CODE'];
            $propertiesOptions[$key]['FOR_JS'] = 'data-filter="'.$option['SITE_ID'].'"';
        }
        $options = array_merge($options, $propertiesOptions);

        $selectCode = $this->getSelect($field, $options, $currentValue);

        $jsCode =
            '<script>'
            .'document.addEventListener("DOMContentLoaded", function () {'
                .'let wb = new Wbs24WbapiAdmin();'
                .'wb.activateOptionsForCurrentValue("'.$field.'", "'.$siteId.'");'
                .'let select = document.querySelector("select[name='.$prefix.'siteId]");'
                .'if (select) {'
                    .'select.addEventListener("change", function (e) {'
                        .'let siteId = e.target.value;'
                        .'wb.activateOptionsForCurrentValue("'.$prefix.'packageProductRatio", siteId);'
                        .'wb.activateOptionsForCurrentValue("'.$prefix.'packageOfferRatio", siteId);'
                    .'});'
                .'}'
            .'});'
            .'</script>'
        ;

        return $selectCode.$jsCode;
    }

    protected function getAllInfoBlocks($siteId = false)
    {
        if ($this->lastError) return [];

        if ($siteId) $filter['SITE_ID'] = $siteId;
        $filter['ACTIVE'] = 'Y';
        $query = \CIBlock::GetList(
            [],
            $filter
        );

        $tradeCatalogs = [];
        $tradeCatalogs['iblocks'] = [];
        while($iblock = $query->Fetch())
        {
            $iblockInfo = \CCatalog::GetByIDExt($iblock['ID']);

            if ($iblockInfo['CATALOG_TYPE'] == 'X') {
                $tradeCatalogs['iblocks'][] = $iblockInfo['PRODUCT_IBLOCK_ID'];
                $tradeCatalogs['trade_iblocks'][] = $iblockInfo['OFFERS_IBLOCK_ID'];

                $tradeCatalogs = $this->linkCatalogIblocksToSiteIds(
                    [$iblockInfo['PRODUCT_IBLOCK_ID'], $iblockInfo['OFFERS_IBLOCK_ID']],
                    $tradeCatalogs
                );

            } elseif ($iblockInfo['CATALOG_TYPE'] == 'D') {
                if (!in_array($iblockInfo['IBLOCK_ID'], $tradeCatalogs['iblocks'])) {
                    $tradeCatalogs['iblocks'][] = $iblockInfo['IBLOCK_ID'];
                    $tradeCatalogs = $this->linkCatalogIblocksToSiteIds(
                        [$iblockInfo['IBLOCK_ID']],
                        $tradeCatalogs
                    );
                }
            }
        }

        return $tradeCatalogs;
    }

    protected function linkCatalogIblocksToSiteIds($catalogIblockIds, $tradeCatalogs)
    {
        foreach ($catalogIblockIds as $catalogIblockId) {
            $allSites = \CIBlock::GetSite($catalogIblockId);
            while($site = $allSites->Fetch()) {
                $tradeCatalogs['catalogIblockIdsToSiteIds'][$catalogIblockId][] = $site['SITE_ID'];
            }
        }

        return $tradeCatalogs;
    }

    protected function getAllStringIblockProperties($allInfoBlocks)
    {
        if ($this->cachedProperties !== null) return $this->cachedProperties;

        $properties = $this->getAllStringIblockPropertiesByType($allInfoBlocks, 'iblocks');
        $this->cachedProperties = $properties;

        return $properties;
    }

    protected function getAllStringTradeIblockProperties($allInfoBlocks)
    {
        if ($this->cachedTradeProperties !== null) return $this->cachedTradeProperties;

        $properties = $this->getAllStringIblockPropertiesByType($allInfoBlocks, 'trade_iblocks');
        $this->cachedTradeProperties = $properties;

        return $properties;
    }

    protected function getAllStringIblockPropertiesByType($allInfoBlocks, $type)
    {
        if ($this->lastError) return [];

        $properties = [];
        $allowedTypes = ['S', 'N'];
        foreach ($allInfoBlocks[$type] as $key => $value) {
            $res = \CIBlockProperty::GetList([], [
                'IBLOCK_ID' => $value,
            ]);
            while ($property = $res->Fetch()) {
                if (!in_array($property['PROPERTY_TYPE'], $allowedTypes)) continue;
                $siteIds = $allInfoBlocks['catalogIblockIdsToSiteIds'][$property['IBLOCK_ID']];
                $key = $siteIds[0].'_'.$property['CODE'];
                $propertyCodeForName = strlen($property['CODE']) > 20 ? substr($property['CODE'], 0, 20).'...' : $property['CODE'];

                if (isset($properties[$key])) continue;

                $properties[$key] = [
                    'ID' => $property['ID'],
                    'NAME' => $property['NAME'] . ' [' . $propertyCodeForName . ']',
                    'CODE' => $property['CODE'],
                    'IBLOCK_ID' => $property['IBLOCK_ID'],
                    'SITE_ID' => implode(',', $siteIds),
                ];
            }
        }

        return $properties;
    }

    protected function getSelect($name, $options, $currentValue)
    {
        $code = '<select name="'.$name.'">';
        foreach ($options as $option) {
            $code .=
                '<option '.$option['FOR_JS'].' value="'.$option['VALUE'].'"'
                .($currentValue == $option['VALUE'] ? ' selected' : '')
                .' data-selected="'.($currentValue == $option['VALUE'] ? 'Y' : 'N').'"'
                .'>'.$option['NAME'].'</option>'
            ;
        }
        $code .= '</select>';

        return $code;
    }

    public function getInfoByIblockId($catalogIblockId)
    {
        if ($this->lastError) return [];

        $iblockInfo = \CCatalog::GetByIDExt($catalogIblockId);

        return $iblockInfo;
    }

    public function getCatalogIblockIdsToOffersIblockIds()
    {
        if ($this->lastError) return [];

        $iblockList = [];

        $res = \CIBlock::GetList();
        while ($iblock = $res->Fetch()) {
            $info = $this->getInfoByIblockId($iblock['ID']);
            if ($info['CATALOG_TYPE'] == 'X') {
                $iblockList[$iblock['ID']] = $info['OFFERS_IBLOCK_ID'];
            }
        }

        return $iblockList;
    }

    protected function getPrefix($field)
    {
        $prefix = '';
        list($firstPart) = explode('_', $field);
        if (is_numeric(str_replace('a', '', $firstPart))) $prefix = $firstPart.'_';

        return $prefix;
    }

    public function getHtmlLogsForDowload() {
        $logsDir = $this->getFullLogsDir();
        $html = '';
        if ($logsDir) {
            foreach (glob($logsDir . '*.txt') as $fileName) {
                $html .= '<a style="padding: 2px 0 2px 0;display:inline-block;" href="/bitrix/tools/'.$this->moduleId.'/logs/'.basename($fileName).'" download>'.basename($fileName).'</a><br>';
            }
            if ($html) {
                $html = '<div>'.$html.'</div>';
            }
        }

        return $html;
    }

    public function clearLogs() {
        $logsDir = $this->getFullLogsDir();
        if (
            $logsDir
            && strpos($logsDir, $this->moduleId) !== false
        ) {
            foreach (glob($logsDir . '*.txt') as $fileName) {
                unlink($fileName);
            }
        }
    }

    public function getSimpleProductProperties($siteId = false)
    {
        $allInfoBlocks = $this->getAllInfoBlocks($siteId);
        $properties = $this->getAllStringIblockProperties($allInfoBlocks);

        return $this->prepareProperties($properties);
    }

    public function getOfferProductProperties($siteId = false)
    {
        $allInfoBlocks = $this->getAllInfoBlocks($siteId);
        $properties = $this->getAllStringTradeIblockProperties($allInfoBlocks);

        return $this->prepareProperties($properties, true);
    }

    protected function prepareProperties($properties, $isOffer = false)
    {
        $preparedProperties = [];
        foreach ($properties as $property) {
            $key = 'PROPERTY_';
            if ($isOffer) $key = 'OFFER_PROPERTY_';
            $preparedProperties[$key.$property['ID']] = $property['NAME'];
        }

        return $preparedProperties;
    }

    public function getNameInput($inputName, $propertiesGroups, $currentValue, $account)
    {
        $marks = [];
        $value = $currentValue ?? '';

        // базовые поля
        $priceTypes = $this->getPriceTypes($modified = true);
        foreach ($priceTypes as $priceMark => $priceName) {
            $marks[] = [
                'TEXT' => $priceName,
                'MARK' => $priceMark,
            ];
        }

        // свойства
        foreach ($propertiesGroups as $group => $properties) {
            $propertyMarks = [];
            foreach ($properties as $mark => $text) {
                $propertyMarks[] = [
                    'TEXT' => $text,
                    'MARK' => $mark,
                ];
            }
            $marks[] = [
                'TEXT' => Loc::getMessage("WBS24.WBAPI.".$group."_PROPERTIES_LABEL"),
                'MENU' => $propertyMarks,
            ];
        }

        return $this->StringTemplate->getInputWithTemplate($inputName, $marks, $value, $account);
    }

    public function getWarehouses()
    {
        $warehouses = [];

        $query = \Bitrix\Catalog\StoreTable::getList(array(
            'filter' => array('ACTIVE'>='Y'),
        ));

        $warehouses['catalog_quantity'] = Loc::getMessage("WBS24.WBAPI.CATALOG_QUANTITY");
        $warehouses['stocks_from_property'] = Loc::getMessage("WBS24.WBAPI.STOCKS_FROM_PROPERTY");
        while($store = $query->fetch())
        {
            $warehouses[$store['ID']] = '['.$store['ID'].'] '.$store['TITLE'];
        }

        return $warehouses;
    }

    public function getAccountsForSelect($excludeId) {
        $accounts = $this->getAccounts();
        $accountsList = [];

        foreach ($accounts as $id) {
            if ($id == $excludeId) continue;
            $accountsList[$id] = Loc::getMessage("WBS24.WBAPI.ACCOUNT").' ['.$id.']';
        }

        return $accountsList;
    }

    public function getSelectForStockProperty($siteId, $field, $currentValue)
    {
        $prefix = $this->getPrefix($field);

        $options = [
            [
                'ID' => '',
                'NAME' => Loc::getMessage("WBS24.WBAPI.NOT_SELECTED"),
                'CODE' => 'nothing',
                'VALUE' => 'nothing',
                'SITE_ID' => 'all',
                'FOR_JS' => '',
            ],
        ];

        $allInfoBlocks = $this->getAllInfoBlocks();
        if ($field == $prefix.'productStockProperty') {
            $propertiesOptions = $this->getAllStringIblockProperties($allInfoBlocks);
        }
        if ($field == $prefix.'offerStockProperty') {
            $propertiesOptions = $this->getAllStringTradeIblockProperties($allInfoBlocks);
        }
        foreach ($propertiesOptions as $key => $option) {
            $propertiesOptions[$key]['VALUE'] = $option['CODE'];
            $propertiesOptions[$key]['FOR_JS'] = 'data-filter="'.$option['SITE_ID'].'"';
        }
        $options = array_merge($options, $propertiesOptions);

        $selectCode = $this->getSelect($field, $options, $currentValue);

        $jsCode =
            '<script>'
            .'document.addEventListener("DOMContentLoaded", function () {'
                .'let wb = new Wbs24WbapiAdmin();'
                .'wb.activateOptionsForCurrentValue("'.$field.'", "'.$siteId.'");'
                .'let select = document.querySelector("select[name='.$prefix.'siteId]");'
                .'if (select) {'
                    .'select.addEventListener("change", function (e) {'
                        .'let siteId = e.target.value;'
                        .'wb.activateOptionsForCurrentValue("'.$prefix.'productStockProperty", siteId);'
                        .'wb.activateOptionsForCurrentValue("'.$prefix.'offerStockProperty", siteId);'
                    .'});'
                .'}'
            .'});'
            .'</script>'
        ;

        return $selectCode.$jsCode;
    }
}
