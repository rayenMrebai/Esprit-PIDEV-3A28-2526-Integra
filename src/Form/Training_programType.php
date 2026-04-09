<?php

namespace App\Form;

use App\Entity\Skill;
use App\Entity\Training_program;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TrainingProgramType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (heures)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Présentiel' => 'présentiel',
                    'En ligne' => 'en ligne',
                    'Hybride' => 'hybride',
                ],
                'placeholder' => '-- Sélectionner un type --',
                'attr' => ['class' => 'form-select']
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Programmé' => 'PROGRAMMÉ',
                    'En cours' => 'EN COURS',
                    'Terminé' => 'TERMINÉ',
                    'Annulé' => 'ANNULÉ',
                ],
                'placeholder' => '-- Sélectionner un statut --',
                'attr' => ['class' => 'form-select']
            ])
            ->add('skills', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'nom',
                'label' => 'Compétences associées',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'attr' => ['class' => 'form-select', 'size' => 6],
                'help' => 'Sélectionnez au moins une compétence (Ctrl+clic pour plusieurs)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Training_program::class,
        ]);
    }
}