<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\{
    SystemException,
    Loader
};

class OptionController
{
    public function __construct($objects = [])
    {
        try {
            if (!Loader::IncludeModule('sale')) {
                throw new SystemException("Sale module isn`t installed");
            }
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function action($data) {
        $result = [];

        switch($data['action']) {
            case 'loadCustomerIds' :
                $result = $this->loadProfiles($data['user_id']);
                break;
        }

        return json_encode($result);
    }

    public function loadProfiles($userId) {
        $customers = [];
        $customers["0"] = Loc::getMessage("WBS24.WBAPI.CUSTOMER_ID_NOT_SELECTED");

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

        return $customers;
    }
}
