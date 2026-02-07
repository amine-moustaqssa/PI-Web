<?php

namespace App\Form;

use App\Entity\RendezVous;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Bénéficiaire',
                'choices'  => [
                    'Moi même' => 'Moi même',
                    'Mon enfant' => 'Mon enfant',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('date_debut', DateTimeType::class, [
                'label' => 'Date et Heure du rendez-vous',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i') // Empêche de choisir une date passée
                ]
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