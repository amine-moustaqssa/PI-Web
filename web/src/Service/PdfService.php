<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Service de génération de PDF utilisant le bundle externe dompdf/dompdf.
 * Génère des rapports PDF personnalisés pour les constantes vitales et consultations.
 */
class PdfService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Génère un PDF à partir d'un template Twig et retourne le contenu binaire.
     *
     * @param string $template  Chemin du template Twig
     * @param array  $data      Variables passées au template
     * @param string $paperSize Format du papier (A4, Letter, etc.)
     * @param string $orientation Orientation (portrait, landscape)
     * @return string Contenu binaire du PDF
     */
    public function generatePdf(string $template, array $data = [], string $paperSize = 'A4', string $orientation = 'portrait'): string
    {
        // Configuration de dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($options);

        // Rendu du template Twig en HTML
        $html = $this->twig->render($template, $data);

        $dompdf->loadHtml($html);
        $dompdf->setPaper($paperSize, $orientation);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Génère le rapport PDF des constantes vitales d'une consultation.
     *
     * @param array $constantes     Liste des constantes vitales
     * @param array $analysis       Résultat de l'analyse des alertes
     * @param array $comparisonData Données de comparaison entre consultations
     * @param array $references     Normes médicales de référence
     * @param int|null $consultationA  ID de la consultation de référence
     * @return string Contenu binaire du PDF
     */
    public function generateConstantesReport(
        array $constantes,
        array $analysis,
        array $comparisonData,
        array $references,
        ?int $consultationA = null
    ): string {
        return $this->generatePdf(
            'front/infirmier/pdf/constantes_report.html.twig',
            [
                'constantes' => $constantes,
                'analysis' => $analysis,
                'comparisonData' => $comparisonData,
                'references' => $references,
                'consultationA' => $consultationA,
                'generatedAt' => new \DateTime(),
            ],
            'A4',
            'landscape'
        );
    }
}
