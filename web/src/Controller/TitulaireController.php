<?php

namespace App\Controller;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Form\ProfilMedicalType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DisponibiliteRepository;

#[Route('/espace-client')]
class TitulaireController extends AbstractController
{
    #[Route('/settings', name: 'app_titulaire_settings')]
    public function settings(): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        return $this->render('titulaire/settings.html.twig');
    }

    #[Route('/dashboard', name: 'app_titulaire_dashboard')]
    #[Route('/{id}/dashboard', name: 'app_titulaire_dashboard_profil')]
    public function index(
        Request $request,
        ?int $id,
        EntityManagerInterface $entityManager,
        \App\Repository\DisponibiliteRepository $disponibiliteRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // --- 1. GESTION DU FORMULAIRE D'AJOUT DE PROFIL (MODAL) ---
        $newProfil = new ProfilMedical();
        $newProfil->setTitulaire($user);
        $form = $this->createForm(ProfilMedicalType::class, $newProfil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dossier = new DossierClinique();
            $dossier->setProfilMedical($newProfil);
            $dossier->setAllergies([]);
            $dossier->setAntecedents("Dossier créé automatiquement.");

            $entityManager->persist($newProfil);
            $entityManager->persist($dossier);
            $entityManager->flush();

            $this->addFlash('success', 'Nouveau profil ajouté avec succès !');

            return $this->redirectToRoute('app_titulaire_dashboard_profil', ['id' => $newProfil->getId()]);
        }

        // --- 2. RÉCUPÉRATION DES DONNÉES DU DASHBOARD ---
        $query = $entityManager->createQuery(
            'SELECT p, d, r 
            FROM App\Entity\ProfilMedical p 
            LEFT JOIN p.dossierClinique d 
            LEFT JOIN d.rapportsMedicaux r
            WHERE p.titulaire = :user'
        )->setParameter('user', $user);

        $allProfils = $query->getArrayResult();

        // --- 3. RÉCUPÉRATION DES DISPONIBILITÉS DES MÉDECINS ---
        // On récupère les 6 dernières disponibilités pour le widget client
        $recent_dispos = $disponibiliteRepository->findBy([], ['id' => 'DESC'], 6);

        // --- 4. NETTOYAGE DES DONNÉES (SANITIZATION) ---
        foreach ($allProfils as &$p) {
            $p['dateNaissance'] = $p['date_naissance'];
            $p['contactUrgence'] = $p['contact_urgence'];

            if (!isset($p['dossierClinique']) || $p['dossierClinique'] === null) {
                $p['dossierClinique'] = [
                    'antecedents' => null,
                    'allergies' => [],
                    'rapportsMedicaux' => []
                ];
            }
        }
        unset($p);

        // --- 5. SÉLECTION DU PROFIL ACTIF ---
        $activeProfil = !empty($allProfils) ? $allProfils[0] : null;
        if ($id && !empty($allProfils)) {
            foreach ($allProfils as $profil) {
                if ($profil['id'] === $id) {
                    $activeProfil = $profil;
                    break;
                }
            }
        }

        // --- 6. RENDU DE LA VUE ---
        return $this->render('titulaire/dashboard.html.twig', [
            'activeProfil' => $activeProfil,
            'allProfils' => $allProfils,
            'recent_dispos' => $recent_dispos, // Variable nécessaire pour votre nouveau tableau
            'form' => $form->createView(),
        ]);
    }
}
