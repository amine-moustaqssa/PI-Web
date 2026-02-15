<?php

namespace App\Controller\Front\Receptionniste;

use App\Entity\Utilisateur;
use App\Form\ReceptionnisteTitulaireType;
use App\Repository\DisponibiliteRepository;
use App\Repository\MedecinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/receptionniste')]
#[IsGranted('ROLE_PERSONNEL')]
class DashboardController extends AbstractController
{
    private function checkReceptionist(): void
    {
        if ($this->getUser()->getNiveauAcces() !== 'RECEPTIONIST') {
            throw $this->createAccessDeniedException('Accès réservé aux réceptionnistes.');
        }
    }

    private function findTitulaires(EntityManagerInterface $em, ?string $query = null): array
    {
        $conn = $em->getConnection();
        $sql = "SELECT id FROM Utilisateur WHERE roles LIKE :role";
        $params = ['role' => '%ROLE_TITULAIRE%'];

        if ($query) {
            $sql .= ' AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q)';
            $params['q'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY nom ASC';

        $ids = $conn->executeQuery($sql, $params)->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $em->createQueryBuilder()
            ->select('u')
            ->from(Utilisateur::class, 'u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
    #[Route('/dashboard', name: 'receptionniste_dashboard')]
    public function index(
        DisponibiliteRepository $disponibiliteRepo,
        MedecinRepository $medecinRepo
    ): Response {
        $this->checkReceptionist();

        // Fetch all doctors
        $medecins = $medecinRepo->findAll();
        $totalMedecins = count($medecins);

        // Fetch all disponibilites with eager-loaded medecin
        $disponibilites = $disponibiliteRepo->findBy([], ['jourSemaine' => 'ASC', 'heureDebut' => 'ASC']);
        $totalDisponibilites = count($disponibilites);

        // Count unique doctors who have at least one schedule
        $doctorsWithSchedule = [];
        foreach ($disponibilites as $dispo) {
            if ($dispo->getMedecin()) {
                $doctorsWithSchedule[$dispo->getMedecin()->getId()] = true;
            }
        }
        $medecinsAvecPlanning = count($doctorsWithSchedule);

        // Day name mapping for the template
        $jourNoms = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return $this->render('front/receptionniste/dashboard/index.html.twig', [
            'disponibilites' => $disponibilites,
            'totalMedecins' => $totalMedecins,
            'totalDisponibilites' => $totalDisponibilites,
            'medecinsAvecPlanning' => $medecinsAvecPlanning,
            'jourNoms' => $jourNoms,
        ]);
    }

    #[Route('/comptes', name: 'receptionniste_comptes', methods: ['GET'])]
    public function comptes(EntityManagerInterface $em, Request $request): Response
    {
        $this->checkReceptionist();

        $query = $request->query->get('q');

        return $this->render('front/receptionniste/comptes/index.html.twig', [
            'titulaires' => $this->findTitulaires($em, $query),
            'search_query' => $query,
        ]);
    }

    #[Route('/comptes/nouveau', name: 'receptionniste_comptes_new', methods: ['GET', 'POST'])]
    public function comptesNew(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->checkReceptionist();

        $titulaire = new Utilisateur();
        $form = $this->createForm(ReceptionnisteTitulaireType::class, $titulaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate a random 12-character password
            $randomPassword = bin2hex(random_bytes(6)); // 12 hex chars
            $titulaire->setPassword($passwordHasher->hashPassword($titulaire, $randomPassword));
            $titulaire->setRoles(['ROLE_TITULAIRE']);
            $titulaire->setIsVerified(true);

            $em->persist($titulaire);
            $em->flush();

            $this->addFlash('success', sprintf(
                'Le compte de %s %s a été créé. Mot de passe généré : %s',
                $titulaire->getPrenom(),
                $titulaire->getNom(),
                $randomPassword
            ));

            return $this->redirectToRoute('receptionniste_comptes');
        }

        return $this->render('front/receptionniste/comptes/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
