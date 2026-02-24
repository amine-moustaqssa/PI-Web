<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class MedicalAssistantService
{
    private array $reglesCliniques = [
        'infection_urinaire' => [
            'mots' => ['infection urinaire', 'cystite', 'brûlures mictionnelles', 'dysurie', 'pollakiurie', 'urine', 'miction'],
            'examens' => [
                'Bandelette urinaire (BU) en première intention',
                'ECBU avec antibiogramme avant traitement',
                'Échographie rénale et vésicale si récidive ou fièvre',
                'Créatininémie pour évaluer la fonction rénale'
            ],
            'traitements' => [
                'Antibiothérapie probabiliste adaptée à l\'âge (amoxicilline 50mg/kg/j en 3 prises)',
                'Boisson abondante (1.5-2L/jour)',
                'Paracétamol 15mg/kg/prise si fièvre ou douleur',
                'Éviter les anti-inflammatoires en phase aiguë'
            ],
            'orientation' => [
                'Consultation de contrôle avec ECBU de contrôle à 48h post-traitement',
                'Consultation néphro-pédiatrique si récidive (>3 épisodes/an)',
                'Surveillance de la fièvre et de l\'état général pendant 48h'
            ]
        ],
        'douleur_thoracique' => [
            'mots' => ['douleur thoracique', 'poitrine', 'thoracique', 'constrictive', 'oppression', 'angine'],
            'examens' => [
                'ECG 12 dérivations en urgence',
                'Dosage des troponines (H0 et H3)',
                'Radiographie thoracique de face',
                'Gaz du sang artériel si dyspnée associée'
            ],
            'traitements' => [
                'Aspirine 250mg per os (si absence de contre-indication)',
                'Dérivés nitrés sublinguaux si douleur persistante',
                'Oxygénothérapie si SpO2 < 92%'
            ],
            'orientation' => [
                'Transfert en unité de soins intensifs cardiologiques si suspicion de SCA',
                'Surveillance scopique pendant 24h',
                'Consultation cardiologique dans les 48h si bilan négatif'
            ]
        ],
        'fievre' => [
            'mots' => ['fièvre', 'température', 'fébrile', 'hyperthermie', 'frissons'],
            'examens' => [
                'NFS (numération formule sanguine)',
                'CRP (protéine C réactive)',
                'Hémocultures (x2 avant antibiothérapie)',
                'ECBU avec examen direct',
                'Recherche de foyer infectieux (ORL, pulmonaire, urinaire)'
            ],
            'traitements' => [
                'Paracétamol 15mg/kg/prise (max 60mg/kg/j)',
                'Antibiothérapie probabiliste selon point d\'appel',
                'Réhydratation abondante (2-3L/jour)'
            ],
            'orientation' => [
                'Réévaluation clinique à 48h',
                'Hospitalisation si signes de gravité (altération de l\'état général, sepsis)'
            ]
        ],
        'toux' => [
            'mots' => ['toux', 'expectoration', 'crachat', 'quinte', 'toux sèche', 'toux grasse'],
            'examens' => [
                'Auscultation pulmonaire systématique',
                'Radiographie thoracique de face',
                'NFS, CRP',
                'Recherche de coqueluche si toux quinteuse'
            ],
            'traitements' => [
                'Traitement symptomatique de la toux (sirop antitussif si toux sèche)',
                'Fluidifiants bronchiques si toux grasse',
                'Antibiothérapie si surinfection avérée'
            ],
            'orientation' => [
                'Consultation ORL si toux chronique (> 3 semaines)',
                'Bilan allergologique si toux évocatrice d\'asthme'
            ]
        ],
        'cephalee' => [
            'mots' => ['céphalée', 'migraine', 'mal de tête', 'tête', 'crânienne'],
            'examens' => [
                'Examen neurologique complet',
                'TDM cérébrale si signes atypiques ou d\'hypertension intracrânienne',
                'Fond d\'œil',
                'Bilan inflammatoire (VS, CRP) si suspicion d\'artérite'
            ],
            'traitements' => [
                'Triptans par voie orale ou nasale si migraine',
                'Anti-inflammatoires non stéroïdiens',
                'Antalgiques de palier I (paracétamol)',
                'Repos en chambre calme et obscure'
            ],
            'orientation' => [
                'Consultation neurologique si céphalées rebelles ou atypiques',
                'Arrêt de travail si nécessaire',
                'Tenue d\'un agenda des céphalées'
            ]
        ],
        'douleur_abdominale' => [
            'mots' => ['douleur abdominale', 'ventre', 'abdomen', 'colique', 'épigastralgie', 'fosse iliaque'],
            'examens' => [
                'Examen abdominal complet (palpation, recherche de défense)',
                'Échographie abdominale',
                'Bilan hépatique et pancréatique',
                'NFS, CRP',
                'Radiographie de l\'abdomen sans préparation'
            ],
            'traitements' => [
                'Antispasmodiques',
                'Antalgiques adaptés à l\'intensité',
                'Régime sans résidu pendant 24-48h',
                'Réhydratation'
            ],
            'orientation' => [
                'Consultation gastro-entérologique',
                'Avis chirurgical si syndrome occlusif ou péritonéal',
                'Hospitalisation si signes de gravité'
            ]
        ],
        'dyspnee' => [
            'mots' => ['dyspnée', 'essoufflement', 'respire mal', 'suffocation', 'orthopnée', 'apnée'],
            'examens' => [
                'Mesure de la saturation en oxygène (SpO2)',
                'Gaz du sang artériel',
                'Radiographie thoracique',
                'Dosage du BNP ou NT-proBNP',
                'ECG'
            ],
            'traitements' => [
                'Oxygénothérapie si SpO2 < 92%',
                'Diurétiques de l\'anse si suspicion d\'OAP',
                'Bronchodilatateurs (bêta-2 mimétiques) si composante obstructive'
            ],
            'orientation' => [
                'Consultation cardiologique si suspicion d\'insuffisance cardiaque',
                'Consultation pneumologique si pathologie respiratoire chronique',
                'Bilan étiologique complet en ambulatoire'
            ]
        ],
        'chute_personne_agee' => [
            'mots' => ['chute', 'personne âgée', 'viellesse', 'ostéoporose', 'fracture', 'hanche'],
            'examens' => [
                'Radiographie du bassin et du membre concerné',
                'Bilan biologique complet (NFS, CRP, ionogramme, créatininémie)',
                'ECG',
                'Recherche de signes de traumatisme crânien'
            ],
            'traitements' => [
                'Antalgiques adaptés (paracétamol, palier II si besoin)',
                'Immobilisation provisoire',
                'Prévention de l\'escarre'
            ],
            'orientation' => [
                'Hospitalisation en gériatrie ou traumatologie',
                'Bilan de chute complet',
                'Consultation gériatrique pour prévention secondaire'
            ]
        ],
        'nausee_vomissement' => [
            'mots' => ['nausée', 'vomissement', 'mal au coeur', 'vomi', 'déshydratation'],
            'examens' => [
                'Examen abdominal complet',
                'Bilan électrolytique (ionogramme sanguin)',
                'Recherche de signes de déshydratation',
                'Bilan hépatique'
            ],
            'traitements' => [
                'Antiémétiques (métoclopramide, ondansétron)',
                'Réhydratation orale ou IV selon tolérance',
                'Alimentation fractionnée, éviter les aliments gras'
            ],
            'orientation' => [
                'Consultation gastro-entérologique si symptômes chroniques',
                'Hospitalisation si vomissements incoercibles',
                'Recherche étiologique (gastro-entérite, médicaments, grossesse)'
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
        $this->logger->info('=== ANALYSE CLINIQUE DÉMARRÉE ===');
        $this->logger->info('📝 Texte reçu: ' . substr($texte, 0, 150));
        $this->logger->info('👤 Contexte patient:', $contextePatient);

        // Essayer Ollama d'abord
        try {
            $ollamaDispo = $this->isOllamaDisponible();
            $this->logger->info('🔌 Ollama disponible: ' . ($ollamaDispo ? '✅ OUI' : '❌ NON'));

            if ($ollamaDispo) {
                $this->logger->info('🤖 Tentative d\'utilisation d\'Ollama...');
                $suggestionsOllama = $this->getSuggestionsParOllama($texte, $contextePatient);

                if (!empty($suggestionsOllama) && $this->hasValidSuggestions($suggestionsOllama)) {
                    $this->logger->info('✅ Utilisation d\'Ollama réussie');
                    return [
                        'suggestions' => $suggestionsOllama,
                        'source' => 'ollama',
                        'count' => $this->compterSuggestions($suggestionsOllama)
                    ];
                } else {
                    $this->logger->warning('⚠️ Ollama a retourné des suggestions vides ou invalides');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur Ollama: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());
        }

        // Fallback sur les règles
        $this->logger->info('📋 Utilisation des règles (fallback)');
        $suggestionsRegles = $this->getSuggestionsParRegles($texte, $contextePatient);
        return [
            'suggestions' => $suggestionsRegles,
            'source' => 'regles',
            'count' => $this->compterSuggestions($suggestionsRegles)
        ];
    }

    /**
     * Vérifie si les suggestions sont valides
     */
    private function hasValidSuggestions(array $suggestions): bool
    {
        return !empty($suggestions['examens']) ||
            !empty($suggestions['traitements']) ||
            !empty($suggestions['orientation']) ||
            !empty($suggestions['alertes']);
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
            $this->logger->info('🔍 Test de connexion à Ollama...');

            $response = $this->httpClient->request('GET', 'http://localhost:11434/api/tags', [
                'timeout' => 3
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('📡 Code réponse Ollama: ' . $statusCode);

            if ($statusCode === 200) {
                $data = $response->toArray();
                $modeles = array_column($data['models'] ?? [], 'name');
                $this->logger->info('✅ Modèles disponibles: ' . implode(', ', $modeles));

                if (in_array('mistral:latest', $modeles) || in_array('mistral', $modeles)) {
                    $this->logger->info('✅ Modèle mistral trouvé');
                    return true;
                } else {
                    $this->logger->warning('⚠️ Modèle mistral non trouvé. Modèles: ' . implode(', ', $modeles));
                    return false;
                }
            }

            $this->logger->warning('⚠️ Ollama a répondu avec le code: ' . $statusCode);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur connexion Ollama: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Suggestions via règles (fallback) - CORRIGÉ avec alertes âge appropriées
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

        // Compter les correspondances pour chaque règle
        $scores = [];
        foreach ($this->reglesCliniques as $situation => $regle) {
            $score = 0;
            foreach ($regle['mots'] as $mot) {
                if (strpos($texteLower, $mot) !== false) {
                    $score += 2;
                    $this->logger->info("   ✓ Mot-clé '{$mot}' correspond à {$situation}");
                }
            }
            if ($score > 0) {
                $scores[$situation] = $score;
            }
        }

        // Trier par score et prendre la meilleure correspondance
        if (!empty($scores)) {
            arsort($scores);
            $bestMatch = array_key_first($scores);
            $this->logger->info("🏆 Meilleure correspondance: {$bestMatch} (score: {$scores[$bestMatch]})");

            $regleChoisie = $this->reglesCliniques[$bestMatch];

            $suggestions['examens'] = $regleChoisie['examens'];
            $suggestions['traitements'] = $regleChoisie['traitements'];
            $suggestions['orientation'] = $regleChoisie['orientation'];
        } else {
            $this->logger->info('📌 Aucune règle spécifique trouvée, utilisation des suggestions génériques');

            $suggestions['examens'] = [
                'Bilan biologique standard (NFS, CRP, ionogramme)',
                'Examens complémentaires orientés par la clinique'
            ];
            $suggestions['traitements'] = [
                'Traitement symptomatique adapté à l\'intensité des symptômes'
            ];
            $suggestions['orientation'] = [
                'Consultation de contrôle à 7 jours',
                'Surveillance ambulatoire avec consignes de réévaluation'
            ];
        }

        // === CORRECTION ICI : Alertes selon l'âge (correctement conditionnées) ===
        if (isset($contexte['age']) && is_numeric($contexte['age'])) {
            $age = (int)$contexte['age'];
            if ($age > 75) {
                $suggestions['alertes'][] = '⚠️ Patient âgé >75 ans : adapter les posologies, surveillance rapprochée';
            } elseif ($age < 16) {
                $suggestions['alertes'][] = '🧒 Patient pédiatrique : adapter les posologies au poids';
            } elseif ($age >= 60 && $age <= 75) {
                $suggestions['alertes'][] = '⚠️ Patient âgé de ' . $age . ' ans : vigilance accrue';
            }
        }

        // Ajouter des alertes selon les antécédents
        if (
            !empty($contexte['antecedents']) &&
            $contexte['antecedents'] !== 'Aucun antécédent notable' &&
            $contexte['antecedents'] !== 'aucun antécédent notable' &&
            $contexte['antecedents'] !== 'Dossier créé automatiquement.'
        ) {
            $suggestions['alertes'][] = '📋 Tenir compte des antécédents: ' . $contexte['antecedents'];
        }

        // Ajouter des alertes selon les allergies
        if (
            !empty($contexte['allergies']) &&
            $contexte['allergies'] !== 'Aucune allergie connue' &&
            $contexte['allergies'] !== 'aucune allergie connue'
        ) {
            $suggestions['alertes'][] = '⚠️ Allergies: ' . $contexte['allergies'];
        }

        return $suggestions;
    }

    /**
     * Suggestions via Ollama (IA générative)
     */
    private function getSuggestionsParOllama(string $texte, array $contexte): array
    {
        $this->logger->info('🤖 Construction du prompt pour Ollama...');
        $prompt = $this->construirePromptMedical($texte, $contexte);

        $this->logger->info('📝 Prompt envoyé (premiers 300 caractères): ' . substr($prompt, 0, 300) . '...');

        try {
            $this->logger->info('📡 Envoi de la requête à Ollama...');

            $response = $this->httpClient->request('POST', 'http://localhost:11434/api/generate', [
                'json' => [
                    'model' => 'mistral',
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.3,
                        'num_predict' => 600,
                        'top_k' => 40,
                        'top_p' => 0.9
                    ]
                ],
                'timeout' => 90
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('📡 Code réponse Ollama: ' . $statusCode);

            if ($statusCode !== 200) {
                throw new \Exception("Ollama a répondu avec le code HTTP $statusCode");
            }

            $data = $response->toArray();
            $reponseBrute = $data['response'] ?? '';
            $this->logger->info('📨 Réponse brute (premiers 300 caractères): ' . substr($reponseBrute, 0, 300));

            $structuree = $this->structurerReponseOllama($reponseBrute);
            $this->logger->info('✅ Suggestions structurées: ' . json_encode($structuree));

            return $structuree;
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur détaillée Ollama: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Construit le prompt pour Ollama (format JSON)
     */
    private function construirePromptMedical(string $texte, array $contexte): string
    {
        $age = $contexte['age'] ?? 'non précisé';
        $antecedents = $contexte['antecedents'] ?? 'aucun antécédent notable';
        $allergies = $contexte['allergies'] ?? 'aucune allergie connue';

        $texteNettoye = substr($texte, 0, 1000);

        $ageContext = '';
        if (is_numeric($age)) {
            if ($age < 16) {
                $ageContext = " (patient pédiatrique)";
            } elseif ($age > 75) {
                $ageContext = " (patient âgé)";
            }
        }

        return "Tu es un médecin urgentiste expérimenté. Analyse cette situation clinique et propose des recommandations médicales PRÉCISES et ADAPTÉES.

CONTEXTE PATIENT:
- Âge: $age ans $ageContext
- Antécédents: $antecedents
- Allergies: $allergies

OBSERVATION CLINIQUE:
\"\"\"
$texteNettoye
\"\"\"

INSTRUCTIONS IMPORTANTES:
1. Identifie le problème médical principal
2. Propose des examens complémentaires PERTINENTS
3. Suggère des traitements ADAPTÉS avec posologies adaptées à l'âge
4. Donne des recommandations d'orientation SPÉCIFIQUES
5. Inclus des alertes liées aux antécédents/allergies/âge

RÈGLES DE FORMATAGE:
- Réponds UNIQUEMENT avec un JSON valide
- Maximum 5 items par catégorie
- Chaque suggestion doit être une phrase complète et spécifique
- Adapte toujours à l'âge du patient

FORMAT JSON ATTENDU:
{
    \"examens\": [
        \"Examen 1: justification\",
        \"Examen 2: justification\"
    ],
    \"traitements\": [
        \"Traitement 1 avec posologie adaptée à l'âge\",
        \"Traitement 2 avec posologie adaptée à l'âge\"
    ],
    \"orientation\": [
        \"Recommandation spécifique 1\",
        \"Recommandation spécifique 2\"
    ],
    \"alertes\": [
        \"Alerte liée au patient\"
    ]
}";
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
        $this->logger->warning('⚠️ Impossible de parser la réponse JSON, utilisation du fallback');
        return [
            'examens' => ['Bilan biologique standard (NFS, CRP, ionogramme)'],
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
            $this->logger->info('🧪 Test de connexion Ollama demandé');

            $response = $this->httpClient->request('GET', 'http://localhost:11434/api/tags', [
                'timeout' => 3
            ]);
            $data = $response->toArray();

            $modeles = array_column($data['models'] ?? [], 'name');

            $this->logger->info('✅ Test connexion réussi. Modèles: ' . implode(', ', $modeles));

            return [
                'disponible' => true,
                'modeles' => $modeles,
                'message' => '✅ Ollama connecté'
            ];
        } catch (\Exception $e) {
            $this->logger->error('❌ Test connexion échoué: ' . $e->getMessage());

            return [
                'disponible' => false,
                'message' => '❌ Ollama non disponible',
                'error' => $e->getMessage()
            ];
        }
    }
}
