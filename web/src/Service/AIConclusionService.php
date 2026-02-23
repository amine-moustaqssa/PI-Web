<?php
// src/Service/AIConclusionService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AIConclusionService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    /**
     * Génère une conclusion basée sur le contenu du rapport
     */
    public function genererConclusion(string $contenu, array $contextePatient): string
    {
        $this->logger->info('Génération de conclusion demandée');
        
        try {
            // Essayer Ollama d'abord
            if ($this->isOllamaDisponible()) {
                $conclusion = $this->getConclusionParOllama($contenu, $contextePatient);
                if (!empty($conclusion)) {
                    return $conclusion;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Ollama indisponible pour conclusion: ' . $e->getMessage());
        }

        // Fallback : conclusion basée sur règles
        return $this->getConclusionParRegles($contenu, $contextePatient);
    }

    /**
     * Vérifie si Ollama est disponible - RENDUE PUBLIQUE
     */
    public function isOllamaDisponible(): bool
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost:11434/api/tags', [
                'timeout' => 2
            ]);
            $data = $response->toArray();
            return !empty($data['models']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Conclusion via Ollama (IA)
     */
    private function getConclusionParOllama(string $contenu, array $contexte): string
    {
        $age = $contexte['age'] ?? 'non précisé';
        $antecedents = $contexte['antecedents'] ?? 'aucun';

        $prompt = "Tu es un médecin expert. À partir de cette observation clinique, rédige une CONCLUSION MÉDICALE professionnelle et concise.

Contexte patient:
- Âge: $age ans
- Antécédents: $antecedents

Observation clinique:
\"$contenu\"

Rédige une conclusion médicale structurée en 3-4 phrases maximum qui:
1. Synthétise le diagnostic probable
2. Résume les examens clés à réaliser
3. Propose la conduite à tenir
4. Mentionne le suivi recommandé

La conclusion doit être professionnelle, précise et utilisable directement dans un rapport médical.";

        $response = $this->httpClient->request('POST', 'http://localhost:11434/api/generate', [
            'json' => [
                'model' => 'mistral',
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.3,
                    'num_predict' => 300
                ]
            ],
            'timeout' => 15
        ]);

        $data = $response->toArray();
        $conclusion = trim($data['response'] ?? '');
        
        // Nettoyer la conclusion
        $conclusion = preg_replace('/^["\']|["\']$/', '', $conclusion);
        $conclusion = str_replace(['```', 'json'], '', $conclusion);
        
        return $conclusion;
    }

    /**
     * Conclusion via règles (fallback)
     */
    private function getConclusionParRegles(string $contenu, array $contexte): string
    {
        $contenuLower = strtolower($contenu);
        $age = $contexte['age'] ?? 30;

        // Règles simples basées sur mots-clés
        if (strpos($contenuLower, 'douleur thoracique') !== false) {
            if (strpos($contenuLower, 'irradiation') !== false || strpos($contenuLower, 'bras') !== false) {
                return "Conclusion : suspicion de syndrome coronarien aigu. Un ECG et un dosage des troponines sont réalisés en urgence. Une surveillance scopique et un transfert en unité de soins intensifs cardiologiques sont à discuter selon les résultats.";
            }
            return "Conclusion : douleur thoracique à explorer. ECG et bilan biologique à réaliser. Consultation cardiologique si persistance.";
        }

        if (strpos($contenuLower, 'fièvre') !== false && strpos($contenuLower, 'toux') !== false) {
            return "Conclusion : pneumopathie infectieuse probable. Une radiographie thoracique et un bilan biologique (NFS, CRP) sont prescrits. Antibiothérapie probabiliste à débuter. Réévaluation clinique dans 48h.";
        }

        if (strpos($contenuLower, 'dyspnée') !== false || strpos($contenuLower, 'essoufflement') !== false) {
            return "Conclusion : dyspnée d'effort. Bilan étiologique à réaliser : radiographie thoracique, EFR, échographie cardiaque. Traitement symptomatique en attendant les résultats.";
        }

        if (strpos($contenuLower, 'migraine') !== false || strpos($contenuLower, 'céphalée') !== false) {
            return "Conclusion : crise migraineuse typique. Traitement de la crise par triptans. Mesures non médicamenteuses associées. Consultation neurologique si fréquence > 4 crises/mois.";
        }

        // Conclusion générique
        return "Conclusion : tableau clinique à surveiller. Examens complémentaires en cours. Traitement symptomatique instauré. Consultation de contrôle dans 7 jours avec les résultats.";
    }
}