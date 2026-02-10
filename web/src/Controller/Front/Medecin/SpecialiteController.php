<?php

namespace App\Controller\Front\Medecin;

use App\Entity\Specialite;
use App\Repository\SpecialiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin/specialite')]
final class SpecialiteController extends AbstractController
{
    /**
     * Affiche la liste des spécialités
     */
    #[Route('/', name: 'medecin_specialite_index', methods: ['GET'])]
    public function index(SpecialiteRepository $repository): Response
    {
        // On utilise le dossier 'specialite' tel qu'on le voit dans vos fichiers
        return $this->render('specialite/index.html.twig', [
            'specialites' => $repository->findAll(),
        ]);
    }

    /**
     * Affiche les détails d'une spécialité
     */
    #[Route('/{id}', name: 'medecin_specialite_show', methods: ['GET'])]
    public function show(Specialite $specialite): Response
    {
        return $this->render('specialite/show.html.twig', [
            'specialite' => $specialite,
        ]);
    }
}