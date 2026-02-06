<?php

namespace App\Controller\Admin;

use App\Entity\Departement;
use App\Entity\Specialite;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Option 1: Show the dashboard page directly
        return parent::index();
        
        // Option 2: If you want to redirect directly to Departements list, uncomment this:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(DepartementCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Gestion Clinique');
    }

    public function configureMenuItems(): iterable
    {
        // Main Dashboard Link
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Section Separator
        yield MenuItem::section('Gestion Médicale');

        // Your New French Entities
        yield MenuItem::linkToCrud('Départements', 'fas fa-hospital', Departement::class);
        yield MenuItem::linkToCrud('Spécialités', 'fas fa-stethoscope', Specialite::class);
    }
}