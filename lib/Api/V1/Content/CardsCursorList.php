<?php
namespace Wbs24\Wbapi\Api\V1\Content;

use Wbs24\Wbapi\Api;

class CardsCursorList extends Api
{
    public function apiLaunch($data)
    {
        $requestUrl = $this->getRequestUrl('/content/v1/cards/cursor/list');
        $limit = $data['limit'] ?: 1000;
        $nmId = $data['last_nm_id'] ?? false;
        $updatedAt = $data['last_updated_at'] ?? false;

        $param = [
            'sort' => [
                'cursor' => [
                    'limit' => $limit,
                ],
                'filter' => [
                    'withPhoto' => -1,
                ],
            ],
        ];
        if ($nmId && $updatedAt) {
            $param['sort']['cursor']['nmId'] = $nmId;
            $param['sort']['cursor']['updatedAt'] = $updatedAt;
        }

        return $this->createRequest($requestUrl, $param, [
            'post' => true,
        ]);
    }
}
