<?php
namespace Wbs24\Wbapi\Prices;

use Wbs24\Wbapi\Stack as StackBase;

class Stack extends StackBase
{
    protected $table = 'wbs24_wbapi_prices_stack';

    public function add($prices)
    {
        foreach ($prices as $key => $price) {
            $data = [
                'account_index' => $this->accountIndex,
                'nm_id' => $price['nmId'],
                'price' => $price['price'],
            ];

            $this->Db->set($this->table, $data);
        }
    }

    public function get($skip = 0, $limit = 100)
    {
        $stackItems = $this->Db->get($this->table, [
            'account_index' => $this->accountIndex,
        ], [
            'order' => 'id',
            'limit' => "${skip}, ${limit}",
        ]);
        $prices = [];
        foreach ($stackItems as $key => $item) {
            $prices[] = [
                'nmId' => $item['nm_id'],
                'price' => $item['price'],
            ];
        }

        return $prices;
    }

    public function clean()
    {
        $this->Db->clear($this->table, $where = [
            'account_index' => $this->accountIndex,
        ]);
    }
}
