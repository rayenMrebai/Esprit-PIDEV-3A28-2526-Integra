<?php

namespace App\Form;

use App\Entity\Skill;
use App\Entity\Training_program;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Choice;

class TrainingProgramType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre *',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Formation Symfony'],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'max' => 150,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description *',
                'required' => true,
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Description détaillée de la formation...'],
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire.']),
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (heures) *',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 40'],
                'constraints' => [
                    new NotBlank(['message' => 'La durée est obligatoire.']),
                    new Positive(['message' => 'La durée doit être un nombre positif.'])
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de formation *',
                'choices' => [
                    '' => '-- Choisir un type --',
                    'Présentiel' => 'présentiel',
                    'En ligne' => 'en ligne',
                    'Hybride' => 'hybride',
                ],
                'required' => true,
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Le type de formation est obligatoire.']),
                    new Choice([
                        'choices' => ['présentiel', 'en ligne', 'hybride'],
                        'message' => 'Le type doit être: présentiel, en ligne ou hybride.'
                    ])
                ]
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début *',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'html5' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La date de début est obligatoire.']),
                ]
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin *',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'html5' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La date de fin est obligatoire.']),
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut *',
                'choices' => [
                    '' => '-- Choisir un statut --',
                    'PROGRAMMÉ' => 'PROGRAMMÉ',
                    'EN COURS' => 'EN COURS',
                    'TERMINÉ' => 'TERMINÉ',
                    'ANNULÉ' => 'ANNULÉ',
                ],
                'required' => true,
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Le statut est obligatoire.']),
                    new Choice([
                        'choices' => ['PROGRAMMÉ', 'EN COURS', 'TERMINÉ', 'ANNULÉ'],
                        'message' => 'Le statut doit être: PROGRAMMÉ, EN COURS, TERMINÉ ou ANNULÉ.'
                    ])
                ]
            ])
            ->add('skills', EntityType::class, [
                'class' => Skill::class,
                'label' => 'Compétences associées *',
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-select select2',
                    'style' => 'width: 100%;'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Au moins une compétence doit être associée.']),
                ],
                'help' => 'Le programme doit avoir au moins une compétence associée.',
                'help_attr' => ['class' => 'text-muted small']
            ]);

        // Transformers pour convertir les chaînes vides en null
        $this->addNullTransformer($builder, 'title');
        $this->addNullTransformer($builder, 'description');
        $this->addNullTransformer($builder, 'duration');
        $this->addNullTransformer($builder, 'type');
        $this->addNullTransformer($builder, 'startDate');
        $this->addNullTransformer($builder, 'endDate');
        $this->addNullTransformer($builder, 'status');
    }

    private function addNullTransformer(FormBuilderInterface $builder, string $field): void
    {
        $builder->get($field)->addModelTransformer(new CallbackTransformer(
            function ($value) {
                return $value;
            },
            function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }
                return $value;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Training_program::class,
            'csrf_protection' => true,
        ]);
    }
}