<?php

namespace Tests\Unit\Application\Query\GetUserByTelegramId;

use App\Application\Query\GetUser\Handler;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use Mockery;

test(
    'findByTelegramId()',
    function (int $userTelegramId, bool $isUserExist) {
        if ($isUserExist) {
            $existingUser = new User(1, $userTelegramId, true);
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
    'user exist' => [3, true],
    'user not exist' => [3, false],
]);
