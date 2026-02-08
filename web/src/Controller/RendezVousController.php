<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\SpecialiteRepository;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RendezVousController extends AbstractController
{
    /**
     * Tunnel de création de rendez-vous (2 Étapes)
     */
    #[Route('/nouveau-rendez-vous', name: 'app_rendez_vous_new')]
    public function new(
        Request $request, 
        SpecialiteRepository $specRepo, 
        EntityManagerInterface $entityManager
    ): Response {
        
        // 1. Étape 1 : Choix de la spécialité
        $specialiteId = $request->query->get('specialite');
        if (!$specialiteId) {
            return $this->render('rendez_vous/step1_specialite.html.twig', [
                'specialites' => $specRepo->findAll()
            ]);
        }

        // 2. Étape 2 : Formulaire direct (Date, Bénéficiaire, Motif)
        $specialite = $specRepo->find($specialiteId);
        $rendezVous = new RendezVous();
        
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rendezVous->setStatut('En attente');
            $rendezVous->setProfilId("1"); 
            
            // On stocke la spécialité dans le type pour que les médecins puissent filtrer
            $beneficiaire = $form->get('type')->getData(); 
            $rendezVous->setType($beneficiaire . ' [' . $specialite->getNom() . ']');

            // Calcul date fin
            if ($rendezVous->getDateDebut()) {
                $dateFin = clone $rendezVous->getDateDebut();
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }

            $entityManager->persist($rendezVous);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande en ' . $specialite->getNom() . ' a été enregistrée. Un médecin disponible vous confirmera le créneau sous peu.');
            
            return $this->redirectToRoute('app_mes_rendez_vous'); 
        }

        return $this->render('rendez_vous/step3_details.html.twig', [
            'specialite' => $specialite, // On passe la spécialité au lieu du médecin
            'form' => $form->createView() 
        ]);
    }

    /**
     * US-3.5 : Dashboard Client - Liste des rendez-vous
     */
    #[Route('/mes-rendez-vous', name: 'app_mes_rendez_vous')]
    public function index_client(RendezVousRepository $rendezVousRepository): Response
    {
        // On récupère les RDV de l'utilisateur "1" triés du plus récent au plus ancien
        $mesRendezVous = $rendezVousRepository->findBy(
            ['profil_id' => '1'], 
            ['date_debut' => 'DESC']
        );

        return $this->render('rendez_vous/index_client.html.twig', [
            'rendez_vous' => $mesRendezVous,
        ]);
    }

    /**
     * US-3.5 : Action d'annulation par le client
     */
    #[Route('/mes-rendez-vous/annuler/{id}', name: 'app_rendez_vous_cancel')]
    public function cancel(RendezVous $rendezVous, EntityManagerInterface $entityManager): Response
    {
        // Sécurité : On vérifie que le RDV appartient bien à l'utilisateur "1"
        if ($rendezVous->getProfilId() === "1") {
            $rendezVous->setStatut('Annulé'); // Mettre la majuscule si c'est ta convention
            $entityManager->flush();
            $this->addFlash('warning', 'Le rendez-vous a été annulé.');
        } else {
            $this->addFlash('danger', 'Action non autorisée.');
        }

        return $this->redirectToRoute('app_mes_rendez_vous');
    }

    /**
     * Modification du rendez-vous
     */
    #[Route('/mes-rendez-vous/modifier/{id}', name: 'app_rendez_vous_edit')]
    public function edit(RendezVous $rendezVous, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Sécurité
        if ($rendezVous->getProfilId() !== "1") {
            throw $this->createAccessDeniedException();
        }

        // --- SAUVEGARDE DU TYPE ORIGINAL ---
        // Avant que le formulaire n'écrase les données, on garde le texte actuel
        // ex: "Mon enfant (Dr. Ben Romdhane)"
        $ancienTypeString = $rendezVous->getType(); 

        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- RECONSTRUCTION DU TYPE ---
            // Le formulaire a mis juste "Moi-même" dans l'entité. Il faut remettre le médecin.
            
            // 1. On extrait la partie médecin "(Dr. ...)" de l'ancien string
            $matches = [];
            preg_match('/\((.*?)\)/', $ancienTypeString, $matches);
            $suffixeMedecin = isset($matches[0]) ? $matches[0] : ''; // ex: "(Dr. Ben Romdhane)"

            // 2. On récupère le nouveau choix de bénéficiaire (ex: "Moi-même")
            $nouveauBeneficiaire = $rendezVous->getType(); 

            // 3. On fusionne
            $rendezVous->setType($nouveauBeneficiaire . ' ' . $suffixeMedecin);
            // -----------------------------

            // Recalcul de la date de fin au cas où l'heure change
            if ($rendezVous->getDateDebut()) {
                $dateFin = clone $rendezVous->getDateDebut();
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre rendez-vous a été modifié avec succès.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        return $this->render('rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
            'rendezVous' => $rendezVous
        ]);
    }
}   