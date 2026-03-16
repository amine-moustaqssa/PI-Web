<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\PaiementRepository;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/titulaire/paiement')]
final class PaiementController extends AbstractController
{
    #[Route('/', name: 'app_patient_paiement_index', methods: ['GET'])]
    public function index(PaiementRepository $paiementRepository): Response
    {
        return $this->render('titulaire/paiement/index.html.twig', [
            'paiements' => $paiementRepository->findAll(),
        ]);
    }

   #[Route('/new', name: 'app_patient_paiement_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    FactureRepository $factureRepository
): Response {
    $paiement = new Paiement();

    // ✅ Pre-fill facture from URL parameter
    $factureId = $request->query->get('factureId');
    if ($factureId) {
        $facture = $factureRepository->find($factureId);
        if ($facture) {
            $paiement->setFacture($facture);
        }
    }

    $form = $this->createForm(PaiementType::class, $paiement);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($paiement);
        $entityManager->flush();
        return $this->redirectToRoute('app_patient_facture_index');
    }

    return $this->render('titulaire/paiement/new.html.twig', [
        'paiement' => $paiement,
        'form'     => $form,
    ]);
}
    #[Route('/{id}', name: 'app_patient_paiement_show', methods: ['GET'])]
    public function show(Paiement $paiement): Response
    {
        return $this->render('titulaire/paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }
    // No Edit or Delete here!
}