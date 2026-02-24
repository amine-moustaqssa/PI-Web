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
                    'Température corporelle' => 'Température corporelle',
                    'Pression artérielle systolique' => 'Pression artérielle systolique',
                    'Pression artérielle diastolique' => 'Pression artérielle diastolique',
                    'Fréquence cardiaque' => 'Fréquence cardiaque',
                    'Fréquence respiratoire' => 'Fréquence respiratoire',
                    'Saturation en oxygène (SpO2)' => 'Saturation en oxygène (SpO2)',
                    'Glycémie à jeun' => 'Glycémie à jeun',
                    'Glycémie postprandiale' => 'Glycémie postprandiale',
                    'Indice de masse corporelle (IMC)' => 'Indice de masse corporelle (IMC)',
                    'Poids' => 'Poids',
                    'Taille' => 'Taille',
                    'Tour de taille' => 'Tour de taille',
                    'Cholestérol total' => 'Cholestérol total',
                    'Cholestérol HDL' => 'Cholestérol HDL',
                    'Cholestérol LDL' => 'Cholestérol LDL',
                    'Triglycérides' => 'Triglycérides',
                    'Hémoglobine' => 'Hémoglobine',
                    'Hématocrite' => 'Hématocrite',
                    'Globules blancs (leucocytes)' => 'Globules blancs (leucocytes)',
                    'Globules rouges (érythrocytes)' => 'Globules rouges (érythrocytes)',
                    'Plaquettes' => 'Plaquettes',
                    'Créatinine' => 'Créatinine',
                    'Urée' => 'Urée',
                    'Acide urique' => 'Acide urique',
                    'Transaminases (ALAT)' => 'Transaminases (ALAT)',
                    'Transaminases (ASAT)' => 'Transaminases (ASAT)',
                    'Bilirubine totale' => 'Bilirubine totale',
                    'Protéine C-réactive (CRP)' => 'Protéine C-réactive (CRP)',
                    'Vitesse de sédimentation (VS)' => 'Vitesse de sédimentation (VS)',
                    'INR' => 'INR',
                    'Débit de filtration glomérulaire (DFG)' => 'Débit de filtration glomérulaire (DFG)',
                    'TSH' => 'TSH',
                    'Sodium (Na+)' => 'Sodium (Na+)',
                    'Potassium (K+)' => 'Potassium (K+)',
                    'Calcium (Ca2+)' => 'Calcium (Ca2+)',
                    'Fer sérique' => 'Fer sérique',
                    'Ferritine' => 'Ferritine',
                    'Vitamine D' => 'Vitamine D',
                    'HbA1c (hémoglobine glyquée)' => 'HbA1c (hémoglobine glyquée)',
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
