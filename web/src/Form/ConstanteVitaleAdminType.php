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
            ])
            ->add('date_prise')
            ->add('type')
            ->add('unite')
            ->add('valeur');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConstanteVitale::class,
        ]);
    }
}
