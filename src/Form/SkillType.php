<?php

namespace App\Form;

use App\Entity\Skill;
use App\Entity\UserAccount;
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
                    new Length(min: 2, max: 100),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description *',
                'required' => true,
                'attr' => ['class' => 'form-control', 'rows' => 4],
                'constraints' => [
                    new NotBlank(message: 'La description est obligatoire.'),
                    new Length(max: 500),
                ],
            ])
            ->add('level_required', IntegerType::class, [
                'label' => 'Niveau requis (1-5) *',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Le niveau requis est obligatoire.'),
                    new Range(min: 1, max: 5),
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
            ])
            ->add('users', EntityType::class, [
                'class' => UserAccount::class,
                'choice_label' => 'email',
                'label' => 'Utilisateurs associés',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'by_reference' => false,  // 👈 CRUCIAL !
                'attr' => [
                    'class' => 'form-select select2-multi',
                    'style' => 'width: 100%;'
                ],
                'help' => 'Sélectionnez les utilisateurs qui auront cette compétence.',
                'help_attr' => ['class' => 'text-muted small']
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
            function ($value) { return $value; },
            function ($value) { return ($value === null || $value === '') ? null : $value; }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Skill::class,
        ]);
    }
}