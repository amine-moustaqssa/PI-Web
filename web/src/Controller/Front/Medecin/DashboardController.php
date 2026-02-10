<?php

namespace App\Controller\Front\Medecin;

use App\Repository\RendezVousRepository;
use App\Repository\DossierCliniqueRepository;
use App\Repository\ConsultationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
