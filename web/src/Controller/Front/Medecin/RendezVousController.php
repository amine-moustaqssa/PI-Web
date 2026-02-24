<?php

namespace App\Controller\Front\Medecin;

use App\Entity\RendezVous;
use App\Repository\RendezVousRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/medecin/rendez-vous')]
final class RendezVousController extends AbstractController
{
    #[Route('/', name: 'medecin_rdv_index', methods: ['GET'])]
    public function index(Request $request, RendezVousRepository $rdvRepo): Response
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

        return $this->render('front/medecin/rendez_vous/index.html.twig', [
            'rendezVous' => $rendezVous,
            'counts' => $counts,
            'currentStatut' => $statut,
        ]);
    }

    #[Route('/{id}', name: 'medecin_rdv_show', methods: ['GET'])]
    public function show(RendezVous $rdv): Response
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

        return $this->render('front/medecin/rendez_vous/show.html.twig', [
            'rdv' => $rdv,
        ]);
    }
}
