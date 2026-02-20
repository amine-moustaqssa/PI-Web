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

class RendezVousController extends AbstractController
{
    #[Route('/mes-rendez-vous', name: 'app_mes_rendez_vous')]
    public function index_client(
        Request $request,
        RendezVousRepository $rendezVousRepository,
        ProfilMedicalRepository $profilRepo,
        SpecialiteRepository $specRepo,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer 
    ): Response {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $rendezVous = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rendezVous, ['titulaire_id' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $specialiteId = $request->request->get('specialite_id');

            if (!$specialiteId) {
                $this->addFlash('danger', 'Veuillez sélectionner une spécialité avant de confirmer.');
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

            // --- EMAIL : CRÉATION ---
            $this->sendRdvEmail($mailer, $user->getUserIdentifier(), 'Confirmation de votre demande de rendez-vous', 
                "<h3>Bonjour,</h3><p>Votre demande pour <strong>" . $rendezVous->getType() . "</strong> le " . $rendezVous->getDateDebut()->format('d/m/Y à H:i') . " a été enregistrée.</p>");

            $this->addFlash('success', 'Rendez-vous ajouté ! Un email de confirmation a été envoyé.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        $profilsFamille = $profilRepo->findBy(['titulaire' => $user]);
        $mesRendezVous = $rendezVousRepository->findBy(['profil' => $profilsFamille], ['date_debut' => 'DESC']);

        return $this->render('rendez_vous/index_client.html.twig', [
            'rendez_vous' => $mesRendezVous,
            'form'         => $form->createView(),
            'specialites' => $specRepo->findAll(),
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
            if ($rendezVous->getDateDebut() instanceof \DateTimeInterface) {
                $dateFin = \DateTime::createFromInterface($rendezVous->getDateDebut());
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