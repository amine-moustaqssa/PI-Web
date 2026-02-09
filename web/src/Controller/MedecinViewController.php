<?php

namespace App\Controller;

use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/medecin-espace', name: 'app_medecin_view_')]
class MedecinViewController extends AbstractController
{
    /**
     * Affiche les rendez-vous en attente pour le médecin
     */
    #[Route('/rendez-vous', name: 'index')]
    public function index(RendezVousRepository $repo): Response
    {
        // On récupère les RDV avec le statut exact utilisé dans ton projet
        $rendezVous = $repo->findBy(['statut' => 'en attente de confirmation']);

        return $this->render('medecin_view/index.html.twig', [
            'rendez_vous' => $rendezVous
        ]);
    }

    /**
     * US-3.4 : Confirmation et envoi de mail
     */
    #[Route('/confirmer/{id}', name: 'confirm')]
    public function confirm(
        int $id, 
        RendezVousRepository $repo, 
        EntityManagerInterface $em, 
        MailerInterface $mailer
    ): Response {
        $rdv = $repo->find($id);

        if (!$rdv) {
            throw $this->createNotFoundException('Rendez-vous introuvable.');
        }

        // 1. Mise à jour du statut
        $rdv->setStatut('validé');
        $em->flush();

        // 2. Notification Mail (US-3.4)
        $this->sendConfirmationEmail($rdv, $mailer);

        $this->addFlash('success', 'Le rendez-vous a été confirmé. Un email a été envoyé au patient.');

        return $this->redirectToRoute('app_medecin_view_index');
    }

    private function sendConfirmationEmail($rdv, MailerInterface $mailer): void
    {
        // On récupère les infos du patient
        $nomPatient = $rdv->getProfil()->getPrenom() . ' ' . $rdv->getProfil()->getNom();
        
        // Note : assure-toi d'avoir un champ email dans ton entité ProfilMedical ou Utilisateur
        $emailPatient = 'patient@test.com'; 

        $email = (new Email())
            ->from('ne-pas-repondre@votreclinique.tn')
            ->to($emailPatient)
            ->subject('Confirmation de votre rendez-vous')
            ->html("
                <h3>Bonjour {$nomPatient},</h3>
                <p>Votre rendez-vous prévu pour le <strong>{$rdv->getDateDebut()->format('d/m/Y à H:i')}</strong> a été validé.</p>
                <p>Type de consultation : {$rdv->getType()}</p>
                <p>Merci de vous présenter 15 minutes à l'avance.</p>
            ");

        $mailer->send($email);
    }
}