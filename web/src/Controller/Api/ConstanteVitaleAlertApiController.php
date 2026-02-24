<?php

namespace App\Controller\Api;

use App\Repository\ConstanteVitaleRepository;
use App\Repository\ConsultationRepository;
use App\Service\ConstanteVitaleAlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/alertes', name: 'api_alertes_')]
class ConstanteVitaleAlertApiController extends AbstractController
{
    public function __construct(
        private ConstanteVitaleAlertService $alertService,
    ) {}

    /**
     * GET /api/alertes/references
     * Returns all medical reference thresholds.
     */
    #[Route('/references', name: 'references', methods: ['GET'])]
    public function references(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'count'   => count($this->alertService->getAllReferences()),
            'data'    => $this->alertService->getAllReferences(),
        ]);
    }

    /**
     * GET /api/alertes/reference/{type}
     * Returns reference thresholds for a specific vital sign type.
     */
    #[Route('/reference/{type}', name: 'reference_type', methods: ['GET'])]
    public function referenceByType(string $type): JsonResponse
    {
        $ref = $this->alertService->getReference($type);

        if (!$ref) {
            return $this->json([
                'success' => false,
                'message' => "Type '$type' non référencé dans la base médicale.",
            ], 404);
        }

        return $this->json([
            'success' => true,
            'type'    => $type,
            'data'    => $ref,
        ]);
    }

    /**
     * GET /api/alertes/check?type=temperature&valeur=39.8
     * Check a single value against medical thresholds.
     */
    #[Route('/check', name: 'check', methods: ['GET'])]
    public function check(Request $request): JsonResponse
    {
        $type   = $request->query->get('type');
        $valeur = $request->query->get('valeur');

        if (!$type || $valeur === null) {
            return $this->json([
                'success' => false,
                'message' => "Paramètres requis : 'type' et 'valeur'. Ex: /api/alertes/check?type=temperature&valeur=39.8",
            ], 400);
        }

        $valeur = (float) $valeur;
        $level  = $this->alertService->getAlertLevel($type, $valeur);
        $ref    = $this->alertService->getReference($type);

        return $this->json([
            'success'   => true,
            'type'      => $type,
            'valeur'    => $valeur,
            'level'     => $level,
            'badge'     => $this->alertService->getAlertBadgeClass($level),
            'icon'      => $this->alertService->getAlertIcon($level),
            'reference' => $ref,
        ]);
    }

    /**
     * POST /api/alertes/analyze
     * Analyze multiple vital signs at once.
     * Body JSON: { "constantes": [ {"type": "temperature", "valeur": 39.8}, {"type": "spo2", "valeur": 88} ] }
     */
    #[Route('/analyze', name: 'analyze', methods: ['POST'])]
    public function analyze(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        if (!$body || !isset($body['constantes']) || !is_array($body['constantes'])) {
            return $this->json([
                'success' => false,
                'message' => "Corps JSON requis : { \"constantes\": [ {\"type\": \"temperature\", \"valeur\": 39.8}, ... ] }",
            ], 400);
        }

        $results = [];
        $hasCritical = false;
        $hasWarning  = false;
        $criticalTypes = [];
        $warningTypes  = [];

        foreach ($body['constantes'] as $c) {
            $type   = $c['type'] ?? null;
            $valeur = $c['valeur'] ?? null;

            if (!$type || $valeur === null) {
                continue;
            }

            $valeur = (float) $valeur;
            $level  = $this->alertService->getAlertLevel($type, $valeur);
            $ref    = $this->alertService->getReference($type);

            if ($level === 'critical') {
                $hasCritical = true;
                $criticalTypes[] = $type;
            } elseif ($level === 'warning') {
                $hasWarning = true;
                $warningTypes[] = $type;
            }

            $results[] = [
                'type'      => $type,
                'valeur'    => $valeur,
                'level'     => $level,
                'badge'     => $this->alertService->getAlertBadgeClass($level),
                'icon'      => $this->alertService->getAlertIcon($level),
                'reference' => $ref,
            ];
        }

        // Build summary
        $summary = '';
        if ($hasCritical) {
            $summary = '⚠ ALERTE CRITIQUE : ' . implode(', ', $criticalTypes);
        } elseif ($hasWarning) {
            $summary = '⚠ Attention : ' . implode(', ', $warningTypes);
        } else {
            $summary = '✅ Toutes les constantes sont dans les plages normales.';
        }

        return $this->json([
            'success'     => true,
            'total'       => count($results),
            'hasCritical' => $hasCritical,
            'hasWarning'  => $hasWarning,
            'summary'     => $summary,
            'alerts'      => $results,
        ]);
    }

    /**
     * GET /api/alertes/consultation/{id}
     * Analyze all constantes of a specific consultation.
     */
    #[Route('/consultation/{id}', name: 'consultation', methods: ['GET'])]
    public function consultation(
        int $id,
        ConsultationRepository $consultationRepo,
        ConstanteVitaleRepository $constanteRepo
    ): JsonResponse {
        $consultation = $consultationRepo->find($id);

        if (!$consultation) {
            return $this->json([
                'success' => false,
                'message' => "Consultation #$id introuvable.",
            ], 404);
        }

        $constantes = $constanteRepo->findBy(['consultation_id' => $consultation->getId()]);

        if (empty($constantes)) {
            return $this->json([
                'success'        => true,
                'consultation_id' => $id,
                'message'        => 'Aucune constante vitale enregistrée pour cette consultation.',
                'alerts'         => [],
            ]);
        }

        $analysis = $this->alertService->analyzeConstantes($constantes);

        return $this->json([
            'success'        => true,
            'consultation_id' => $id,
            'total'          => count($constantes),
            'hasCritical'    => $analysis['hasCritical'],
            'hasWarning'     => $analysis['hasWarning'],
            'summary'        => $analysis['summary'],
            'alerts'         => $analysis['alerts'],
        ]);
    }
}
