<?php

namespace App\Service;

/**
 * Service centralisant les seuils de référence médicale pour les constantes vitales (adulte).
 * Sources : OMS, AHA, ESC, ERS, ADA, KDIGO, NICE, HAS.
 *
 * Niveaux d'alerte :
 *   - 'normal'   : valeur dans la plage normale
 *   - 'warning'  : valeur hors norme mais pas critique
 *   - 'critical' : valeur dangereuse nécessitant une attention immédiate
 */
class ConstanteVitaleAlertService
{
    /**
     * Référence médicale complète — seuils adultes.
     * Clé = type normalisé (minuscule, sans accents).
     */
    private const REFERENCES = [
        // ─── Température ───
        'temperature' => [
            'label'         => 'Température',
            'unite'         => '°C',
            'critical_low'  => 35.0,
            'normal_low'    => 36.1,
            'normal_high'   => 37.8,
            'critical_high' => 39.5,
            'source'        => 'OMS',
        ],
        // ─── Fréquence cardiaque ───
        'frequence cardiaque' => [
            'label'         => 'Fréquence cardiaque',
            'unite'         => 'bpm',
            'critical_low'  => 40,
            'normal_low'    => 60,
            'normal_high'   => 100,
            'critical_high' => 130,
            'source'        => 'AHA',
        ],
        'pouls' => [
            'label'         => 'Pouls',
            'unite'         => 'bpm',
            'critical_low'  => 40,
            'normal_low'    => 60,
            'normal_high'   => 100,
            'critical_high' => 130,
            'source'        => 'AHA',
        ],
        // ─── Tension artérielle ───
        'tension systolique' => [
            'label'         => 'Tension systolique',
            'unite'         => 'mmHg',
            'critical_low'  => 80,
            'normal_low'    => 90,
            'normal_high'   => 140,
            'critical_high' => 180,
            'source'        => 'ESC/AHA',
        ],
        'tension diastolique' => [
            'label'         => 'Tension diastolique',
            'unite'         => 'mmHg',
            'critical_low'  => 50,
            'normal_low'    => 60,
            'normal_high'   => 90,
            'critical_high' => 120,
            'source'        => 'ESC/AHA',
        ],
        'pression arterielle moyenne' => [
            'label'         => 'Pression artérielle moyenne (PAM)',
            'unite'         => 'mmHg',
            'critical_low'  => 60,
            'normal_low'    => 70,
            'normal_high'   => 105,
            'critical_high' => 110,
            'source'        => 'AHA',
        ],
        // ─── Saturation O2 ───
        'saturation o2' => [
            'label'         => 'Saturation O2 (SpO2)',
            'unite'         => '%',
            'critical_low'  => 90,
            'normal_low'    => 95,
            'normal_high'   => 100,
            'critical_high' => null,
            'source'        => 'OMS',
        ],
        'spo2' => [
            'label'         => 'SpO2',
            'unite'         => '%',
            'critical_low'  => 90,
            'normal_low'    => 95,
            'normal_high'   => 100,
            'critical_high' => null,
            'source'        => 'OMS',
        ],
        // ─── Fréquence respiratoire ───
        'frequence respiratoire' => [
            'label'         => 'Fréquence respiratoire',
            'unite'         => 'resp/min',
            'critical_low'  => 8,
            'normal_low'    => 12,
            'normal_high'   => 20,
            'critical_high' => 30,
            'source'        => 'ERS',
        ],
        // ─── Glycémie ───
        'glycemie a jeun' => [
            'label'         => 'Glycémie à jeun',
            'unite'         => 'g/L',
            'critical_low'  => 0.60,
            'normal_low'    => 0.70,
            'normal_high'   => 1.10,
            'critical_high' => 1.26,
            'source'        => 'OMS',
        ],
        'glycemie' => [
            'label'         => 'Glycémie',
            'unite'         => 'g/L',
            'critical_low'  => 0.60,
            'normal_low'    => 0.70,
            'normal_high'   => 1.10,
            'critical_high' => 1.26,
            'source'        => 'OMS',
        ],
        'glycemie postprandiale' => [
            'label'         => 'Glycémie postprandiale',
            'unite'         => 'g/L',
            'critical_low'  => null,
            'normal_low'    => null,
            'normal_high'   => 1.40,
            'critical_high' => 2.00,
            'source'        => 'ADA',
        ],
        // ─── IMC ───
        'imc' => [
            'label'         => 'IMC',
            'unite'         => 'kg/m²',
            'critical_low'  => 16.0,
            'normal_low'    => 18.5,
            'normal_high'   => 25.0,
            'critical_high' => 35.0,
            'source'        => 'OMS',
        ],
        // ─── Débit cardiaque ───
        'debit cardiaque' => [
            'label'         => 'Débit cardiaque',
            'unite'         => 'L/min',
            'critical_low'  => 3.5,
            'normal_low'    => 4.0,
            'normal_high'   => 8.0,
            'critical_high' => 8.5,
            'source'        => 'ESC',
        ],
        // ─── Diurèse ───
        'diurese' => [
            'label'         => 'Diurèse',
            'unite'         => 'mL/kg/h',
            'critical_low'  => 0.5,
            'normal_low'    => 0.5,
            'normal_high'   => 2.0,
            'critical_high' => 3.0,
            'source'        => 'KDIGO',
        ],
        // ─── Score de Glasgow ───
        'glasgow' => [
            'label'         => 'Score de Glasgow',
            'unite'         => '/15',
            'critical_low'  => 8,
            'normal_low'    => 13,
            'normal_high'   => 15,
            'critical_high' => null,
            'source'        => 'NICE',
        ],
        'score de glasgow' => [
            'label'         => 'Score de Glasgow',
            'unite'         => '/15',
            'critical_low'  => 8,
            'normal_low'    => 13,
            'normal_high'   => 15,
            'critical_high' => null,
            'source'        => 'NICE',
        ],
        // ─── Douleur EVA ───
        'douleur' => [
            'label'         => 'Douleur (EVA)',
            'unite'         => '/10',
            'critical_low'  => null,
            'normal_low'    => 0,
            'normal_high'   => 3,
            'critical_high' => 7,
            'source'        => 'HAS',
        ],
        'eva' => [
            'label'         => 'Douleur (EVA)',
            'unite'         => '/10',
            'critical_low'  => null,
            'normal_low'    => 0,
            'normal_high'   => 3,
            'critical_high' => 7,
            'source'        => 'HAS',
        ],
        // ─── Hémoglobine ───
        'hemoglobine' => [
            'label'         => 'Hémoglobine',
            'unite'         => 'g/dL',
            'critical_low'  => 7.0,
            'normal_low'    => 12.0,
            'normal_high'   => 17.5,
            'critical_high' => 20.0,
            'source'        => 'OMS',
        ],
        // ─── Créatinine ───
        'creatinine' => [
            'label'         => 'Créatinine',
            'unite'         => 'mg/L',
            'critical_low'  => null,
            'normal_low'    => 6.0,
            'normal_high'   => 12.0,
            'critical_high' => 20.0,
            'source'        => 'KDIGO',
        ],
        // ─── Potassium (Kaliémie) ───
        'potassium' => [
            'label'         => 'Potassium (Kaliémie)',
            'unite'         => 'mmol/L',
            'critical_low'  => 2.5,
            'normal_low'    => 3.5,
            'normal_high'   => 5.0,
            'critical_high' => 6.0,
            'source'        => 'KDIGO',
        ],
        'kaliemie' => [
            'label'         => 'Kaliémie',
            'unite'         => 'mmol/L',
            'critical_low'  => 2.5,
            'normal_low'    => 3.5,
            'normal_high'   => 5.0,
            'critical_high' => 6.0,
            'source'        => 'KDIGO',
        ],
        // ─── Sodium (Natrémie) ───
        'sodium' => [
            'label'         => 'Sodium (Natrémie)',
            'unite'         => 'mmol/L',
            'critical_low'  => 120,
            'normal_low'    => 136,
            'normal_high'   => 145,
            'critical_high' => 155,
            'source'        => 'OMS',
        ],
        'natremie' => [
            'label'         => 'Natrémie',
            'unite'         => 'mmol/L',
            'critical_low'  => 120,
            'normal_low'    => 136,
            'normal_high'   => 145,
            'critical_high' => 155,
            'source'        => 'OMS',
        ],
        // ─── Plaquettes ───
        'plaquettes' => [
            'label'         => 'Plaquettes',
            'unite'         => '×10³/µL',
            'critical_low'  => 50,
            'normal_low'    => 150,
            'normal_high'   => 400,
            'critical_high' => 600,
            'source'        => 'OMS',
        ],
        // ─── Leucocytes (Globules blancs) ───
        'leucocytes' => [
            'label'         => 'Leucocytes',
            'unite'         => '×10³/µL',
            'critical_low'  => 2.0,
            'normal_low'    => 4.0,
            'normal_high'   => 10.0,
            'critical_high' => 30.0,
            'source'        => 'OMS',
        ],
        'globules blancs' => [
            'label'         => 'Globules blancs',
            'unite'         => '×10³/µL',
            'critical_low'  => 2.0,
            'normal_low'    => 4.0,
            'normal_high'   => 10.0,
            'critical_high' => 30.0,
            'source'        => 'OMS',
        ],
    ];

    /**
     * Normalise le type pour matcher les clés de référence.
     */
    private function normalizeType(string $type): string
    {
        $type = mb_strtolower(trim($type));
        // Retirer accents simples
        $type = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'ù', 'û', 'ô', 'î', 'ï', 'ç'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'u', 'u', 'o', 'i', 'i', 'c'],
            $type
        );
        return $type;
    }

    /**
     * Retourne le niveau d'alerte pour une constante donnée.
     *
     * @return string 'normal'|'warning'|'critical'|'unknown'
     */
    public function getAlertLevel(string $type, float|string $valeur): string
    {
        $ref = $this->getReference($type);
        if ($ref === null) {
            return 'unknown';
        }

        $val = (float) $valeur;

        // Critical low
        if ($ref['critical_low'] !== null && $val < $ref['critical_low']) {
            return 'critical';
        }
        // Critical high
        if ($ref['critical_high'] !== null && $val > $ref['critical_high']) {
            return 'critical';
        }
        // Warning low
        if ($ref['normal_low'] !== null && $val < $ref['normal_low']) {
            return 'warning';
        }
        // Warning high
        if ($ref['normal_high'] !== null && $val > $ref['normal_high']) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Retourne les infos de la référence pour un type donné, ou null.
     */
    public function getReference(string $type): ?array
    {
        $key = $this->normalizeType($type);
        return self::REFERENCES[$key] ?? null;
    }

    /**
     * Retourne toutes les références.
     */
    public function getAllReferences(): array
    {
        return self::REFERENCES;
    }

    /**
     * Retourne un label humain pour le niveau d'alerte.
     */
    public function getAlertLabel(string $level): string
    {
        return match ($level) {
            'critical' => 'Critique',
            'warning'  => 'Attention',
            'normal'   => 'Normal',
            default    => 'Non référencé',
        };
    }

    /**
     * Retourne la classe CSS Bootstrap badge pour le niveau d'alerte.
     */
    public function getAlertBadgeClass(string $level): string
    {
        return match ($level) {
            'critical' => 'badge-danger',
            'warning'  => 'badge-warning',
            'normal'   => 'badge-success',
            default    => 'badge-secondary',
        };
    }

    /**
     * Retourne l'icône FontAwesome pour le niveau d'alerte.
     */
    public function getAlertIcon(string $level): string
    {
        return match ($level) {
            'critical' => 'fas fa-exclamation-triangle',
            'warning'  => 'fas fa-exclamation-circle',
            'normal'   => 'fas fa-check-circle',
            default    => 'fas fa-question-circle',
        };
    }

    /**
     * Analyse un tableau de constantes et retourne les alertes.
     *
     * @param array $constantes Tableau d'entités ConstanteVitale
     * @return array ['alerts' => [...], 'hasCritical' => bool, 'hasWarning' => bool, 'summary' => string]
     */
    public function analyzeConstantes(array $constantes): array
    {
        $alerts = [];
        $criticalCount = 0;
        $warningCount = 0;

        foreach ($constantes as $c) {
            $type = $c->getType();
            $valeur = $c->getValeur();
            $level = $this->getAlertLevel($type, $valeur);
            $ref = $this->getReference($type);

            $alerts[] = [
                'constante_id' => $c->getId(),
                'type'         => $type,
                'valeur'       => $valeur,
                'unite'        => $c->getUnite(),
                'level'        => $level,
                'label'        => $this->getAlertLabel($level),
                'badge_class'  => $this->getAlertBadgeClass($level),
                'icon'         => $this->getAlertIcon($level),
                'reference'    => $ref,
            ];

            if ($level === 'critical') {
                $criticalCount++;
            } elseif ($level === 'warning') {
                $warningCount++;
            }
        }

        $summary = '';
        if ($criticalCount > 0) {
            $summary = "⚠ {$criticalCount} constante(s) en état CRITIQUE !";
        } elseif ($warningCount > 0) {
            $summary = "{$warningCount} constante(s) hors norme.";
        }

        return [
            'alerts'      => $alerts,
            'hasCritical' => $criticalCount > 0,
            'hasWarning'  => $warningCount > 0,
            'criticalCount' => $criticalCount,
            'warningCount'  => $warningCount,
            'summary'     => $summary,
        ];
    }
}
