<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'intégration avec l'API externe Google Gemini (IA générative).
 * Utilisé pour analyser les constantes vitales d'un patient et générer
 * un résumé médical intelligent avec recommandations.
 */
class GeminiService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $geminiApiKey
    ) {
        $this->apiKey = $geminiApiKey;
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    }

    /**
     * Envoie un prompt à l'API Gemini et retourne la réponse textuelle.
     *
     * @param string $prompt Le texte à envoyer à l'IA
     * @return string La réponse générée par Gemini
     * @throws \Exception En cas d'erreur de l'API
     */
    public function generate(string $prompt): string
    {
        $response = $this->httpClient->request('POST', $this->apiUrl, [
            'query' => ['key' => $this->apiKey],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 4096,
                ],
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode === 429) {
            throw new \Exception('Quota API Gemini dépassé. Veuillez réessayer dans environ 1 minute.');
        }
        if ($statusCode !== 200) {
            throw new \Exception('Erreur API Gemini (HTTP ' . $statusCode . ')');
        }

        $data = $response->toArray();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Aucune réponse générée.';
    }

    /**
     * Analyse les constantes vitales d'un patient via l'IA Gemini.
     *
     * @param array $constantesData Tableau de constantes ['type' => ..., 'valeur' => ..., 'unite' => ..., 'date' => ...]
     * @param array $alertsData Résultat de ConstanteVitaleAlertService::analyzeConstantes()
     * @return string Résumé médical généré par l'IA
     */
    public function analyzeConstantesVitales(array $constantesData, array $alertsData): string
    {
        $constantesList = '';
        foreach ($constantesData as $c) {
            $constantesList .= sprintf(
                "- %s : %.2f %s (mesuré le %s)\n",
                $c['type'],
                $c['valeur'],
                $c['unite'] ?? '',
                $c['date'] ?? 'N/A'
            );
        }

        $alertsSummary = '';
        foreach ($alertsData['alerts'] as $alert) {
            $alertsSummary .= sprintf(
                "- %s : %s (niveau: %s, plage normale: %s)\n",
                $alert['type'],
                $alert['valeur'],
                $alert['label'],
                $alert['reference'] ? ($alert['reference']['normal_low'] . ' – ' . $alert['reference']['normal_high'] . ' ' . $alert['reference']['unite']) : 'Non référencé'
            );
        }

        $prompt = <<<PROMPT
Tu es un assistant médical intelligent. Analyse les constantes vitales suivantes d'un patient hospitalisé et fournis un résumé médical structuré.

📊 CONSTANTES VITALES RELEVÉES :
{$constantesList}

🔔 ANALYSE DES ALERTES :
{$alertsSummary}

Nombre de valeurs critiques : {$alertsData['criticalCount']}
Nombre de valeurs en alerte : {$alertsData['warningCount']}

📋 INSTRUCTIONS :
1. Fais un **résumé clinique** clair et concis de l'état du patient
2. Identifie les **constantes anormales** et explique les risques associés
3. Propose des **recommandations** pour l'équipe soignante
4. Indique le **niveau d'urgence** global (Normal / Surveillance / Urgent / Critique)

⚠️ IMPORTANT : Ceci est un outil d'aide à la décision. Toute décision médicale doit être validée par un médecin.

Réponds en français, de manière structurée avec des titres et des puces.
PROMPT;

        return $this->generate($prompt);
    }
}
