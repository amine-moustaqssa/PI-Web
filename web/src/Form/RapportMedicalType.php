<?php

namespace App\Form;

use App\Entity\RapportMedical;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RapportMedicalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu du rapport',
                'attr' => [
                    'rows' => 10,
                    'class' => 'form-control',
                    'placeholder' => 'Description détaillée du rapport médical...'
                ]
            ])
            ->add('conclusion', TextareaType::class, [
                'label' => 'Conclusion',
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Conclusion du rapport...'
                ]
            ])
            ->add('url_pdf', TextType::class, [
                'label' => 'URL du PDF (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://exemple.com/document.pdf'
                ]
            ]);
        // date_creation et dossierClinique sont gérés dans le contrôleur
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RapportMedical::class,
        ]);
    }
}