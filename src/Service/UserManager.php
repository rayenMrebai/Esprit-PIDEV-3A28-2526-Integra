<?php

namespace App\Service;

use App\Entity\UserAccount;
use InvalidArgumentException;

class UserManager
{
    private const ALLOWED_ROLES = ['ADMINISTRATEUR', 'MANAGER', 'EMPLOYE'];

    /**
     * Valide les règles métier d'un compte utilisateur.
     *
     * @param UserAccount $user
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validate(UserAccount $user): bool
    {
        // Règle 1 : Le nom d'utilisateur est obligatoire
        if (empty($user->getUsername())) {
            throw new InvalidArgumentException('Le nom d\'utilisateur est obligatoire.');
        }

        // Règle 2 : L'email doit être valide
        $email = $user->getEmail();
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L\'email n\'est pas valide.');
        }

        // Règle 3 : Le rôle doit être parmi les rôles autorisés
        if (!in_array($user->getRole(), self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException('Le rôle est invalide. Rôles autorisés : ' . implode(', ', self::ALLOWED_ROLES));
        }

        return true;
    }
}