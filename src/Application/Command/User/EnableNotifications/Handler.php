<?php

namespace App\Application\Command\User\EnableNotifications;

use App\Domain\User\UserRepositoryInterface;

final readonly class Handler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws EnableNotificationsException
     */
    public function __invoke(int $userId): void
    {
        $user = $this->userRepository->findByUserId($userId);

        if (is_null($user)) {
            throw new EnableNotificationsException('User not found');
        }

        $user->enableNotifications();
        $this->userRepository->save($user);
    }
}
