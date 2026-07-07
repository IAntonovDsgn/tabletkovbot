<?php

namespace Tests\Unit\Application\Query\GetUserByTelegramId;

use App\Application\Query\User\GetUser\Handler;
use App\Domain\User\UserFactory;
use App\Domain\User\UserRepositoryInterface;
use Mockery;

test(
    '',
    function (bool $isUserExist) {
        $userTelegramId = 1;

        if ($isUserExist) {
            $existingUser = UserFactory::create($userTelegramId);
        } else {
            $existingUser = null;
        }

        $userRepositoryMock = Mockery::mock(UserRepositoryInterface::class)
            ->shouldReceive('findByTelegramId')
            ->with($userTelegramId)
            ->andReturn($existingUser)
            ->getMock();

        $handler = new Handler($userRepositoryMock);
        $user = $handler->getUserByTelegramId($userTelegramId);
        expect($user)->toBe($existingUser);
    }
)->with('get user by telegram id');

dataset('get user by telegram id', [
    'user exist - получаем экземпляр модели User' => true,
    'user not exist - получаем null' => false
]);
