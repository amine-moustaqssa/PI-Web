<?php

namespace App\Controller\Front\Infirmier;

use App\Repository\ConstanteVitaleRepository;
use App\Repository\ConsultationRepository;
use App\Service\ConstanteVitaleAlertService;
use App\Service\PdfService;
use App\Service\MailingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Utilisateur;

#[Route('/infirmier/historique')]
#[IsGranted('ROLE_PERSONNEL')]
class HistoriqueController extends AbstractController
{
    /**
     * Page principale : Historique & Comparaison des constantes vitales.
     * Affiche les graphiques d'évolution et la comparaison entre consultations.
     */
    #[Route('', name: 'infirmier_historique_index', methods: ['GET'])]
    public function index(
        Request $request,
        ConstanteVitaleRepository $constanteRepo,
        ConsultationRepository $consultationRepo,
        ConstanteVitaleAlertService $alertService
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if ($user->getNiveauAcces() !== 'INFIRMIER') {
            throw $this->createAccessDeniedException('Accès réservé aux infirmiers.');
        }

        // Récupérer les filtres
        $selectedType = $request->query->get('type');
        $dateFrom = $request->query->get('date_from') ? new \DateTime($request->query->get('date_from')) : null;
        $dateTo = $request->query->get('date_to') ? new \DateTime($request->query->get('date_to') . ' 23:59:59') : null;
        $consultationA = $request->query->get('consultation_a') ? (int) $request->query->get('consultation_a') : null;
        $consultationsB = $request->query->all('consultations_b') ?: [];
        $consultationsB = array_map('intval', array_filter($consultationsB));

        // Listes pour les filtres
        $types = $constanteRepo->findDistinctTypes();
        $consultations = $consultationRepo->findBy([], ['dateEffectuee' => 'DESC']);

        // Construire la liste des IDs à récupérer (A + B)
        $selectedIds = null;
        if ($consultationA) {
            $selectedIds = array_unique(array_merge([$consultationA], $consultationsB));
        } elseif (!empty($consultationsB)) {
            $selectedIds = $consultationsB;
        }

        // Récupérer l'historique filtré
        $constantes = $constanteRepo->findHistorique($selectedType, $dateFrom, $dateTo, $selectedIds);

        // Analyser les alertes
        $analysis = $alertService->analyzeConstantes($constantes);

        // Préparer les données pour Chart.js — groupées par type
        $chartData = [];
        foreach ($constantes as $c) {
            $type = $c->getType();
            if (!isset($chartData[$type])) {
                $chartData[$type] = [
                    'labels' => [],
                    'values' => [],
                    'consultations' => [],
                ];
            }
            $chartData[$type]['labels'][] = $c->getDatePrise() ? $c->getDatePrise()->format('d/m/Y H:i') : 'N/A';
            $chartData[$type]['values'][] = (float) $c->getValeur();
            $chartData[$type]['consultations'][] = '#' . ($c->getConsultationId() ? $c->getConsultationId()->getId() : '?');
        }

        // Préparer la comparaison entre consultations
        $comparisonData = [];
        foreach ($constantes as $c) {
            $consultId = $c->getConsultationId() ? $c->getConsultationId()->getId() : 0;
            $type = $c->getType();
            if (!isset($comparisonData[$consultId])) {
                $consultation = $c->getConsultationId();
                $comparisonData[$consultId] = [
                    'consultation' => $consultation,
                    'date' => $consultation ? ($consultation->getDateEffectuee() ? $consultation->getDateEffectuee()->format('d/m/Y') : 'N/A') : 'N/A',
                    'medecin' => $consultation && $consultation->getMedecin() ? 'Dr. ' . $consultation->getMedecin()->getNom() . ' ' . $consultation->getMedecin()->getPrenom() : 'N/A',
                    'constantes' => [],
                ];
            }
            $comparisonData[$consultId]['constantes'][$type] = [
                'valeur' => $c->getValeur(),
                'unite' => $c->getUnite(),
                'date_prise' => $c->getDatePrise() ? $c->getDatePrise()->format('d/m/Y H:i') : 'N/A',
            ];
        }

        // Récupérer les normes pour le type sélectionné (pour les lignes horizontales sur les graphiques)
        $references = $alertService->getAllReferences();

        return $this->render('front/infirmier/historique/index.html.twig', [
            'constantes' => $constantes,
            'analysis' => $analysis,
            'chartData' => $chartData,
            'comparisonData' => $comparisonData,
            'types' => $types,
            'consultations' => $consultations,
            'selectedType' => $selectedType,
            'dateFrom' => $request->query->get('date_from'),
            'dateTo' => $request->query->get('date_to'),
            'consultationA' => $consultationA,
            'consultationsB' => $consultationsB,
            'references' => $references,
        ]);
    }

    /**
     * API JSON pour les données de graphique (utilisé par AJAX pour mise à jour dynamique).
     */
    #[Route('/chart-data', name: 'infirmier_historique_chart_data', methods: ['GET'])]
    public function chartData(
        Request $request,
        ConstanteVitaleRepository $constanteRepo,
        ConstanteVitaleAlertService $alertService
    ): JsonResponse {
        $type = $request->query->get('type');
        $dateFrom = $request->query->get('date_from') ? new \DateTime($request->query->get('date_from')) : null;
        $dateTo = $request->query->get('date_to') ? new \DateTime($request->query->get('date_to') . ' 23:59:59') : null;
        $consultationA = $request->query->get('consultation_a') ? (int) $request->query->get('consultation_a') : null;
        $consultationsB = $request->query->all('consultations_b') ?: [];
        $consultationsB = array_map('intval', array_filter($consultationsB));
        $selectedIds = null;
        if ($consultationA) {
            $selectedIds = array_unique(array_merge([$consultationA], $consultationsB));
        } elseif (!empty($consultationsB)) {
            $selectedIds = $consultationsB;
        }

        $constantes = $constanteRepo->findHistorique($type, $dateFrom, $dateTo, $selectedIds);

        $chartData = [];
        foreach ($constantes as $c) {
            $t = $c->getType();
            if (!isset($chartData[$t])) {
                $chartData[$t] = ['labels' => [], 'values' => [], 'consultations' => []];
            }
            $chartData[$t]['labels'][] = $c->getDatePrise() ? $c->getDatePrise()->format('d/m/Y H:i') : 'N/A';
            $chartData[$t]['values'][] = (float) $c->getValeur();
            $chartData[$t]['consultations'][] = '#' . ($c->getConsultationId() ? $c->getConsultationId()->getId() : '?');
        }

        $references = $alertService->getAllReferences();

        return new JsonResponse([
            'chartData' => $chartData,
            'references' => $references,
            'total' => count($constantes),
        ]);
    }

    /**
     * Télécharger le rapport PDF des constantes vitales.
     * Utilise le bundle externe dompdf/dompdf via PdfService.
     */
    #[Route('/pdf', name: 'infirmier_historique_pdf', methods: ['GET'])]
    public function downloadPdf(
        Request $request,
        ConstanteVitaleRepository $constanteRepo,
        ConsultationRepository $consultationRepo,
        ConstanteVitaleAlertService $alertService,
        PdfService $pdfService
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if ($user->getNiveauAcces() !== 'INFIRMIER') {
            throw $this->createAccessDeniedException('Accès réservé aux infirmiers.');
        }

        // Récupérer les mêmes filtres que la page principale
        $selectedType = $request->query->get('type');
        $dateFrom = $request->query->get('date_from') ? new \DateTime($request->query->get('date_from')) : null;
        $dateTo = $request->query->get('date_to') ? new \DateTime($request->query->get('date_to') . ' 23:59:59') : null;
        $consultationA = $request->query->get('consultation_a') ? (int) $request->query->get('consultation_a') : null;
        $consultationsB = $request->query->all('consultations_b') ?: [];
        $consultationsB = array_map('intval', array_filter($consultationsB));

        $selectedIds = null;
        if ($consultationA) {
            $selectedIds = array_unique(array_merge([$consultationA], $consultationsB));
        } elseif (!empty($consultationsB)) {
            $selectedIds = $consultationsB;
        }

        $constantes = $constanteRepo->findHistorique($selectedType, $dateFrom, $dateTo, $selectedIds);
        $analysis = $alertService->analyzeConstantes($constantes);

        // Préparer les données de comparaison
        $comparisonData = [];
        foreach ($constantes as $c) {
            $consultId = $c->getConsultationId() ? $c->getConsultationId()->getId() : 0;
            $type = $c->getType();
            if (!isset($comparisonData[$consultId])) {
                $consultation = $c->getConsultationId();
                $comparisonData[$consultId] = [
                    'consultation' => $consultation,
                    'date' => $consultation ? ($consultation->getDateEffectuee() ? $consultation->getDateEffectuee()->format('d/m/Y') : 'N/A') : 'N/A',
                    'medecin' => $consultation && $consultation->getMedecin() ? 'Dr. ' . $consultation->getMedecin()->getNom() . ' ' . $consultation->getMedecin()->getPrenom() : 'N/A',
                    'constantes' => [],
                ];
            }
            $comparisonData[$consultId]['constantes'][$type] = [
                'valeur' => $c->getValeur(),
                'unite' => $c->getUnite(),
                'date_prise' => $c->getDatePrise() ? $c->getDatePrise()->format('d/m/Y H:i') : 'N/A',
            ];
        }

        $references = $alertService->getAllReferences();

        // Générer le PDF via dompdf
        $pdfContent = $pdfService->generateConstantesReport(
            $constantes,
            $analysis,
            $comparisonData,
            $references,
            $consultationA
        );

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport_constantes_' . date('Y-m-d_H-i') . '.pdf"',
        ]);
    }

    /**
     * Envoyer le rapport des constantes vitales par email.
     * Utilise le bundle externe symfony/mailer via MailingService.
     */
    #[Route('/email', name: 'infirmier_historique_email', methods: ['POST'])]
    public function sendEmail(
        Request $request,
        ConstanteVitaleRepository $constanteRepo,
        ConsultationRepository $consultationRepo,
        ConstanteVitaleAlertService $alertService,
        PdfService $pdfService,
        MailingService $mailingService
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if ($user->getNiveauAcces() !== 'INFIRMIER') {
            throw $this->createAccessDeniedException('Accès réservé aux infirmiers.');
        }

        $recipientEmail = $request->request->get('email');
        $recipientName = $request->request->get('name', 'Destinataire');
        $sendType = $request->request->get('send_type', 'report'); // 'report' ou 'alert'

        if (!$recipientEmail || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('danger', 'Adresse email invalide.');
            return $this->redirectToRoute('infirmier_historique_index');
        }

        // Récupérer les filtres
        $selectedType = $request->request->get('type');
        $dateFrom = $request->request->get('date_from') ? new \DateTime($request->request->get('date_from')) : null;
        $dateTo = $request->request->get('date_to') ? new \DateTime($request->request->get('date_to') . ' 23:59:59') : null;
        $consultationA = $request->request->get('consultation_a') ? (int) $request->request->get('consultation_a') : null;
        $consultationsB = $request->request->all('consultations_b') ?: [];
        $consultationsB = array_map('intval', array_filter($consultationsB));

        $selectedIds = null;
        if ($consultationA) {
            $selectedIds = array_unique(array_merge([$consultationA], $consultationsB));
        } elseif (!empty($consultationsB)) {
            $selectedIds = $consultationsB;
        }

        $constantes = $constanteRepo->findHistorique($selectedType, $dateFrom, $dateTo, $selectedIds);
        $analysis = $alertService->analyzeConstantes($constantes);

        // Préparer les données de comparaison
        $comparisonData = [];
        foreach ($constantes as $c) {
            $consultId = $c->getConsultationId() ? $c->getConsultationId()->getId() : 0;
            $type = $c->getType();
            if (!isset($comparisonData[$consultId])) {
                $consultation = $c->getConsultationId();
                $comparisonData[$consultId] = [
                    'consultation' => $consultation,
                    'date' => $consultation ? ($consultation->getDateEffectuee() ? $consultation->getDateEffectuee()->format('d/m/Y') : 'N/A') : 'N/A',
                    'medecin' => $consultation && $consultation->getMedecin() ? 'Dr. ' . $consultation->getMedecin()->getNom() . ' ' . $consultation->getMedecin()->getPrenom() : 'N/A',
                    'constantes' => [],
                ];
            }
            $comparisonData[$consultId]['constantes'][$type] = [
                'valeur' => $c->getValeur(),
                'unite' => $c->getUnite(),
                'date_prise' => $c->getDatePrise() ? $c->getDatePrise()->format('d/m/Y H:i') : 'N/A',
            ];
        }

        $references = $alertService->getAllReferences();

        try {
            if ($sendType === 'alert') {
                // Envoyer uniquement les alertes critiques
                $criticalAlerts = [];
                foreach ($analysis['alerts'] as $key => $alert) {
                    if ($alert['level'] === 'critical') {
                        $criticalAlerts[] = [
                            'type' => $constantes[$key]->getType(),
                            'valeur' => $constantes[$key]->getValeur(),
                            'unite' => $constantes[$key]->getUnite(),
                            'reference' => $references[$constantes[$key]->getType()] ?? null,
                        ];
                    }
                }

                if (empty($criticalAlerts)) {
                    $this->addFlash('warning', 'Aucune alerte critique à envoyer.');
                    return $this->redirectToRoute('infirmier_historique_index');
                }

                $mailingService->sendCriticalAlert(
                    $recipientEmail,
                    $recipientName,
                    $criticalAlerts,
                    $consultationA ?? 0
                );

                $this->addFlash('success', 'Alerte critique envoyée avec succès à ' . $recipientEmail);
            } else {
                // Envoyer le rapport complet avec PDF en pièce jointe
                $pdfContent = $pdfService->generateConstantesReport(
                    $constantes,
                    $analysis,
                    $comparisonData,
                    $references,
                    $consultationA
                );

                $mailingService->sendConstantesReport(
                    $recipientEmail,
                    $recipientName,
                    [
                        'constantes' => $constantes,
                        'analysis' => $analysis,
                        'comparisonData' => $comparisonData,
                        'consultationA' => $consultationA,
                    ],
                    $pdfContent
                );

                $this->addFlash('success', 'Rapport envoyé avec succès à ' . $recipientEmail . ' (avec PDF en pièce jointe)');
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('infirmier_historique_index');
    }
}
