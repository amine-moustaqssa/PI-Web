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

            // Formulaire pour chaque profil
            $forms[$profil->getId()] = $this->createForm(
                DossierCliniqueAdminType::class,
                $dossier,
                [
                    'with_profil' => false,
                    'action' => $this->generateUrl('admin_dossier_clinique_edit', ['id' => $profil->getId()]),
                    'method' => 'POST',
                ]
            )->createView();

            // Calcul du score si le dossier existe
            if ($dossier->getId()) {
                $scores[$profil->getId()] = $this->calculator->calculate($dossier);
            }
        }

        return $this->render('admin/dossier_clinique/index.html.twig', [
            'profils' => $profils,
            'forms' => $forms,
            'scores' => $scores, // ⚡ corrigé ici
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
                // retourner les erreurs du formulaire
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

    // GET ou chargement modal
    return $this->render('admin/dossier_clinique/edit_modal.html.twig', [
        'profil' => $profil,
        'dossier' => $dossier,
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

        $form = $this->createForm(DossierCliniqueAdminType::class, $dossier, [
            'with_profil' => true
        ]);
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

            $this->addFlash('success', 'Dossier clinique ajouté avec succès');
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
            'profil' => $profil,        // ⚡ corrigé
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



    #[Route('/ajax', name: 'admin_dossier_clinique_ajax')]
public function ajax(Request $request, ProfilMedicalRepository $profilRepo): JsonResponse
{
    $draw = (int) $request->query->get('draw', 1);
    $start = (int) $request->query->get('start', 0);
    $length = (int) $request->query->get('length', 10);

    $searchValue = $request->query->get('search')['value'] ?? '';

    // Colonnes pour DataTables
    $columns = ['id', 'nom', 'prenom', 'dateNaissance'];

    $orderColumnIndex = $request->query->get('order')[0]['column'] ?? 0;
    $orderColumn = $columns[$orderColumnIndex] ?? 'id';
    $orderDir = $request->query->get('order')[0]['dir'] ?? 'asc';

    // Création QueryBuilder
    $qb = $profilRepo->createQueryBuilder('p');

    // Filtrage par recherche
    if ($searchValue) {
        $qb->andWhere('p.nom LIKE :search OR p.prenom LIKE :search');
        $qb->setParameter('search', '%'.$searchValue.'%');
    }

    // Total filtré
    $totalFiltered = count($qb->getQuery()->getResult());

    // Tri et pagination
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
                'dossier' => $dossier,
            ]),
        ];
    }

    return new JsonResponse([
        'draw' => $draw,
        'recordsTotal' => count($profilRepo->findAll()),
        'recordsFiltered' => $totalFiltered,
        'data' => $data,
    ]);
}

#[Route('/data', name: 'admin_dossier_clinique_data')]
public function data(Request $request, ProfilMedicalRepository $profilRepo): JsonResponse
{
    // Paramètres envoyés par DataTables
    $params = $request->query->all();

    $draw = (int) ($params['draw'] ?? 1);
    $start = (int) ($params['start'] ?? 0);
    $length = (int) ($params['length'] ?? 10);
    $searchValue = $params['search']['value'] ?? '';

    // Récupérer tous les profils
    $profils = $profilRepo->findAll();

    $data = [];
    foreach ($profils as $profil) {
        $dossier = $profil->getDossierClinique();
        $score = $dossier ? $this->calculator->calculate($dossier) : null;

        // Filtrage global (recherche dans nom / prénom)
        if ($searchValue) {
            if (
                stripos($profil->getNom(), $searchValue) === false &&
                stripos($profil->getPrenom(), $searchValue) === false
            ) {
                continue;
            }
        }

        $data[] = [
            'id' => $profil->getId(),
            'nom' => $profil->getNom(),
            'prenom' => $profil->getPrenom(),
            'dateNaissance' => $profil->getDateNaissance() ? $profil->getDateNaissance()->format('d/m/Y') : 'N/A',
            'score' => $score ? $score['level'] : 'N/A',
            'actions' => $this->renderView('admin/dossier_clinique/_actions.html.twig', [
                'profil' => $profil,
                'dossier' => $dossier
            ])
        ];
    }

    $recordsTotal = count($profils);
    $recordsFiltered = count($data);

    // Pagination
    $data = array_slice($data, $start, $length);

    return new JsonResponse([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
}



}


