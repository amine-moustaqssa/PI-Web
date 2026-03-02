<?php

namespace App\Controller\Front\Infirmier;

use App\Repository\ConsultationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Utilisateur;

#[Route('/infirmier')]
#[IsGranted('ROLE_PERSONNEL')]
class ConsultationController extends AbstractController
{
    #[Route('/consultations', name: 'infirmier_consultation_index')]
    public function index(ConsultationRepository $consultRepo): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if ($user->getNiveauAcces() !== 'INFIRMIER') {
            throw $this->createAccessDeniedException('Accès réservé aux infirmiers.');
        }

        $activeConsultations = $consultRepo->findBy(['statut' => 'en cours']);
        $plannedConsultations = $consultRepo->findBy(['statut' => 'planifié']);
        $completedConsultations = $consultRepo->findBy(['statut' => 'terminé'], ['dateEffectuee' => 'DESC']);

        return $this->render('front/infirmier/consultation/index.html.twig', [
            'activeConsultations' => $activeConsultations,
            'plannedConsultations' => $plannedConsultations,
            'completedConsultations' => $completedConsultations,
        ]);
    }
}
