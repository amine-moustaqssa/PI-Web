<?php

namespace App\Controller;

use App\Repository\DisponibiliteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlanningController extends AbstractController
{
    #[Route('/planning/agenda', name: 'app_planning_agenda')]
    public function agenda(DisponibiliteRepository $dispoRepo): Response
    {
        // 1. Fetch all availabilities
        $disponibilites = $dispoRepo->findAll();

        // 2. Create an empty array to hold our grouped data
        $agenda = [];

        // 3. Group the slots by their Date
        foreach ($disponibilites as $dispo) {
            if (!$dispo->isEstRecurrent()) {
                $timestamp = $dispo->getJourSemaine();
                $dateKey = date('Y-m-d', $timestamp);
            } else {
                $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
                $dayName = $days[$dispo->getJourSemaine()];
                $dateKey = (new \DateTime("next $dayName"))->format('Y-m-d');
            }
            $agenda[$dateKey][] = $dispo;
        }

        // 4. Sort the array so the earliest dates appear first
        ksort($agenda);

        return $this->render('disponibilite/agenda.html.twig', [
            'layout' => 'base.html.twig', // For patients
            'agenda' => $agenda, 
        ]);
    }
}
