<?php

namespace App\Form;

use App\Entity\Disponibilite;
use App\Entity\Medecin;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\DateType;

class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateSpecifique', DateType::class, [
                'label' => 'Date spécifique (pour un test ou un créneau unique)',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('estRecurrent', CheckboxType::class, [
                'label' => 'Créneau récurrent',
                'required' => false,
            ])
        ;

        if (!$options['hide_medecin']) {
            $builder->add('medecin', EntityType::class, [
                'class' => Medecin::class,
                // Uses Medecin::__toString() automatically
                'placeholder' => '-- Sélectionner un médecin --',
                'required' => false,
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
