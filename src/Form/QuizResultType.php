<?php

namespace App\Form;

use App\Entity\Quiz_result;
use App\Entity\Training_program;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuizResultType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user_id')
            ->add('training_id', EntityType::class, [
                'class' => Training_program::class,
                'choice_label' => 'title',
            ])
            ->add('score')
            ->add('total_questions')
            ->add('percentage')
            ->add('passed')
            ->add('completed_at')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz_result::class,
        ]);
    }
}