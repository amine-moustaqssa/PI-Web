<?php

namespace App\Form;

use App\Entity\Medecin;
use App\Entity\Specialite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminMedecinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du médecin'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prénom du médecin'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => ['class' => 'form-control', 'placeholder' => 'exemple@clinique360.tn'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => ['class' => 'form-control', 'placeholder' => '••••••••'],
                'constraints' => $isEdit ? [] : [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        'max' => 4096,
                    ]),
                ],
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
