<?php

namespace App\Form;

use App\Entity\RendezVous;
use App\Entity\ProfilMedical;
use App\Repository\ProfilMedicalRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $tid = $options['titulaire_id'];

        $builder
            ->add('date_debut', DateTimeType::class, [
                'label' => 'Date et Heure souhaitée',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i')
                ]
            ])
            // ON CHANGE "type" PAR "profil" ICI :
            ->add('profil', EntityType::class, [
                'class' => ProfilMedical::class,
                'label' => 'Pour qui est ce rendez-vous ?',
                'choice_label' => function (ProfilMedical $profil) {
                    return $profil->getPrenom() . ' ' . $profil->getNom();
                },
                'query_builder' => function (ProfilMedicalRepository $er) use ($tid) {
                    return $er->createQueryBuilder('p')
                        ->where('p.titulaire_id = :tid')
                        ->setParameter('tid', $tid);
                },
                'attr' => ['class' => 'form-select mb-3'],
                'placeholder' => 'Choisissez le bénéficiaire'
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif de la consultation',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez brièvement vos symptômes...'
                ],
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
            'titulaire_id' => null, 
        ]);
        $resolver->setRequired('titulaire_id');
    }
}