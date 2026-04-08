<?php

namespace App\Form;

use App\Entity\BonusRule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BonusRuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomRegle')
            ->add('percentage')
            ->add('conditionText');

        // 🔥 ajouter status seulement si edit
        if ($options['is_edit']) {
            $builder->add('status', ChoiceType::class, [
                'choices' => [
                    'Créé' => 'CRÉE',
                    'Active' => 'ACTIVE'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonusRule::class,
            'is_edit' => false, // 🔥 important
        ]);
    }
}