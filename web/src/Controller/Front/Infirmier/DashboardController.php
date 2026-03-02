<?php

namespace App\Controller\Front\Infirmier;

use App\Repository\ConsultationRepository;
use App\Repository\ConstanteVitaleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Utilisateur;

#[Route('/infirmier')]
#[IsGranted('ROLE_PERSONNEL')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'infirmier_dashboard')]
    public function index(
        ConsultationRepository $consultRepo,
        ConstanteVitaleRepository $constanteRepo
    ): Response {
        // Verify the logged-in user is an infirmier
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if ($user->getNiveauAcces() !== 'INFIRMIER') {
            throw $this->createAccessDeniedException('Accès réservé aux infirmiers.');
        }

        // Active consultations (en cours)
        $activeConsultations = $consultRepo->findBy(['statut' => 'en cours']);
        // Planned consultations
        $plannedConsultations = $consultRepo->findBy(['statut' => 'planifié']);
        // All consultations for the table
        $allConsultations = $consultRepo->findBy([], ['dateEffectuee' => 'DESC']);

        // Count stats
        $activeCount = count($activeConsultations);
        $plannedCount = count($plannedConsultations);
        $todayConstantes = $constanteRepo->countToday();

        return $this->render('front/infirmier/dashboard/index.html.twig', [
            'activeConsultations' => $activeConsultations,
            'activeCount' => $activeCount,
            'plannedCount' => $plannedCount,
            'todayConstantes' => $todayConstantes,
            'allConsultations' => $allConsultations,
        ]);
    }
}
