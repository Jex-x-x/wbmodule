<?php
namespace Wbs24\Wbapi\Api\V3;

use Wbs24\Wbapi\Api;

class GetStocks extends Api
{
    public function apiLaunch($data)
    {
        $warehouseId = $data['warehouse_id'] ?? false;
        if (!is_numeric($warehouseId)) return [];

        $requestUrl = $this->getRequestUrl('/api/v3/stocks/'.$warehouseId);

        return $this->createRequest($requestUrl, [
            'skus' => $data['barcodes'],
        ], [
            'post' => true,
        ]);
    }
}
