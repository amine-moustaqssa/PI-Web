<?php

namespace App\Service;

use App\Entity\DossierClinique;

class MedicalScoreCalculator
{
    public function calculate(DossierClinique $dossier): array
    {
        $score = 0;

        // 1️⃣ Allergies
        $allergies = $dossier->getAllergies() ?? [];
        if (count($allergies) >= 3) {
            $score += 2;
        } elseif (count($allergies) >= 1) {
            $score += 1;
        }

        // 2️⃣ Antécédents
        $antecedents = $dossier->getAntecedents() ? explode(',', $dossier->getAntecedents()) : [];
        if (count($antecedents) >= 3) {
            $score += 2;
        } elseif (count($antecedents) >= 1) {
            $score += 1;
        }

        // 3️⃣ Âge
        $profil = $dossier->getProfilMedical();
        if ($profil && $profil->getDateNaissance()) {
            $age = date_diff(new \DateTime(), $profil->getDateNaissance())->y;
            if ($age >= 65) {
                $score += 2;
            } elseif ($age >= 50) {
                $score += 1;
            }
        }

        // 4️⃣ Définition du niveau
        if ($score <= 1) {
            $level = 'Normal';
            $color = 'success';
        } elseif ($score <= 3) {
            $level = 'À vérifier';
            $color = 'warning';
        } else {
            $level = 'Prioritaire';
            $color = 'danger';
        }

        return [
            'score' => $score,
            'level' => $level,
            'color' => $color,
            'comment' => $level === 'Normal' ? 'Patient sans risque particulier.' :
                         ($level === 'À vérifier' ? 'Patient à risque modéré, vérification recommandée.' :
                         'Patient à risque élevé, suivi nécessaire.')
        ];
    }
}
