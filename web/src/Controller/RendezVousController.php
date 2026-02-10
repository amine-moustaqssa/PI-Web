<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\SpecialiteRepository;
use App\Repository\RendezVousRepository;
use App\Repository\ProfilMedicalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RendezVousController extends AbstractController
{
    #[Route('/mes-rendez-vous', name: 'app_mes_rendez_vous')]
    public function index_client(
        Request $request,
        RendezVousRepository $rendezVousRepository,
        ProfilMedicalRepository $profilRepo,
        SpecialiteRepository $specRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // --- FORMULAIRE D'AJOUT ---
        $rendezVous = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rendezVous, ['titulaire_id' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Get the ID from the request
            $specialiteId = $request->request->get('specialite_id');

            // 2. CRITICAL SECURITY CHECK: Prevent crash if ID is missing
            if (!$specialiteId) {
                $this->addFlash('danger', 'Veuillez sélectionner une spécialité avant de confirmer.');
                // Re-render the page with errors (do not redirect)
                // We fetch the data again to render the view correctly
                return $this->render('rendez_vous/index_client.html.twig', [
                    'rendez_vous' => $rendezVousRepository->findBy(['profil' => $profilRepo->findBy(['titulaire' => $user])], ['date_debut' => 'DESC']),
                    'form'        => $form->createView(),
                    'specialites' => $specRepo->findAll(),
                ]);
            }

            $specialite = $specRepo->find($specialiteId);
            $profil = $rendezVous->getProfil();

            $rendezVous->setStatut('en attente de confirmation');
            $specName = $specialite ? $specialite->getNom() : 'Consultation';
            $rendezVous->setType($profil->getNom() . ' ' . $profil->getPrenom() . ' [' . $specName . ']');

            if ($rendezVous->getDateDebut() instanceof \DateTimeInterface) {
                $dateFin = \DateTime::createFromInterface($rendezVous->getDateDebut());
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }

            $entityManager->persist($rendezVous);
            $entityManager->flush();

            $this->addFlash('success', 'Rendez-vous ajouté avec succès !');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // --- LISTE DES RENDEZ-VOUS ---
        $profilsFamille = $profilRepo->findBy(['titulaire' => $user]);
        $mesRendezVous = $rendezVousRepository->findBy(
            ['profil' => $profilsFamille],
            ['date_debut' => 'DESC']
        );

        return $this->render('rendez_vous/index_client.html.twig', [
            'rendez_vous' => $mesRendezVous,
            'form'        => $form->createView(),
            'specialites' => $specRepo->findAll(),
        ]);
    }

    #[Route('/mes-rendez-vous/modifier/{id}', name: 'app_rendez_vous_edit')]
    public function edit(RendezVous $rendezVous, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        // Vérification de sécurité
        if ($rendezVous->getProfil()->getTitulaire() !== $user && $rendezVous->getProfil()->getTitulaireId() != 7) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(RendezVousType::class, $rendezVous, ['titulaire_id' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($rendezVous->getDateDebut() instanceof \DateTimeInterface) {
                $dateFin = \DateTime::createFromInterface($rendezVous->getDateDebut());
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Modifications enregistrées.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // Check if this is an AJAX request
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('rendez_vous/_edit_modal.html.twig', [
                'form' => $form->createView(),
                'rendezVous' => $rendezVous
            ]);
        }

        return $this->render('rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
            'rendezVous' => $rendezVous
        ]);
    }

    #[Route('/mes-rendez-vous/annuler/{id}', name: 'app_rendez_vous_cancel')]
    public function cancel(RendezVous $rendezVous, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($rendezVous->getProfil()->getTitulaire() === $user || $rendezVous->getProfil()->getTitulaireId() == 7) {
            $rendezVous->setStatut('annulé');
            $entityManager->flush();
            $this->addFlash('warning', 'Le rendez-vous a été annulé.');
        }
        return $this->redirectToRoute('app_mes_rendez_vous');
    }
}
