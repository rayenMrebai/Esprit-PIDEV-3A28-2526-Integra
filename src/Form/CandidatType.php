<?php

namespace App\Form;

use App\Entity\Candidat;
use App\Entity\Jobposition;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('phone', IntegerType::class, [
                'label' => 'Téléphone',
            ])
            ->add('educationLevel', TextType::class, [
                'label' => "Niveau d'étude",
            ])
            ->add('skills', TextType::class, [
                'label' => 'Compétences',
            ])
            ->add('status', TextType::class, [
                'label' => 'Statut',
            ])
            ->add('jobposition', EntityType::class, [
                'class' => Jobposition::class,
                'choice_label' => 'title',
                'label' => 'Offre',
                'placeholder' => 'Choisir une offre',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidat::class,
        ]);
    }
}