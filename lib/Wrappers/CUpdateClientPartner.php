<?php
namespace Wbs24\Wbapi\Wrappers;

class CUpdateClientPartner
{
    public function GetUpdatesList(...$args)
    {
        if (!class_exists('\CUpdateClientPartner')) return [];

        return \CUpdateClientPartner::GetUpdatesList(...$args);
    }
}
