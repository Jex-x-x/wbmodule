<?php
namespace Wbs24\Wbapi;

abstract class Stack
{
    protected $Db;

    protected $accountIndex;

    public function __construct($accountIndex = 1, $objects = [])
    {
        $this->Db = $objects['Db'] ?? new Db();

        $this->accountIndex = $accountIndex;
    }

    public function safeUnserialize($s)
    {
        $u = unserialize($s);
        if (!$u) {
            $s = preg_replace_callback(
                '/s:(\d+):"(.*?)"([;}])/',
                function($m) {
                    return 's:'.strlen($m[2]).':"'.$m[2].'"'.$m[3];
                },
                $s
            );
            $u = unserialize($s);
        }

        return $u;
    }

    abstract public function add($elements);

    abstract public function get();

    abstract public function clean();
}
