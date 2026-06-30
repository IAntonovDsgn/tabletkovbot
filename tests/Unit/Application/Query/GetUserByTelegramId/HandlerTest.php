<?php

test('handle() - пользователь существует', function ($telegramId, $name, $exceptionClass) {
    $existingUser = new User($telegramId, $name);

    $userRepo = mock(UserRepositoryInterface::class);
    $userRepo->shouldReceive('findByTelegramId')
        ->with($telegramId)
        ->andReturn($existingUser);

    $handler = new Handler();

    if (!is_null($exceptionClass)) {
        $user = $handler->handle($telegramId);
        expect($user)->toBe($existingUser);
    } else {
        expect(fn () => $handler->handle($telegramId))
            ->toThrow($exceptionClass);
    }
})->with('user exist');

dataset('user exist', [
    'correct $telegramId and $name' => [3, 'John Doe', null],
    'incorrect $telegramId 1' => [-1, 'John Doe', RequestUserException::class],
    'incorrect $telegramId 2' => ['first', 'John Doe', RequestUserException::class],
    'incorrect $telegramId 3' => [true, 'John Doe', RequestUserException::class],
    'incorrect $telegramId 4' => ['', 'John Doe', RequestUserException::class],
    'incorrect $name 1' => [3, '', RequestUserException::class],
    'incorrect $name 2' => [3, true, RequestUserException::class],
]);
