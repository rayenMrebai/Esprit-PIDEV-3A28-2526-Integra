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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SkillType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la compétence',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('level_required', IntegerType::class, [
                'label' => 'Niveau requis',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Technique' => 'technique',
                    'Soft skills' => 'soft',
                    'Management' => 'management',
                    'Autre' => 'autre',
                ],
                'placeholder' => '-- Sélectionner une catégorie --',
                'attr' => ['class' => 'form-select']
            ])
            ->add('trainingProgram', EntityType::class, [
                'class' => Training_program::class,
                'choice_label' => 'title',
                'label' => 'Programme de formation',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'placeholder' => '-- Sélectionner un programme --',
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Skill::class,
        ]);
    }
}