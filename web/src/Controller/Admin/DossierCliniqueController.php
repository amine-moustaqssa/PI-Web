<?php

namespace App\Controller\Admin;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Form\DossierCliniqueAdminType;
use App\Repository\ProfilMedicalRepository;
use App\Repository\DossierCliniqueRepository;
use App\Service\MedicalScoreCalculator;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

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
            $dossier = $this->dossierRepo->findOneBy(['profilMedical' => $profil]) ?? new DossierClinique();
            if (!$dossier->getId()) {
                $dossier->setProfilMedical($profil);
            }

            $forms[$profil->getId()] = $this->createForm(
                DossierCliniqueAdminType::class,
                $dossier,
                [
                    'with_profil' => false,
                    'action' => $this->generateUrl('admin_dossier_clinique_edit', ['id' => $profil->getId()]),
                    'method' => 'POST',
                ]
            )->createView();

            if ($dossier->getId()) {
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
    #[Route('/{id}/edit', name: 'admin_dossier_clinique_edit', methods: ['GET','POST'])]
    public function edit(
        ProfilMedical $profil,
        DossierCliniqueRepository $dossierRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $dossier = $dossierRepo->findOneBy(['profilMedical' => $profil]) ?? new DossierClinique();
        if (!$dossier->getId()) {
            $dossier->setProfilMedical($profil);
        }

        $form = $this->createForm(DossierCliniqueAdminType::class, $dossier, [
            'with_profil' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($dossier);
                $em->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'status' => 'success',
                        'message' => 'Dossier clinique mis à jour avec succès',
                    ]);
                }

                $this->addFlash('success', 'Dossier clinique mis à jour');
                return $this->redirectToRoute('admin_dossier_clinique_index');
            } else {
                if ($request->isXmlHttpRequest()) {
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $errors[] = $error->getMessage();
                    }
                    return $this->json([
                        'status' => 'error',
                        'message' => implode("\n", $errors)
                    ]);
                }
            }
        }

        return $this->render('admin/dossier_clinique/edit_modal.html.twig', [
            'profil' => $profil,
            'dossier' => $dossier,
            'form' => $form->createView(),
        ]);
    }

    // -------------------------
    // Nouveau dossier clinique AVEC ENVOI SMS - CLINIQUE 360
    // -------------------------
    #[Route('/new', name: 'admin_dossier_clinique_new')]
    public function new(
        Request $request, 
        EntityManagerInterface $em,
        SmsService $smsService
    ): Response
    {
        $dossier = new DossierClinique();

        $form = $this->createForm(DossierCliniqueAdminType::class, $dossier, [
            'with_profil' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $dossier->getProfilMedical();

            // Vérifier si un dossier existe déjà
            $existing = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);
            if ($existing) {
                $this->addFlash('danger', 'Ce profil médical a déjà un dossier clinique.');
                return $this->redirectToRoute('admin_dossier_clinique_new');
            }

            // Sauvegarde du dossier
            $em->persist($dossier);
            $em->flush();

            // ---------- ENVOI SMS AU CONTACT D'URGENCE ----------
            $contactUrgence = $profil->getContactUrgence();
            
            if ($contactUrgence) {
                // Date et heure actuelles
                $dateCreation = (new \DateTime())->format('d/m/Y à H:i');
                
                // Formatage automatique du numéro
                $numeroFormate = $this->formatPhoneNumber($contactUrgence);
                
                // Message personnalisé avec CLINIQUE 360 et date
                $message = sprintf(
                    "📋 CLINIQUE 360 - NOUVEAU DOSSIER CLINIQUE\n\n".
                    "Patient : %s %s\n".
                    "Date de création : %s\n\n".
                    "Un dossier clinique vient d'être créé pour ce patient.\n".
                    "Merci de votre vigilance.\n\n".
                    "— CLINIQUE 360",
                    $profil->getPrenom(),
                    $profil->getNom(),
                    $dateCreation
                );

                // Envoi du SMS
                $resultat = $smsService->sendSms($numeroFormate, $message);
                
                if ($resultat['success']) {
                    $this->addFlash('success', sprintf(
                        '✅ Dossier créé et SMS envoyé au %s',
                        $numeroFormate
                    ));
                    
                    $this->addFlash('info', sprintf(
                        'SMS envoyé à %s %s le %s - SID: %s',
                        $profil->getPrenom(),
                        $profil->getNom(),
                        $dateCreation,
                        $resultat['sid']
                    ));
                    
                } else {
                    $this->addFlash('warning', sprintf(
                        '⚠️ Dossier créé mais échec SMS pour %s %s : %s',
                        $profil->getPrenom(),
                        $profil->getNom(),
                        $resultat['error']
                    ));
                }
            } else {
                $this->addFlash('warning', sprintf(
                    '⚠️ Aucun numéro de contact d\'urgence pour %s %s',
                    $profil->getPrenom(),
                    $profil->getNom()
                ));
            }
            // ---------- FIN ENVOI SMS ----------

            $this->addFlash('success', 'Dossier clinique ajouté avec succès');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        return $this->render('admin/dossier_clinique/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formate le numéro de téléphone au format international E.164
     */
    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (substr($phone, 0, 2) === '00') {
            $phone = '+' . substr($phone, 2);
        }
        elseif (substr($phone, 0, 1) === '0') {
            $phone = '+216' . substr($phone, 1);
        }
        elseif (strlen($phone) === 8 && is_numeric($phone)) {
            $phone = '+216' . $phone;
        }
        elseif (strlen($phone) === 9 && is_numeric($phone)) {
            $phone = '+212' . $phone;
        }
        elseif (strlen($phone) >= 11 && substr($phone, 0, 3) === '216' && substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }
        elseif (strlen($phone) >= 11 && substr($phone, 0, 3) === '212' && substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }
        elseif (strlen($phone) >= 11 && substr($phone, 0, 2) === '33' && substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }
        elseif (substr($phone, 0, 1) !== '+') {
            $phone = '+216' . $phone;
        }
        
        return $phone;
    }

    // -------------------------
    // ROUTE DE TEST SMS POUR UN PROFIL SPÉCIFIQUE
    // -------------------------
    #[Route('/test-sms-profil/{id}', name: 'admin_test_envoi_sms_profil')]
    public function testSmsProfil(ProfilMedical $profil, SmsService $smsService): Response
    {
        $contact = $profil->getContactUrgence();
        
        if (!$contact) {
            $this->addFlash('danger', 'Ce profil n\'a pas de numéro de contact d\'urgence.');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }
        
        $numeroFormate = $this->formatPhoneNumber($contact);
        $message = "🔔 CLINIQUE 360 - TEST: Envoi manuel pour {$profil->getPrenom()} {$profil->getNom()} - " . date('H:i:s');
        
        $resultat = $smsService->sendSms($numeroFormate, $message);
        
        if ($resultat['success']) {
            $this->addFlash('success', "✅ SMS test envoyé à {$profil->getPrenom()} ($numeroFormate) - SID: {$resultat['sid']}");
        } else {
            $this->addFlash('danger', "❌ Échec pour {$profil->getPrenom()} : " . $resultat['error']);
        }
        
        return $this->redirectToRoute('admin_dossier_clinique_index');
    }

    // -------------------------
    // ROUTE DE TEST MULTI-PROFILS
    // -------------------------
    #[Route('/test-multi-sms', name: 'admin_test_multi_sms')]
    public function testMultiSms(ProfilMedicalRepository $profilRepo, SmsService $smsService): Response
    {
        $profils = $profilRepo->createQueryBuilder('p')
            ->where('p.contact_urgence IS NOT NULL')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        $compteur = 0;
        $total = count($profils);
        
        foreach ($profils as $profil) {
            $contact = $profil->getContactUrgence();
            if ($contact) {
                $numeroFormate = $this->formatPhoneNumber($contact);
                $message = "🔔 CLINIQUE 360 - Test multiple - " . $profil->getPrenom() . " " . $profil->getNom();
                
                $resultat = $smsService->sendSms($numeroFormate, $message);
                
                if ($resultat['success']) {
                    $compteur++;
                    $this->addFlash('success', "✅ SMS envoyé à {$profil->getPrenom()} ($numeroFormate)");
                } else {
                    $this->addFlash('warning', "⚠️ Échec pour {$profil->getPrenom()} : {$resultat['error']}");
                }
            }
        }
        
        $this->addFlash('info', "📊 $compteur SMS envoyés sur $total profils testés");
        
        return $this->redirectToRoute('admin_dossier_clinique_index');
    }

    // -------------------------
    // Supprimer un dossier
    // -------------------------
    #[Route('/{id}/delete', name: 'admin_dossier_clinique_delete', methods: ['POST'])]
    public function delete(
        ProfilMedical $profil,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $dossier = $this->dossierRepo->findOneBy(['profilMedical' => $profil]);

        if (!$dossier) {
            $this->addFlash('danger', 'Dossier clinique introuvable.');
            return $this->redirectToRoute('admin_dossier_clinique_index');
        }

        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $profil->getId(), $submittedToken)) {
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

    // -------------------------
    // Score médical
    // -------------------------
    #[Route('/{id}/score', name: 'admin_dossier_clinique_score')]
    public function score(int $id): Response
    {
        $dossier = $this->dossierRepo->find($id);

        if (!$dossier) {
            throw $this->createNotFoundException('Dossier clinique introuvable.');
        }

        $profil = $dossier->getProfilMedical();

        $scoreData = $this->calculator->calculate($dossier);

        $allergies = $dossier->getAllergies() ?? [];
        $antecedents = $dossier->getAntecedents() ? explode(',', $dossier->getAntecedents()) : [];
        $age = $profil->getDateNaissance() 
            ? (new \DateTime())->diff($profil->getDateNaissance())->y 
            : null;

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
    public function reports(int $id): Response
    {
        return $this->render('admin/dossier_clinique/reports.html.twig', [
            'id' => $id,
        ]);
    }

    // -------------------------
    // Data pour DataTables
    // -------------------------
    #[Route('/data', name: 'admin_dossier_clinique_data')]
    public function data(Request $request, ProfilMedicalRepository $profilRepo): JsonResponse
    {
        $params = $request->query->all();

        $draw = (int) ($params['draw'] ?? 1);
        $start = (int) ($params['start'] ?? 0);
        $length = (int) ($params['length'] ?? 10);
        $searchValue = $params['search']['value'] ?? '';

        $columns = ['id', 'nom', 'prenom', 'date_naissance'];

        $qb = $profilRepo->createQueryBuilder('p');

        if ($searchValue) {
            $qb->andWhere('p.nom LIKE :search OR p.prenom LIKE :search')
               ->setParameter('search', '%'.$searchValue.'%');
        }

        foreach ($columns as $index => $column) {
            $colSearch = $params['columns'][$index]['search']['value'] ?? null;
            if ($colSearch) {
                if ($column === 'date_naissance') {
                    $date = \DateTime::createFromFormat('d/m/Y', $colSearch);
                    if ($date) {
                        $startOfDay = (clone $date)->setTime(0, 0, 0);
                        $endOfDay = (clone $date)->setTime(23, 59, 59);
                        $qb->andWhere('p.date_naissance BETWEEN :start' . $index . ' AND :end' . $index)
                           ->setParameter('start' . $index, $startOfDay)
                           ->setParameter('end' . $index, $endOfDay);
                    }
                } else {
                    $qb->andWhere("p.$column LIKE :colSearch$index")
                       ->setParameter("colSearch$index", "%$colSearch%");
                }
            }
        }

        $orderColumnIndex = $params['order'][0]['column'] ?? 0;
        $orderDir = $params['order'][0]['dir'] ?? 'asc';
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $qb->orderBy('p.' . $orderColumn, $orderDir)
           ->setFirstResult($start)
           ->setMaxResults($length);

        $profils = $qb->getQuery()->getResult();

        $data = [];
        foreach ($profils as $profil) {
            $dossier = $profil->getDossierClinique();
            $score = $dossier ? ($this->calculator->calculate($dossier)['level'] ?? 'N/A') : 'N/A';

            $data[] = [
                'id' => $profil->getId(),
                'nom' => $profil->getNom(),
                'prenom' => $profil->getPrenom(),
                'dateNaissance' => $profil->getDateNaissance() ? $profil->getDateNaissance()->format('d/m/Y') : 'N/A',
                'score' => $score,
                'actions' => $this->renderView('admin/dossier_clinique/_actions.html.twig', [
                    'profil' => $profil,
                    'dossier' => $dossier
                ])
            ];
        }

        $recordsTotal = count($profilRepo->findAll());

        $qbFiltered = clone $qb;
        $qbFiltered->setFirstResult(null)->setMaxResults(null);
        $recordsFiltered = count($qbFiltered->getQuery()->getResult());

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }
}