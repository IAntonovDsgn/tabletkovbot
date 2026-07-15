<?php

namespace App\Domain\Entities\Session;

use App\Domain\Exceptions\InvalidValueException;

interface StateHandlerInterface
{
    /**
     * @throws InvalidValueException
     */
    public function handle(string $currentSessionValue, string $newSessionValue = ''): void;
}
