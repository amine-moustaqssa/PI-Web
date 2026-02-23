<?php

namespace App\Controller\Admin;

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

        return $this->render('admin/dashboard/index.html.twig', [
            'rendez_vous' => $rendezVousList,
            'search_query' => $query,
            'specialiteChart' => $specialiteChart,
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