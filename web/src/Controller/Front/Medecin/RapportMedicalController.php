<?php

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
    /**
     * Récupère le médecin connecté avec le typage correct
     * 
     * @return Medecin
     * @throws \Exception
     */
    private function getCurrentMedecin(): Medecin
    {
        $user = $this->getUser();
        
        if (!$user) {
            throw new \Exception('Aucun utilisateur connecté');
        }
        
        if (!$user instanceof Medecin) {
            throw new \Exception('L\'utilisateur connecté n\'est pas un médecin');
        }
        
        return $user;
    }

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

    // ===============================
    // Ajouter un rapport médical
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

        return $this->render('front/medecin/rapport_medical/form.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'action' => 'Ajouter'
        ]);
    }

    // ===============================
    // Modifier un rapport médical
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

        return $this->render('front/medecin/rapport_medical/form.html.twig', [
            'form' => $form->createView(),
            'dossier' => $rapport->getDossierClinique(),
            'action' => 'Modifier'
        ]);
    }

    // ===============================
    // Supprimer un rapport médical
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
    // Visualiser PDF via API PDF.co
    // ===============================
    #[Route('/pdf-api/{id}', name: 'medecin_rapport_medical_pdf_api')]
    public function visualiserPdf(RapportMedical $rapport, PdfGeneratorService $pdfGenerator): Response
    {
        try {
            // Récupérer le médecin connecté avec le typage correct
            $medecin = $this->getCurrentMedecin();
            
            $dossier = $rapport->getDossierClinique();
            $profil = $dossier->getProfilMedical();
            $titulaire = $profil->getTitulaire();

            // Récupérer la spécialité et le département du médecin
            $specialite = $medecin->getSpecialite();
            $departement = null;
            
            if ($specialite) {
                $departement = $specialite->getDepartement();
            }

            // Générer le HTML du rapport
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
            
            // Nom du fichier
            $nomFichier = sprintf(
                'rapport_medical_%s_%s_%s.pdf',
                strtolower($profil->getNom() ?? 'inconnu'),
                strtolower($profil->getPrenom() ?? 'inconnu'),
                $rapport->getDateCreation()->format('Y-m-d')
            );
            
            // Générer le PDF via l'API PDF.co
            $pdfContent = $pdfGenerator->generatePdfFromHtml($html, $nomFichier);
            
            if ($pdfContent === null) {
                // Fallback: méthode Dompdf si l'API échoue
                $this->addFlash('warning', 'L\'API PDF est temporairement indisponible. Utilisation du générateur local.');
                return $this->visualiserPdfFallback($rapport);
            }
            
            // Retourner le PDF en visualisation
            return new Response(
                $pdfContent,
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $nomFichier . '"',
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                    'Pragma' => 'public'
                ]
            );
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            return $this->redirectToRoute('medecin_dossier_rapports', [
                'id' => $rapport->getDossierClinique()->getId()
            ]);
        }
    }

    // ===============================
    // Télécharger PDF via API PDF.co
    // ===============================
    #[Route('/pdf-api-download/{id}', name: 'medecin_rapport_medical_pdf_download')]
    public function telechargerPdf(RapportMedical $rapport, PdfGeneratorService $pdfGenerator): Response
    {
        try {
            $medecin = $this->getCurrentMedecin();
            
            $dossier = $rapport->getDossierClinique();
            $profil = $dossier->getProfilMedical();
            $titulaire = $profil->getTitulaire();
            
            // Récupérer la spécialité et le département
            $specialite = $medecin->getSpecialite();
            $departement = null;
            
            if ($specialite) {
                $departement = $specialite->getDepartement();
            }
            
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
            
            // Retourner le PDF en téléchargement
            return new Response(
                $pdfContent,
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $nomFichier . '"',
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                    'Pragma' => 'public'
                ]
            );
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // ===============================
    // Fallback: Visualiser PDF avec Dompdf
    // ===============================
    private function visualiserPdfFallback(RapportMedical $rapport): Response
    {
        try {
            $medecin = $this->getCurrentMedecin();
            
            $dossier = $rapport->getDossierClinique();
            $profil = $dossier->getProfilMedical();
            $titulaire = $profil->getTitulaire();
            
            // Récupérer la spécialité et le département
            $specialite = $medecin->getSpecialite();
            $departement = null;
            
            if ($specialite) {
                $departement = $specialite->getDepartement();
            }
            
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
            $options->set('isHtml5ParserEnabled', true);
            
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
            
            return new Response(
                $dompdf->output(),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $nomFichier . '"'
                ]
            );
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur fallback: ' . $e->getMessage());
            return $this->redirectToRoute('medecin_dossier_rapports', [
                'id' => $rapport->getDossierClinique()->getId()
            ]);
        }
    }

    // ===============================
    // Fallback: Télécharger PDF avec Dompdf
    // ===============================
    private function telechargerPdfFallback(RapportMedical $rapport): Response
    {
        try {
            $medecin = $this->getCurrentMedecin();
            
            $dossier = $rapport->getDossierClinique();
            $profil = $dossier->getProfilMedical();
            $titulaire = $profil->getTitulaire();
            
            // Récupérer la spécialité et le département
            $specialite = $medecin->getSpecialite();
            $departement = null;
            
            if ($specialite) {
                $departement = $specialite->getDepartement();
            }
            
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
            $options->set('isHtml5ParserEnabled', true);
            
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
            
            return new Response(
                $dompdf->output(),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $nomFichier . '"'
                ]
            );
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // ===============================
    // Test de connexion à l'API PDF.co
    // ===============================
    #[Route('/test-api-connexion', name: 'medecin_rapport_medical_test_api')]
    public function testApiConnexion(PdfGeneratorService $pdfGenerator): JsonResponse
    {
        try {
            $isConnected = $pdfGenerator->testConnection();
            
            if ($isConnected) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Connexion à l\'API PDF.co établie avec succès'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Impossible de se connecter à l\'API PDF.co'
                ], 500);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}