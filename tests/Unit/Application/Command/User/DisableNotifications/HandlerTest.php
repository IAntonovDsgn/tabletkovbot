<?php

namespace Tests\Unit\Application\Command\User\DisableNotifications;

use App\Application\Command\User\DisableNotifications\DisableNotificationsException;
use App\Application\Command\User\DisableNotifications\Handler;
use App\Domain\User\UserFactory;
use App\Domain\User\UserRepositoryInterface;
use Mockery;
use Throwable;

test('', function (bool $isUserExist) {
    $userId = 1;
    $user = null;

    if ($isUserExist) {
        $user = UserFactory::create($userId);
    }

    $userRepositoryMock = Mockery::mock(UserRepositoryInterface::class);
    $userRepositoryMock
        ->shouldReceive('findByUserId')
        ->with($userId)
        ->andReturn($user);

    $handler = new Handler($userRepositoryMock);

    if ($isUserExist) {
        expect(fn() => $handler($userId))
            ->not->toThrow(Throwable::class);
    } else {
        expect(fn() => $handler($userId))
            ->toThrow(DisableNotificationsException::class);
    }

})->with('disable notifications');

dataset('disable notifications', [
    'Когда пользователь существует, метод не выбрасывает исключение' => true,
    'Когда пользователь не существует, метод выбрасывает исключение' => false,
]);
