<?php

namespace App\Controller\Front\Medecin;

use App\Entity\Consultation;
use App\Form\ConsultationMedecinType;
use App\Repository\ConsultationRepository;
use App\Repository\ConstanteVitaleRepository;
use App\Service\ConstanteVitaleAlertService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Utilisateur;

#[Route('/medecin/consultation')]
final class ConsultationController extends AbstractController
{
    #[Route('/', name: 'medecin_consultation_index', methods: ['GET'])]
    public function index(Request $request, ConsultationRepository $repository): Response
    {
        $medecin = $this->getUser();
        $statut = $request->query->get('statut');

        if ($statut && in_array($statut, ['en cours', 'terminé', 'planifié', 'annulée'], true)) {
            $consultations = $repository->findByMedecinAndStatut($medecin, $statut);
        } else {
            $consultations = $repository->findByMedecin($medecin);
            $statut = null;
        }

        // Count by status for the filter cards
        $allConsultations = $repository->findByMedecin($medecin);
        $counts = ['total' => count($allConsultations), 'en cours' => 0, 'terminé' => 0, 'planifié' => 0, 'annulée' => 0];
        foreach ($allConsultations as $c) {
            $s = $c->getStatut();
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
        }

        return $this->render('front/medecin/consultation/index.html.twig', [
            'consultations' => $consultations,
            'counts' => $counts,
            'currentStatut' => $statut,
        ]);
    }

    #[Route('/{id}', name: 'medecin_consultation_show', methods: ['GET'])]
    public function show(
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

        // Check constantes for alerts
        $constantes = $constanteRepository->findBy(['consultation_id' => $consultation->getId()]);
        if (!empty($constantes)) {
            $analysis = $alertService->analyzeConstantes($constantes);
            if ($analysis['hasCritical']) {
                $this->addFlash('danger', $analysis['summary']);
            } elseif ($analysis['hasWarning']) {
                $this->addFlash('warning', $analysis['summary']);
            }
        }

        return $this->render('front/medecin/consultation/show.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    #[Route('/{id}/edit', name: 'medecin_consultation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Consultation $consultation, EntityManagerInterface $em): Response
    {
        // Security: only the owning doctor can edit
        /** @var Utilisateur $currentUser */
        $currentUser = $this->getUser();
        if ($consultation->getMedecin()->getId() !== $currentUser->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConsultationMedecinType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Consultation mise à jour.');
            return $this->redirectToRoute('medecin_consultation_index');
        }

        return $this->render('front/medecin/consultation/edit.html.twig', [
            'form' => $form,
            'consultation' => $consultation,
        ]);
    }
}
