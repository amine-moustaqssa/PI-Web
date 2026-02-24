<?php

namespace App\Controller\Admin;

use App\Entity\Facture;
use App\Form\FactureType;
use App\Repository\PaiementRepository;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin/facture')]
final class FactureController extends AbstractController
{
    #[Route('/', name: 'app_admin_facture_index', methods: ['GET'])]
    public function index(Request $request, FactureRepository $factureRepository): Response
    {
        // Check if it's an AJAX request
        if ($request->isXmlHttpRequest() && $request->query->get('ajax') === '1') {
            $searchTerm = $request->query->get('search', '');
            $status = $request->query->get('status', '');
            
            $factures = $factureRepository->searchFactures($searchTerm, $status);
            
            return $this->render('admin/facture/_table_rows.html.twig', [
                'factures' => $factures,
            ]);
        }
        
        // Normal page load
        $factures = $factureRepository->findAll();

        return $this->render('admin/facture/index.html.twig', [
            'factures' => $factures,
            'groq_api_key' => $_ENV['GROQ_API_KEY'],

        ]);
    }

    // IMPORTANT: /search must come BEFORE /{id}
    #[Route('/search', name: 'app_admin_facture_search', methods: ['GET'])]
    public function search(Request $request, FactureRepository $factureRepository): Response
    {
        // Check if it's an AJAX request
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('app_admin_facture_index');
        }

        $searchTerm = $request->query->get('search', '');
        $status = $request->query->get('status', '');

        // Search factures
        $factures = $factureRepository->searchFactures($searchTerm, $status);

        return $this->render('admin/facture/_table_rows.html.twig', [
            'factures' => $factures,
        ]);
    }

    #[Route('/new', name: 'app_admin_facture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($facture);
            $entityManager->flush();
            return $this->redirectToRoute('app_admin_facture_index');
        }

        return $this->render('admin/facture/new.html.twig', [
            'facture' => $facture,
            'form' => $form,
        ]);
    }
 #[Route('/stats', name: 'stats_dashboard', methods: ['GET'])]
public function dashboard(
    FactureRepository  $factureRepository,
    PaiementRepository $paiementRepository
): Response {
    $factures  = $factureRepository->findAll();
    $paiements = $paiementRepository->findAll();

    // ── Status counts ──────────────────────────────────────
    $payees   = count(array_filter($factures, fn($f) => strtoupper($f->getStatut()) === 'PAYEE'));
    $attente  = count(array_filter($factures, fn($f) => strtoupper($f->getStatut()) === 'EN_ATTENTE'));
    $annulees = count(array_filter($factures, fn($f) => strtoupper($f->getStatut()) === 'ANNULEE'));

    // ── Monthly revenue (current year) ─────────────────────
    $monthlyRevenue = array_fill(1, 12, 0); // [1 => 0, 2 => 0, ..., 12 => 0]
    foreach ($paiements as $paiement) {
        $month = (int) $paiement->getDatePaiement()->format('n');
        $year  = (int) $paiement->getDatePaiement()->format('Y');
        if ($year === (int) date('Y')) {
            $monthlyRevenue[$month] += (float) $paiement->getMontant();
        }
    }

    // ── KPIs ───────────────────────────────────────────────
    $totalEncaisse = array_sum(array_map(fn($p) => (float) $p->getMontant(), $paiements));
    $totalFactures = count($factures);

    // ── Last 7 days paiements ──────────────────────────────
    $last7Days      = [];
    $last7DaysLabel = [];
    for ($i = 6; $i >= 0; $i--) {
        $date              = new \DateTime("-$i days");
        $label             = $date->format('d/m');
        $last7DaysLabel[]  = $label;
        $dayTotal          = 0;
        foreach ($paiements as $p) {
            if ($p->getDatePaiement()->format('d/m/Y') === $date->format('d/m/Y')) {
                $dayTotal += (float) $p->getMontant();
            }
        }
        $last7Days[] = $dayTotal;
    }
        return $this->render('admin/facture/stats/dashboard.html.twig',[
        // Status doughnut
        'payees'   => $payees,
        'attente'  => $attente,
        'annulees' => $annulees,
        // Monthly line chart
        'monthlyRevenue' => array_values($monthlyRevenue),
        // Last 7 days
        'last7Days'       => $last7Days,
        'last7DaysLabels' => $last7DaysLabel,
        // KPIs
        'totalEncaisse' => $totalEncaisse,
        'totalFactures' => $totalFactures,
        'totalPayees'   => $payees,
        'totalImpayees' => $attente,
    ]);
    }
    #[Route('/{id}', name: 'app_admin_facture_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('admin/facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_facture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_admin_facture_index');
        }

        return $this->render('admin/facture/edit.html.twig', [
            'facture' => $facture,
            'form' => $form,
        ]);
    }
    
    #[Route('/{id}/delete', name: 'app_admin_facture_delete', methods: ['POST'])]
    public function delete(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$facture->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($facture);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_facture_index', [], Response::HTTP_SEE_OTHER);
    }
    

}