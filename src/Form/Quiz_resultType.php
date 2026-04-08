<?php

namespace App\Form;

use App\Entity\Quiz_result;
use App\Entity\Training_program;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class QuizResultType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('userId', IntegerType::class, [
                'label' => 'ID Utilisateur',
                'attr' => ['class' => 'form-control']
            ])
            ->add('training', EntityType::class, [
                'class' => Training_program::class,
                'choice_label' => 'title',
                'label' => 'Formation',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'placeholder' => '-- Sélectionner une formation --'
            ])
            ->add('score', IntegerType::class, [
                'label' => 'Score',
                'attr' => ['class' => 'form-control']
            ])
            ->add('totalQuestions', IntegerType::class, [
                'label' => 'Total questions',
                'attr' => ['class' => 'form-control']
            ])
            ->add('percentage', NumberType::class, [
                'label' => 'Pourcentage',
                'attr' => ['class' => 'form-control']
            ])
            ->add('passed', CheckboxType::class, [
                'label' => 'Réussi',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('completedAt', DateTimeType::class, [
                'label' => 'Date de complétion',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz_result::class,
        ]);
    }
}