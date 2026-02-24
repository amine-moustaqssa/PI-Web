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

#[Route('/espace-client')]
class TitulaireController extends AbstractController
{
    #[Route('/dashboard', name: 'app_titulaire_dashboard')]
    #[Route('/{id}/dashboard', name: 'app_titulaire_dashboard_profil')]
    public function index(Request $request, ?int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // --- 1. HANDLE ADD PROFILE FORM (Moved here for Modal) ---
        $newProfil = new ProfilMedical();
        $newProfil->setTitulaire($user);
        $form = $this->createForm(ProfilMedicalType::class, $newProfil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Create default Dossier Clinique
            $dossier = new DossierClinique();
            $dossier->setProfilMedical($newProfil);
            $dossier->setAllergies([]);
            $dossier->setAntecedents("Dossier créé automatiquement.");

            $entityManager->persist($newProfil);
            $entityManager->persist($dossier);
            $entityManager->flush();

            $this->addFlash('success', 'Nouveau profil ajouté avec succès !');

            // Redirect to self to show the new profile
            return $this->redirectToRoute('app_titulaire_dashboard_profil', ['id' => $newProfil->getId()]);
        }

        // --- 2. FETCH DASHBOARD DATA (Your existing logic) ---
        $query = $entityManager->createQuery(
            'SELECT p, d, r 
             FROM App\Entity\ProfilMedical p 
             LEFT JOIN p.dossierClinique d 
             LEFT JOIN d.rapportsMedicaux r
             WHERE p.titulaire = :user'
        )->setParameter('user', $user);

        $allProfils = $query->getArrayResult();

        // Handle Empty State (If no profiles exist yet, just render the page, the modal form is there)
        // You might want to remove the redirect here so they can see the "Add" button/modal immediately
        if (empty($allProfils) && !$form->isSubmitted()) {
            // Optional: You could pass a flag to open the modal automatically if list is empty
        }

        // Sanitize Data (Your existing logic)
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

        // Select Active Profile
        $activeProfil = !empty($allProfils) ? $allProfils[0] : null;
        if ($id && !empty($allProfils)) {
            foreach ($allProfils as $profil) {
                if ($profil['id'] === $id) {
                    $activeProfil = $profil;
                    break;
                }
            }
        }

        return $this->render('titulaire/dashboard.html.twig', [
            'activeProfil' => $activeProfil,
            'allProfils' => $allProfils,
            'form' => $form->createView(), // Pass the form to the view
        ]);
    }
}
