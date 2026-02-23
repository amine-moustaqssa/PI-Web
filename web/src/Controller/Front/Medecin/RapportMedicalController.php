<?php
// src/Controller/Front/Medecin/RapportMedicalController.php

namespace App\Controller\Front\Medecin;

use App\Entity\DossierClinique;
use App\Entity\RapportMedical;
use App\Entity\Medecin;
use App\Form\RapportMedicalType;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin/rapports')]
#[IsGranted('ROLE_MEDECIN')]
class RapportMedicalController extends AbstractController
{
    private function getCurrentMedecin(): Medecin
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof Medecin) {
            throw new \Exception('Médecin non connecté');
        }
        return $user;
    }

    #[Route('/dossier/{id}', name: 'medecin_dossier_rapports')]
    public function index(DossierClinique $dossier): Response
    {
        return $this->render('front/medecin/rapport_medical/index.html.twig', [
            'dossier'  => $dossier,
            'profil'   => $dossier->getProfilMedical(),
            'rapports' => $dossier->getRapportsMedicaux(),
        ]);
    }

    // ===============================
    // AJOUTER un rapport avec IA
    // ===============================
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

        // === DONNÉES POUR L'ASSISTANT IA ===
        $profil = $dossier->getProfilMedical();
        $dateNaissance = $profil->getDateNaissance();
        $patientAge = $dateNaissance ? date_diff(new \DateTime(), $dateNaissance)->y : null;
        $patientAntecedents = $dossier->getAntecedents() ?? '';

        return $this->render('front/medecin/rapport_medical/form.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'action' => 'Ajouter',
            'patientAge' => $patientAge,
            'patientAntecedents' => $patientAntecedents
        ]);
    }

    // ===============================
    // MODIFIER un rapport avec IA
    // ===============================
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

        // === DONNÉES POUR L'ASSISTANT IA ===
        $dossier = $rapport->getDossierClinique();
        $profil = $dossier->getProfilMedical();
        $dateNaissance = $profil->getDateNaissance();
        $patientAge = $dateNaissance ? date_diff(new \DateTime(), $dateNaissance)->y : null;
        $patientAntecedents = $dossier->getAntecedents() ?? '';

        return $this->render('front/medecin/rapport_medical/form.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'action' => 'Modifier',
            'patientAge' => $patientAge,
            'patientAntecedents' => $patientAntecedents
        ]);
    }

    // ===============================
    // Supprimer un rapport
    // ===============================
    #[Route('/supprimer/{id}', name: 'medecin_rapport_medical_delete')]
    public function supprimer(RapportMedical $rapport, EntityManagerInterface $em): Response
    {
        $dossierId = $rapport->getDossierClinique()->getId();
        $em->remove($rapport);
        $em->flush();
        $this->addFlash('success', 'Rapport médical supprimé.');
        return $this->redirectToRoute('medecin_dossier_rapports', ['id' => $dossierId]);
    }

    // ===============================
    // PDF - Visualiser
    // ===============================
    #[Route('/pdf-api/{id}', name: 'medecin_rapport_medical_pdf_api')]
    public function visualiserPdf(RapportMedical $rapport, PdfGeneratorService $pdfGenerator): Response
    {
        try {
            $medecin = $this->getCurrentMedecin();
            $dossier = $rapport->getDossierClinique();
            $profil = $dossier->getProfilMedical();
            $titulaire = $profil->getTitulaire();

            $specialite = $medecin->getSpecialite();
            $departement = $specialite ? $specialite->getDepartement() : null;

            $html = $this->renderView('front/medecin/rapport_medical/pdf_template.html.twig', [
                'rapport' => $rapport,
                'profil' => $profil,
                'dossier' => $dossier,
                'medecin' => $medecin,
                'specialite' => $specialite,
                'departement' => $departement,
                'titulaire' => $titulaire,
                'date_generation' => new \DateTime()
            ]);
            
            $nomFichier = sprintf(
                'rapport_medical_%s_%s_%s.pdf',
                strtolower($profil->getNom() ?? 'inconnu'),
                strtolower($profil->getPrenom() ?? 'inconnu'),
                $rapport->getDateCreation()->format('Y-m-d')
            );
            
            $pdfContent = $pdfGenerator->generatePdfFromHtml($html, $nomFichier);
            
            if ($pdfContent === null) {
                $this->addFlash('warning', 'API PDF indisponible, utilisation du générateur local.');
                return $this->visualiserPdfFallback($rapport);
            }
            
            return new Response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $nomFichier . '"'
            ]);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            return $this->redirectToRoute('medecin_dossier_rapports', ['id' => $rapport->getDossierClinique()->getId()]);
        }
    }

    // ===============================
    // PDF - Télécharger
    // ===============================
    #[Route('/pdf-api-download/{id}', name: 'medecin_rapport_medical_pdf_download')]
    public function telechargerPdf(RapportMedical $rapport, PdfGeneratorService $pdfGenerator): Response
    {
        try {
            $medecin = $this->getCurrentMedecin();
            $dossier = $rapport->getDossierClinique();
            $profil = $dossier->getProfilMedical();
            $titulaire = $profil->getTitulaire();
            
            $specialite = $medecin->getSpecialite();
            $departement = $specialite ? $specialite->getDepartement() : null;
            
            $html = $this->renderView('front/medecin/rapport_medical/pdf_template.html.twig', [
                'rapport' => $rapport,
                'profil' => $profil,
                'dossier' => $dossier,
                'medecin' => $medecin,
                'specialite' => $specialite,
                'departement' => $departement,
                'titulaire' => $titulaire,
                'date_generation' => new \DateTime()
            ]);
            
            $nomFichier = sprintf(
                'rapport_medical_%s_%s_%s.pdf',
                strtolower($profil->getNom() ?? 'inconnu'),
                strtolower($profil->getPrenom() ?? 'inconnu'),
                $rapport->getDateCreation()->format('Y-m-d')
            );
            
            $pdfContent = $pdfGenerator->generatePdfFromHtml($html, $nomFichier);
            
            if ($pdfContent === null) {
                return $this->telechargerPdfFallback($rapport);
            }
            
            return new Response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $nomFichier . '"'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // Fallback methods...
    private function visualiserPdfFallback(RapportMedical $rapport): Response
    {
        try {
            $medecin = $this->getCurrentMedecin();
            $dossier = $rapport->getDossierClinique();
            $profil = $dossier->getProfilMedical();
            $titulaire = $profil->getTitulaire();
            
            $specialite = $medecin->getSpecialite();
            $departement = $specialite ? $specialite->getDepartement() : null;
            
            $html = $this->renderView('front/medecin/rapport_medical/pdf_template.html.twig', [
                'rapport' => $rapport,
                'profil' => $profil,
                'dossier' => $dossier,
                'medecin' => $medecin,
                'specialite' => $specialite,
                'departement' => $departement,
                'titulaire' => $titulaire,
                'date_generation' => new \DateTime(),
                'fallback' => true
            ]);
            
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $nomFichier = sprintf(
                'rapport_medical_%s_%s_%s.pdf',
                strtolower($profil->getNom() ?? 'inconnu'),
                strtolower($profil->getPrenom() ?? 'inconnu'),
                $rapport->getDateCreation()->format('Y-m-d')
            );
            
            return new Response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $nomFichier . '"'
            ]);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur fallback: ' . $e->getMessage());
            return $this->redirectToRoute('medecin_dossier_rapports', ['id' => $rapport->getDossierClinique()->getId()]);
        }
    }

    private function telechargerPdfFallback(RapportMedical $rapport): Response
    {
        try {
            $medecin = $this->getCurrentMedecin();
            $dossier = $rapport->getDossierClinique();
            $profil = $dossier->getProfilMedical();
            $titulaire = $profil->getTitulaire();
            
            $specialite = $medecin->getSpecialite();
            $departement = $specialite ? $specialite->getDepartement() : null;
            
            $html = $this->renderView('front/medecin/rapport_medical/pdf_template.html.twig', [
                'rapport' => $rapport,
                'profil' => $profil,
                'dossier' => $dossier,
                'medecin' => $medecin,
                'specialite' => $specialite,
                'departement' => $departement,
                'titulaire' => $titulaire,
                'date_generation' => new \DateTime(),
                'fallback' => true
            ]);
            
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $nomFichier = sprintf(
                'rapport_medical_%s_%s_%s.pdf',
                strtolower($profil->getNom() ?? 'inconnu'),
                strtolower($profil->getPrenom() ?? 'inconnu'),
                $rapport->getDateCreation()->format('Y-m-d')
            );
            
            return new Response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $nomFichier . '"'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/test-api-connexion', name: 'medecin_rapport_medical_test_api')]
    public function testApiConnexion(PdfGeneratorService $pdfGenerator): JsonResponse
    {
        try {
            $isConnected = $pdfGenerator->testConnection();
            return new JsonResponse([
                'success' => $isConnected,
                'message' => $isConnected ? 'Connexion OK' : 'Connexion échouée'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}