<?php

namespace App\Controller;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Repository\ProfilMedicalRepository;
use App\Repository\DossierCliniqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier')]
final class DossierCliniqueController extends AbstractController
{
    // ✅ Liste des profils médicaux
    #[Route('/profils', name: 'profils_list')]
    public function listProfils(ProfilMedicalRepository $repo): Response
    {
        return $this->render('dossier_clinique/profils.html.twig', [
            'profils' => $repo->findAll()
        ]);
    }

    // ✅ Afficher dossier clinique d’un profil
    #[Route('/{id}', name: 'dossier_show')]
    public function show(
        ProfilMedical $profil,
        DossierCliniqueRepository $dossierRepo
    ): Response {
        $dossier = $dossierRepo->findOneBy([
            'profilMedical' => $profil
        ]);

        return $this->render('dossier_clinique/show.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier
        ]);
    }

    // ✅ Modifier / compléter le dossier clinique
    #[Route('/{id}/edit', name: 'dossier_edit')]
    public function edit(
        ProfilMedical $profil,
        DossierCliniqueRepository $dossierRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $dossier = $dossierRepo->findOneBy(['profilMedical' => $profil]);

        if (!$dossier) {
            $dossier = new DossierClinique();
            $dossier->setProfilMedical($profil);
        }

        if ($request->isMethod('POST')) {

            // 🔁 STRING → ARRAY (IMPORTANT)
            $allergiesInput = $request->request->get('allergies');
            $antecedentsInput = $request->request->get('antecedents');

            $dossier->setAllergies(
                $allergiesInput
                    ? array_map('trim', explode(',', $allergiesInput))
                    : null
            );

            $dossier->setAntecedents($antecedentsInput ?: null);

            $em->persist($dossier);
            $em->flush();

            return $this->redirectToRoute('dossier_show', [
                'id' => $profil->getId()
            ]);
        }

        return $this->render('dossier_clinique/edit.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier
        ]);
    }

    
}
