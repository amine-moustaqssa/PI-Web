<?php

namespace App\Form;

use App\Entity\RendezVous;
use App\Entity\ProfilMedical;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RendezVousType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profil', EntityType::class, [
                'class' => ProfilMedical::class,
                'choices' => $options['titulaire_id'] ? $options['titulaire_id']->getProfilsMedicaux() : [],

                'choice_label' => function (ProfilMedical $p) { return $p->getNom() . ' ' . $p->getPrenom(); },
                'placeholder' => 'Choisir le patient...',
                'constraints' => [
                    new NotBlank(['message' => 'Ce champ est obligatoire.']),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('motif', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Ce champ est obligatoire.']),
                ],
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ]);

        if ($options['is_edit']) {
            $builder->add('date_debut', DateTimeType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Ce champ est obligatoire.']),
                ],
                'attr' => ['class' => 'form-control']
            ]);
        } else {
            $builder->add('date_debut', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Ce champ est obligatoire.']),
                ],
                'attr' => ['class' => 'form-control']
            ]);

            $builder->add('heure_choisie', HiddenType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir un créneau horaire.']),
                ],
            ]);
        }

        if ($options['include_medecin']) {
            $builder->add('medecin', EntityType::class, [
                'class' => Utilisateur::class,
                'placeholder' => 'Sélectionnez une spécialité d\'abord',
                'choices' => [], // Sera rempli par le JS et validé par l'event ci-dessous
                'constraints' => [
                    new NotBlank(['message' => 'Ce champ est obligatoire.']),
                ],
                'attr' => ['class' => 'form-control']
            ]);
        }

        // --- CE BLOC EST INDISPENSABLE POUR LA VALIDATION ---
        if ($options['include_medecin']) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
                if (array_key_exists('medecin', $data)) {
                    $medecinChoice = [];
                    if (!empty($data['medecin'])) {
                        $medecin = $this->entityManager->getRepository(Utilisateur::class)->find($data['medecin']);
                        if ($medecin) {
                            $medecinChoice = [$medecin];
                        }
                    }

                    $form->add('medecin', EntityType::class, [
                        'class' => Utilisateur::class,
                        'choices' => $medecinChoice,
                        'constraints' => [
                            new NotBlank(['message' => 'Ce champ est obligatoire.']),
                        ],
                        'attr' => ['class' => 'form-control']
                    ]);
                }
            });
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
            'titulaire_id' => null,
            'include_medecin' => true,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('include_medecin', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}