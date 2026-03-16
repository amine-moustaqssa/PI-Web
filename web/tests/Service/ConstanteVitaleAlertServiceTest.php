<?php

namespace App\Tests\Service;

use App\Service\ConstanteVitaleAlertService;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service d'alertes des constantes vitales.
 * Fonctionnalité 6 : Infirmier / Constantes vitales — détection d'alertes.
 */
class ConstanteVitaleAlertServiceTest extends TestCase
{
    private ConstanteVitaleAlertService $service;

    protected function setUp(): void
    {
        $this->service = new ConstanteVitaleAlertService();
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLevel() — seuils de température
    // ──────────────────────────────────────────────────────────────

    public function testTemperatureNormale(): void
    {
        $this->assertSame('normal', $this->service->getAlertLevel('temperature', 37.0));
    }

    public function testTemperatureWarningHaute(): void
    {
        // 38.5 > 37.8 (normal_high) mais <= 39.5 (critical_high)
        $this->assertSame('warning', $this->service->getAlertLevel('temperature', 38.5));
    }

    public function testTemperatureCritiqueBasse(): void
    {
        // 34.0 < 35.0 (critical_low)
        $this->assertSame('critical', $this->service->getAlertLevel('temperature', 34.0));
    }

    public function testTemperatureCritiqueHaute(): void
    {
        // 40.5 > 39.5 (critical_high)
        $this->assertSame('critical', $this->service->getAlertLevel('temperature', 40.5));
    }

    public function testTemperatureWarningBasse(): void
    {
        // 35.5 >= 35.0 (critical_low) mais < 36.1 (normal_low) → warning
        $this->assertSame('warning', $this->service->getAlertLevel('temperature', 35.5));
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLevel() — fréquence cardiaque
    // ──────────────────────────────────────────────────────────────

    public function testFrequenceCardiaqueNormale(): void
    {
        $this->assertSame('normal', $this->service->getAlertLevel('frequence cardiaque', 75));
    }

    public function testFrequenceCardiaqueTachycardieCritique(): void
    {
        $this->assertSame('critical', $this->service->getAlertLevel('frequence cardiaque', 150));
    }

    public function testFrequenceCardiaqueBradycardieCritique(): void
    {
        $this->assertSame('critical', $this->service->getAlertLevel('frequence cardiaque', 35));
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLevel() — saturation O2 (SpO2)
    // ──────────────────────────────────────────────────────────────

    public function testSpO2Normale(): void
    {
        $this->assertSame('normal', $this->service->getAlertLevel('spo2', 98));
    }

    public function testSpO2Warning(): void
    {
        // 93 >= 90 (critical_low) mais < 95 (normal_low) → warning
        $this->assertSame('warning', $this->service->getAlertLevel('spo2', 93));
    }

    public function testSpO2Critique(): void
    {
        // 85 < 90 (critical_low)
        $this->assertSame('critical', $this->service->getAlertLevel('spo2', 85));
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLevel() — glycémie à jeun
    // ──────────────────────────────────────────────────────────────

    public function testGlycemieNormale(): void
    {
        $this->assertSame('normal', $this->service->getAlertLevel('glycemie a jeun', 0.90));
    }

    public function testGlycemieHypoglycemieCritique(): void
    {
        $this->assertSame('critical', $this->service->getAlertLevel('glycemie a jeun', 0.50));
    }

    public function testGlycemieHyperglycemieCritique(): void
    {
        $this->assertSame('critical', $this->service->getAlertLevel('glycemie a jeun', 1.30));
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLevel() — potassium (kaliémie)
    // ──────────────────────────────────────────────────────────────

    public function testPotassiumNormal(): void
    {
        $this->assertSame('normal', $this->service->getAlertLevel('potassium', 4.2));
    }

    public function testPotassiumHypokalemieCritique(): void
    {
        $this->assertSame('critical', $this->service->getAlertLevel('potassium', 2.0));
    }

    public function testPotassiumHyperkalemieWarning(): void
    {
        // 5.5 > 5.0 (normal_high) mais <= 6.0 (critical_high) → warning
        $this->assertSame('warning', $this->service->getAlertLevel('potassium', 5.5));
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLevel() — type inconnu
    // ──────────────────────────────────────────────────────────────

    public function testTypeInconnuRetourneUnknown(): void
    {
        $this->assertSame('unknown', $this->service->getAlertLevel('type_inexistant', 42));
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLevel() — normalisation des accents
    // ──────────────────────────────────────────────────────────────

    public function testNormalisationAccents(): void
    {
        // « Température » (avec accents) doit être normalisé → 'temperature'
        $this->assertSame('normal', $this->service->getAlertLevel('Température', 37.0));
    }

    public function testNormalisationMajuscules(): void
    {
        $this->assertSame('normal', $this->service->getAlertLevel('TEMPERATURE', 37.0));
    }

    // ──────────────────────────────────────────────────────────────
    //  getReference()
    // ──────────────────────────────────────────────────────────────

    public function testGetReferenceExistante(): void
    {
        $ref = $this->service->getReference('temperature');
        $this->assertNotNull($ref);
        $this->assertSame('°C', $ref['unite']);
        $this->assertSame('OMS', $ref['source']);
        $this->assertSame(36.1, $ref['normal_low']);
        $this->assertSame(37.8, $ref['normal_high']);
    }

    public function testGetReferenceInexistante(): void
    {
        $this->assertNull($this->service->getReference('bidon'));
    }

    // ──────────────────────────────────────────────────────────────
    //  getAllReferences()
    // ──────────────────────────────────────────────────────────────

    public function testGetAllReferencesNonVide(): void
    {
        $refs = $this->service->getAllReferences();
        $this->assertNotEmpty($refs);
        $this->assertGreaterThan(20, count($refs));
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLabel() / getAlertBadgeClass() / getAlertIcon()
    // ──────────────────────────────────────────────────────────────

    public function testGetAlertLabelCritique(): void
    {
        $this->assertSame('Critique', $this->service->getAlertLabel('critical'));
    }

    public function testGetAlertLabelNormal(): void
    {
        $this->assertSame('Normal', $this->service->getAlertLabel('normal'));
    }

    public function testGetAlertLabelWarning(): void
    {
        $this->assertSame('Attention', $this->service->getAlertLabel('warning'));
    }

    public function testGetAlertLabelUnknown(): void
    {
        $this->assertSame('Non référencé', $this->service->getAlertLabel('unknown'));
    }

    public function testGetAlertBadgeClassCritique(): void
    {
        $this->assertSame('badge-danger', $this->service->getAlertBadgeClass('critical'));
    }

    public function testGetAlertBadgeClassNormal(): void
    {
        $this->assertSame('badge-success', $this->service->getAlertBadgeClass('normal'));
    }

    public function testGetAlertIconCritique(): void
    {
        $this->assertSame('fas fa-exclamation-triangle', $this->service->getAlertIcon('critical'));
    }

    public function testGetAlertIconNormal(): void
    {
        $this->assertSame('fas fa-check-circle', $this->service->getAlertIcon('normal'));
    }

    // ──────────────────────────────────────────────────────────────
    //  analyzeConstantes()
    // ──────────────────────────────────────────────────────────────

    public function testAnalyzeConstantesAvecCritique(): void
    {
        $constantes = [
            $this->createConstanteMock('temperature', '40.5', '°C', 1),
            $this->createConstanteMock('spo2', '98', '%', 2),
        ];

        $result = $this->service->analyzeConstantes($constantes);

        $this->assertTrue($result['hasCritical']);
        $this->assertSame(1, $result['criticalCount']);
        $this->assertSame(0, $result['warningCount']);
        $this->assertStringContainsString('CRITIQUE', $result['summary']);
        $this->assertCount(2, $result['alerts']);
    }

    public function testAnalyzeConstantesSansAlerte(): void
    {
        $constantes = [
            $this->createConstanteMock('temperature', '37.0', '°C', 1),
            $this->createConstanteMock('spo2', '98', '%', 2),
        ];

        $result = $this->service->analyzeConstantes($constantes);

        $this->assertFalse($result['hasCritical']);
        $this->assertFalse($result['hasWarning']);
        $this->assertSame(0, $result['criticalCount']);
        $this->assertSame(0, $result['warningCount']);
        $this->assertEmpty($result['summary']);
    }

    public function testAnalyzeConstantesAvecWarning(): void
    {
        $constantes = [
            $this->createConstanteMock('temperature', '38.5', '°C', 1), // warning
        ];

        $result = $this->service->analyzeConstantes($constantes);

        $this->assertFalse($result['hasCritical']);
        $this->assertTrue($result['hasWarning']);
        $this->assertSame(1, $result['warningCount']);
        $this->assertStringContainsString('hors norme', $result['summary']);
    }

    public function testAnalyzeConstantesMultiplesCritiques(): void
    {
        $constantes = [
            $this->createConstanteMock('temperature', '40.5', '°C', 1),
            $this->createConstanteMock('spo2', '85', '%', 2),
            $this->createConstanteMock('potassium', '2.0', 'mmol/L', 3),
        ];

        $result = $this->service->analyzeConstantes($constantes);

        $this->assertTrue($result['hasCritical']);
        $this->assertSame(3, $result['criticalCount']);
    }

    // ──────────────────────────────────────────────────────────────
    //  getAlertLevel() — valeurs limites (boundary testing)
    // ──────────────────────────────────────────────────────────────

    public function testTemperatureBorneNormaleLow(): void
    {
        // Exactement 36.1 = normal_low → devrait être normal
        $this->assertSame('normal', $this->service->getAlertLevel('temperature', 36.1));
    }

    public function testTemperatureBorneNormaleHigh(): void
    {
        // Exactement 37.8 = normal_high → devrait être normal (<=)
        $this->assertSame('normal', $this->service->getAlertLevel('temperature', 37.8));
    }

    public function testTemperatureJusteAuDessusCriticalHigh(): void
    {
        // 39.6 > 39.5 → critical
        $this->assertSame('critical', $this->service->getAlertLevel('temperature', 39.6));
    }

    public function testGlasgowScoreCritique(): void
    {
        // Glasgow 7 < 8 (critical_low) → critical
        $this->assertSame('critical', $this->service->getAlertLevel('glasgow', 7));
    }

    public function testGlasgowScoreNormal(): void
    {
        // Glasgow 15 → normal
        $this->assertSame('normal', $this->service->getAlertLevel('glasgow', 15));
    }

    // ──────────────────────────────────────────────────────────────
    //  Helper : créer un mock de ConstanteVitale
    // ──────────────────────────────────────────────────────────────

    private function createConstanteMock(string $type, string $valeur, string $unite, int $id): object
    {
        $mock = $this->createMock(\App\Entity\ConstanteVitale::class);
        $mock->method('getType')->willReturn($type);
        $mock->method('getValeur')->willReturn($valeur);
        $mock->method('getUnite')->willReturn($unite);
        $mock->method('getId')->willReturn($id);
        return $mock;
    }
}
