<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'algorithme de génération de créneaux disponibles.
 * Fonctionnalité 3 : Créneaux horaires disponibles dans le formulaire de RDV.
 *
 * L'algorithme est extrait du contrôleur pour le tester de manière isolée.
 */
class RendezVousCreneauxTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────
    //  Génération de créneaux de 30 minutes
    // ──────────────────────────────────────────────────────────────

    public function testGenerationCreneauxSimple(): void
    {
        // Dispo : 08:00 → 10:00
        $slots = $this->generateSlots(
            [['heureDebut' => '08:00', 'heureFin' => '10:00']],
            []
        );

        $this->assertSame(['08:00', '08:30', '09:00', '09:30'], $slots);
    }

    public function testCreneauxAvecRdvDejaReserve(): void
    {
        // Dispo : 08:00 → 10:00 — RDV existant à 08:30
        $slots = $this->generateSlots(
            [['heureDebut' => '08:00', 'heureFin' => '10:00']],
            ['08:30']
        );

        $this->assertSame(['08:00', '09:00', '09:30'], $slots);
        $this->assertNotContains('08:30', $slots);
    }

    public function testCreneauxAvecPlusieursRdvReserves(): void
    {
        // Dispo : 08:00 → 10:00 — RDV à 08:00 et 09:00
        $slots = $this->generateSlots(
            [['heureDebut' => '08:00', 'heureFin' => '10:00']],
            ['08:00', '09:00']
        );

        $this->assertSame(['08:30', '09:30'], $slots);
    }

    public function testCreneauxTousReserves(): void
    {
        // Dispo : 09:00 → 10:00 — les deux créneaux possibles sont pris
        $slots = $this->generateSlots(
            [['heureDebut' => '09:00', 'heureFin' => '10:00']],
            ['09:00', '09:30']
        );

        $this->assertEmpty($slots);
    }

    // ──────────────────────────────────────────────────────────────
    //  Plusieurs plages de disponibilité
    // ──────────────────────────────────────────────────────────────

    public function testPlusieursPlagesDeDisponibilite(): void
    {
        // Matin 08:00-10:00 et après-midi 14:00-15:00
        $slots = $this->generateSlots(
            [
                ['heureDebut' => '08:00', 'heureFin' => '10:00'],
                ['heureDebut' => '14:00', 'heureFin' => '15:00'],
            ],
            []
        );

        $expected = ['08:00', '08:30', '09:00', '09:30', '14:00', '14:30'];
        $this->assertSame($expected, $slots);
    }

    // ──────────────────────────────────────────────────────────────
    //  Alignement sur les demi-heures
    // ──────────────────────────────────────────────────────────────

    public function testAlignementSurDemiHeures(): void
    {
        // Dispo : 08:15 → 10:00 — le premier créneau doit être 08:30
        $slots = $this->generateSlots(
            [['heureDebut' => '08:15', 'heureFin' => '10:00']],
            []
        );

        $this->assertSame(['08:30', '09:00', '09:30'], $slots);
        $this->assertNotContains('08:15', $slots);
    }

    public function testAlignementCreneauExactDemiHeure(): void
    {
        // Dispo : 08:30 → 10:00 — pas besoin d'aligner
        $slots = $this->generateSlots(
            [['heureDebut' => '08:30', 'heureFin' => '10:00']],
            []
        );

        $this->assertSame(['08:30', '09:00', '09:30'], $slots);
    }

    // ──────────────────────────────────────────────────────────────
    //  Cas limites
    // ──────────────────────────────────────────────────────────────

    public function testPlageTropCourte(): void
    {
        // Dispo : 08:00 → 08:20 → pas de créneau de 30 min possible
        $slots = $this->generateSlots(
            [['heureDebut' => '08:00', 'heureFin' => '08:20']],
            []
        );

        $this->assertEmpty($slots);
    }

    public function testPlageExactement30Min(): void
    {
        // Dispo : 08:00 → 08:30 → exactement 1 créneau
        $slots = $this->generateSlots(
            [['heureDebut' => '08:00', 'heureFin' => '08:30']],
            []
        );

        // Le créneau 08:00 + 30 min = 08:30 qui est exactement la fin → not > end, so included
        // Actually: $t->modify('+30 minutes') > $end → 08:30 > 08:30 is false → included
        $this->assertSame(['08:00'], $slots);
    }

    public function testAucuneDisponibilite(): void
    {
        $slots = $this->generateSlots([], []);
        $this->assertEmpty($slots);
    }

    public function testCreneauxTriesChronologiquement(): void
    {
        // Après-midi d'abord, puis matin dans l'input → doit être trié
        $slots = $this->generateSlots(
            [
                ['heureDebut' => '14:00', 'heureFin' => '15:00'],
                ['heureDebut' => '08:00', 'heureFin' => '09:00'],
            ],
            []
        );

        // Le sort à la fin doit trier par heure
        $this->assertSame(['08:00', '08:30', '14:00', '14:30'], $slots);
    }

    // ──────────────────────────────────────────────────────────────
    //  Helper : reproduit l'algorithme du contrôleur
    // ──────────────────────────────────────────────────────────────

    /**
     * @param array<array{heureDebut: string, heureFin: string}> $dispos
     * @param array<string> $rdvPris  ex: ['08:30', '09:00']
     * @return array<string>
     */
    private function generateSlots(array $dispos, array $rdvPris): array
    {
        $date = new \DateTimeImmutable('2025-06-15'); // date fixe pour les tests

        $creneauxPris = [];
        foreach ($rdvPris as $heure) {
            $creneauxPris[$heure] = true;
        }

        $slots = [];
        foreach ($dispos as $dispo) {
            [$h, $m] = explode(':', $dispo['heureDebut']);
            $start = $date->setTime((int) $h, (int) $m);

            [$h, $m] = explode(':', $dispo['heureFin']);
            $end = $date->setTime((int) $h, (int) $m);

            // Alignement sur les demi-heures (logique du contrôleur)
            $minuteStart = (int) $start->format('i');
            $minuteMod = $minuteStart % 30;
            if ($minuteMod !== 0) {
                $start = $start->modify('+' . (30 - $minuteMod) . ' minutes');
            }

            for ($t = $start; $t < $end; $t = $t->modify('+30 minutes')) {
                $label = $t->format('H:i');

                if (isset($creneauxPris[$label])) {
                    continue;
                }

                if ($t->modify('+30 minutes') > $end) {
                    continue;
                }

                $slots[$label] = true;
            }
        }

        $available = array_keys($slots);
        sort($available);

        return $available;
    }
}
