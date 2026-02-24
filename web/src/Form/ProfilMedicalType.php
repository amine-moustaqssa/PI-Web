<?php

namespace App\Form;

use App\Entity\ProfilMedical;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfilMedicalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'First Name',
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('date_naissance', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date of Birth',
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('contact_urgence', TextType::class, [
                'label' => 'Emergency Contact',
                'attr' => ['class' => 'form-control mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProfilMedical::class,
        ]);
    }
}
