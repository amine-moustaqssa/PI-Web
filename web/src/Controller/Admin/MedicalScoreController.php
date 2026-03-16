<?php

namespace App\Controller\Admin;

use App\Entity\DossierClinique;
use App\Repository\DossierCliniqueRepository;
use App\Service\MedicalScoreCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/dossier-clinique')]
class MedicalScoreController extends AbstractController
{
    private DossierCliniqueRepository $dossierRepo;
    private MedicalScoreCalculator $calculator;

    public function __construct(
        DossierCliniqueRepository $dossierRepo,
        MedicalScoreCalculator $calculator
    ) {
        $this->dossierRepo = $dossierRepo;
        $this->calculator = $calculator;
    }

    #[Route('/{id}/score', name: 'admin_dossier_clinique_score')]
    public function score(int $id): Response
    {
        // ✅ On récupère le dossier clinique
        $dossier = $this->dossierRepo->find($id);

        if (!$dossier) {
            throw $this->createNotFoundException('Dossier clinique introuvable.');
        }

        // ✅ On récupère le profil lié au dossier
        $profil = $dossier->getProfilMedical();
        if (!$profil) {
            throw $this->createNotFoundException('Profil médical introuvable.');
        }

        // ✅ Calcul du score avec le service
        $scoreData = $this->calculator->calculate($dossier);

        // ✅ Préparation des données pour Twig
        $allergies = $dossier->getAllergies();
        $antecedents = $dossier->getAntecedents() ? explode(',', $dossier->getAntecedents()) : [];
        $age = $profil->getDateNaissance()
            ? (new \DateTime())->diff($profil->getDateNaissance())->y
            : null;

        // ✅ Passer toutes les variables à Twig
        return $this->render('admin/medical_score/score.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier,
            'scoreData' => $scoreData,
            'allergies' => $allergies,
            'antecedents' => $antecedents,
            'age' => $age,
        ]);
    }
}
