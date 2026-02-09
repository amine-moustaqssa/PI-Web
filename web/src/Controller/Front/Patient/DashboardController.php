<?php

namespace App\Controller\Front\Patient; // <--- Check 1: Is the namespace correct?

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // <--- Check 2: Is this line here?

#[Route('/patient', name: 'patient_')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('front/patient/dashboard/index.html.twig', [
            // We will pass real data here later
        ]);
    }
}
