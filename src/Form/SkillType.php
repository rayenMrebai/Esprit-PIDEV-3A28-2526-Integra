<?php

namespace App\Form;

use App\Entity\Skill;
use App\Entity\Training_program;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class SkillType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la compétence *',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(
                        min: 2, max: 100,
                        minMessage: 'Minimum {{ limit }} caractères.',
                        maxMessage: 'Maximum {{ limit }} caractères.'
                    ),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description *',
                'required' => true,
                'attr' => ['class' => 'form-control', 'rows' => 4],
                'constraints' => [
                    new NotBlank(message: 'La description est obligatoire.'),
                    new Length(max: 500, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            ->add('level_required', IntegerType::class, [
                'label' => 'Niveau requis (1-5) *',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Le niveau requis est obligatoire.'),
                    new Range(
                        min: 1, max: 5,
                        notInRangeMessage: 'Le niveau doit être entre {{ min }} et {{ max }}.'
                    ),
                ],
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie *',
                'required' => true,
                'placeholder' => '-- Choisir --',
                'choices' => [
                    'Technique' => 'technique',
                    'Soft skill' => 'soft',
                    'Management' => 'management',
                    'Autre' => 'autre',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(message: 'La catégorie est obligatoire.'),
                ],
            ])
            ->add('trainingProgram', EntityType::class, [
                'class' => Training_program::class,
                'choice_label' => 'title',
                'label' => 'Programme de formation *',
                'required' => true,
                'placeholder' => '-- Choisir --',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(message: 'Le programme de formation est obligatoire.'),
                ],
            ]);

        // Transformers pour convertir les chaînes vides en null
        $this->addNullTransformer($builder, 'nom');
        $this->addNullTransformer($builder, 'description');
        $this->addNullTransformer($builder, 'level_required');
        $this->addNullTransformer($builder, 'categorie');
        $this->addNullTransformer($builder, 'trainingProgram');
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
            'data_class' => Skill::class,
        ]);
    }
}