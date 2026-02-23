<?php

namespace App\Controller\Api;

use App\Service\AIConclusionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/medical')]
#[IsGranted('ROLE_MEDECIN')]
class AIConclusionController extends AbstractController
{
    public function __construct(
        private AIConclusionService $conclusionService
    ) {}

    #[Route('/conclusion', name: 'api_generate_conclusion', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['error' => 'Données invalides'], 400);
            }

            $contenu = trim(strip_tags($data['contenu'] ?? ''));
            $contexte = $data['context'] ?? [];

            if (strlen($contenu) < 50) {
                return $this->json([
                    'error' => 'Contenu trop court',
                    'conclusion' => 'Le contenu est insuffisant pour générer une conclusion.'
                ], 400);
            }

            $conclusion = $this->conclusionService->genererConclusion($contenu, $contexte);
            
            return $this->json([
                'conclusion' => $conclusion,
                'source' => $this->conclusionService->isOllamaDisponible() ? 'ollama' : 'regles'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur temporaire',
                'conclusion' => 'Impossible de générer une conclusion pour le moment.'
            ], 500);
        }
    }
}