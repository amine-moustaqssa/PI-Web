<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminPersonnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du personnel'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prénom du personnel'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => ['class' => 'form-control', 'placeholder' => 'exemple@clinique360.tn'],
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'required' => !$isEdit,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 12345678', 'maxlength' => 8],
                'help' => $isEdit ? null : 'Le CIN sera utilisé comme mot de passe initial.',
            ])
            ->add('niveauAcces', ChoiceType::class, [
                'label' => 'Niveau d\'accès',
                'required' => false,
                'placeholder' => '-- Choisir un niveau --',
                'choices' => [
                    'Infirmier' => 'INFIRMIER',
                    'Réceptionniste' => 'RECEPTIONIST',
                    'Technicien' => 'TECHNICIEN',
                    'Secrétaire' => 'SECRETAIRE',
                    'Accueil' => 'ACCUEIL',
                    'Gestionnaire' => 'GESTIONNAIRE',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse complète'],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 1000'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'is_edit' => false,
        ]);
    }
}
