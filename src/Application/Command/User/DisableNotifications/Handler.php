<?php

namespace App\Application\Command\User\DisableNotifications;

use App\Domain\User\UserRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws DisableNotificationsException
     */
    public function __invoke(int $userId): void
    {
        $user = $this->userRepository->findByUserId($userId);

        if (is_null($user)) {
            throw new DisableNotificationsException('Пользователь не найден');
        }

        $user->disableNotifications();
        $this->userRepository->save($user);
    }
}
