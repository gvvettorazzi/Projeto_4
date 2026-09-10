<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Client;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ClientChecker implements UserCheckerInterface
{
    private const AUTHENTICATION_ERROR_MESSAGE = 'Unable to authenticate user.';

    /**
     * Executa validações de segurança antes da autenticação.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Client) {
            return;
        }

        $this->validateClient($user);
    }

    /**
     * Executa verificações adicionais após a autenticação.
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof Client) {
            return;
        }

        // Reservado para verificações adicionais pós-autenticação.
    }

    /**
     * Valida se o cliente está autorizado a prosseguir
     * com o processo de autenticação.
     */
    private function validateClient(Client $client): void
    {
        if (!$this->isActiveClient($client)) {
            throw new LockedException(
                self::AUTHENTICATION_ERROR_MESSAGE
            );
        }
    }

    /**
     * Verifica de forma explícita o estado da conta.
     */
    private function isActiveClient(Client $client): bool
    {
        return $client->getIsActive() === true;
    }
}
