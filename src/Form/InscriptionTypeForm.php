<?php
// src/Form/InscriptionType.php

namespace App\Form;

use App\Entity\Inscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motivation', TextareaType::class, [
                'label' => 'Motivation *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Expliquez pourquoi vous souhaitez suivre cette formation...'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez expliquer votre motivation.']),
                    new Length([
                        'min' => 20,
                'minMessage' => 'Votre motivation doit contenir au moins {{ limit }} caractères.',
                        'max' => 1000,
                        'maxMessage' => 'Votre motivation ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inscription::class,
        ]);
    }
}