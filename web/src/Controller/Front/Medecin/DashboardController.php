<?php

namespace App\Controller\Front\Medecin;

use App\Repository\RendezVousRepository;
use App\Repository\DossierCliniqueRepository;
use App\Repository\ConsultationRepository;
use App\Repository\DisponibiliteRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/medecin/{id}', name: 'medecin_')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(
        int $id,
        RendezVousRepository $rdvRepo,
        DossierCliniqueRepository $dossierRepo,
        ConsultationRepository $consultRepo
    ): Response {
        $user = $this->getUser();
        // Get the medical profiles collection
        $profilsMedicaux = $user->getProfilsMedicaux(); // This is a PersistentCollection

        // Pick the first profile (if you want a single doctor context)
        $profilMedical = $profilsMedicaux->first();
        // Security check
        if ($user->getId() !== $id) {
            throw $this->createAccessDeniedException();
        }

        $today = new \DateTime('today');

        // 🔹 Count rendez-vous today for this doctor

        $rdvsToday = $rdvRepo->countTodayByProfil($profilMedical);
        $consultsToday = $consultRepo->countTodayByMedecin($user);
        $dossiersCount = $dossierRepo->countByProfilMedical($profilMedical);
        $todayRdvs = $rdvRepo->findTodayByProfil($profilMedical);

        return $this->render('front/medecin/dashboard/index.html.twig', [
            'rdvsToday' => $rdvsToday,
            'dossiersCount' => $dossiersCount,
            'consultsToday' => $consultsToday,
            'todayRdvs' => $todayRdvs,
        ]);
    }

    #[Route('/planning/agenda', name: 'app_medecin_disponibilite_agenda', methods: ['GET'])]
    public function agenda(int $id, DisponibiliteRepository $dispoRepo, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();
        if ($user->getId() !== $id) {
            throw $this->createAccessDeniedException();
        }

        $queryBuilder = $dispoRepo->createQueryBuilder('d')
            ->where('d.medecin = :user')
            ->setParameter('user', $user)
            ->orderBy('d.jourSemaine', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        $agenda = [];

        foreach ($pagination as $dispo) {
            $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            $jour = $dispo->getJourSemaine();
            $dayName = isset($days[$jour]) ? $days[$jour] : 'Monday';
            $dateKey = (new \DateTime("next $dayName"))->format('Y-m-d');

            $agenda[$dateKey][] = $dispo;
        }

        ksort($agenda);

        return $this->render('disponibilite/agenda.html.twig', [
            'layout' => 'front/medecin/base_medecin.html.twig',
            'agenda' => $agenda,
            'pagination' => $pagination
        ]);
    }
}
