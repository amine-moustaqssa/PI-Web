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

        // ==========================================
        // 1. HANDLE NEW APPOINTMENT MODAL FORM
        // ==========================================
        $rendezVous = new RendezVous();
        // Passing 'titulaire_id' as option to FormType
        $form = $this->createForm(RendezVousType::class, $rendezVous, ['titulaire_id' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $specialiteId = $request->request->get('specialite_id');
            $specialite = $specRepo->find($specialiteId);
            $profil = $rendezVous->getProfil();

            $rendezVous->setStatut('en attente de confirmation');

            // Generate a descriptive Type string
            $specName = $specialite ? $specialite->getNom() : 'Consultation';
            $rendezVous->setType($profil->getNom() . ' ' . $profil->getPrenom() . ' [' . $specName . ']');

            // Set End Date (+30 mins)
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

        // ==========================================
        // 2. FETCH APPOINTMENT LIST
        // ==========================================
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
        // Security Check: Ensure user owns this appointment
        if ($rendezVous->getProfil()->getTitulaire() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(RendezVousType::class, $rendezVous, [
            'titulaire_id' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($rendezVous->getDateDebut() instanceof \DateTimeInterface) {
                $dateFin = \DateTime::createFromInterface($rendezVous->getDateDebut());
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Rendez-vous mis à jour.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // --- CRITICAL CHANGE FOR MODAL ---
        // If this is an AJAX request (triggered by your JS), return ONLY the form HTML
        if ($request->isXmlHttpRequest()) {
            return $this->render('rendez_vous/_form_edit.html.twig', [
                'form' => $form->createView(),
                'rendezVous' => $rendezVous
            ]);
        }

        // Fallback for non-JS users (Standard page)
        return $this->render('rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
            'rendezVous' => $rendezVous
        ]);
    }

    #[Route('/mes-rendez-vous/annuler/{id}', name: 'app_rendez_vous_cancel')]
    public function cancel(RendezVous $rendezVous, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($rendezVous->getProfil()->getTitulaire() === $user) {
            $rendezVous->setStatut('annulé');
            $entityManager->flush();
            $this->addFlash('warning', 'Le rendez-vous a été annulé.');
        }
        return $this->redirectToRoute('app_mes_rendez_vous');
    }

    // Optional: Keep this only if you need a standalone "New Page" fallback
    #[Route('/nouveau-rendez-vous', name: 'app_rendez_vous_new')]
    public function new(
        Request $request,
        SpecialiteRepository $specRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $specialiteId = $request->query->get('specialite');
        if (!$specialiteId) {
            return $this->render('rendez_vous/step1_specialite.html.twig', [
                'specialites' => $specRepo->findAll()
            ]);
        }

        $specialite = $specRepo->find($specialiteId);
        $rendezVous = new RendezVous();

        $form = $this->createForm(RendezVousType::class, $rendezVous, [
            'titulaire_id' => $user
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $rendezVous->getProfil();
            $rendezVous->setStatut('en attente de confirmation');
            $rendezVous->setType($profil->getNom() . ' ' . $profil->getPrenom() . ' [' . $specialite->getNom() . ']');

            if ($rendezVous->getDateDebut() instanceof \DateTimeInterface) {
                $dateFin = \DateTime::createFromInterface($rendezVous->getDateDebut());
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }

            $entityManager->persist($rendezVous);
            $entityManager->flush();

            $this->addFlash('success', 'Demande enregistrée pour ' . $profil->getPrenom());
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        return $this->render('rendez_vous/step3_details.html.twig', [
            'specialite' => $specialite,
            'form' => $form->createView()
        ]);
    }
}
