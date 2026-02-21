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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Service\NewsHealthService;

class RendezVousController extends AbstractController
{
    #[Route('/mes-rendez-vous', name: 'app_mes_rendez_vous')]
    public function index_client(
        Request $request,
        EntityManagerInterface $entityManager,
        RendezVousRepository $rendezVousRepository,
        ProfilMedicalRepository $profilRepo,
        SpecialiteRepository $specRepo,
        MailerInterface $mailer,
        NewsHealthService $newsService
    ): Response {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // 1. Initialisation du nouveau Rendez-vous et du formulaire
        $rendezVous = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rendezVous, ['titulaire_id' => $user]);
        $form->handleRequest($request);

        // 2. Traitement de la soumission du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération du médecin (champ non mappé)
            $medecin = $form->get('medecin')->getData();
            
            // On génère le type/titre du RDV
            if ($medecin) {
                $rendezVous->setType("Consultation - Dr " . $medecin->getNom());
            }

            // Calcul de la date de fin (+30 minutes)
            if ($rendezVous->getDateDebut()) {
                $dateFin = clone $rendezVous->getDateDebut();
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }

            $rendezVous->setStatut('En attente');

            // Sauvegarde en base
            $entityManager->persist($rendezVous);
            $entityManager->flush();

            // Envoi de l'email de confirmation
            $this->sendRdvEmail(
                $mailer, 
                $user->getUserIdentifier(), 
                'Confirmation de votre rendez-vous', 
                "<p>Votre rendez-vous du <strong>" . $rendezVous->getDateDebut()->format('d/m/Y à H:i') . "</strong> a bien été enregistré. Il est en attente de confirmation par la clinique.</p>"
            );

            $this->addFlash('success', 'Votre demande de rendez-vous a été envoyée avec succès.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        // 3. Récupération de l'historique pour l'affichage
        $profilsFamille = $profilRepo->findBy(['titulaire' => $user]);
        $mesRendezVous = $rendezVousRepository->findBy(['profil' => $profilsFamille], ['date_debut' => 'DESC']);

        // 4. LOGIQUE NEWS API
        $sujetNews = 'Santé'; // Sujet par défaut
        if (!empty($mesRendezVous)) {
            $dernierRDV = $mesRendezVous[0];
            // On extrait un mot clé, ou on utilise le type du dernier RDV
            $sujetNews = $dernierRDV->getType() ?: 'Médecine';
        }

        $articles = $newsService->getHealthNews($sujetNews);

        return $this->render('rendez_vous/index_client.html.twig', [
            'rendez_vous' => $mesRendezVous,
            'form'        => $form->createView(),
            'specialites' => $specRepo->findAll(),
            'articles'    => $articles,
            'sujet'       => $sujetNews
        ]);
    }

    #[Route('/mes-rendez-vous/modifier/{id}', name: 'app_rendez_vous_edit')]
    public function edit(RendezVous $rendezVous, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if ($rendezVous->getProfil()->getTitulaire() !== $user && $rendezVous->getProfil()->getTitulaireId() != 7) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(RendezVousType::class, $rendezVous, ['titulaire_id' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Mise à jour du médecin si modifié
            $medecin = $form->get('medecin')->getData();
            if ($medecin) {
                $rendezVous->setType("Consultation - Dr " . $medecin->getNom());
            }

            if ($rendezVous->getDateDebut() instanceof \DateTimeInterface) {
                $dateFin = clone $rendezVous->getDateDebut();
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
            }
            
            $entityManager->flush();

            // --- EMAIL : MODIFICATION ---
            $this->sendRdvEmail($mailer, $user->getUserIdentifier(), 'Modification de votre rendez-vous', 
                "<p>Votre rendez-vous a été mis à jour. Nouvelle date : <strong>" . $rendezVous->getDateDebut()->format('d/m/Y à H:i') . "</strong>.</p>");

            $this->addFlash('success', 'Modifications enregistrées et email envoyé.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

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
    public function cancel(RendezVous $rendezVous, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if ($rendezVous->getProfil()->getTitulaire() === $user || $rendezVous->getProfil()->getTitulaireId() == 7) {
            $rendezVous->setStatut('annulé');
            $entityManager->flush();

            // --- EMAIL : ANNULATION ---
            $this->sendRdvEmail($mailer, $user->getUserIdentifier(), 'Annulation de votre rendez-vous', 
                "<p>Votre rendez-vous du " . $rendezVous->getDateDebut()->format('d/m/Y') . " a bien été annulé.</p>");

            $this->addFlash('warning', 'Le rendez-vous a été annulé (email envoyé).');
        }
        return $this->redirectToRoute('app_mes_rendez_vous');
    }

    /**
     * Fonction utilitaire privée pour éviter la répétition de code d'envoi d'email
     */
    private function sendRdvEmail(MailerInterface $mailer, string $to, string $subject, string $content): void
    {
        $email = (new Email())
            ->from('no-reply@clinique.tn')
            ->to($to)
            ->subject($subject)
            ->html($content);

        $mailer->send($email);
    }
}