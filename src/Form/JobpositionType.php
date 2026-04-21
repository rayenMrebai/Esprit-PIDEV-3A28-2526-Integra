<?php

namespace App\Form;

use App\Entity\Jobposition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobpositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('departement', TextType::class, [
                'label' => 'Département',
            ])
            ->add('employeeType', TextType::class, [
                'label' => 'Type',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('status', TextType::class, [
                'label' => 'Statut',
            ])
            ->add('postedAt', DateType::class, [
                'label' => 'Publié le',
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jobposition::class,
        ]);
    }
}