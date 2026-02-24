<?php

namespace App\Form;

use App\Entity\ConstanteVitale;
use App\Entity\Consultation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConstanteVitaleAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('consultation_id', EntityType::class, [
                'class' => Consultation::class,
                'choice_label' => 'id',
                'label' => 'Consultation',
                'required' => false,
            ])
            ->add('date_prise', null, ['required' => false])
            ->add('type', null, ['required' => false])
            ->add('unite', null, ['required' => false])
            ->add('valeur', null, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConstanteVitale::class,
        ]);
    }
}
