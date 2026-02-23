<?php

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
                if (!empty($conclusion) && strlen($conclusion) > 30) {
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
     * Vérifie si Ollama est disponible
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
     * Conclusion via Ollama (IA) - AMÉLIORÉ
     */
    private function getConclusionParOllama(string $contenu, array $contexte): string
    {
        $age = $contexte['age'] ?? 'non précisé';
        $antecedents = $contexte['antecedents'] ?? 'aucun antécédent notable';
        $allergies = $contexte['allergies'] ?? 'aucune allergie connue';
        $nom = $contexte['nom'] ?? 'Patient';
        $prenom = $contexte['prenom'] ?? '';

        $prompt = "Tu es un médecin chef de service. Rédige une CONCLUSION MÉDICALE professionnelle et personnalisée pour ce patient.

DOSSIER PATIENT:
- Patient: $prenom $nom
- Âge: $age ans
- Antécédents: $antecedents
- Allergies: $allergies

OBSERVATION CLINIQUE:
\"$contenu\"

RÈGLES POUR LA CONCLUSION:
1. Structure en 3-4 phrases maximum
2. Commence par \"Conclusion :\"
3. Inclus le diagnostic probable ou les hypothèses principales
4. Mentionne les examens clés à réaliser en priorité
5. Propose la conduite à tenir immédiate
6. Adapte au contexte patient (âge, antécédents, allergies)
7. Sois précise, pas de généralités

La conclusion doit être directement utilisable dans un rapport médical.";

        try {
            $response = $this->httpClient->request('POST', 'http://localhost:11434/api/generate', [
                'json' => [
                    'model' => 'mistral',
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.3,
                        'num_predict' => 400
                    ]
                ],
                'timeout' => 20
            ]);

            $data = $response->toArray();
            $conclusion = trim($data['response'] ?? '');
            
            // Nettoyer la conclusion
            $conclusion = preg_replace('/^["\']|["\']$/', '', $conclusion);
            $conclusion = str_replace(['```', 'json', '```json', '```html', '```text'], '', $conclusion);
            $conclusion = trim($conclusion);
            
            // S'assurer que la conclusion commence par "Conclusion :"
            if (strpos($conclusion, 'Conclusion') !== 0 && strpos($conclusion, 'CONCLUSION') !== 0) {
                $conclusion = 'Conclusion : ' . lcfirst($conclusion);
            }
            
            return $conclusion;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur Ollama: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Conclusion via règles (fallback) - AMÉLIORÉ
     */
    private function getConclusionParRegles(string $contenu, array $contexte): string
    {
        $contenuLower = strtolower($contenu);
        $age = $contexte['age'] ?? 30;
        $allergies = $contexte['allergies'] ?? '';
        $antecedents = $contexte['antecedents'] ?? '';

        // Alerte allergies
        $alerteAllergies = '';
        if (!empty($allergies) && $allergies !== 'Aucune allergie connue' && $allergies !== 'aucune allergie connue') {
            $alerteAllergies = " Attention aux allergies: $allergies.";
        }

        // Alerte antécédents
        $alerteAntecedents = '';
        if (!empty($antecedents) && $antecedents !== 'Aucun antécédent notable' && $antecedents !== 'aucun antécédent notable') {
            $alerteAntecedents = " À prendre en compte: $antecedents.";
        }

        // Règles plus détaillées
        if (preg_match('/(douleur thoracique|angine|poitrine).*(irradiation|bras|mâchoire)/i', $contenu)) {
            return "Conclusion : syndrome coronarien aigu suspecté. Un ECG et dosage des troponines en urgence. Mise sous aspirine 250mg et surveillance scopique. Transfert en USIC à discuter.$alerteAllergies$alerteAntecedents";
        }
        
        if (preg_match('/(douleur thoracique|angine|poitrine)/i', $contenu)) {
            return "Conclusion : douleur thoracique à explorer. ECG et bilan biologique (troponines, NFS, CRP). Consultation cardiologique rapide.$alerteAllergies$alerteAntecedents";
        }

        if (preg_match('/(fièvre|température|frissons).*(toux|expectoration|crachat)/i', $contenu)) {
            return "Conclusion : pneumopathie infectieuse probable. Radiographie thoracique, NFS, CRP, hémocultures. Antibiothérapie probabiliste adaptée aux allergies. Réévaluation clinique dans 48h.$alerteAllergies$alerteAntecedents";
        }

        if (preg_match('/(fièvre|température).*(dysurie|brûlures|urines)/i', $contenu)) {
            return "Conclusion : infection urinaire probable. ECBU avec antibiogramme. Antibiothérapie probabiliste si symptômes sévères. Boisson abondante.$alerteAllergies$alerteAntecedents";
        }

        if (preg_match('/(dyspnée|essoufflement|orthopnée)/i', $contenu)) {
            if ($age > 65) {
                return "Conclusion : suspicion d'insuffisance cardiaque chez patient âgé. Radiographie thoracique, BNP, échocardiographie. Traitement diurétique à discuter. Surveillance rapprochée.$alerteAllergies$alerteAntecedents";
            }
            return "Conclusion : dyspnée d'effort. Bilan étiologique: radiographie thoracique, EFR, échographie cardiaque. Traitement symptomatique en attendant les résultats.$alerteAllergies$alerteAntecedents";
        }

        if (preg_match('/(migraine|céphalée|mal de tête)/i', $contenu)) {
            if (preg_match('/(aura|photophobie|nausée)/i', $contenu)) {
                return "Conclusion : crise migraineuse avec aura. Traitement par triptans au début des symptômes. Repos au calme. Consultation neurologique si > 4 crises/mois.$alerteAllergies$alerteAntecedents";
            }
            return "Conclusion : céphalées de tension probable. Antalgiques de palier I. Repos. Consultation si persistance ou aggravation.$alerteAllergies$alerteAntecedents";
        }

        if (preg_match('/(vomissement|vomit|nausée)/i', $contenu)) {
            if (preg_match('/(diarrhée|déshydratation)/i', $contenu)) {
                return "Conclusion : gastro-entérite aiguë. Réhydratation orale abondante. Antiémétiques si besoin. Surveillance des signes de déshydratation. Régime sans lait pendant 48h.$alerteAllergies$alerteAntecedents";
            }
            return "Conclusion : nausées/vomissements. Bilan électrolytique. Réhydratation. Recherche étiologique (médicaments, grossesse, trouble digestif).$alerteAllergies$alerteAntecedents";
        }

        if (preg_match('/(diabète|glycémie|sucre)/i', $contenu)) {
            return "Conclusion : déséquilibre glycémique. Adaptation du traitement antidiabétique. Éducation thérapeutique. Consultation diététique. Surveillance glycémique rapprochée.$alerteAllergies$alerteAntecedents";
        }

        if (preg_match('/(hypertension|tension|hta)/i', $contenu)) {
            return "Conclusion : hypertension artérielle. Bilan initial: ECG, créatininémie, bandelette urinaire. Règles hygiéno-diététiques. Adaptation thérapeutique si nécessaire.$alerteAllergies$alerteAntecedents";
        }

        // Conclusion générique personnalisée selon l'âge
        if ($age > 75) {
            return "Conclusion : patient âgé de $age ans. Tableau clinique à surveiller. Examens complémentaires adaptés. Adaptation des posologies. Consultation de contrôle dans 48-72h.$alerteAllergies$alerteAntecedents";
        } elseif ($age < 16) {
            return "Conclusion : patient pédiatrique de $age ans. Adaptation des doses au poids. Surveillance parentale. Réévaluation rapide en cas d'aggravation.$alerteAllergies$alerteAntecedents";
        } else {
            return "Conclusion : tableau clinique à explorer. Examens complémentaires en cours. Traitement symptomatique instauré. Consultation de contrôle dans 7 jours avec les résultats.$alerteAllergies$alerteAntecedents";
        }
    }
}