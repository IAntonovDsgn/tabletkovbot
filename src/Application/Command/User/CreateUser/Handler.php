<?php

namespace App\Application\Command\User\CreateUser;

use App\Domain\User\UserFactory;
use App\Domain\User\UserRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws CreateUserException
     */
    public function __invoke(int $telegramId): void
    {
        $user = $this->userRepository->findByTelegramId($telegramId);

        if (is_null($user)) {
            UserFactory::create($telegramId);
        } else {
            throw new CreateUserException('Пользователь с таким telegram id уже существует');
        }
    }
}
