<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymptomTriageService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ?string $endpoint,
        ?string $apiKey,
    ) {
        $this->endpoint = $endpoint ?? '';
        $this->apiKey = $apiKey ?? '';
    }

    private readonly string $endpoint;

    private readonly string $apiKey;

    /**
     * Analyse un texte libre de symptômes.
     *
     * Retour:
     * - urgency: Normal|Urgent|Urgence Vitale
     * - specialty: string (nom de spécialité suggérée)
     * - reasoning: string (explication courte)
     */
    public function triage(string $symptomsText): array
    {
        $symptomsText = trim($symptomsText);

        if ($symptomsText === '') {
            return [
                'urgency' => 'Normal',
                'specialty' => 'Médecine générale',
                'reasoning' => "Aucun symptôme saisi.",
            ];
        }

        // Si aucune configuration IA n'est fournie, on bascule sur une heuristique simple.
        if (trim((string) $this->endpoint) === '' || trim((string) $this->apiKey) === '') {
            return $this->fallbackHeuristic($symptomsText);
        }

        try {
            // Simulation d'un appel IA (OpenAI/Claude). Le format exact dépendra de l'API.
            // Ici on envoie un JSON générique et on attend un JSON standardisé.
            $response = $this->httpClient->request('POST', $this->endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'input' => [
                        'symptoms' => $symptomsText,
                    ],
                    'task' => 'symptom_triage',
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray(false);

            // On s'attend à un payload du type:
            // {"urgency":"Urgent","specialty":"Cardiologie","reasoning":"..."}
            if (
                !isset($data['urgency'], $data['specialty'])
                || !is_string($data['urgency'])
                || !is_string($data['specialty'])
            ) {
                return $this->fallbackHeuristic($symptomsText);
            }

            $urgency = $this->normalizeUrgency($data['urgency']);

            return [
                'urgency' => $urgency,
                'specialty' => trim($data['specialty']) !== '' ? $data['specialty'] : 'Médecine générale',
                'reasoning' => isset($data['reasoning']) && is_string($data['reasoning']) ? $data['reasoning'] : 'Analyse IA.',
            ];
        } catch (\Throwable) {
            // En cas de panne API IA, on ne bloque pas le parcours utilisateur.
            return $this->fallbackHeuristic($symptomsText);
        }
    }

    private function normalizeUrgency(string $urgency): string
    {
        $u = mb_strtolower(trim($urgency));

        return match (true) {
            str_contains($u, 'vitale') || str_contains($u, 'crit') || str_contains($u, 'urgence vitale') => 'Urgence Vitale',
            str_contains($u, 'urgent') => 'Urgent',
            default => 'Normal',
        };
    }

    /**
     * Heuristique minimale (fallback) basée sur des mots-clés.
     *
     * But: fournir un résultat exploitable même sans IA.
     */
    private function fallbackHeuristic(string $symptomsText): array
    {
        $t = mb_strtolower($symptomsText);

        // Urgence vitale (mots-clés indicatifs)
        if (
            str_contains($t, 'douleur thorac')
            || str_contains($t, 'oppression')
            || str_contains($t, 'difficult') && str_contains($t, 'resp')
            || str_contains($t, 'perte de connaissance')
            || str_contains($t, 'avc')
            || str_contains($t, 'paralys')
        ) {
            return [
                'urgency' => 'Urgence Vitale',
                'specialty' => 'Urgences',
                'reasoning' => "Symptômes possiblement graves (douleur thoracique / détresse / neurologique).",
            ];
        }

        // Urgent
        if (
            str_contains($t, 'fièvre')
            || str_contains($t, 'douleur intense')
            || str_contains($t, 'saignement')
            || str_contains($t, 'vomissement')
            || str_contains($t, 'fracture')
        ) {
            return [
                'urgency' => 'Urgent',
                'specialty' => 'Médecine générale',
                'reasoning' => "Symptômes nécessitant une consultation rapide.",
            ];
        }

        // Spécialités indicatives
        if (str_contains($t, 'peau') || str_contains($t, 'eczema') || str_contains($t, 'acné')) {
            return [
                'urgency' => 'Normal',
                'specialty' => 'Dermatologie',
                'reasoning' => "Symptômes cutanés.",
            ];
        }

        if (str_contains($t, 'toux') || str_contains($t, 'asthme') || str_contains($t, 'respir')) {
            return [
                'urgency' => 'Normal',
                'specialty' => 'Pneumologie',
                'reasoning' => "Symptômes respiratoires.",
            ];
        }

        if (str_contains($t, 'ventre') || str_contains($t, 'nausée') || str_contains($t, 'diarrh')) {
            return [
                'urgency' => 'Normal',
                'specialty' => 'Gastro-entérologie',
                'reasoning' => "Symptômes digestifs.",
            ];
        }

        if (str_contains($t, 'tête') || str_contains($t, 'migraine') || str_contains($t, 'vertige')) {
            return [
                'urgency' => 'Normal',
                'specialty' => 'Neurologie',
                'reasoning' => "Symptômes neurologiques.",
            ];
        }

        return [
            'urgency' => 'Normal',
            'specialty' => 'Médecine générale',
            'reasoning' => "Suggestion par défaut.",
        ];
    }
}
