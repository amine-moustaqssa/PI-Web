<?php

namespace App\Form;

use App\Entity\ConstanteVitale;
use App\Entity\Consultation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConstanteVitaleAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['hide_consultation']) {
            $builder->add('consultation_id', EntityType::class, [
                'class' => Consultation::class,
                'choice_label' => 'id',
                'label' => 'Consultation',
                'placeholder' => 'Sélectionner une consultation',
                'attr' => ['class' => 'form-control'],
            ]);
        }

        $builder
            ->add('date_prise', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de prise',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'placeholder' => 'Sélectionner un type',
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
                'attr' => ['class' => 'form-control'],
            ])
            ->add('unite', TextType::class, [
                'label' => 'Unité',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: °C, mmHg, bpm...'],
            ])
            ->add('valeur', NumberType::class, [
                'label' => 'Valeur',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: 37.5'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConstanteVitale::class,
            'hide_consultation' => false,
        ]);
    }
}
