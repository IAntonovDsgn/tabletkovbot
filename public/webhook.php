<?php

require __DIR__ . '/../bootstrap/bootstrap.php';

use App\Components\Log\Log;
use App\Presentation\http\Telegram\RequestHandler;
use Telegram\Bot\Laravel\Facades\Telegram;

Log::info('Запуск приложения');
//
//$container = ContainerFactory::build();
//$handler = $container->get(RequestHandler::class);
//
//$update = Telegram::getWebhookUpdate();
//$handler->handle($update);
