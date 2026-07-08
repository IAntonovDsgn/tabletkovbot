<?php

namespace App\Presentation\http\Telegram;

use Telegram\Bot\Objects\Update;

class RequestHandler
{
    public function handle(Update $requestData): void
    {
        $messages = $requestData->getMessage();

    }
}
