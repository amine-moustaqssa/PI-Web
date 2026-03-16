<?php 

namespace App\Form; 

use App\Entity\ConstanteVitale; 
use Symfony\Component\Form\AbstractType; 
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface; 
use Symfony\Component\OptionsResolver\OptionsResolver; 
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; 

class ConstanteVitaleInfirmierType extends AbstractType 
{
    public function buildForm(FormBuilderInterface $builder, array $options): void 
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Température'                => 'temperature',
                    'Fréquence cardiaque'        => 'frequence cardiaque',
                    'Pouls'                      => 'pouls',
                    'Tension systolique'         => 'tension systolique',
                    'Tension diastolique'        => 'tension diastolique',
                    'Pression artérielle moyenne' => 'pression arterielle moyenne',
                    'Saturation O2 (SpO2)'       => 'saturation o2',
                    'Fréquence respiratoire'     => 'frequence respiratoire',
                    'Glycémie à jeun'            => 'glycemie a jeun',
                    'Glycémie'                   => 'glycemie',
                    'Glycémie postprandiale'     => 'glycemie postprandiale',
                    'IMC'                        => 'imc',
                    'Débit cardiaque'            => 'debit cardiaque',
                    'Diurèse'                    => 'diurese',
                    'Score de Glasgow'           => 'glasgow',
                    'Douleur (EVA)'              => 'douleur',
                    'Hémoglobine'                => 'hemoglobine',
                    'Créatinine'                 => 'creatinine',
                    'Potassium (Kaliémie)'       => 'potassium',
                    'Sodium (Natrémie)'          => 'sodium',
                    'Plaquettes'                 => 'plaquettes',
                    'Leucocytes'                 => 'leucocytes',
                ],
                'placeholder' => '-- Choisir un type --',
                'required' => true,
                'attr' => ['id' => 'constante_type_select'],
            ])
            ->add('valeur')
            ->add('unite')
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
