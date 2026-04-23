<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\Projectassignment;
use App\Entity\UserAccount;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectassignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'name',  // ⚠️ Utilise 'name' pas 'id'
                'label' => 'Projet',
                'attr' => ['class' => 'form-control']
            ])
            ->add('userAccount', EntityType::class, [
                'class' => UserAccount::class,
                'choice_label' => 'username',
                'label' => 'Employé',
                'attr' => ['class' => 'form-control']
            ])
            ->add('role', TextType::class, [
                'label' => 'Rôle',
                'attr' => ['class' => 'form-control']
            ])
            ->add('allocationRate', IntegerType::class, [
                'label' => 'Allocation (%)',
                'attr' => ['class' => 'form-control', 'min' => 0, 'max' => 100]
            ])
            ->add('assignedFrom', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('assignedTo', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Projectassignment::class,
            'csrf_protection' => true,
        ]);
    }
}