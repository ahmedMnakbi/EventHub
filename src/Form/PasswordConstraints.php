<?php

namespace App\Form;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shared password rules for registration and password change.
 */
final class PasswordConstraints
{
    /** @return list<\Symfony\Component\Validator\Constraint> */
    public static function all(): array
    {
        return [
            new Assert\NotBlank(message: 'Veuillez saisir un mot de passe.'),
            new Assert\Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
            new Assert\PasswordStrength(
                minScore: Assert\PasswordStrength::STRENGTH_MEDIUM,
                message: 'Mot de passe trop faible : ajoutez des chiffres, majuscules ou symboles.',
            ),
        ];
    }
}
