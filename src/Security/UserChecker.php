<?php

namespace App\Security;

use App\Entity\UserAccount;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof UserAccount) {
            return;
        }

        if (!$user->getIsActive()) {
            throw new DisabledException('Your account has been disabled due to inactivity. Please contact an administrator.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // nothing needed
    }
}