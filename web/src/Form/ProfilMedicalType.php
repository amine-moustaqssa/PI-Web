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
                'required' => false,
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'First Name',
                'required' => false,
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('date_naissance', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date of Birth',
                'required' => false,
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('contact_urgence', TextType::class, [
                'label' => 'Emergency Contact',
                'required' => false,
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
