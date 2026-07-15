<?php

namespace Tests\Unit\Application\Command\Chat\DisableNotifications;

use App\Application\Command\Chat\DisableNotifications\DisableNotificationsException;
use App\Application\Command\Chat\DisableNotifications\Handler;
use App\Domain\Entities\Chat\Chat;
use App\Domain\Entities\Chat\ChatRepositoryInterface;
use Mockery;
use Throwable;

test('', function (bool $isChatExist) {
    $chatId = 1;
    $chat = null;

    if ($isChatExist) {
        $chat = new Chat($chatId);
    }

    $chatRepositoryMock = Mockery::mock(ChatRepositoryInterface::class);
    $chatRepositoryMock
        ->shouldReceive('findById')
        ->with($chatId)
        ->andReturn($chat);

    $handler = new Handler($chatRepositoryMock);

    if ($isChatExist) {
        expect(fn() => $handler($chatId))
            ->not->toThrow(Throwable::class);
    } else {
        expect(fn() => $handler($chatId))
            ->toThrow(DisableNotificationsException::class);
    }

})->with('disable notifications');

dataset('disable notifications', [
    'Когда чат существует в базе, метод не выбрасывает исключение' => true,
    'Когда чат не существует в базе, метод выбрасывает исключение' => false,
]);
