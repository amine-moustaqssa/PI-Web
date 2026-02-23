<?php
// src/Service/MedicalAssistantService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class MedicalAssistantService
{
    private array $reglesCliniques = [
        'douleur_thoracique' => [
            'mots' => ['douleur thoracique', 'poitrine', 'thoracique', 'constrictive', 'oppression'],
            'examens' => [
                'ECG 12 dérivations en urgence',
                'Dosage des troponines (H0 et H3)',
                'Radiographie thoracique de face'
            ],
            'traitements' => [
                'Aspirine 250mg per os (si absence de contre-indication)',
                'Dérivés nitrés sublinguaux si douleur persistante'
            ],
            'orientation' => [
                'Transfert en unité de soins intensifs cardiologiques si suspicion de SCA',
                'Surveillance scopique pendant 24h'
            ]
        ],
        'fievre' => [
            'mots' => ['fièvre', 'température', 'fébrile', 'hyperthermie'],
            'examens' => [
                'NFS (numération formule sanguine)',
                'CRP (protéine C réactive)',
                'Hémocultures (x2 avant antibiothérapie)',
                'ECBU avec examen direct'
            ],
            'traitements' => [
                'Paracétamol 1g x4/j (max 4g/24h)',
                'Antibiothérapie probabiliste selon point d\'appel'
            ],
            'orientation' => [
                'Réévaluation clinique à 48h',
                'Hospitalisation si signes de gravité'
            ]
        ],
        'dyspnee' => [
            'mots' => ['dyspnée', 'essoufflement', 'respire mal', 'suffocation', 'orthopnée'],
            'examens' => [
                'Mesure de la saturation en oxygène (SpO2)',
                'Gaz du sang artériel',
                'Radiographie thoracique',
                'Dosage du BNP ou NT-proBNP'
            ],
            'traitements' => [
                'Oxygénothérapie si SpO2 < 92%',
                'Diurétiques de l\'anse si suspicion d\'OAP',
                'Bronchodilatateurs si composante obstructive'
            ],
            'orientation' => [
                'Explorations fonctionnelles respiratoires à distance',
                'Consultation cardiologique si suspicion d\'insuffisance cardiaque'
            ]
        ],
        'toux' => [
            'mots' => ['toux', 'expectoration', 'crachat', 'quinte'],
            'examens' => [
                'Auscultation pulmonaire systématique',
                'Radiographie thoracique de face'
            ],
            'traitements' => [
                'Traitement symptomatique de la toux',
                'Antibiothérapie si surinfection avérée'
            ],
            'orientation' => [
                'Consultation ORL si toux chronique',
                'Bilan allergologique si toux évocatrice'
            ]
        ],
        'cephalee' => [
            'mots' => ['céphalée', 'migraine', 'mal de tête', 'tête'],
            'examens' => [
                'Examen neurologique complet',
                'TDM cérébrale si signes atypiques ou d\'hypertension intracrânienne'
            ],
            'traitements' => [
                'Triptans par voie orale ou nasale si migraine',
                'Anti-inflammatoires non stéroïdiens',
                'Antalgiques de palier I'
            ],
            'orientation' => [
                'Consultation neurologique si céphalées rebelles',
                'Arrêt de travail si nécessaire'
            ]
        ],
        'hypertension' => [
            'mots' => ['hypertension', 'tension', 'hta', 'pression artérielle'],
            'examens' => [
                'ECG de repos',
                'Créatininémie avec estimation du débit de filtration glomérulaire',
                'Bandelette urinaire (recherche de protéinurie)',
                'MAPA des 24h si suspicion d\'HTA blouse blanche'
            ],
            'traitements' => [
                'Traitement antihypertenseur adapté au profil patient',
                'Règles hygiéno-diététiques (régime pauvre en sel)'
            ],
            'orientation' => [
                'Consultation de néphrologie si HTA résistante',
                'Surveillance tensionnelle régulière'
            ]
        ],
        'nausee' => [
            'mots' => ['nausée', 'vomissement', 'mal au coeur', 'nausées'],
            'examens' => [
                'Examen abdominal complet',
                'Bilan électrolytique (ionogramme sanguin)',
                'Recherche de signes de déshydratation'
            ],
            'traitements' => [
                'Antiémétiques (métoclopramide, ondansétron)',
                'Réhydratation orale ou IV selon tolérance'
            ],
            'orientation' => [
                'Consultation gastro-entérologique si symptômes chroniques',
                'Hospitalisation si vomissements incoercibles'
            ]
        ]
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    /**
     * Point d'entrée principal - utilise Ollama en priorité
     */
    public function analyserSituationClinique(string $texte, array $contextePatient): array
    {
        $this->logger->info('Analyse clinique demandée', [
            'texte' => substr($texte, 0, 100)
        ]);

        // Essayer Ollama d'abord
        try {
            if ($this->isOllamaDisponible()) {
                $suggestionsOllama = $this->getSuggestionsParOllama($texte, $contextePatient);
                if (!empty($suggestionsOllama)) {
                    return [
                        'suggestions' => $suggestionsOllama,
                        'source' => 'ollama',
                        'count' => $this->compterSuggestions($suggestionsOllama)
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Ollama indisponible, utilisation des règles: ' . $e->getMessage());
        }

        // Fallback sur les règles
        $suggestionsRegles = $this->getSuggestionsParRegles($texte, $contextePatient);
        return [
            'suggestions' => $suggestionsRegles,
            'source' => 'regles',
            'count' => $this->compterSuggestions($suggestionsRegles)
        ];
    }

    /**
     * Compte le nombre total de suggestions
     */
    private function compterSuggestions(array $suggestions): int
    {
        $total = 0;
        foreach ($suggestions as $categorie => $items) {
            if (is_array($items)) {
                $total += count($items);
            }
        }
        return $total;
    }

    /**
     * Vérifie si Ollama est disponible
     */
    private function isOllamaDisponible(): bool
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
     * Suggestions via règles (fallback)
     */
    private function getSuggestionsParRegles(string $texte, array $contexte): array
    {
        $texteLower = strtolower($texte);
        $suggestions = [
            'examens' => [],
            'traitements' => [],
            'orientation' => [],
            'alertes' => []
        ];

        // Identifier la situation clinique
        $situationTrouvee = false;
        foreach ($this->reglesCliniques as $situation => $regle) {
            foreach ($regle['mots'] as $mot) {
                if (strpos($texteLower, $mot) !== false) {
                    $situationTrouvee = true;
                    $suggestions['examens'] = $regle['examens'];
                    $suggestions['traitements'] = $regle['traitements'];
                    $suggestions['orientation'] = $regle['orientation'];
                    break 2;
                }
            }
        }

        // Suggestions génériques si aucune situation trouvée
        if (!$situationTrouvee) {
            $suggestions['examens'] = [
                'Bilan biologique standard (NFS, CRP, ionogramme)',
                'Examens complémentaires orientés par la clinique'
            ];
            $suggestions['traitements'] = [
                'Traitement symptomatique adapté à l\'intensité des symptômes',
                'Antalgiques si douleur (palier I ou II selon EVA)'
            ];
            $suggestions['orientation'] = [
                'Consultation de contrôle à 7 jours',
                'Surveillance ambulatoire avec consignes de réévaluation'
            ];
        }

        // Ajouter des alertes selon l'âge
        if (isset($contexte['age'])) {
            $age = (int)$contexte['age'];
            if ($age > 75) {
                $suggestions['alertes'][] = 'Patient âgé >75 ans : adapter les posologies, surveillance rapprochée';
            } elseif ($age < 16) {
                $suggestions['alertes'][] = 'Patient pédiatrique : adapter les posologies au poids';
            }
        }

        // Ajouter des alertes selon les antécédents
        if (!empty($contexte['antecedents'])) {
            $suggestions['alertes'][] = 'Tenir compte des antécédents dans la prise en charge thérapeutique';
        }

        return $suggestions;
    }

    /**
     * Suggestions via Ollama (IA générative)
     */
    private function getSuggestionsParOllama(string $texte, array $contexte): array
    {
        $prompt = $this->construirePromptMedical($texte, $contexte);

        $response = $this->httpClient->request('POST', 'http://localhost:11434/api/generate', [
            'json' => [
                'model' => 'mistral',
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.3,
                    'num_predict' => 800,
                    'top_k' => 40,
                    'top_p' => 0.9
                ]
            ],
            'timeout' => 20
        ]);

        $data = $response->toArray();
        return $this->structurerReponseOllama($data['response'] ?? '');
    }

    /**
     * Construit le prompt pour Ollama (format JSON)
     */
    private function construirePromptMedical(string $texte, array $contexte): string
    {
        $age = $contexte['age'] ?? 'non précisé';
        $antecedents = $contexte['antecedents'] ?? 'aucun antécédent notable';

        return "Tu es un médecin expert. Analyse cette situation clinique et propose des recommandations structurées.

Contexte patient:
- Âge: $age ans
- Antécédents: $antecedents

Description clinique:
\"$texte\"

Retourne UNIQUEMENT un objet JSON valide avec cette structure exacte (pas de texte avant ou après):
{
    \"examens\": [
        \"examen 1 avec détails\",
        \"examen 2 avec détails\"
    ],
    \"traitements\": [
        \"traitement 1 avec posologie\",
        \"traitement 2 avec posologie\"
    ],
    \"orientation\": [
        \"orientation 1\",
        \"orientation 2\"
    ],
    \"alertes\": [
        \"alerte 1\",
        \"alerte 2\"
    ]
}

Règles:
- Maximum 4 items par catégorie
- Phrases professionnelles et précises
- Inclure des détails (posologies, délais) quand pertinent
- Format JSON uniquement, pas d'explication";
    }

    /**
     * Structure la réponse d'Ollama
     */
    private function structurerReponseOllama(string $reponse): array
    {
        // Nettoyer la réponse (enlever les éventuels textes avant/après JSON)
        preg_match('/\{.*\}/s', $reponse, $matches);
        
        if (!empty($matches[0])) {
            try {
                $data = json_decode($matches[0], true);
                if (is_array($data)) {
                    return [
                        'examens' => $data['examens'] ?? [],
                        'traitements' => $data['traitements'] ?? [],
                        'orientation' => $data['orientation'] ?? [],
                        'alertes' => $data['alertes'] ?? []
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->warning('Erreur parsing JSON Ollama: ' . $e->getMessage());
            }
        }

        // Fallback: suggestions génériques
        return [
            'examens' => ['Bilan biologique standard', 'Examens complémentaires orientés'],
            'traitements' => ['Traitement symptomatique adapté'],
            'orientation' => ['Consultation de contrôle à 7 jours'],
            'alertes' => []
        ];
    }

    /**
     * Teste la connexion à Ollama
     */
    public function testConnexionOllama(): array
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost:11434/api/tags', [
                'timeout' => 3
            ]);
            $data = $response->toArray();
            
            $modeles = array_column($data['models'] ?? [], 'name');
            
            return [
                'disponible' => true,
                'modeles' => $modeles,
                'message' => '✅ Ollama connecté'
            ];
        } catch (\Exception $e) {
            return [
                'disponible' => false,
                'message' => '❌ Ollama non disponible',
                'error' => $e->getMessage()
            ];
        }
    }
}