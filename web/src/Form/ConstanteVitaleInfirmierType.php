<?php 

namespace App\Form; 

use App\Entity\ConstanteVitale; 
use Symfony\Component\Form\AbstractType; 
use Symfony\Component\Form\FormBuilderInterface; 
use Symfony\Component\OptionsResolver\OptionsResolver; 
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; 

class ConstanteVitaleInfirmierType extends AbstractType 
{
    public function buildForm(FormBuilderInterface $builder, array $options): void 
    {
        $builder
            ->add('type', null, ['required' => false])
            ->add('valeur', null, ['required' => false])
            ->add('unite', null, ['required' => false])
            ->add('date_prise', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void 
    {
        $resolver->setDefaults([
            'data_class' => ConstanteVitale::class,
        ]);
    }
}
