<?php

namespace App\Controller\Admin;

use App\Entity\DossierClinique;
use App\Entity\RapportMedical;
use App\Form\RapportMedicalType;
use App\Repository\DossierCliniqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/rapports')]
class RapportMedicalController extends AbstractController
{
    #[Route('/{id}', name: 'admin_dossier_clinique_reports')]
    public function liste(DossierClinique $dossier): Response
    {
        $rapports = $dossier->getRapportsMedicaux();

        return $this->render('admin/rapport_medical/liste.html.twig', [
            'dossier' => $dossier,
            'rapports' => $rapports,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'admin_rapport_ajouter')]
    public function ajouter(
        DossierClinique $dossier,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $rapport = new RapportMedical();
        $rapport->setDossierClinique($dossier);
        $rapport->setDateCreation(new \DateTime());

        $form = $this->createForm(RapportMedicalType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($rapport);
            $em->flush();

            $this->addFlash('success', 'Rapport ajouté avec succès.');
            return $this->redirectToRoute('admin_dossier_clinique_reports', ['id' => $dossier->getId()]);
        }

        return $this->render('admin/rapport_medical/form.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'action' => 'Ajouter'
        ]);
    }

    #[Route('/modifier/{id}', name: 'admin_rapport_modifier')]
    public function modifier(
        RapportMedical $rapport,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(RapportMedicalType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Rapport modifié avec succès.');

            return $this->redirectToRoute('admin_dossier_clinique_reports', [
                'id' => $rapport->getDossierClinique()->getId()
            ]);
        }

        return $this->render('admin/rapport_medical/form.html.twig', [
            'form' => $form->createView(),
            'dossier' => $rapport->getDossierClinique(),
            'action' => 'Modifier'
        ]);
    }

    #[Route('/supprimer/{id}', name: 'admin_rapport_supprimer')]
    public function supprimer(RapportMedical $rapport, EntityManagerInterface $em): Response
    {
        $dossierId = $rapport->getDossierClinique()->getId();
        $em->remove($rapport);
        $em->flush();

        $this->addFlash('success', 'Rapport supprimé.');
        return $this->redirectToRoute('admin_dossier_clinique_reports', ['id' => $dossierId]);
    }
}
