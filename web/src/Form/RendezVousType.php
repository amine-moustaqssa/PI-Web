<?php

namespace App\Form;

use App\Entity\RendezVous;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType; // <-- Import important
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1. Choix de la Date et de l'Heure
            ->add('date_debut', DateTimeType::class, [
                'label' => 'Date et Heure souhaitée',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i')
                ]
            ])
            // 2. Choix du Bénéficiaire (Le menu déroulant demandé)
            ->add('type', ChoiceType::class, [
                'label' => 'Pour qui est ce rendez-vous ?',
                'choices'  => [
                    'Pour moi-même' => 'Moi-même',
                    'Pour mon enfant' => 'Mon enfant',
                    'Pour un proche' => 'Un proche',
                ],
                'attr' => ['class' => 'form-select mb-3']
            ])
            // 3. Le Motif (Nouvelle zone de texte)
            ->add('motif', TextareaType::class, [
                'label' => 'Motif de la consultation (Décrivez brièvement vos symptômes)',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Ex: Douleurs abdominales, fièvre, renouvellement ordonnance...'
                ],
                'required' => true // Vous pouvez le mettre à false si optionnel
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
        ]);
    }
}