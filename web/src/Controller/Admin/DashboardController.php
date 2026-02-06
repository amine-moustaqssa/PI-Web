<?php

namespace App\Controller\Admin; // <--- Check 1: Is the namespace correct?

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // <--- Check 2: Is this line here?

#[Route('/admin', name: 'admin_')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard/index.html.twig', [
            // We will pass real data here later
        ]);
    }
}
