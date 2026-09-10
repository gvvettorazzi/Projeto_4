<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Client;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ClientChecker implements UserCheckerInterface
{
    private const LOCKED_ACCOUNT_MESSAGE = 'Account is locked';

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Client) {
            return;
        }

        if ($user->getIsActive()) {
            return;
        }

        throw new LockedException(self::LOCKED_ACCOUNT_MESSAGE);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No post-authentication checks are required.
    }
}

