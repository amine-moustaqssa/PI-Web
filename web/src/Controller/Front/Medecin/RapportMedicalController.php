<?php

namespace App\Controller\Front\Medecin;

use App\Entity\DossierClinique;
use App\Entity\RapportMedical;
use App\Form\RapportMedicalType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/medecin/rapports')]
class RapportMedicalController extends AbstractController
{
    // ===============================
    // Historique des rapports médicaux
    // ===============================
    #[Route('/dossier/{id}', name: 'medecin_dossier_rapports')]
    public function index(DossierClinique $dossier): Response
    {
        return $this->render('front/medecin/rapport_medical/index.html.twig', [
            'dossier'  => $dossier,
            'profil'   => $dossier->getProfilMedical(),
            'rapports' => $dossier->getRapportsMedicaux(),
        ]);
    }
// Ajouter un rapport médical
#[Route('/ajouter/{id}', name: 'medecin_rapport_medical_new')]
public function ajouter(DossierClinique $dossier, Request $request, EntityManagerInterface $em): Response
{
    $rapport = new RapportMedical();
    $rapport->setDossierClinique($dossier);
    $rapport->setDateCreation(new \DateTime());

    $form = $this->createForm(RapportMedicalType::class, $rapport);
    $form->handleRequest($request);

    if($form->isSubmitted() && $form->isValid()){
        $em->persist($rapport);
        $em->flush();

        $this->addFlash('success', 'Rapport médical ajouté.');
        return $this->redirectToRoute('medecin_dossier_rapports', ['id' => $dossier->getId()]);
    }

    return $this->render('front/medecin/rapport_medical/form.html.twig', [
        'form' => $form->createView(),
        'dossier' => $dossier,
        'action' => 'Ajouter'
    ]);
}

// Modifier un rapport médical
#[Route('/modifier/{id}', name: 'medecin_rapport_medical_edit')]
public function modifier(RapportMedical $rapport, Request $request, EntityManagerInterface $em): Response
{
    $form = $this->createForm(RapportMedicalType::class, $rapport);
    $form->handleRequest($request);

    if($form->isSubmitted() && $form->isValid()){
        $em->flush();
        $this->addFlash('success', 'Rapport médical modifié.');
        return $this->redirectToRoute('medecin_dossier_rapports', ['id' => $rapport->getDossierClinique()->getId()]);
    }

    return $this->render('front/medecin/rapport_medical/form.html.twig', [
        'form' => $form->createView(),
        'dossier' => $rapport->getDossierClinique(),
        'action' => 'Modifier'
    ]);
}

// Supprimer un rapport médical
#[Route('/supprimer/{id}', name: 'medecin_rapport_medical_delete')]
public function supprimer(RapportMedical $rapport, EntityManagerInterface $em): Response
{
    $dossierId = $rapport->getDossierClinique()->getId();
    $em->remove($rapport);
    $em->flush();

    $this->addFlash('success', 'Rapport médical supprimé.');
    return $this->redirectToRoute('medecin_dossier_rapports', ['id' => $dossierId]);
}

}
