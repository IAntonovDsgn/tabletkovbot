<?php

use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\Message\MessageFacadeInterface;

test('', function (bool $chatExist) {
    $chatId = 1;
    $text = 'test message';
    $chat = null;

    if ($chatExist) {
        $chat = new App\Domain\Chat\Chat($chatId);
    }

    $message = new App\Domain\Message\Message($chatId, $text);

    $chatRepositoryMock = Mockery::mock(ChatRepositoryInterface::class);
    $chatRepositoryMock
        ->shouldReceive('findById')
        ->with($chatId)
        ->andReturn($chat);

    $messageFacadeMock = Mockery::mock(MessageFacadeInterface::class);
    $messageFacadeMock
        ->shouldReceive('sendMessage')
        ->with($message);

    $handler = new \App\Application\Command\Message\SendMessage\Handler($chatRepositoryMock, $messageFacadeMock);

    if ($chatExist) {
        expect(fn() => $handler($chatId, $text))
            ->not->toThrow(Throwable::class);
    } else {
        expect(fn() => $handler($chatId, $text))
            ->toThrow(\App\Application\Command\Message\SendMessage\SendMessageException::class);
    }

})->with('send message dataset');

dataset('send message dataset', [
    'chat exist - метод не выбрасывает исключения' => true,
    'chat is not exist - метод выбрасывает исключение' => true,
]);
