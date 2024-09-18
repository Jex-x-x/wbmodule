<?php
namespace Wbs24\Wbapi;

use PHPUnit\Framework\TestCase;

class BitrixTestCase extends TestCase {
    protected function getMethod($className, $methodName)
    {
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    protected function getModuleId()
    {
        return strtolower(str_replace('\\', '.', __NAMESPACE__));
    }

    protected $backupGlobals = false;
}
