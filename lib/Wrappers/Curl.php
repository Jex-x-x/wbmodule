<?
namespace Wbs24\Wbapi\Wrappers;

use Bitrix\Main\SystemException;
use Wbs24\Wbapi\Exception;

class Curl {
    use Exception; // trait

    public function __construct($dontDie = false)
    {
        try {
            if (!function_exists('curl_init')) throw new SystemException("cURL don`t installed");
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception, $dontDie);
        }
    }

    public function curl_init(...$args) {
        return curl_init(...$args);
    }

    public function curl_setopt(...$args) {
        return curl_setopt(...$args);
    }

    public function curl_exec(...$args) {
        return curl_exec(...$args);
    }

    public function curl_close(...$args) {
        return curl_close(...$args);
    }

    public function curl_error(...$args) {
        return curl_error(...$args);
    }
}
