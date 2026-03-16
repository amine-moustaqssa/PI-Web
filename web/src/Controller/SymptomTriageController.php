<?php

namespace App\Controller;

use App\Form\SymptomTriageType;
use App\Repository\SpecialiteRepository;
use App\Service\SymptomTriageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SymptomTriageController extends AbstractController
{
    #[Route('/triage-symptomes', name: 'app_symptom_triage')]
    public function index(
        Request $request,
        SymptomTriageService $triageService,
        SpecialiteRepository $specialiteRepository,
    ): Response {
        $form = $this->createForm(SymptomTriageType::class);
        $form->handleRequest($request);

        $result = null;
        $matchedSpecialite = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $symptomsText = (string) $form->get('symptomes')->getData();
            $result = $triageService->triage($symptomsText);

            // On essaie de retrouver une spécialité existante à partir du texte suggéré.
            // Ici on fait une comparaison simple (insensible à la casse) pour rester robuste.
            $suggested = isset($result['specialty']) ? trim((string) $result['specialty']) : '';
            if ($suggested !== '') {
                foreach ($specialiteRepository->findAll() as $spec) {
                    if (mb_strtolower($spec->getNom()) === mb_strtolower($suggested)) {
                        $matchedSpecialite = $spec;
                        break;
                    }
                }
            }
        }

        return $this->render('triage/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
            'matchedSpecialite' => $matchedSpecialite,
        ]);
    }
}
