<?php

namespace App\Controller\Front\Medecin;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Repository\ProfilMedicalRepository;
use App\Repository\DossierCliniqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/medecin/dossier')]
final class MedecinDossierCliniqueController extends AbstractController
{
    // ✅ Liste des profils avec pagination
    #[Route('/profils', name: 'medecin_profils_list')]
    public function listProfils(Request $request, ProfilMedicalRepository $repo, PaginatorInterface $paginator): Response
    {
        // Créer une requête pour tous les profils (triés par nom)
        $queryBuilder = $repo->createQueryBuilder('p')
            ->orderBy('p.nom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC');

        // Paginer les résultats
        $profils = $paginator->paginate(
            $queryBuilder, // Requête Doctrine
            $request->query->getInt('page', 1), // Numéro de page
            $request->query->getInt('limit', 10) // Limite par page
        );

        return $this->render('front/medecin/dossier_clinique/profils.html.twig', [
            'profils' => $profils,
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
            $this->addFlash('warning', 'Ce profil n’a pas encore de dossier clinique.');
            return $this->redirectToRoute('medecin_profils_list');
        }

        if ($request->isMethod('POST')) {
            $antecedents = $request->request->get('antecedents', null);
            $dossier->setAntecedents($antecedents ?: null);

            // ✅ Récupération sécurisée des checkboxes allergies
            $allergies = $request->request->all('allergies'); // tableau ou vide
            if (!is_array($allergies)) {
                $allergies = [];
            }
            $dossier->setAllergies($allergies ?: null);

            $em->persist($dossier);
            $em->flush();

            $this->addFlash('success', 'Dossier clinique mis à jour avec succès.');

            return $this->redirectToRoute('medecin_dossier_show', [
                'id' => $profil->getId(),
            ]);
        }

        return $this->render('front/medecin/dossier_clinique/edit.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier,
        ]);
    }

    #[Route('/profil/dossier/{id}/delete', name: 'medecin_dossier_delete', methods: ['POST','GET'])]
    public function delete(DossierClinique $dossier, EntityManagerInterface $em): Response
    {
        $em->remove($dossier);
        $em->flush();

        $this->addFlash('success', 'Dossier clinique supprimé avec succès.');

        return $this->redirectToRoute('medecin_profils_list');
    }
}