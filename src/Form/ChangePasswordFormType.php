<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr' => ['autocomplete' => 'current-password'],
                'constraints' => [new UserPassword(message: 'Mot de passe actuel incorrect.')],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'first_options' => ['label' => 'Nouveau mot de passe', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Confirmer le nouveau mot de passe', 'attr' => ['autocomplete' => 'new-password']],
                'constraints' => PasswordConstraints::all(),
            ])
        ;
    }
}
