<?php

namespace App\Form;

use App\Entity\UserAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin'] ?? false;

        $builder
            ->add('username', TextType::class, [
                'label' => "Nom d'utilisateur",
                'attr'  => [
                    'autocomplete' => 'off',
                    'placeholder'  => 'Ex: jean.dupont',
                    'class'        => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => "Le nom d'utilisateur est obligatoire."]),
                    new Length([
                        'min'        => 3,
                        'max'        => 50,
                        'minMessage' => "Le nom d'utilisateur doit contenir au moins 3 caractères.",
                        'maxMessage' => "Le nom d'utilisateur ne peut pas dépasser 50 caractères.",
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9._\-]+$/',
                        'message' => "Seuls les lettres, chiffres, points, tirets et underscores sont autorisés.",
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr'  => [
                    'autocomplete' => 'off',
                    'placeholder'  => 'exemple@email.com',
                    'class'        => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => "L'email est obligatoire."]),
                    new Email(['message' => "L'adresse email '{{ value }}' n'est pas valide."]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'           => PasswordType::class,
                'mapped'         => false,
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr'  => [
                        'autocomplete' => 'new-password',
                        'placeholder'  => 'Au moins 8 caractères',
                        'class'        => 'form-control',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => [
                        'autocomplete' => 'new-password',
                        'placeholder'  => 'Répéter le mot de passe',
                        'class'        => 'form-control',
                    ],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints'     => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe.']),
                    new Length([
                        'min'        => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/[A-Z]/',
                        'message' => 'Le mot de passe doit contenir au moins une majuscule.',
                    ]),
                    new Regex([
                        'pattern' => '/[0-9]/',
                        'message' => 'Le mot de passe doit contenir au moins un chiffre.',
                    ]),
                ],
            ]);

        // Only admins can pick the role when adding a user
        if ($isAdmin) {
            $builder->add('role', ChoiceType::class, [
                'label'   => 'Rôle',
                'choices' => [
                    'Employé'        => 'EMPLOYE',
                    'Manager'        => 'MANAGER',
                    'Administrateur' => 'ADMINISTRATEUR',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir un rôle.']),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserAccount::class,
            'is_admin'   => false,   // pass true from AdminController::add()
        ]);
    }
}