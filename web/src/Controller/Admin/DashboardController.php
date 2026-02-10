<?php

namespace App\Controller\Admin;

use App\Repository\RendezVousRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(RendezVousRepository $repo, Request $request): Response
    {
        // On récupère le paramètre de recherche 'q'
        $query = $request->query->get('q');

        // On récupère les rendez-vous filtrés
        $rendezVousList = $repo->findBySearchQuery($query);

        return $this->render('admin/dashboard/index.html.twig', [
            'rendez_vous' => $rendezVousList,
            'search_query' => $query
        ]);
    }
}