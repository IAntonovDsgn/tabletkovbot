<?php

namespace Tests\Unit\Application\Command\User\CreateUser;

use App\Application\Command\User\CreateUser\CreateUserException;
use App\Application\Command\User\CreateUser\Handler;
use App\Domain\User\UserFactory;
use App\Domain\User\UserRepositoryInterface;
use Mockery;
use Throwable;

test('', function (bool $isUserExist) {
    $telegramId = 1;
    $user = null;

    if ($isUserExist) {
        $user = UserFactory::create($telegramId);
    }

    $userRepositoryMock = Mockery::mock(UserRepositoryInterface::class);
    $userRepositoryMock
        ->shouldReceive('findByTelegramId')
        ->with($telegramId)
        ->andReturn($user);

    $handler = new Handler($userRepositoryMock);

    if ($isUserExist) {
        expect(fn() => $handler($telegramId))
            ->toThrow(CreateUserException::class);
    } else {
        expect(fn() => $handler($telegramId))
            ->not->toThrow(Throwable::class);
    }
})->with('create user dataset');

dataset('create user dataset', [
    'Пользователь уже существует - метод выбросит исключение' => true,
    'Пользователя не существует - метод не выбросит исключение' => false
]);
