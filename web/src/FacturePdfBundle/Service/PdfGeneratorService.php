<?php
// src/FacturePdfBundle/Service/PdfGeneratorService.php

namespace App\FacturePdfBundle\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    public function __construct(private Environment $twig) {}

    public function generatePdf(string $template, array $data, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->twig->render($template, $data);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();

        return new \Symfony\Component\HttpFoundation\Response(
            $pdfContent,
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}