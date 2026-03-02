<?php

namespace App\Controller\Admin;

use App\Entity\Medecin;
use App\Repository\RendezVousRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\SpecialiteRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(
        RendezVousRepository $repo,
        DisponibiliteRepository $dispoRepo,
        SpecialiteRepository $specialiteRepo,
        ChartBuilderInterface $chartBuilder,
        Request $request
    ): Response {
        // On récupère le paramètre de recherche 'q'
        $query = $request->query->get('q');

        // On récupère les rendez-vous filtrés
        $rendezVousList = $repo->findBySearchQuery($query);

        // --- Chart 1: Spécialités (nombre de médecins par spécialité) ---
        $allSpecialites = $specialiteRepo->findAll();
        $specialiteLabels = [];
        $specialiteData = [];
        $specialiteColors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#858796', '#5a5c69', '#6f42c1', '#fd7e14', '#20c997',
            '#17a2b8', '#6610f2', '#e83e8c', '#28a745', '#dc3545',
        ];

        foreach ($allSpecialites as $spec) {
            $specialiteLabels[] = $spec->getNom();
            $specialiteData[] = $spec->getMedecins()->count();
        }

        $specialiteChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $specialiteChart->setData([
            'labels' => $specialiteLabels,
            'datasets' => [
                [
                    'label' => 'Médecins',
                    'backgroundColor' => array_slice(
                        array_merge($specialiteColors, $specialiteColors),
                        0,
                        count($specialiteLabels)
                    ),
                    'data' => $specialiteData,
                ],
            ],
        ]);
        $specialiteChart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['position' => 'bottom'],
                'title' => ['display' => true, 'text' => 'Médecins par Spécialité'],
            ],
        ]);

        // ---------------------------------------------------------
        // 🧠 MODULE IA : SYSTÈME DE RECOMMANDATION (SMART ALERT)
        // ---------------------------------------------------------

        $alertesIA = [];
        $disponibilites = $dispoRepo->findAll();
        $totalCreneaux = count($disponibilites);

        // Jours de la semaine en français
        $joursFr = [
            1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi',
            4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
        ];

        // 1. PRÉTRAITEMENT DES DONNÉES (Data Pretreatment)
        //    Regroupement multi-dimensionnel : par spécialité, par jour, et par spécialité+jour
        $comptageParSpecialite = [];
        $comptageParJour = [];
        $comptageParSpecialiteJour = [];

        foreach ($disponibilites as $dispo) {
            $medecin = $dispo->getMedecin();
            $specName = 'Non assigné';
            $deptName = null;

            if ($medecin instanceof Medecin && $medecin->getSpecialite()) {
                $specName = $medecin->getSpecialite()->getNom();
                if ($medecin->getSpecialite()->getDepartement()) {
                    $deptName = $medecin->getSpecialite()->getDepartement()->getNom();
                }
            }

            $jour = $dispo->getJourSemaine();

            // Cluster par spécialité
            $comptageParSpecialite[$specName] = ($comptageParSpecialite[$specName] ?? 0) + 1;

            // Cluster par jour
            $comptageParJour[$jour] = ($comptageParJour[$jour] ?? 0) + 1;

            // Cluster croisé spécialité × jour
            $key = $specName . '|' . $jour;
            if (!isset($comptageParSpecialiteJour[$key])) {
                $comptageParSpecialiteJour[$key] = [
                    'specialite' => $specName,
                    'departement' => $deptName,
                    'jour' => $jour,
                    'count' => 0,
                ];
            }
            $comptageParSpecialiteJour[$key]['count']++;
        }

        // 2. CLASSIFICATION ET ANALYSE (Clustering simplifié / seuils adaptatifs)

        if ($totalCreneaux > 0) {
            $nbSpecialites = count($comptageParSpecialite);
            $moyenneParSpec = $totalCreneaux / max($nbSpecialites, 1);

            // --- Analyse par spécialité (concentration des ressources) ---
            foreach ($comptageParSpecialite as $spe => $count) {
                if ($spe === 'Non assigné') continue;

                $pourcentage = ($count / $totalCreneaux) * 100;
                $ratio = $count / max($moyenneParSpec, 1);

                // Seuil critique : une spécialité monopolise > 40% des créneaux
                if ($pourcentage > 40) {
                    $alertesIA[] = [
                        'type' => 'danger',
                        'icon' => 'fa-exclamation-circle',
                        'titre' => 'Surcharge Critique — ' . $spe,
                        'message' => "Le service <b>$spe</b> concentre <b>" . round($pourcentage) . "%</b> de la charge totale ($count créneaux sur $totalCreneaux). "
                            . "Recommandation : Redistribuer les créneaux vers les spécialités sous-dotées.",
                    ];
                }
                // Seuil d'alerte : > 30%
                elseif ($pourcentage > 30) {
                    $alertesIA[] = [
                        'type' => 'warning',
                        'icon' => 'fa-exclamation-triangle',
                        'titre' => 'Surcharge Prédictive — ' . $spe,
                        'message' => "Le service <b>$spe</b> accumule <b>" . round($pourcentage) . "%</b> des créneaux. "
                            . "Tendance à surveiller : ce pôle risque la saturation.",
                    ];
                }
                // Sous-utilisation : < 5% et au moins 3 spécialités existent
                elseif ($pourcentage < 5 && $nbSpecialites > 2) {
                    $alertesIA[] = [
                        'type' => 'info',
                        'icon' => 'fa-lightbulb',
                        'titre' => 'Sous-utilisation — ' . $spe,
                        'message' => "Le service <b>$spe</b> ne représente que <b>" . round($pourcentage, 1) . "%</b> des créneaux ($count). "
                            . "Vérifiez la disponibilité des médecins de ce service.",
                    ];
                }
            }

            // --- Analyse par jour (détection des pics et creux) ---
            $moyenneParJour = $totalCreneaux / 7;

            foreach ($comptageParJour as $jour => $count) {
                $jourNom = $joursFr[$jour] ?? "Jour $jour";
                $ecart = $count / max($moyenneParJour, 1);

                // Pic de surcharge : plus de 2x la moyenne
                if ($ecart > 2.0) {
                    $alertesIA[] = [
                        'type' => 'warning',
                        'icon' => 'fa-calendar-day',
                        'titre' => 'Pic détecté le ' . $jourNom,
                        'message' => "<b>$count créneaux</b> concentrés le <b>$jourNom</b> (moyenne : " . round($moyenneParJour) . "/jour). "
                            . "Recommandation : Étaler les consultations sur les jours creux.",
                    ];
                }
                // Jour creux : moins de 30% de la moyenne
                elseif ($ecart < 0.3 && $moyenneParJour > 1) {
                    $alertesIA[] = [
                        'type' => 'info',
                        'icon' => 'fa-calendar-minus',
                        'titre' => 'Jour sous-exploité — ' . $jourNom,
                        'message' => "Seulement <b>$count créneau(x)</b> le <b>$jourNom</b>. "
                            . "Ce jour pourrait absorber une partie de la charge des jours surchargés.",
                    ];
                }
            }

            // --- Analyse croisée spécialité × jour (micro-clusters "Zone Rouge") ---
            foreach ($comptageParSpecialiteJour as $data) {
                if ($data['specialite'] === 'Non assigné') continue;

                $specTotal = $comptageParSpecialite[$data['specialite']] ?? 1;
                $jourNom = $joursFr[$data['jour']] ?? "Jour " . $data['jour'];
                $concentration = ($data['count'] / max($specTotal, 1)) * 100;

                // Zone Rouge : > 60% des créneaux d'une spécialité tombent sur un seul jour
                if ($concentration > 60 && $data['count'] >= 3) {
                    $dept = $data['departement'] ? " (Dept. " . $data['departement'] . ")" : '';
                    $alertesIA[] = [
                        'type' => 'danger',
                        'icon' => 'fa-fire',
                        'titre' => '🔴 Zone Rouge — ' . $data['specialite'] . ' / ' . $jourNom,
                        'message' => "<b>" . round($concentration) . "%</b> des créneaux de <b>" . $data['specialite'] . "</b>$dept sont concentrés le <b>$jourNom</b> ({$data['count']} créneaux). "
                            . "Recommandation : Ouvrir des créneaux supplémentaires les jours adjacents.",
                    ];
                }
            }

            // --- Spécialités sans aucun créneau ---
            foreach ($allSpecialites as $spec) {
                if (!isset($comptageParSpecialite[$spec->getNom()])) {
                    $alertesIA[] = [
                        'type' => 'secondary',
                        'icon' => 'fa-ghost',
                        'titre' => 'Aucun créneau — ' . $spec->getNom(),
                        'message' => "La spécialité <b>" . $spec->getNom() . "</b> n'a aucune disponibilité enregistrée. "
                            . "Aucun patient ne peut prendre rendez-vous dans ce service.",
                    ];
                }
            }
        }

        // 3. RÉSULTAT FINAL : Si aucune anomalie, l'IA confirme l'équilibre
        if (empty($alertesIA)) {
            $alertesIA[] = [
                'type' => 'success',
                'icon' => 'fa-check-circle',
                'titre' => 'Équilibre Optimal',
                'message' => "L'algorithme d'analyse indique que la répartition des ressources médicales est actuellement <b>stable et équilibrée</b>. Aucune action corrective requise.",
            ];
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'rendez_vous' => $rendezVousList,
            'search_query' => $query,
            'specialiteChart' => $specialiteChart,
            'alertesIA' => $alertesIA,
        ]);
    }

    #[Route('/planning/agenda', name: 'app_admin_disponibilite_agenda', methods: ['GET'])]
    public function agenda(DisponibiliteRepository $dispoRepo, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $dispoRepo->createQueryBuilder('d')
            ->orderBy('d.jourSemaine', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->addOrderBy('d.id', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $agenda = [];

        foreach ($pagination as $dispo) {
            $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            $jour = $dispo->getJourSemaine();
            $dayName = isset($days[$jour]) ? $days[$jour] : 'Monday';
            $dateKey = (new \DateTime("next $dayName"))->format('Y-m-d');
            
            $agenda[$dateKey][] = $dispo;
        }

        ksort($agenda);

        return $this->render('disponibilite/agenda.html.twig', [
            'layout' => 'admin/base_admin.html.twig',
            'agenda' => $agenda,
            'pagination' => $pagination
        ]);
    }
}