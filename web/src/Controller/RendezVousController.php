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
     * Tunnel de création de rendez-vous (3 Étapes)
     */
    #[Route('/nouveau-rendez-vous', name: 'app_rendez_vous_new')]
    public function new(
        Request $request, 
        SpecialiteRepository $specRepo, 
        MedecinRepository $medRepo,
        EntityManagerInterface $entityManager
    ): Response {
        
        // 1. Étape 1 : Choix de la spécialité
        $specialiteId = $request->query->get('specialite');
        if (!$specialiteId) {
            return $this->render('rendez_vous/step1_specialite.html.twig', [
                'specialites' => $specRepo->findAll()
            ]);
        }

        // 2. Étape 2 : Choix du médecin (filtré par spécialité)
        $medecinId = $request->query->get('medecin');
        if (!$medecinId) {
            $specialite = $specRepo->find($specialiteId);
            return $this->render('rendez_vous/step2_medecin.html.twig', [
                'specialite' => $specialite,
                'medecins' => $medRepo->findBy(['specialite' => $specialite])
            ]);
        }

        // 3. Étape 3 : Formulaire final (Date et Type)
        $medecin = $medRepo->find($medecinId);
        $rendezVous = new RendezVous();
        
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Remplissage manuel des colonnes obligatoires (defaultdb)
            $rendezVous->setStatut('en attente de confirmation');
            $rendezVous->setProfilId("1"); // Utilisateur test
            
            // Astuce : On inclut le nom du médecin dans le type pour ne pas perdre l'info
            $typeOriginal = $form->get('type')->getData();
            $rendezVous->setType($typeOriginal . ' (Dr. ' . $medecin->getNom() . ')');

            // Calcul automatique de la date de fin (+30 minutes)
            if ($rendezVous->getDateDebut()) {
                $dateFin = clone $rendezVous->getDateDebut();
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }

            $entityManager->persist($rendezVous);
            $entityManager->flush();

            $this->addFlash('success', 'Demande enregistrée ! En attente de validation par l\'admin.');
            
            return $this->redirectToRoute('app_mes_rendez_vous'); 
        }

        return $this->render('rendez_vous/step3_details.html.twig', [
            'medecin' => $medecin,
            'form' => $form->createView() 
        ]);
    }

    /**
     * US-3.5 : Dashboard Client - Liste des rendez-vous
     */
    #[Route('/mes-rendez-vous', name: 'app_mes_rendez_vous')]
    public function index_client(RendezVousRepository $rendezVousRepository): Response
    {
        // On récupère les RDV de l'utilisateur test (profil_id = 1)
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
        // On vérifie que le RDV appartient bien à l'utilisateur
        if ($rendezVous->getProfilId() === "1") {
            $rendezVous->setStatut('annulé');
            $entityManager->flush();
            $this->addFlash('warning', 'Votre rendez-vous a été annulé.');
        }

        return $this->redirectToRoute('app_mes_rendez_vous');
    }
    #[Route('/mes-rendez-vous/modifier/{id}', name: 'app_rendez_vous_edit')]
public function edit(RendezVous $rendezVous, Request $request, EntityManagerInterface $entityManager): Response
{
    // Sécurité : on vérifie que c'est bien le RDV de l'utilisateur "1"
    if ($rendezVous->getProfilId() !== "1") {
        throw $this->createAccessDeniedException();
    }

    // On réutilise le formulaire RendezVousType
    $form = $this->createForm(RendezVousType::class, $rendezVous);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // On peut mettre à jour la date de fin si la date de début a changé
        $dateFin = clone $rendezVous->getDateDebut();
        $dateFin->modify('+30 minutes');
        $rendezVous->setDateFin($dateFin);

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