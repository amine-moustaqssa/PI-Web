<?php

namespace App\Form;

use App\Entity\Disponibilite;
use App\Entity\Medecin;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jourSemaine', ChoiceType::class, [
                'label' => 'Jour de la semaine',
                'choices' => [
                    'Lundi' => 1,
                    'Mardi' => 2,
                    'Mercredi' => 3,
                    'Jeudi' => 4,
                    'Vendredi' => 5,
                    'Samedi' => 6,
                    'Dimanche' => 7,
                ],
                'placeholder' => '-- Choisir un jour --',
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
            ])
            ->add('estRecurrent', ChoiceType::class, [
                'label' => 'Créneau récurrent',
                'choices' => [
                    'Oui (hebdomadaire)' => true,
                    'Non (ponctuel)' => false,
                ],
            ])
        ;

        if (!$options['hide_medecin']) {
            $builder->add('medecin', EntityType::class, [
                'class' => Medecin::class,
                'choice_label' => 'id',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Disponibilite::class,
            'hide_medecin' => false,
        ]);
    }
}
