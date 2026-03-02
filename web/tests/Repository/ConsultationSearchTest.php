<?php

namespace App\Tests\Repository;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la logique de recherche multicritère des consultations.
 * Fonctionnalité 1 : Recherche multicritère dans la gestion des dossiers médicaux (admin).
 *
 * On valide ici la logique de filtrage dynamique (les combinaisons de critères).
 * Les tests simulent le comportement du QueryBuilder sans base de données.
 */
class ConsultationSearchTest extends TestCase
{
    /**
     * Données simulées de consultations.
     */
    private array $consultations;

    protected function setUp(): void
    {
        $this->consultations = [
            ['id' => 1, 'medecinId' => 10, 'date' => '2025-03-15', 'statut' => 'En cours'],
            ['id' => 2, 'medecinId' => 10, 'date' => '2025-03-15', 'statut' => 'Terminée'],
            ['id' => 3, 'medecinId' => 20, 'date' => '2025-03-15', 'statut' => 'En cours'],
            ['id' => 4, 'medecinId' => 10, 'date' => '2025-04-01', 'statut' => 'En cours'],
            ['id' => 5, 'medecinId' => 20, 'date' => '2025-04-01', 'statut' => 'Terminée'],
            ['id' => 6, 'medecinId' => 30, 'date' => '2025-04-10', 'statut' => 'Planifiée'],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  Filtre par médecin seul
    // ──────────────────────────────────────────────────────────────

    public function testRechercheParMedecin(): void
    {
        $result = $this->searchConsultations(10, null, null);

        $this->assertCount(3, $result);
        foreach ($result as $c) {
            $this->assertSame(10, $c['medecinId']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Filtre par date seule
    // ──────────────────────────────────────────────────────────────

    public function testRechercheParDate(): void
    {
        $result = $this->searchConsultations(null, '2025-03-15', null);

        $this->assertCount(3, $result);
        foreach ($result as $c) {
            $this->assertSame('2025-03-15', $c['date']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Filtre par statut seul
    // ──────────────────────────────────────────────────────────────

    public function testRechercheParStatut(): void
    {
        $result = $this->searchConsultations(null, null, 'Terminée');

        $this->assertCount(2, $result);
        foreach ($result as $c) {
            $this->assertSame('Terminée', $c['statut']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Combinaison de critères
    // ──────────────────────────────────────────────────────────────

    public function testRechercheMedecinEtDate(): void
    {
        $result = $this->searchConsultations(10, '2025-03-15', null);

        $this->assertCount(2, $result);
    }

    public function testRechercheMedecinEtStatut(): void
    {
        $result = $this->searchConsultations(10, null, 'En cours');

        $this->assertCount(2, $result);
    }

    public function testRechercheTroisCriteres(): void
    {
        $result = $this->searchConsultations(10, '2025-03-15', 'En cours');

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
    }

    // ──────────────────────────────────────────────────────────────
    //  Aucun critère → tout est retourné
    // ──────────────────────────────────────────────────────────────

    public function testRechercheSansCritere(): void
    {
        $result = $this->searchConsultations(null, null, null);

        $this->assertCount(6, $result);
    }

    // ──────────────────────────────────────────────────────────────
    //  Aucun résultat
    // ──────────────────────────────────────────────────────────────

    public function testRechercheAucunResultat(): void
    {
        $result = $this->searchConsultations(99, null, null);

        $this->assertEmpty($result);
    }

    public function testRechercheMedecinDateStatutSansResultat(): void
    {
        $result = $this->searchConsultations(30, '2025-03-15', 'En cours');

        $this->assertEmpty($result);
    }

    // ──────────────────────────────────────────────────────────────
    //  Statut vide traité comme null
    // ──────────────────────────────────────────────────────────────

    public function testStatutVideIgnore(): void
    {
        $result = $this->searchConsultations(null, null, '');

        $this->assertCount(6, $result);
    }

    // ──────────────────────────────────────────────────────────────
    //  Helper : reproduit la logique du repository
    // ──────────────────────────────────────────────────────────────

    private function searchConsultations(?int $medecinId, ?string $date, ?string $statut): array
    {
        return array_values(array_filter($this->consultations, function ($c) use ($medecinId, $date, $statut) {
            if ($medecinId !== null && $c['medecinId'] !== $medecinId) {
                return false;
            }
            if ($date !== null && $c['date'] !== $date) {
                return false;
            }
            if ($statut !== null && $statut !== '' && $c['statut'] !== $statut) {
                return false;
            }
            return true;
        }));
    }
}
