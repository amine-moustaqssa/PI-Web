<?php
// src/FacturePdfBundle/Controller/PdfController.php

namespace App\FacturePdfBundle\Controller;

use App\FacturePdfBundle\Service\PdfGeneratorService;
use App\Repository\FactureRepository;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;

#[IsGranted('ROLE_ADMIN')]
class PdfController extends AbstractController
{
    public function __construct(
        private PdfGeneratorService $pdfService,
        private FactureRepository   $factureRepo,
        private PaiementRepository  $paiementRepo,
    ) {}

    #[Route('/admin/pdf/factures', name: 'pdf_all_factures')]
    public function allFactures(): Response
    {
        $factures = $this->factureRepo->findAll();

        return $this->pdfService->generatePdf(
            '@FacturePdf/facture_pdf.html.twig',
            ['factures' => $factures, 'generated_at' => new \DateTime()],
            'factures_rapport_'.date('Y-m-d').'.pdf'
        );
    }

    #[Route('/admin/pdf/paiements', name: 'pdf_all_paiements')]
    public function allPaiements(): Response
    {
        $paiements = $this->paiementRepo->findAll();

        return $this->pdfService->generatePdf(
            '@FacturePdf/paiement_pdf.html.twig',
            ['paiements' => $paiements, 'generated_at' => new \DateTime()],
            'paiements_rapport_'.date('Y-m-d').'.pdf'
        );
    }

    #[Route('/admin/pdf/facture/{id}', name: 'pdf_single_facture')]
    public function singleFacture(int $id): Response
    {
        $facture   = $this->factureRepo->find($id);
        $paiements = $facture->getPaiements(); // make sure the relation exists

        return $this->pdfService->generatePdf(
            '@FacturePdf/facture_pdf.html.twig',
            ['factures' => [$facture], 'paiements' => $paiements, 'generated_at' => new \DateTime()],
            'facture_'.$facture->getReference().'.pdf'
        );
    }
}