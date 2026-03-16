<?php

namespace App\Form;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierCliniqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('allergies', null, ['required' => false])
            ->add('antecedents', null, ['required' => false])
            ->add('profilMedical', EntityType::class, [
                'class' => ProfilMedical::class,
                'choice_label' => 'id',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierClinique::class,
        ]);
    }
}
