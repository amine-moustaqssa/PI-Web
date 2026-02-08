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
    #[Route('/nouveau-rendez-vous', name: 'app_rendez_vous_new')]
    public function new(
        Request $request, 
        SpecialiteRepository $specRepo, 
        EntityManagerInterface $entityManager
    ): Response {
        
        $specialiteId = $request->query->get('specialite');
        if (!$specialiteId) {
            return $this->render('rendez_vous/step1_specialite.html.twig', [
                'specialites' => $specRepo->findAll()
            ]);
        }

        $specialite = $specRepo->find($specialiteId);
        $rendezVous = new RendezVous();
        
        $form = $this->createForm(RendezVousType::class, $rendezVous, [
            'titulaire_id' => '6' 
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $rendezVous->getProfil(); 
            $rendezVous->setStatut('en attente de confirmation');
            $rendezVous->setType($profil->getNom() . ' ' . $profil->getPrenom() . ' [' . $specialite->getNom() . ']');

            // CORRECTION VS CODE : On s'assure que c'est un objet DateTime pour utiliser modify()
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

    #[Route('/mes-rendez-vous', name: 'app_mes_rendez_vous')]
    public function index_client(RendezVousRepository $rendezVousRepository, ProfilMedicalRepository $profilRepo): Response
    {
        $profilsFamille = $profilRepo->findBy(['titulaire_id' => '6']);
        $mesRendezVous = $rendezVousRepository->findBy(
            ['profil' => $profilsFamille], 
            ['date_debut' => 'DESC']
        );

        return $this->render('rendez_vous/index_client.html.twig', [
            'rendez_vous' => $mesRendezVous,
        ]);
    }

    #[Route('/mes-rendez-vous/modifier/{id}', name: 'app_rendez_vous_edit')]
    public function edit(RendezVous $rendezVous, Request $request, EntityManagerInterface $entityManager): Response 
    {
        // Sécurité : vérifier que le RDV appartient à la famille 6
        if ($rendezVous->getProfil()->getTitulaireId() !== "6") {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(RendezVousType::class, $rendezVous, [
            'titulaire_id' => '6'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalcul de la date de fin
            if ($rendezVous->getDateDebut() instanceof \DateTimeInterface) {
                $dateFin = \DateTime::createFromInterface($rendezVous->getDateDebut());
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Rendez-vous mis à jour.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        return $this->render('rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
            'rendezVous' => $rendezVous
        ]);
    }

    #[Route('/mes-rendez-vous/annuler/{id}', name: 'app_rendez_vous_cancel')]
    public function cancel(RendezVous $rendezVous, EntityManagerInterface $entityManager): Response
    {
        if ($rendezVous->getProfil()->getTitulaireId() === "6") {
            $rendezVous->setStatut('annulé');
            $entityManager->flush();
            $this->addFlash('warning', 'Le rendez-vous a été annulé.');
        }
        return $this->redirectToRoute('app_mes_rendez_vous');
    }
}