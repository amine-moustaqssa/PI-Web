<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SymptomTriageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('symptomes', TextareaType::class, [
                'label' => 'Décrivez vos symptômes',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez décrire vos symptômes.']),
                ],
                'attr' => [
                    'rows' => 6,
                    'placeholder' => "Ex: fièvre depuis 2 jours, toux sèche, douleur thoracique...",
                ],
            ])
            ->add('analyser', SubmitType::class, [
                'label' => 'Analyser',
            ]);
    }
}
