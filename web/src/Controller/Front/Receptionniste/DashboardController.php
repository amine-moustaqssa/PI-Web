<?php

namespace App\Controller\Front\Receptionniste;

use App\Repository\DisponibiliteRepository;
use App\Repository\MedecinRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/receptionniste')]
#[IsGranted('ROLE_PERSONNEL')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'receptionniste_dashboard')]
    public function index(
        DisponibiliteRepository $disponibiliteRepo,
        MedecinRepository $medecinRepo
    ): Response {
        $user = $this->getUser();
        if ($user->getNiveauAcces() !== 'RECEPTIONIST') {
            throw $this->createAccessDeniedException('Accès réservé aux réceptionnistes.');
        }

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
}
