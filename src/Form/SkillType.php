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
            ->add('levelRequired', IntegerType::class, [  // ← levelRequired (camelCase)
                'label' => 'Niveau requis',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('trainingProgram', EntityType::class, [
                'class' => Training_program::class,
                'choice_label' => 'title',
                'label' => 'Formation liée',
                'required' => false,
                'placeholder' => '-- Sélectionner une formation --',
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