<?php

namespace App\Form;

use App\Entity\Medecin;
use App\Entity\Specialite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MedecinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, ['required' => false])
            ->add('password', null, ['required' => false])
            ->add('nom', null, ['required' => false])
            ->add('prenom', null, ['required' => false])
            ->add('niveauAcces', null, ['required' => false])
            ->add('adresse', null, ['required' => false])
            ->add('codePostal', null, ['required' => false])
            ->add('matricule', null, ['required' => false])
            ->add('tarif_consultation', null, ['required' => false])
            ->add('specialite', EntityType::class, [
                'class' => Specialite::class,
                'choice_label' => 'id',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Medecin::class,
        ]);
    }
}
