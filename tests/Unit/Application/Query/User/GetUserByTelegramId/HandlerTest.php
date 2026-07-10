<?php

namespace Tests\Unit\Application\Query\GetChatById;

use App\Application\Query\Chat\GetChat\Handler;
use App\Domain\Chat\ChatFactory;
use App\Domain\Chat\ChatRepositoryInterface;
use Mockery;

test(
    '',
    function (bool $isChatExist) {
        $chatId = 1;

        if ($isChatExist) {
            $existingChat = ChatFactory::create($chatId);
        } else {
            $existingChat = null;
        }

        $chatRepositoryMock = Mockery::mock(ChatRepositoryInterface::class)
            ->shouldReceive('findById')
            ->with($chatId)
            ->andReturn($existingChat)
            ->getMock();

        $handler = new Handler($chatRepositoryMock);
        $chat = $handler->getChatById($chatId);
        expect($chat)->toBe($existingChat);
    }
)->with('get chat by id');

dataset('get chat by id', [
    'chat exist - получаем экземпляр модели Chat' => true,
    'chat not exist - получаем null' => false
]);
