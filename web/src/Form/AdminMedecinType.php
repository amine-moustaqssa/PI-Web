<?php

namespace App\Form;

use App\Entity\Medecin;
use App\Entity\Specialite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminMedecinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du médecin'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prénom du médecin'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'exemple@clinique360.tn'],
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 12345678'],
                'help' => $isEdit ? null : 'Le CIN sera utilisé comme mot de passe initial.',
            ])
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: MED-001'],
            ])
            ->add('tarif_consultation', NumberType::class, [
                'label' => 'Tarif consultation (TND)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
            ])
            ->add('specialite', EntityType::class, [
                'class' => Specialite::class,
                'choice_label' => 'nom',
                'label' => 'Spécialité',
                'placeholder' => '-- Choisir une spécialité --',
                'required' => false,
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
            'data_class' => Medecin::class,
            'is_edit' => false,
        ]);
    }
}
