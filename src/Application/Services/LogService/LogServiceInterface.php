<?php

namespace App\Application\Services\LogService;

interface LogServiceInterface
{
    public function addRecord(string $name, array $arguments): void;
}
