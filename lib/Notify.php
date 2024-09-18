<?php
namespace Wbs24\Wbapi;

class Notify
{
    protected const TAG = "WBAPI";
    protected const MODULE_ID = "wbs24.wbapi";

    public function addAdminNotify($message, $error = false)
    {
        $notify = [
            "MESSAGE" => $message,
            "TAG" => self::TAG.($error ? "_ERROR" : ""),
            "MODULE_ID" => self::MODULE_ID,
            "ENABLE_CLOSE" => "Y",
            "LANG" => [],
            "NOTIFY_TYPE" => $error ? "E" : "",
        ];
        \CAdminNotify::Add($notify);
    }
}
