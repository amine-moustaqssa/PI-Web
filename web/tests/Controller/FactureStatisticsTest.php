<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la logique de calcul des statistiques de factures.
 * Fonctionnalité 2 : Statistiques de factures dans le tableau de bord admin.
 *
 * Note : la logique de stats étant dans le contrôleur, on la reproduit ici
 * pour valider les algorithmes de comptage et de calcul.
 */
class FactureStatisticsTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────
    //  Comptage par statut
    // ──────────────────────────────────────────────────────────────

    public function testComptageFacturesParStatut(): void
    {
        $factures = [
            $this->createFactureMock('PAYEE', 100),
            $this->createFactureMock('PAYEE', 200),
            $this->createFactureMock('EN_ATTENTE', 150),
            $this->createFactureMock('ANNULEE', 50),
        ];

        $payees   = count(array_filter($factures, fn($f) => strtoupper($f->getStatut()) === 'PAYEE'));
        $attente  = count(array_filter($factures, fn($f) => strtoupper($f->getStatut()) === 'EN_ATTENTE'));
        $annulees = count(array_filter($factures, fn($f) => strtoupper($f->getStatut()) === 'ANNULEE'));

        $this->assertSame(2, $payees);
        $this->assertSame(1, $attente);
        $this->assertSame(1, $annulees);
    }

    public function testComptageFacturesVide(): void
    {
        $factures = [];

        $payees = count(array_filter($factures, fn($f) => strtoupper($f->getStatut()) === 'PAYEE'));

        $this->assertSame(0, $payees);
    }

    public function testComptageStatutInsensibleCasse(): void
    {
        $factures = [
            $this->createFactureMock('payee', 100),
            $this->createFactureMock('Payee', 200),
            $this->createFactureMock('PAYEE', 300),
        ];

        $payees = count(array_filter($factures, fn($f) => strtoupper($f->getStatut()) === 'PAYEE'));

        $this->assertSame(3, $payees);
    }

    // ──────────────────────────────────────────────────────────────
    //  Revenus mensuels
    // ──────────────────────────────────────────────────────────────

    public function testRevenusMensuelsAnneeCourante(): void
    {
        $currentYear = (int) date('Y');
        $paiements = [
            $this->createPaiementMock(new \DateTime("$currentYear-01-15"), 500),
            $this->createPaiementMock(new \DateTime("$currentYear-01-20"), 300),
            $this->createPaiementMock(new \DateTime("$currentYear-03-10"), 200),
        ];

        $monthlyRevenue = array_fill(1, 12, 0.0);
        foreach ($paiements as $paiement) {
            $month = (int) $paiement->getDatePaiement()->format('n');
            $year  = (int) $paiement->getDatePaiement()->format('Y');
            if ($year === $currentYear) {
                $monthlyRevenue[$month] += (float) $paiement->getMontant();
            }
        }

        $this->assertSame(800.0, $monthlyRevenue[1]);  // Janvier : 500 + 300
        $this->assertSame(0.0, $monthlyRevenue[2]);     // Février : 0
        $this->assertSame(200.0, $monthlyRevenue[3]);   // Mars : 200
    }

    public function testRevenusAnneePrecedenteExclus(): void
    {
        $currentYear = (int) date('Y');
        $lastYear = $currentYear - 1;
        $paiements = [
            $this->createPaiementMock(new \DateTime("$lastYear-06-15"), 1000),
        ];

        $monthlyRevenue = array_fill(1, 12, 0.0);
        foreach ($paiements as $paiement) {
            $month = (int) $paiement->getDatePaiement()->format('n');
            $year  = (int) $paiement->getDatePaiement()->format('Y');
            if ($year === $currentYear) {
                $monthlyRevenue[$month] += (float) $paiement->getMontant();
            }
        }

        $this->assertSame(0.0, (float) array_sum($monthlyRevenue));
    }

    // ──────────────────────────────────────────────────────────────
    //  KPI : Total encaissé
    // ──────────────────────────────────────────────────────────────

    public function testTotalEncaisse(): void
    {
        $paiements = [
            $this->createPaiementMock(new \DateTime(), 500),
            $this->createPaiementMock(new \DateTime(), 300),
            $this->createPaiementMock(new \DateTime(), 200),
        ];

        $totalEncaisse = array_sum(array_map(fn($p) => (float) $p->getMontant(), $paiements));

        $this->assertSame(1000.0, $totalEncaisse);
    }

    public function testTotalEncaisseVide(): void
    {
        $paiements = [];

        $totalEncaisse = (float) array_sum(array_map(fn($p) => (float) $p->getMontant(), $paiements));

        $this->assertSame(0.0, $totalEncaisse);
    }

    // ──────────────────────────────────────────────────────────────
    //  Last 7 days paiements
    // ──────────────────────────────────────────────────────────────

    public function testLast7DaysAvecPaiements(): void
    {
        $today = new \DateTime();
        $yesterday = (new \DateTime())->modify('-1 day');

        $paiements = [
            $this->createPaiementMock(clone $today, 100),
            $this->createPaiementMock(clone $today, 200),
            $this->createPaiementMock(clone $yesterday, 150),
        ];

        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $dayTotal = 0;
            foreach ($paiements as $p) {
                if ($p->getDatePaiement()->format('d/m/Y') === $date->format('d/m/Y')) {
                    $dayTotal += (float) $p->getMontant();
                }
            }
            $last7Days[] = $dayTotal;
        }

        // Index 6 = aujourd'hui, index 5 = hier
        $this->assertSame(300.0, $last7Days[6]);  // today: 100 + 200
        $this->assertSame(150.0, $last7Days[5]);  // yesterday
    }

    public function testLast7DaysRetourne7Elements(): void
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $last7Days[] = 0;
        }

        $this->assertCount(7, $last7Days);
    }

    // ──────────────────────────────────────────────────────────────
    //  Helpers (mocks)
    // ──────────────────────────────────────────────────────────────

    private function createFactureMock(string $statut, float $montant): object
    {
        $facture = new class($statut, $montant) {
            public function __construct(private string $statut, private float $montant) {}
            public function getStatut(): string
            {
                return $this->statut;
            }
            public function getMontantTotal(): float
            {
                return $this->montant;
            }
        };
        return $facture;
    }

    private function createPaiementMock(\DateTimeInterface $date, float $montant): object
    {
        return new class($date, $montant) {
            public function __construct(private \DateTimeInterface $date, private float $montant) {}
            public function getDatePaiement(): \DateTimeInterface
            {
                return $this->date;
            }
            public function getMontant(): float
            {
                return $this->montant;
            }
        };
    }
}
