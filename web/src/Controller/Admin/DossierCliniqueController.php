<?php

namespace App\Controller\Admin;


use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Form\DossierCliniqueAdminType;
use App\Repository\ProfilMedicalRepository;
use App\Repository\DossierCliniqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/dossier-clinique')]
class DossierCliniqueController extends AbstractController
{
    #[Route('/', name: 'admin_dossier_clinique_index')]
    public function index(ProfilMedicalRepository $profilRepo): Response
    {
        $profils = $profilRepo->findAll();
        $forms = [];

        foreach ($profils as $profil) {
            $dossier = $profil->getDossierClinique() ?? new DossierClinique();
            if (!$dossier->getId()) {
                $dossier->setProfilMedical($profil);
            }

            $forms[$profil->getId()] = $this->createForm(
                DossierCliniqueAdminType::class,
                $dossier,
                [
                    'with_profil' => false, // ❌ ne pas afficher le profil en édition
                    'action' => $this->generateUrl('admin_dossier_clinique_edit', ['id' => $profil->getId()]),
                    'method' => 'POST',
                ]
            )->createView();
        }

        return $this->render('admin/dossier_clinique/index.html.twig', [
            'profils' => $profils,
            'forms' => $forms,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_dossier_clinique_edit', methods: ['POST','GET'])]
    public function edit(
        ProfilMedical $profil,
        DossierCliniqueRepository $dossierRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $dossier = $dossierRepo->findOneBy(['profilMedical' => $profil]) ?? new DossierClinique();
        if (!$dossier->getId()) {
            $dossier->setProfilMedical($profil);
        }

        $form = $this->createForm(DossierCliniqueAdminType::class, $dossier, [
            'with_profil' => false // ❌ champ profil masqué
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($dossier);
            $em->flush();

            $this->addFlash('success', 'Dossier clinique mis à jour');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        return $this->render('admin/dossier_clinique/edit.html.twig', [
            'profil' => $profil,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'admin_dossier_clinique_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $dossier = new DossierClinique();

        $form = $this->createForm(DossierCliniqueAdminType::class, $dossier, [
            'with_profil' => true // ✅ profil sélectionnable
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $dossier->getProfilMedical();

            $existing = $em->getRepository(DossierClinique::class)
                ->findOneBy(['profilMedical' => $profil]);

            if ($existing) {
                $this->addFlash('danger', 'Ce profil médical a déjà un dossier clinique.');
                return $this->redirectToRoute('admin_dossier_clinique_new');
            }

            $em->persist($dossier);
            $em->flush();

            $this->addFlash('success', 'Dossier clinique ajouté avec succès');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        return $this->render('admin/dossier_clinique/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/show', name: 'admin_dossier_clinique_show')]
    public function show(int $id, ProfilMedicalRepository $profilRepo): Response
    {
        $profilMedical = $profilRepo->find($id);

        if (!$profilMedical) {
            throw $this->createNotFoundException('Profil médical introuvable');
        }

        return $this->render('admin/dossier_clinique/show.html.twig', [
            'profilMedical' => $profilMedical,
        ]);
    }

    #[Route('/{id}/reports', name: 'admin_dossier_clinique_reports')]
    public function reports(int $id): Response
    {
        return $this->render('admin/dossier_clinique/reports.html.twig', [
            'id' => $id,
        ]);
    }
}
