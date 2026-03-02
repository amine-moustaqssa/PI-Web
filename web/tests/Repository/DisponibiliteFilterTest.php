<?php

namespace App\Tests\Repository;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la logique de filtrage des disponibilités médecin.
 * Fonctionnalité 5 : Filtrage dans la page de disponibilité du médecin.
 *
 * Simule la logique de findByFilters() et findOverlapping() sans base de données.
 */
class DisponibiliteFilterTest extends TestCase
{
    /**
     * Données simulées de disponibilités.
     */
    private array $disponibilites;

    protected function setUp(): void
    {
        $this->disponibilites = [
            ['id' => 1, 'medecinId' => 10, 'jourSemaine' => 1, 'estRecurrent' => true,  'heureDebut' => '08:00', 'heureFin' => '12:00'],
            ['id' => 2, 'medecinId' => 10, 'jourSemaine' => 3, 'estRecurrent' => true,  'heureDebut' => '14:00', 'heureFin' => '18:00'],
            ['id' => 3, 'medecinId' => 10, 'jourSemaine' => 1, 'estRecurrent' => false, 'heureDebut' => '14:00', 'heureFin' => '16:00'],
            ['id' => 4, 'medecinId' => 20, 'jourSemaine' => 1, 'estRecurrent' => true,  'heureDebut' => '09:00', 'heureFin' => '13:00'],
            ['id' => 5, 'medecinId' => 20, 'jourSemaine' => 5, 'estRecurrent' => false, 'heureDebut' => '08:00', 'heureFin' => '11:00'],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  findByFilters — par médecin
    // ──────────────────────────────────────────────────────────────

    public function testFiltreParMedecin(): void
    {
        $result = $this->findByFilters(null, null, 10);

        $this->assertCount(3, $result);
        foreach ($result as $d) {
            $this->assertSame(10, $d['medecinId']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  findByFilters — par jour de la semaine
    // ──────────────────────────────────────────────────────────────

    public function testFiltreParJour(): void
    {
        $result = $this->findByFilters(1, null, null);

        $this->assertCount(3, $result); // Lundi : id=1,3,4
    }

    // ──────────────────────────────────────────────────────────────
    //  findByFilters — par récurrence
    // ──────────────────────────────────────────────────────────────

    public function testFiltreParRecurrent(): void
    {
        $result = $this->findByFilters(null, true, null);

        $this->assertCount(3, $result);
        foreach ($result as $d) {
            $this->assertTrue($d['estRecurrent']);
        }
    }

    public function testFiltreParNonRecurrent(): void
    {
        $result = $this->findByFilters(null, false, null);

        $this->assertCount(2, $result);
        foreach ($result as $d) {
            $this->assertFalse($d['estRecurrent']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  findByFilters — combinaison
    // ──────────────────────────────────────────────────────────────

    public function testFiltreMedecinEtJour(): void
    {
        $result = $this->findByFilters(1, null, 10);

        $this->assertCount(2, $result); // Medecin 10, Lundi : id=1,3
    }

    public function testFiltreTroisCriteres(): void
    {
        $result = $this->findByFilters(1, true, 10);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
    }

    // ──────────────────────────────────────────────────────────────
    //  findByFilters — aucun filtre → tout
    // ──────────────────────────────────────────────────────────────

    public function testSansFiltreRetourneTout(): void
    {
        $result = $this->findByFilters(null, null, null);

        $this->assertCount(5, $result);
    }

    // ──────────────────────────────────────────────────────────────
    //  findByFilters — aucun résultat
    // ──────────────────────────────────────────────────────────────

    public function testFiltreAucunResultat(): void
    {
        $result = $this->findByFilters(7, null, null); // Dimanche : personne

        $this->assertEmpty($result);
    }

    // ──────────────────────────────────────────────────────────────
    //  findOverlapping — détection de chevauchement
    // ──────────────────────────────────────────────────────────────

    public function testChevauchementDetecte(): void
    {
        // Médecin 10, Lundi, 09:00–11:00 chevauche id=1 (08:00–12:00)
        $result = $this->findOverlapping(10, 1, '09:00', '11:00', null);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
    }

    public function testPasDeChevauchement(): void
    {
        // Médecin 10, Lundi, 13:00–14:00 ne chevauche pas id=1 (08:00–12:00)
        // mais chevauche id=3 (14:00–16:00) → non, 13:00–14:00 finit à 14:00 = début de id=3 → pas de chevauchement strict (< et >)
        $result = $this->findOverlapping(10, 1, '12:00', '14:00', null);

        $this->assertEmpty($result);
    }

    public function testChevauchementAvecExclusion(): void
    {
        // Même créneau que id=1 mais en l'excluant (cas d'édition)
        $result = $this->findOverlapping(10, 1, '08:00', '12:00', 1);

        $this->assertEmpty($result);
    }

    public function testChevauchementPartielFin(): void
    {
        // 11:00–15:00 chevauche id=1 (08:00–12:00) ET id=3 (14:00–16:00)
        $result = $this->findOverlapping(10, 1, '11:00', '15:00', null);

        $this->assertCount(2, $result);
    }

    // ──────────────────────────────────────────────────────────────
    //  Helpers — reproduisent la logique du repository
    // ──────────────────────────────────────────────────────────────

    private function findByFilters(?int $jour, ?bool $recurrent, ?int $medecin): array
    {
        return array_values(array_filter($this->disponibilites, function ($d) use ($jour, $recurrent, $medecin) {
            if ($medecin !== null && $d['medecinId'] !== $medecin) {
                return false;
            }
            if ($jour !== null && $d['jourSemaine'] !== $jour) {
                return false;
            }
            if ($recurrent !== null) {
                if ($d['estRecurrent'] !== $recurrent) {
                    return false;
                }
            }
            return true;
        }));
    }

    private function findOverlapping(int $medecinId, int $jourSemaine, string $heureDebut, string $heureFin, ?int $excludeId): array
    {
        return array_values(array_filter($this->disponibilites, function ($d) use ($medecinId, $jourSemaine, $heureDebut, $heureFin, $excludeId) {
            if ($d['medecinId'] !== $medecinId) {
                return false;
            }
            if ($d['jourSemaine'] !== $jourSemaine) {
                return false;
            }
            // Chevauchement : d.heureDebut < heureFin AND d.heureFin > heureDebut
            if (!($d['heureDebut'] < $heureFin && $d['heureFin'] > $heureDebut)) {
                return false;
            }
            if ($excludeId !== null && $d['id'] === $excludeId) {
                return false;
            }
            return true;
        }));
    }
}
