<?php

namespace App\Controller\Front\Reception; // <--- Check 1: Is the namespace correct?

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // <--- Check 2: Is this line here?

#[Route('/{id}', name: 'reception_', requirements: ['id' => '\d+'])]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(int $id): Response
    {
        $user = $this->getUser();

        // 🔒 VERY IMPORTANT SECURITY CHECK
        if ($user->getId() !== $id) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('front/reception/dashboard/index.html.twig');
    }
}
