<?php
namespace Wbs24\Wbapi;

trait Accounts
{
    public function getDependencies($accountIndex)
    {
        $this->setAccount($accountIndex);

        return ['Option' => $this->wrappers->Option];
    }

    public function setAccount($accountIndex)
    {
        $this->createOptionIfNotExist();
        $this->wrappers->Option->setPrefix($accountIndex);
    }

    protected function createOptionIfNotExist()
    {
        if (!$this->wrappers) {
            $this->wrappers = new Wrappers();
        }
    }
}
