<?php

namespace App\Tests\Service;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Service\MedicalScoreCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le calculateur de score médical.
 * Fonctionnalité 6 : Score de risque patient (composant du dossier clinique).
 */
class MedicalScoreCalculatorTest extends TestCase
{
    private MedicalScoreCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MedicalScoreCalculator();
    }

    // ──────────────────────────────────────────────────────────────
    //  Patient sans risque : score 0 → Normal
    // ──────────────────────────────────────────────────────────────

    public function testPatientSansRisque(): void
    {
        $dossier = $this->createDossierMock([], null, null);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(0, $result['score']);
        $this->assertSame('Normal', $result['level']);
        $this->assertSame('success', $result['color']);
        $this->assertStringContainsString('sans risque', $result['comment']);
    }

    // ──────────────────────────────────────────────────────────────
    //  Allergies : 1-2 → +1 pt, ≥3 → +2 pts
    // ──────────────────────────────────────────────────────────────

    public function testUneAllergie(): void
    {
        $dossier = $this->createDossierMock(['Pénicilline'], null, null);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(1, $result['score']);
        $this->assertSame('Normal', $result['level']);  // score ≤ 1 → Normal
    }

    public function testTroisAllergies(): void
    {
        $dossier = $this->createDossierMock(['Pénicilline', 'Aspirine', 'Latex'], null, null);

        $result = $this->calculator->calculate($dossier);

        $this->assertGreaterThanOrEqual(2, $result['score']);
    }

    // ──────────────────────────────────────────────────────────────
    //  Antécédents : 1-2 → +1 pt, ≥3 → +2 pts
    // ──────────────────────────────────────────────────────────────

    public function testUnAntecedent(): void
    {
        $dossier = $this->createDossierMock([], 'Diabète', null);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(1, $result['score']);
    }

    public function testTroisAntecedents(): void
    {
        $dossier = $this->createDossierMock([], 'Diabète,Hypertension,Asthme', null);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(2, $result['score']);
    }

    // ──────────────────────────────────────────────────────────────
    //  Âge : 50-64 → +1 pt, ≥65 → +2 pts
    // ──────────────────────────────────────────────────────────────

    public function testPatientJeune30Ans(): void
    {
        $birthDate = (new \DateTime())->modify('-30 years');
        $dossier = $this->createDossierMock([], null, $birthDate);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(0, $result['score']);
    }

    public function testPatient55Ans(): void
    {
        $birthDate = (new \DateTime())->modify('-55 years');
        $dossier = $this->createDossierMock([], null, $birthDate);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(1, $result['score']);
    }

    public function testPatient70Ans(): void
    {
        $birthDate = (new \DateTime())->modify('-70 years');
        $dossier = $this->createDossierMock([], null, $birthDate);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(2, $result['score']);
    }

    // ──────────────────────────────────────────────────────────────
    //  Combinaisons — niveaux composites
    // ──────────────────────────────────────────────────────────────

    public function testNiveauAVerifier(): void
    {
        // 1 allergie (+1) + 1 antécédent (+1) = 2 → À vérifier
        $dossier = $this->createDossierMock(['Pénicilline'], 'Diabète', null);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(2, $result['score']);
        $this->assertSame('À vérifier', $result['level']);
        $this->assertSame('warning', $result['color']);
    }

    public function testNiveauPrioritaire(): void
    {
        // 3 allergies (+2) + 3 antécédents (+2) + âge 70 (+2) = 6 → Prioritaire
        $birthDate = (new \DateTime())->modify('-70 years');
        $dossier = $this->createDossierMock(
            ['Pénicilline', 'Aspirine', 'Latex'],
            'Diabète,Hypertension,Asthme',
            $birthDate
        );

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(6, $result['score']);
        $this->assertSame('Prioritaire', $result['level']);
        $this->assertSame('danger', $result['color']);
        $this->assertStringContainsString('risque élevé', $result['comment']);
    }

    // ──────────────────────────────────────────────────────────────
    //  Cas limites
    // ──────────────────────────────────────────────────────────────

    public function testDossierSansProfilMedical(): void
    {
        $dossier = $this->createMock(DossierClinique::class);
        $dossier->method('getAllergies')->willReturn([]);
        $dossier->method('getAntecedents')->willReturn(null);
        $dossier->method('getProfilMedical')->willReturn(null);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(0, $result['score']);
        $this->assertSame('Normal', $result['level']);
    }

    public function testScoreLimiteEntreNormalEtAVerifier(): void
    {
        // Score exact 1 → Normal (≤1)
        $dossier = $this->createDossierMock(['Aspirine'], null, null);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(1, $result['score']);
        $this->assertSame('Normal', $result['level']);
    }

    public function testScoreLimiteEntreAVerifierEtPrioritaire(): void
    {
        // Score exact 3 → À vérifier (≤3)
        // 1 allergie (+1) + 1 antécédent (+1) + age 55 (+1) = 3
        $birthDate = (new \DateTime())->modify('-55 years');
        $dossier = $this->createDossierMock(['Aspirine'], 'Diabète', $birthDate);

        $result = $this->calculator->calculate($dossier);

        $this->assertSame(3, $result['score']);
        $this->assertSame('À vérifier', $result['level']);
    }

    // ──────────────────────────────────────────────────────────────
    //  Retour de la structure complète
    // ──────────────────────────────────────────────────────────────

    public function testStructureRetour(): void
    {
        $dossier = $this->createDossierMock([], null, null);

        $result = $this->calculator->calculate($dossier);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertArrayHasKey('color', $result);
        $this->assertArrayHasKey('comment', $result);
    }

    // ──────────────────────────────────────────────────────────────
    //  Helper
    // ──────────────────────────────────────────────────────────────

    private function createDossierMock(array $allergies, ?string $antecedents, ?\DateTimeInterface $birthDate): DossierClinique
    {
        $profil = null;
        if ($birthDate !== null) {
            $profil = $this->createMock(ProfilMedical::class);
            $profil->method('getDateNaissance')->willReturn($birthDate);
        }

        $dossier = $this->createMock(DossierClinique::class);
        $dossier->method('getAllergies')->willReturn($allergies);
        $dossier->method('getAntecedents')->willReturn($antecedents);
        $dossier->method('getProfilMedical')->willReturn($profil);

        return $dossier;
    }
}
