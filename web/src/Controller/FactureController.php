<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Repository\FactureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/titulaire/facture')]
final class FactureController extends AbstractController
{
    #[Route('', name: 'app_patient_facture_index', methods: ['GET'])] 
    public function index(FactureRepository $factureRepository): Response
    {
        // Logic: Patients should only see THEIR OWN invoices
        // $user = $this->getUser();
        return $this->render('titulaire/facture/index.html.twig', [
            'factures' => $factureRepository->findAll(), 
        ]);
    }

    #[Route('/{id}', name: 'app_patient_facture_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('titulaire/facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }
}