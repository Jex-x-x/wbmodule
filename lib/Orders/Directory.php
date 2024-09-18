<?php
namespace Wbs24\Wbapi\Orders;

use Wbs24\Wbapi\Stack;

class Directory extends Stack
{
    protected $table = 'wbs24_wbapi_orders_directory';

    public function add($order)
    {
        $data = [
            'external_id' => $order['posting_number'],
            'parent_external_id' => $order['parent_external_id'] ?? $order['posting_number'],
            'order_group_id' => $order['order_group_id'],
            'offer_id' => '', // отключено, так как больше нет необходимости в этом ключе
            'account_index' => $this->accountIndex,
        ];

        $this->Db->set($this->table, $data);
    }

    public function update($data)
    {
        if (!isset($data['account_index'])) $data['account_index'] = $this->accountIndex;

        $this->Db->set($this->table, $data);
    }

    public function get($where = [])
    {
        $where['account_index'] = $this->accountIndex;
        $result = $this->Db->get($this->table, $where);

        return $result;
    }

    public function clean()
    {
        $this->Db->clear($this->table, $where = [
            'account_index' => $this->accountIndex,
        ]);
    }
}
