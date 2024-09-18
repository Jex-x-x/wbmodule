<?php
namespace Wbs24\Wbapi\Wrappers;

class CAgent {
    public function RemoveAgent(...$args) {
        return \CAgent::RemoveAgent(...$args);
    }

    public function RemoveModuleAgents(...$args)
    {
        return \CAgent::RemoveModuleAgents(...$args);
    }

    public function AddAgent(...$args) {
        return \CAgent::AddAgent(...$args);
    }
}
