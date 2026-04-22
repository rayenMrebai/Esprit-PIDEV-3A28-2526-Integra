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
            ->add('nomRegle', TextType::class, [
                'required' => true,
                'empty_data' => '', // important
            ])

            ->add('percentage', NumberType::class, [
                'required' => true,
                'empty_data' => null, // 🔥 évite crash
            ])

            ->add('conditionText', TextareaType::class, [
                'required' => true,
                'empty_data' => '', // important pour NotBlank
            ]);

        if ($options['is_edit']) {
            $builder->add('status', ChoiceType::class, [
                'choices' => [
                    'Créé' => 'CRÉE',
                    'Active' => 'ACTIVE'
                ],
                'required' => true
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonusRule::class,
            'is_edit' => false,
        ]);
    }
}