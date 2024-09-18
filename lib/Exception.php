<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;

trait Exception
{
    protected $maxMegaBytes = 50;
    protected $logsDir = '/bitrix/tools/wbs24.wbapi/logs/';

    public function exceptionHandler($exception, $dontDie = false)
    {
        $this->lastError =
            $exception->getFile()."(".$exception->getLine()."): ".$exception->getMessage()."\r\n".
            $exception->getTraceAsString()
        ;
        $this->createReport('exception_log.txt', $this->lastError);
        if (!$dontDie) die();
    }

    public function createReport($fileName, $report)
    {
        $prefix = trim(strtolower(str_replace('\\', '_', __NAMESPACE__)), '_');

        $text = date('Y.m.d H:i:s')."\r\n";
        $text .= print_r($report, true)."\r\n\r\n";

        $fullFileName = $prefix.'_'.$fileName;
        $fullLogsDir = $this->getFullLogsDir();

        $this->rotateReport(
            $fullLogsDir,
            $fullFileName
        );

        $handle = @fopen($fullLogsDir . $fullFileName, 'a');
        if ($handle) {
            fwrite($handle, $text);
            fclose($handle);
        }
    }

    protected function rotateReport($dir, $fileName)
    {
        if (!file_exists($dir.$fileName)) return false;

        $bytes = filesize($dir.$fileName);
        $maxBytes = $this->convertMegaBytesToBytes();
        if ($bytes >= $maxBytes) {
            $this->renameFile($dir, $fileName);
        }
    }

    protected function convertMegaBytesToBytes()
    {
        return $this->maxMegaBytes * 1024 * 1024;
    }

    protected function renameFile($dir, $fileName)
    {
        rename($dir.$fileName, $dir.'old_'.$fileName);
    }

    protected function getFullLogsDir()
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->logsDir;
    }
}
