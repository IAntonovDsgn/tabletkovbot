<?php

namespace App\Presentation\Api;

use Telegram\Bot\Objects\Update;

class Controller
{
    public function handle(Update $requestData): void
    {
        $messages = $requestData->getMessage();

    }
}
