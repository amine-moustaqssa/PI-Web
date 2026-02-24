<?php

namespace App\Controller\Api;

use App\Service\MedicalAssistantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/medical')]  // Changé pour correspondre à l'appel JS
#[IsGranted('ROLE_MEDECIN')]
class MedicalAssistantController extends AbstractController
{
    public function __construct(
        private MedicalAssistantService $assistant
    ) {}

    #[Route('/suggest', name: 'api_medical_suggest', methods: ['POST'])]
    public function suggest(Request $request): JsonResponse
    {
        set_time_limit(120);

        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['suggestions' => []], 400);
            }

            // Nettoyage sécurité
            $texte = trim(strip_tags($data['text'] ?? ''));
            $contexte = $data['context'] ?? [];

            if (strlen($texte) < 10) {
                return $this->json(['suggestions' => []]);
            }

            // Analyse clinique avec Ollama + règles
            $resultat = $this->assistant->analyserSituationClinique($texte, $contexte);

            return $this->json([
                'suggestions' => $resultat['suggestions'],
                'source' => $resultat['source'],
                'count' => $resultat['count']
            ]);
        } catch (\Exception $e) {
            // Fallback de sécurité
            return $this->json([
                'suggestions' => [
                    'examens' => ['Bilan biologique standard (NFS, CRP, ionogramme)'],
                    'traitements' => ['Traitement symptomatique adapté'],
                    'orientation' => ['Consultation de contrôle à 48h'],
                    'alertes' => []
                ],
                'source' => 'fallback',
                'count' => 3
            ]);
        }
    }

    #[Route('/health', name: 'api_medical_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $ollama = $this->assistant->testConnexionOllama();

        return $this->json([
            'status' => 'operational',
            'service' => 'Medical Assistant',
            'ollama' => $ollama,
            'mode' => $ollama['disponible'] ? 'IA + Règles' : 'Règles uniquement',
            'timestamp' => date('c')
        ]);
    }
}
