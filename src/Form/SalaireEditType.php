<?php

namespace App\Form;

use App\Entity\Salaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalaireEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'CRÉÉ' => 'CREÉ',
                    'EN COURS' => 'EN_COURS',
                    'PAYÉ' => 'PAYÉ',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('datePaiement', DateType::class, [
                'label' => 'Date de Paiement',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Mettre à Jour',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Salaire::class,
        ]);
    }
}