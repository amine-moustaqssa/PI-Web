<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\SpecialiteRepository;
use App\Repository\RendezVousRepository;
use App\Repository\ProfilMedicalRepository;
use App\Repository\DisponibiliteRepository;
use App\Service\NewsHealthService;
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
        RendezVousRepository $rdvRepo,
        ProfilMedicalRepository $profilRepo,
        SpecialiteRepository $specRepo,
        EntityManagerInterface $em,
        NewsHealthService $newsService
    ): Response {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $rendezVous = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rendezVous, ['titulaire_id' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $specialiteId = $request->request->get('specialite_id');
                $spec = $specRepo->find($specialiteId);

                // Calcul de la fin (+30min)
                $dateFin = clone $rendezVous->getDateDebut();
                $dateFin->modify('+30 minutes');
                $rendezVous->setDateFin($dateFin);
                $rendezVous->setStatut('en attente de confirmation');
                
                // Libellé dynamique
                $nomPatient = $rendezVous->getProfil()->getNom();
                $nomSpec = $spec ? $spec->getNom() : 'Consultation';
                $rendezVous->setType($nomPatient . " [" . $nomSpec . "]");

                $em->persist($rendezVous);
                $em->flush();

                $this->addFlash('success', 'Rendez-vous enregistré avec succès !');
                return $this->redirectToRoute('app_mes_rendez_vous');
            } else {
                $this->addFlash('danger', 'Le formulaire contient des erreurs. Vérifiez les champs.');
            }
        }

        // Récupération sécurisée : On cherche tous les RDV liés aux profils de l'utilisateur
        $mesProfils = $user->getProfilsMedicaux();
        $mesRendezVous = $rdvRepo->findBy(['profil' => $mesProfils->toArray()], ['date_debut' => 'DESC']);

        return $this->render('rendez_vous/index_client.html.twig', [
            'rendez_vous' => $mesRendezVous,
            'form'        => $form->createView(),
            'specialites' => $specRepo->findAll(),
            'articles'    => $newsService->getHealthNews('Santé'),
            'sujet'       => 'Santé'
        ]);
    }

    #[Route('/mes-rendez-vous/modifier/{id}', name: 'app_rendez_vous_edit')]
    public function edit(RendezVous $rendezVous, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        // Sécurité : Propriétaire ou Admin
        if ($rendezVous->getProfil()->getTitulaire() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(RendezVousType::class, $rendezVous, [
            'titulaire_id' => $this->getUser(),
            'include_medecin' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateFin = clone $rendezVous->getDateDebut();
            $dateFin->modify('+30 minutes');
            $rendezVous->setDateFin($dateFin);
            
            $entityManager->flush();

            $this->sendRdvEmail($mailer, $user->getUserIdentifier(), 'Modification de RDV', "<p>Votre RDV a été mis à jour pour le " . $rendezVous->getDateDebut()->format('d/m/Y à H:i') . ".</p>");
            $this->addFlash('success', 'Modifications enregistrées.');
            return $this->redirectToRoute('app_mes_rendez_vous');
        }

        return $this->render($request->isXmlHttpRequest() ? 'rendez_vous/_edit_modal.html.twig' : 'rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
            'rendezVous' => $rendezVous
        ]);
    }

    #[Route('/mes-rendez-vous/annuler/{id}', name: 'app_rendez_vous_cancel')]
    public function cancel(RendezVous $rendezVous, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if ($rendezVous->getProfil()->getTitulaire() === $user || $this->isGranted('ROLE_ADMIN')) {
            $rendezVous->setStatut('annulé');
            $entityManager->flush();
            $this->sendRdvEmail($mailer, $user->getUserIdentifier(), 'Annulation de RDV', "<p>Le RDV du " . $rendezVous->getDateDebut()->format('d/m/Y') . " est annulé.</p>");
            $this->addFlash('warning', 'Rendez-vous annulé.');
        }
        return $this->redirectToRoute('app_mes_rendez_vous');
    }

    private function sendRdvEmail(MailerInterface $mailer, string $to, string $subject, string $content): void
    {
        $email = (new Email())->from('no-reply@clinique.tn')->to($to)->subject($subject)->html($content);
        $mailer->send($email);
    }

    #[Route('/api/medecins-par-specialite', name: 'api_medecins_by_specialite', methods: ['GET'])]
    public function getMedecinsBySpecialite(Request $request, SpecialiteRepository $specRepo): Response
    {
        $specialite = $specRepo->find($request->query->get('specialite_id'));
        if (!$specialite) return $this->json([]);

        $medecinsList = [];
        foreach ($specialite->getMedecins() as $medecin) {
            $medecinsList[] = ['id' => $medecin->getId(), 'nom' => 'Dr ' . $medecin->getNom() . ' ' . $medecin->getPrenom()];
        }
        return $this->json($medecinsList);
    }
}