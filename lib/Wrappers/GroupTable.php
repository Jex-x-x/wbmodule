<?php
namespace Wbs24\Wbapi\Wrappers;

class GroupTable
{
    public function getList(...$args)
    {
        return \Bitrix\Catalog\GroupTable::getList(...$args);
    }
}
