<?php

namespace App\Controller\Front\Medecin;

use App\Entity\Consultation;
use App\Entity\RendezVous;
use App\Repository\ConsultationRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/medecin/rendez-vous')]
final class RendezVousController extends AbstractController
{
    #[Route('/', name: 'medecin_rdv_index', methods: ['GET'])]
    public function index(Request $request, RendezVousRepository $rdvRepo, ConsultationRepository $consultRepo): Response
    {
        $user = $this->getUser();
        $profils = $user->getProfilsMedicaux();
        $statut = $request->query->get('statut');

        $validStatuts = ['confirmé', 'en_attente', 'annulé', 'terminé'];

        if ($statut && in_array($statut, $validStatuts, true)) {
            $rendezVous = $rdvRepo->findByProfilsAndStatut($profils, $statut);
        } else {
            $rendezVous = $rdvRepo->findByProfils($profils);
            $statut = null;
        }

        // Count by status for filter cards
        $allRdvs = $rdvRepo->findByProfils($profils);
        $counts = ['total' => count($allRdvs), 'confirmé' => 0, 'en_attente' => 0, 'annulé' => 0, 'terminé' => 0];
        $upcoming = 0;
        $now = new \DateTime();
        foreach ($allRdvs as $rdv) {
            $s = $rdv->getStatut();
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
            if ($rdv->getDateDebut() > $now && $s !== 'annulé' && $s !== 'terminé') {
                $upcoming++;
            }
        }
        $counts['upcoming'] = $upcoming;

        // Build a set of rdv IDs that already have a consultation
        $rdvIdsWithConsultation = [];
        foreach ($allRdvs as $rdv) {
            $existing = $consultRepo->findOneBy(['rdvId' => (int) $rdv->getId()]);
            if ($existing) {
                $rdvIdsWithConsultation[$rdv->getId()] = $existing->getId();
            }
        }

        return $this->render('front/medecin/rendez_vous/index.html.twig', [
            'rendezVous' => $rendezVous,
            'counts' => $counts,
            'currentStatut' => $statut,
            'rdvIdsWithConsultation' => $rdvIdsWithConsultation,
        ]);
    }

    #[Route('/{id}', name: 'medecin_rdv_show', methods: ['GET'])]
    public function show(RendezVous $rdv, ConsultationRepository $consultRepo): Response
    {
        // Security: ensure this RDV belongs to one of the doctor's profiles
        $user = $this->getUser();
        $profils = $user->getProfilsMedicaux();
        $allowed = false;
        foreach ($profils as $p) {
            if ($rdv->getProfil() && $rdv->getProfil()->getId() === $p->getId()) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            throw $this->createAccessDeniedException();
        }

        // Check if a consultation already exists for this RDV
        $existingConsultation = $consultRepo->findOneBy(['rdvId' => (int) $rdv->getId()]);

        return $this->render('front/medecin/rendez_vous/show.html.twig', [
            'rdv' => $rdv,
            'existingConsultation' => $existingConsultation,
        ]);
    }

    #[Route('/{id}/start-consultation', name: 'medecin_rdv_start_consultation', methods: ['POST'])]
    public function startConsultation(
        RendezVous $rdv,
        ConsultationRepository $consultRepo,
        EntityManagerInterface $em
    ): Response {
        // Security: ensure this RDV belongs to one of the doctor's profiles
        $user = $this->getUser();
        $profils = $user->getProfilsMedicaux();
        $allowed = false;
        foreach ($profils as $p) {
            if ($rdv->getProfil() && $rdv->getProfil()->getId() === $p->getId()) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            throw $this->createAccessDeniedException();
        }

        // Check if a consultation already exists for this RDV
        $existing = $consultRepo->findOneBy(['rdvId' => (int) $rdv->getId()]);
        if ($existing) {
            $this->addFlash('warning', 'Une consultation existe déjà pour ce rendez-vous.');
            return $this->redirectToRoute('medecin_consultation_show', ['id' => $existing->getId()]);
        }

        // Create the consultation
        $consultation = new Consultation();
        $consultation->setDateEffectuee(new \DateTime());
        $consultation->setStatut('en cours');
        $consultation->setMedecin($user);
        $consultation->setRdvId((int) $rdv->getId());

        $em->persist($consultation);
        $em->flush();

        $this->addFlash('success', 'Consultation démarrée avec succès.');
        return $this->redirectToRoute('medecin_consultation_show', ['id' => $consultation->getId()]);
    }
}
