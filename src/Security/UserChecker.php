<?php
namespace App\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Psr\Log\LoggerInterface;

class UserChecker implements UserCheckerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function checkPreAuth(UserInterface $user): void
    {
        // Log para verificar si se está ejecutando el UserChecker
        $this->logger->info('UserChecker invoked for user: ' . $user->getEmail());

        if (method_exists($user, 'getDeletedAt') && $user->getDeletedAt() !== null) {
            $this->logger->warning('User account is deactivated: ' . $user->getEmail());
            throw new CustomUserMessageAuthenticationException('Your account has been deactivated.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // post-autenticación ...
    }
}
