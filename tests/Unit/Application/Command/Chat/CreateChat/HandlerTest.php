<?php

namespace Tests\Unit\Application\Command\Chat\CreateChat;

use App\Application\Command\Chat\CreateChat\Handler;
use App\Application\Command\Chat\CreateChat\CreateChatException;
use App\Domain\Chat\ChatFactory;
use App\Domain\Chat\ChatRepositoryInterface;
use Mockery;
use Throwable;

test('', function (bool $isChatExist) {
    $chatId = 1;
    $chat = null;

    if ($isChatExist) {
        $chat = ChatFactory::create($chatId);
    }

    $chatRepositoryMock = Mockery::mock(ChatRepositoryInterface::class);
    $chatRepositoryMock
        ->shouldReceive('findById')
        ->with($chatId)
        ->andReturn($chat);

    $handler = new Handler($chatRepositoryMock);

    if ($isChatExist) {
        expect(fn() => $handler($chatId))
            ->toThrow(CreateChatException::class);
    } else {
        expect(fn() => $handler($chatId))
            ->not->toThrow(Throwable::class);
    }
})->with('create chat dataset');

dataset('create chat dataset', [
    'Чат уже существует в базе - метод выбросит исключение' => true,
    'Чат не существует в базе - метод не выбросит исключение' => false
]);
