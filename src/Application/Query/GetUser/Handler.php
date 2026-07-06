<?php

namespace App\Application\Query\GetUser;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function getUserByTelegramId($telegramId): ?User
    {
        return $this->userRepository->findByTelegramId($telegramId);
    }
}
