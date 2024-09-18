<?php
namespace Wbs24\Wbapi\Wrappers;

class Price
{
    public function getList(...$args)
    {
        return \Bitrix\Catalog\Model\Price::getList(...$args);
    }
}
