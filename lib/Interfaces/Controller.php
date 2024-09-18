<?php
namespace Wbs24\Wbapi\Interfaces;

interface Controller
{
    public function getAllowedActions(): array;
    public function process(array $param): array;
}
