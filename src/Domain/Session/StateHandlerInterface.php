<?php

namespace App\Domain\Session;

interface StateHandlerInterface
{
    public function handle(): void;
}
