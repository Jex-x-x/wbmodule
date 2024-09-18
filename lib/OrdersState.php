<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;

class OrdersState {
    use Exception; // trait

    protected $Db;

    protected $accountIndex;

    public function __construct($accountIndex = 1, $objects = [])
    {
        try {
            $this->Db = $objects['Db'] ?? new Db();

            $this->accountIndex = $accountIndex;
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function addOrdersInformation($ordersFromTradingPlatform)
    {
        $data = [];
        foreach ($ordersFromTradingPlatform as $key => $resultOrder) {
            $data['external_id'] = $resultOrder['posting_number'];
            $data['delivery_method_id'] = $resultOrder['delivery_method']['id'];
            $data['account_index'] = $this->accountIndex;
            $this->Db->set('wbs24_wbapi_orders_state', $data);
        }
    }

    public function setSuccessShip($externalId)
    {
        try {
            $data['sucсess_ship'] = 'success';
            $data['external_id'] = $externalId;
            $data['account_index'] = $this->accountIndex;
            $this->Db->set('wbs24_wbapi_orders_state', $data);

            return 'success';
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function setPathToPackageLabel($externalId, $packageLabel, $disabled = null)
    {
        try {
            $data['external_id'] = $externalId;
            $data['package_label'] = $packageLabel;
            $data['disabled_package_label'] = $disabled;
            $data['account_index'] = $this->accountIndex;
            $this->Db->set('wbs24_wbapi_orders_state', $data);

            return 'success';
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function setCreatedActId($externalId, $createActId, $disabled = null)
    {
        try {
            $data['external_id'] = $externalId;
            $data['act_сreate_id'] = $createActId;
            $data['disabled_act'] = $disabled;
            $data['account_index'] = $this->accountIndex;
            $this->Db->set('wbs24_wbapi_orders_state', $data);

            return 'success';
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function setStatusAct($externalId, $status)
    {
        try {
            $data['external_id'] = $externalId;
            $data['act_creation_status'] = $status;
            $data['account_index'] = $this->accountIndex;
            $this->Db->set('wbs24_wbapi_orders_state', $data);

            return 'success';
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function setAct($externalId, $act)
    {
        try {
            $data['external_id'] = $externalId;
            $data['act'] = $act;
            $data['account_index'] = $this->accountIndex;
            $this->Db->set('wbs24_wbapi_orders_state', $data);

            return 'success';
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function getOrdersInformation($externalId = null)
    {
        if ($externalId) {
            $orders = $this->Db->get('wbs24_wbapi_orders_state', [
                'external_id' => $externalId,
                'account_index' => $this->accountIndex,
            ]);
            return $orders[0];
        } else {
            $orders = $this->Db->get('wbs24_wbapi_orders_state');
            return $orders;
        }
    }

    public function setOrderCancel($externalId)
    {
        try {
            $data['external_id'] = $externalId;
            $data['cancelled_order'] = true;
            $data['account_index'] = $this->accountIndex;
            $this->Db->set('wbs24_wbapi_orders_state', $data);
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }
}
