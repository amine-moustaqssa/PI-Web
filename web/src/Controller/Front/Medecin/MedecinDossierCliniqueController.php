<?php

namespace App\Controller\Front\Medecin;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Repository\ProfilMedicalRepository;
use App\Repository\DossierCliniqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/medecin/dossier')]
final class MedecinDossierCliniqueController extends AbstractController
{
    // ✅ Liste des profils
    #[Route('/profils', name: 'medecin_profils_list')]
    public function listProfils(ProfilMedicalRepository $repo): Response
    {
        return $this->render('front/medecin/dossier_clinique/profils.html.twig', [
            'profils' => $repo->findAll(),
        ]);
    }

    // ✅ Affichage d’un dossier clinique
    #[Route('/profil/{id}', name: 'medecin_dossier_show')]
    public function show(
        ProfilMedical $profil,
        DossierCliniqueRepository $dossierRepo
    ): Response {
        $dossier = $dossierRepo->findOneBy([
            'profilMedical' => $profil,
        ]);

        return $this->render('front/medecin/dossier_clinique/show.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier,
        ]);
    }

    // ✅ Création / modification d’un dossier clinique
    #[Route('/profil/{id}/edit', name: 'medecin_dossier_edit')]
    public function edit(
        ProfilMedical $profil,
        DossierCliniqueRepository $dossierRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $dossier = $dossierRepo->findOneBy([
            'profilMedical' => $profil,
        ]);

        if (!$dossier) {
            $dossier = new DossierClinique();
            $dossier->setProfilMedical($profil);
        }

        if ($request->isMethod('POST')) {
            $allergies = $request->request->get('allergies');
            $antecedents = $request->request->get('antecedents');

            $dossier->setAllergies(
                $allergies ? array_map('trim', explode(',', $allergies)) : null
            );
            $dossier->setAntecedents($antecedents ?: null);

            $em->persist($dossier);
            $em->flush();

            $this->addFlash('success', 'Dossier clinique enregistré avec succès');

            return $this->redirectToRoute('medecin_dossier_show', [
                'id' => $profil->getId(),
            ]);
        }

        return $this->render('front/medecin/dossier_clinique/edit.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier,
        ]);
    }
}
