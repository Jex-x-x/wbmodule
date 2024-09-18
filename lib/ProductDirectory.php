<?php
namespace Wbs24\Wbapi;

class ProductDirectory extends Stack
{
    protected $table = 'wbs24_wbapi_products';

    public function add($products)
    {
        foreach ($products as $key => $product) {
            $data = [
                'account_index' => $this->accountIndex,
                'barcode' => $product['barcode'],
                'article' => $product['article'],
                'chrt_id' => $product['chrtId'],
                'nm_id' => $product['nmId'],
            ];

            $this->Db->set($this->table, $data);
        }
    }

    public function get($where = [], $param = [], $groupBy = '')
    {
        $where['account_index'] = $this->accountIndex;
        $result = $this->Db->get($this->table, $where, $param);
        $products = [];
        foreach ($result as $key => $item) {
            $groupKey = $groupBy ?: 'nm_id';
            $products[$item[$groupKey]] = [
                'barcode' => $item['barcode'],
                'article' => $item['article'],
                'chrtId' => $item['chrt_id'],
                'nmId' => $item['nm_id'],
            ];
        }

        return $products;
    }

    public function clean()
    {
        $this->Db->clear($this->table, $where = [
            'account_index' => $this->accountIndex,
        ]);
    }
}
