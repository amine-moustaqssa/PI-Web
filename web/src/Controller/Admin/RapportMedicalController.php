<?php
// src/Controller/Admin/RapportMedicalController.php

namespace App\Controller\Admin;

use App\Entity\DossierClinique;
use App\Entity\RapportMedical;
use App\Form\RapportMedicalType;
use App\Repository\DossierCliniqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/rapports')]
class RapportMedicalController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    // Injection de l'EntityManager dans le constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/{id}', name: 'admin_dossier_clinique_reports')]
    public function liste(
        DossierClinique $dossier,
        Request $request,
        PaginatorInterface $paginator
    ): Response
    {
        // Utilisation de $this->entityManager au lieu de $this->getDoctrine()
        $query = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(RapportMedical::class, 'r')
            ->where('r.dossierClinique = :dossier')
            ->setParameter('dossier', $dossier)
            ->orderBy('r.date_creation', 'DESC')
            ->getQuery();

        // Paginer les résultats (5 par page)
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('admin/rapport_medical/liste.html.twig', [
            'dossier' => $dossier,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'admin_rapport_ajouter')]
    public function ajouter(
        DossierClinique $dossier,
        Request $request
    ): Response {
        $rapport = new RapportMedical();
        $rapport->setDossierClinique($dossier);
        $rapport->setDateCreation(new \DateTime());

        $form = $this->createForm(RapportMedicalType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($rapport);
            $this->entityManager->flush();

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
        Request $request
    ): Response {
        $form = $this->createForm(RapportMedicalType::class, $rapport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
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
    public function supprimer(RapportMedical $rapport): Response
    {
        $dossierId = $rapport->getDossierClinique()->getId();
        $this->entityManager->remove($rapport);
        $this->entityManager->flush();

        $this->addFlash('success', 'Rapport supprimé.');
        return $this->redirectToRoute('admin_dossier_clinique_reports', ['id' => $dossierId]);
    }
}