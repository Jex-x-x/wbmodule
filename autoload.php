<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class Autoload
{
    public function __construct()
    {
        Loader::registerAutoLoadClasses(
            $this->getModuleId(),
            $this->getModuleClasses()
        );
    }

    public function getModuleId()
    {
        return basename(__DIR__);
    }

    public function getModuleNamespace()
    {
        $moduleId = $this->getModuleId();
        $names = explode(".", $moduleId);
        $namespace = "";

        foreach ($names as $name) {
            $namespace .= "\\".ucfirst($name);
        }

        return $namespace;
    }

    public function getModuleClasses($path = "lib")
    {
        $includedNamespaces = str_replace(["lib", "/"], ["", "\\"], $path);
        $libPath = $path."/";
        $libFiles = scandir(__DIR__."/".$libPath);
        $namespace = $this->getModuleNamespace();
        $moduleClasses = [];

        foreach ($libFiles as $libName) {
            if (substr($libName, 0, 1) == '.') continue;
            if (substr($libName, -4) != ".php") {
                $nextLevelModuleClasses = $this->getModuleClasses($path.'/'.$libName);
                $moduleClasses = array_merge($moduleClasses, $nextLevelModuleClasses);
            } else {
                $class = $namespace.$includedNamespaces."\\".substr($libName, 0, -4);
                $moduleClasses[$class] = $libPath.$libName;
            }
        }

        return $moduleClasses;
    }
}

$autoload = new Autoload();
