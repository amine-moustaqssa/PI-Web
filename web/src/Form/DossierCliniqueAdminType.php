<?php

namespace App\Form;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierCliniqueAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Affiche le champ profil seulement si with_profil = true (création)
        if ($options['with_profil']) {
            $builder->add('profilMedical', EntityType::class, [
                'class' => ProfilMedical::class,
                'choice_label' => fn(ProfilMedical $p) => $p->getNom().' '.$p->getPrenom(),
                'placeholder' => 'Sélectionner un profil',
                'attr' => ['class' => 'form-control select2', 'style' => 'width: 100%'],
                'required' => true,
            ]);
        }

        $builder
            ->add('antecedents', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('allergies', ChoiceType::class, [
    'choices' => [
        'Pollen' => 'Pollen',
        'Gluten' => 'Gluten',
        'Fruits de mer' => 'Fruits de mer',
        'Lactose' => 'Lactose',
        'Médicaments' => 'Médicaments',
        'Autre' => 'Autre',
    ],
    'expanded' => true,
    'multiple' => true,
    'required' => false,
    'empty_data' => [], // IMPORTANT
]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DossierClinique::class,
            'with_profil' => true, // par défaut pour le formulaire new
        ]);
    }
}
