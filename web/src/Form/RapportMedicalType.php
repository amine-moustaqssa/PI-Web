<?php

namespace App\Form;

use App\Entity\RapportMedical;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Vich\UploaderBundle\Form\Type\VichFileType;

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
            ->add('pdfFile', VichFileType::class, [
                'label' => 'Document PDF',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer le fichier',
                'download_uri' => true,
                'download_label' => 'Télécharger le fichier actuel',
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'application/pdf'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RapportMedical::class,
        ]);
    }
}