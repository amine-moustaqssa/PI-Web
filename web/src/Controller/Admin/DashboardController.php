<?php

namespace App\Controller\Admin;

use App\Repository\RendezVousRepository;
use App\Repository\DisponibiliteRepository;
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
    public function index(RendezVousRepository $repo, DisponibiliteRepository $dispoRepo, ChartBuilderInterface $chartBuilder, Request $request): Response
    {
        // On récupère le paramètre de recherche 'q'
        $query = $request->query->get('q');

        // On récupère les rendez-vous filtrés
        $rendezVousList = $repo->findBySearchQuery($query);

        // --- UX ChartJS : Répartition des Créneaux ---
        $disponibilites = $dispoRepo->findAll();
        $specialiteCounts = [];
        
        foreach ($disponibilites as $dispo) {
            $medecin = $dispo->getMedecin();
            if ($medecin && method_exists($medecin, 'getSpecialite') && $medecin->getSpecialite()) {
                $specName = $medecin->getSpecialite()->getNom();
                $specialiteCounts[$specName] = ($specialiteCounts[$specName] ?? 0) + 1;
            } else {
                $specialiteCounts['Non assigné'] = ($specialiteCounts['Non assigné'] ?? 0) + 1;
            }
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => array_keys($specialiteCounts),
            'datasets' => [
                [
                    'label' => 'Créneaux',
                    'backgroundColor' => ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'],
                    'data' => array_values($specialiteCounts),
                ],
            ],
        ]);
        
        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['position' => 'bottom']
            ]
        ]);

        return $this->render('admin/dashboard/index.html.twig', [
            'rendez_vous' => $rendezVousList,
            'search_query' => $query,
            'chart' => $chart,
        ]);
    }

    #[Route('/planning/agenda', name: 'app_admin_disponibilite_agenda', methods: ['GET'])]
    public function agenda(DisponibiliteRepository $dispoRepo, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $dispoRepo->createQueryBuilder('d')
            ->orderBy('d.jourSemaine', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC');

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