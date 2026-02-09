<?php

namespace App\Controller\Admin;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Form\DossierCliniqueAdminType;
use App\Repository\ProfilMedicalRepository;
use App\Repository\DossierCliniqueRepository;
use App\Service\MedicalScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/dossier-clinique')]
class DossierCliniqueController extends AbstractController
{
    private DossierCliniqueRepository $dossierRepo;
    private MedicalScoreCalculator $calculator;

    public function __construct(DossierCliniqueRepository $dossierRepo, MedicalScoreCalculator $calculator)
    {
        $this->dossierRepo = $dossierRepo;
        $this->calculator = $calculator;
    }

    // -------------------------
    // Liste des profils et scores
    // -------------------------
    #[Route('/', name: 'admin_dossier_clinique_index')]
    public function index(ProfilMedicalRepository $profilRepo): Response
    {
        $profils = $profilRepo->findAll();
        $forms = [];
        $scores = [];

        foreach ($profils as $profil) {
            $dossier = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);

            // Créer un formulaire même si le dossier n'existe pas (pour modal)
            $tempDossier = $dossier ?: new DossierClinique();
            if (!$dossier) {
                $tempDossier->setProfilMedical($profil);
            }

            $forms[$profil->getId()] = $this->createForm(
                DossierCliniqueAdminType::class,
                $tempDossier,
                [
                    'with_profil' => false,
                    'action' => $this->generateUrl('admin_dossier_clinique_edit', ['id' => $profil->getId()]),
                    'method' => 'POST',
                ]
            )->createView();

            // Calcul du score uniquement si le dossier existe
            if ($dossier) {
                $scores[$profil->getId()] = $this->calculator->calculate($dossier);
            }
        }

        return $this->render('admin/dossier_clinique/index.html.twig', [
            'profils' => $profils,
            'forms' => $forms,
            'scores' => $scores,
        ]);
    }

    // -------------------------
    // Editer un dossier clinique
    // -------------------------
    #[Route('/{id}/edit', name: 'admin_dossier_clinique_edit', methods: ['POST','GET'])]
    public function edit(ProfilMedical $profil, Request $request, EntityManagerInterface $em): Response
    {
        $dossier = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);

        if (!$dossier) {
            $this->addFlash('warning', 'Impossible de modifier : ce profil n’a pas encore de dossier clinique.');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        $form = $this->createForm(DossierCliniqueAdminType::class, $dossier, ['with_profil' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($dossier);
            $em->flush();
            $this->addFlash('success', 'Dossier clinique mis à jour.');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        return $this->render('admin/dossier_clinique/edit.html.twig', [
            'profil' => $profil,
            'form' => $form->createView(),
        ]);
    }

    // -------------------------
    // Nouveau dossier clinique
    // -------------------------
    #[Route('/new', name: 'admin_dossier_clinique_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $dossier = new DossierClinique();
        $form = $this->createForm(DossierCliniqueAdminType::class, $dossier, ['with_profil' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $dossier->getProfilMedical();
            $existing = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);

            if ($existing) {
                $this->addFlash('danger', 'Ce profil médical a déjà un dossier clinique.');
                return $this->redirectToRoute('admin_dossier_clinique_new');
            }

            $em->persist($dossier);
            $em->flush();
            $this->addFlash('success', 'Dossier clinique ajouté avec succès.');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        return $this->render('admin/dossier_clinique/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // -------------------------
    // Supprimer un dossier
    // -------------------------
    #[Route('/{id}/delete', name: 'admin_dossier_clinique_delete', methods: ['POST'])]
    public function delete(ProfilMedical $profil, Request $request, EntityManagerInterface $em): Response
    {
        $dossier = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);

        if (!$dossier) {
            $this->addFlash('warning', 'Impossible de supprimer : ce profil n’a pas encore de dossier clinique.');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$profil->getId(), $submittedToken)) {
            $em->remove($dossier);
            $em->flush();
            $this->addFlash('success', 'Dossier clinique supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_dossier_clinique_index');
    }

    // -------------------------
    // Voir un dossier
    // -------------------------
    #[Route('/{id}/show', name: 'admin_dossier_clinique_show')]
    public function show(ProfilMedical $profil): Response
    {
        $dossier = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);
        return $this->render('admin/dossier_clinique/show.html.twig', [
            'profilMedical' => $profil,
            'dossier' => $dossier,
        ]);
    }

    // -------------------------
    // Score médical
    // -------------------------
    #[Route('/{id}/score', name: 'admin_dossier_clinique_score')]
    public function score(ProfilMedical $profil): Response
    {
        $dossier = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);

        if (!$dossier) {
            $this->addFlash('warning', 'Impossible de calculer le score : ce profil n’a pas encore de dossier clinique.');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        $scoreData = $this->calculator->calculate($dossier);
        $allergies = $dossier->getAllergies() ?? [];
        $antecedents = $dossier->getAntecedents() ? explode(',', $dossier->getAntecedents()) : [];
        $age = $profil->getDateNaissance() ? (new \DateTime())->diff($profil->getDateNaissance())->y : null;

        return $this->render('admin/medical_score/score.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier,
            'scoreData' => $scoreData,
            'allergies' => $allergies,
            'antecedents' => $antecedents,
            'age' => $age,
        ]);
    }

    // -------------------------
    // Rapports
    // -------------------------
    #[Route('/{id}/reports', name: 'admin_dossier_clinique_reports')]
    public function reports(ProfilMedical $profil): Response
    {
        $dossier = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);

        if (!$dossier) {
            $this->addFlash('warning', 'Impossible d’afficher les rapports : ce profil n’a pas encore de dossier clinique.');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        return $this->render('admin/dossier_clinique/reports.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier,
        ]);
    }
}
