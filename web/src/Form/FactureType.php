<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\Facture;
use App\Entity\Utilisateur; // <--- VÉRIFIEZ CETTE LIGNE (Entity, pas Form)
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference')
            ->add('montantTotal')
            ->add('statut')
            ->add('consultation', EntityType::class, [
                'class' => Consultation::class,
                'choice_label' => 'id',
            ])
            ->add('titulaire', EntityType::class, [
                'class' => Utilisateur::class, // Utilise la classe importée plus haut
                'choice_label' => 'nom', 
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture::class,
        ]);
    }
}