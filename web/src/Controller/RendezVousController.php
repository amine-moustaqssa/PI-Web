<?php

namespace App\Controller;

use App\Entity\Medecin;
use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\SpecialiteRepository;
use App\Repository\RendezVousRepository;
use App\Repository\ProfilMedicalRepository;
use App\Repository\DisponibiliteRepository;
use App\Service\NewsHealthService;
use App\Service\QrCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Utilisateur;

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
        NewsHealthService $newsService,
        QrCodeGenerator $qrCodeGenerator,
        PaginatorInterface $paginator
    ): Response {
        /** @var Utilisateur|null $user */
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
                $dateFin = \DateTime::createFromInterface($rendezVous->getDateDebut());
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

        // Pagination
        $pagination = $paginator->paginate(
            $mesRendezVous,
            $request->query->getInt('page', 1),
            5
        );

        // Génération des QR codes
        $qrCodes = [];
        foreach ($pagination as $rdv) {
            if (!$rdv instanceof RendezVous) {
                continue;
            }

            $profil = $rdv->getProfil();
            $nomPatient = $profil ? (string) $profil->getNom() : '';
            $dateDebut = $rdv->getDateDebut();
            if (!$dateDebut) {
                continue;
            }

            $qrCodes[$rdv->getId()] = $qrCodeGenerator->generateRdvDataUri(
                (int) $rdv->getId(),
                $nomPatient,
                $dateDebut
            );
        }

        // 4. LOGIQUE NEWS API
        $sujetNews = 'Santé'; // Sujet par défaut
        if (!empty($mesRendezVous)) {
            $dernierRDV = $mesRendezVous[0];
            // On extrait un mot clé, ou on utilise le type du dernier RDV
            $sujetNews = $dernierRDV->getType() ?: 'Médecine';
        }

        $articles = $newsService->getHealthNews($sujetNews);

        return $this->render('rendez_vous/index_client.html.twig', [
            'rendez_vous' => $pagination,
            'qr_codes' => $qrCodes,
            'form' => $form->createView(),
            'specialites' => $specRepo->findAll(),
            'articles' => $articles,
            'sujet' => $sujetNews,
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
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour du médecin si modifié
            $medecin = $form->get('medecin')->getData();
            if ($medecin) {
                $rendezVous->setType("Consultation - Dr " . $medecin->getNom());
            }

            $dateDebut = $rendezVous->getDateDebut();
            $dateFin = $dateDebut ? \DateTime::createFromInterface($dateDebut) : null;
            if ($dateFin) {
                $dateFin->modify('+30 minutes');
            }
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
        try {
            $email = (new Email())
                ->from('no-reply@clinique.tn')
                ->to($to)
                ->subject($subject)
                ->html($content);

            $mailer->send($email);
        } catch (\Throwable $e) {
            // Ne bloque pas le parcours RDV si le SMTP est indisponible.
            $this->addFlash('warning', "Email non envoyé (problème SMTP). Vous pouvez réessayer plus tard.");
        }
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

    #[Route('/api/creneaux-disponibles', name: 'api_creneaux', methods: ['GET'])]
    public function getCreneauxDisponibles(
        Request $request,
        EntityManagerInterface $entityManager,
        DisponibiliteRepository $disponibiliteRepository,
        RendezVousRepository $rendezVousRepository,
    ): Response {
        $medecinId = $request->query->get('medecin_id');
        $dateStr = $request->query->get('date');

        if (!$medecinId || !$dateStr) {
            return $this->json(['error' => 'Paramètres requis: medecin_id, date'], 400);
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $dateStr);
        if (!$date) {
            return $this->json(['error' => 'Format date invalide. Utilisez YYYY-MM-DD.'], 400);
        }

        $medecin = $entityManager->getRepository(Medecin::class)->find($medecinId);
        if (!$medecin) {
            return $this->json(['error' => 'Médecin introuvable.'], 404);
        }

        // 1=lundi ... 7=dimanche
        $jourSemaine = (int) $date->format('N');

        // Dispos du médecin pour ce jour
        $dispos = $disponibiliteRepository->findBy([
            'medecin' => $medecin,
            'jourSemaine' => $jourSemaine,
        ]);

        if (!$dispos) {
            return $this->json([]);
        }

        // RDV déjà pris ce jour-là (hors annulés)
        $dayStart = $date->setTime(0, 0, 0);
        $dayEnd = $date->setTime(23, 59, 59);

        $rdvs = $rendezVousRepository->createQueryBuilder('r')
            ->andWhere('r.medecin = :medecin')
            ->andWhere('r.statut != :annule')
            ->andWhere('r.date_debut BETWEEN :start AND :end')
            ->setParameter('medecin', $medecin)
            ->setParameter('annule', 'annulé')
            ->setParameter('start', $dayStart)
            ->setParameter('end', $dayEnd)
            ->getQuery()
            ->getResult();

        $creneauxPris = [];
        foreach ($rdvs as $rdv) {
            if ($rdv instanceof RendezVous && $rdv->getDateDebut()) {
                $creneauxPris[$rdv->getDateDebut()->format('H:i')] = true;
            }
        }

        // Génération des créneaux de 30 minutes
        $slots = [];
        foreach ($dispos as $dispo) {
            $heureDebut = $dispo->getHeureDebut();
            $heureFin = $dispo->getHeureFin();
            if (!$heureDebut || !$heureFin) {
                continue;
            }

            $start = $date->setTime((int) $heureDebut->format('H'), (int) $heureDebut->format('i'));
            $end = $date->setTime((int) $heureFin->format('H'), (int) $heureFin->format('i'));

            // Aligner les créneaux sur les demi-heures (xx:00 / xx:30) pour plus de lisibilité
            $minuteStart = (int) $start->format('i');
            $minuteMod = $minuteStart % 30;
            if ($minuteMod !== 0) {
                $start = $start->modify('+' . (30 - $minuteMod) . ' minutes');
            }

            for ($t = $start; $t < $end; $t = $t->modify('+30 minutes')) {
                $label = $t->format('H:i');

                // Ne pas proposer si déjà pris
                if (isset($creneauxPris[$label])) {
                    continue;
                }

                // Ne pas proposer le dernier créneau si ça dépasse la fin (durée RDV = 30min)
                if ($t->modify('+30 minutes') > $end) {
                    continue;
                }

                $slots[$label] = true;
            }
        }

        $available = array_keys($slots);
        sort($available);

        return $this->json($available);
    }
}
