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
        // This contains the User object passed from the controller
        $user = $options['titulaire_id'];

        $builder
            ->add('date_debut', DateTimeType::class, [
                'label' => 'Date et Heure souhaitée',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control mb-3',
                ]
            ])
            ->add('profil', EntityType::class, [
                'class' => ProfilMedical::class,
                'label' => 'Pour qui est ce rendez-vous ?',
                'choice_label' => function (ProfilMedical $profil) {
                    return $profil->getPrenom() . ' ' . $profil->getNom();
                },
                'query_builder' => function (ProfilMedicalRepository $er) use ($user) {
                    return $er->createQueryBuilder('p')
                        ->where('p.titulaire = :user')
                        ->setParameter('user', $user);
                },
                'attr' => ['class' => 'form-select mb-3'],
                'placeholder' => 'Choisissez le bénéficiaire',
                'required' => false,
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif de la consultation',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez brièvement vos symptômes...'
                ],
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
        ]);

        // We define that this form MUST receive a 'titulaire_id' option
        $resolver->setRequired('titulaire_id');
    }
}
