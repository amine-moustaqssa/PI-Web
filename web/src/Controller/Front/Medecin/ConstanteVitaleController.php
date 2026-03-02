<?php

namespace App\Controller\Front\Medecin;

use App\Entity\Consultation;
use App\Repository\ConstanteVitaleRepository;
use App\Service\ConstanteVitaleAlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Utilisateur;

#[Route('/medecin/consultation/{id}/constantes')]
final class ConstanteVitaleController extends AbstractController
{
    #[Route('', name: 'medecin_constante_index', methods: ['GET'])]
    public function index(
        Consultation $consultation,
        ConstanteVitaleRepository $constanteRepository,
        ConstanteVitaleAlertService $alertService
    ): Response {
        // Security: only the owning doctor can view
        /** @var Utilisateur $currentUser */
        $currentUser = $this->getUser();
        if ($consultation->getMedecin()->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException();
        }

        $constantes = $constanteRepository->findBy(['consultation_id' => $consultation->getId()]);
        $analysis = $alertService->analyzeConstantes($constantes);

        // Flash alert if critical values found
        if ($analysis['hasCritical']) {
            $this->addFlash('danger', $analysis['summary']);
        } elseif ($analysis['hasWarning']) {
            $this->addFlash('warning', $analysis['summary']);
        }

        return $this->render('front/medecin/constante_vitale/index.html.twig', [
            'consultation' => $consultation,
            'constantes' => $constantes,
            'analysis' => $analysis,
        ]);
    }
}
