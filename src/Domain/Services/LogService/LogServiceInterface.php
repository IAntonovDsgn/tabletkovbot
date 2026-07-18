<?php

namespace App\Domain\Services\LogService;

interface LogServiceInterface
{
    public function addRecord(string $name, array $arguments): void;
}
