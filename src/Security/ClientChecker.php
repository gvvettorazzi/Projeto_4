<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Client;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ClientChecker implements UserCheckerInterface
{
    private const AUTHENTICATION_FAILURE_MESSAGE = 'Authentication failed.';

    public function checkPreAuth(UserInterface $user): void
    {
        $client = $this->requireValidClient($user);

        $this->assertAccountIsActive($client);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        $client = $this->requireValidClient($user);

        $this->assertAccountIsActive($client);
    }

    private function requireValidClient(UserInterface $user): Client
    {
        if (!$user instanceof Client) {
            throw new AccountStatusException(
                self::AUTHENTICATION_FAILURE_MESSAGE
            );
        }

        return $user;
    }

    private function assertAccountIsActive(Client $client): void
    {
        if ($client->getIsActive() !== true) {
            throw new LockedException(
                self::AUTHENTICATION_FAILURE_MESSAGE
            );
        }
    }
}
