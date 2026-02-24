<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'required' => false,
                'label'    => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: FAC-2024-001',
                ],
            ])
            ->add('montantTotal', NumberType::class, [
                'required' => false,
                'label'    => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: 250.00',
                    'step'        => '0.01',
                    'min'         => '0',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'required' => false,
                'label'    => false,
                'attr'     => ['class' => 'form-control'],
                'choices'  => [
                    'Payée'      => 'PAYEE',
                    'En attente' => 'EN_ATTENTE',
                    'Annulée'    => 'ANNULEE',
                ],
                'placeholder' => '-- Sélectionner un statut --',
            ])
            ->add('consultation', EntityType::class, [
                'class'        => Consultation::class,
                'choice_label' => 'id',
                'required'     => false,
                'label'        => false,
                'attr'         => ['class' => 'form-control'],
                'placeholder'  => '-- Sélectionner une consultation --',
            ])
            ->add('titulaire', EntityType::class, [
                'class'        => Utilisateur::class,
                'choice_label' => 'nom',
                'required'     => false,
                'label'        => false,
                'attr'         => ['class' => 'form-control'],
                'placeholder'  => '-- Sélectionner un titulaire --',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture::class,
        ]);
    }
}