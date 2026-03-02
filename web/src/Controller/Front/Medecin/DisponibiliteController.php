<?php

namespace App\Controller\Front\Medecin;

use App\Entity\Disponibilite;
use App\Form\DisponibiliteType;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin/disponibilite')]
#[IsGranted('ROLE_MEDECIN')]
final class DisponibiliteController extends AbstractController
{
    #[Route('/', name: 'medecin_disponibilite_index', methods: ['GET'])]
    public function index(Request $request, DisponibiliteRepository $repository): Response
    {
        $jour = $request->query->get('jour');
        $recurrent = $request->query->get('recurrent');

        return $this->render('front/medecin/disponibilite/index.html.twig', [
            'disponibilites' => $repository->findByFilters($jour, $recurrent, $this->getUser()),
        ]);
    }

    #[Route('/new', name: 'medecin_disponibilite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, DisponibiliteRepository $repo, \App\Service\HolidayApiService $holidayApi): Response
    {
        $disponibilite = new Disponibilite();
        $disponibilite->setMedecin($this->getUser());

        $form = $this->createForm(DisponibiliteType::class, $disponibilite, ['hide_medecin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // --- AUTO DÉDUCTION DU JOUR DE LA SEMAINE OU TIMESTAMP ---
            if ($disponibilite->getDateSpecifique()) {
                // Ensure jourSemaine is strictly 1-7 to prevent SQL check constraint violations (US-2.3)
                $disponibilite->setJourSemaine((int) $disponibilite->getDateSpecifique()->format('N'));
            }

            // --- US-2.3 : VÉRIFICATION JOURS FÉRIÉS ---
            if ($disponibilite->getDateSpecifique() && $holidayApi->isHoliday($disponibilite->getDateSpecifique())) {
                $form->get('dateSpecifique')->addError(new FormError("⚠️ Cette date correspond à un jour férié national. Veuillez choisir une autre date."));
                return $this->render('front/medecin/disponibilite/new.html.twig', [
                    'form' => $form,
                    'disponibilite' => $disponibilite,
                ]);
            }
            // -------------------------------------

            // --- RÈGLE MÉTIER : ANTI-COLLISION ---
            $conflits = $repo->findOverlapping(
                $disponibilite->getMedecin(),
                $disponibilite->getJourSemaine(),
                $disponibilite->getHeureDebut(),
                $disponibilite->getHeureFin()
            );

            if (count($conflits) > 0) {
                $this->addFlash('danger', 'Impossible : Ce créneau chevauche un horaire existant.');
                return $this->render('front/medecin/disponibilite/new.html.twig', [
                    'form' => $form,
                    'disponibilite' => $disponibilite,
                ]);
            }
            // -------------------------------------

            $em->persist($disponibilite);
            $em->flush();

            return $this->redirectToRoute('medecin_disponibilite_index');
        }

        return $this->render('front/medecin/disponibilite/new.html.twig', [
            'form' => $form,
            'disponibilite' => $disponibilite,
        ]);
    }

    #[Route('/{id}/edit', name: 'medecin_disponibilite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Disponibilite $disponibilite, EntityManagerInterface $em, DisponibiliteRepository $repo, \App\Service\HolidayApiService $holidayApi): Response
    {
        if ($disponibilite->getMedecin()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$disponibilite->getDateSpecifique() && $disponibilite->getJourSemaine()) {
            // Find the closest upcoming day of the week to prefill the calendar
            $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            $dayName = $days[$disponibilite->getJourSemaine()];
            $disponibilite->setDateSpecifique(new \DateTime("next $dayName"));
        }

        $form = $this->createForm(DisponibiliteType::class, $disponibilite, ['hide_medecin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // --- AUTO DÉDUCTION DU JOUR DE LA SEMAINE OU TIMESTAMP ---
            if ($disponibilite->getDateSpecifique()) {
                // Ensure jourSemaine is strictly 1-7 to prevent SQL check constraint violations
                $disponibilite->setJourSemaine((int) $disponibilite->getDateSpecifique()->format('N'));
            }

            // --- US-2.3 : VÉRIFICATION JOURS FÉRIÉS ---
            if ($disponibilite->getDateSpecifique() && $holidayApi->isHoliday($disponibilite->getDateSpecifique())) {
                $form->get('dateSpecifique')->addError(new FormError("⚠️ Cette date correspond à un jour férié national. Veuillez choisir une autre date."));
                return $this->render('front/medecin/disponibilite/edit.html.twig', [
                    'form' => $form,
                    'disponibilite' => $disponibilite,
                ]);
            }
            // -------------------------------------

            // --- RÈGLE MÉTIER : ANTI-COLLISION (Exclusion de soi-même) ---
            $conflits = $repo->findOverlapping(
                $disponibilite->getMedecin(),
                $disponibilite->getJourSemaine(),
                $disponibilite->getHeureDebut(),
                $disponibilite->getHeureFin(),
                $disponibilite->getId()
            );

            if (count($conflits) > 0) {
                $this->addFlash('danger', 'Modification impossible : Chevauchement détecté.');
                return $this->render('front/medecin/disponibilite/edit.html.twig', [
                    'form' => $form,
                    'disponibilite' => $disponibilite,
                ]);
            }
            // -------------------------------------

            $em->flush();

            return $this->redirectToRoute('medecin_disponibilite_index');
        }

        return $this->render('front/medecin/disponibilite/edit.html.twig', [
            'form' => $form,
            'disponibilite' => $disponibilite,
        ]);
    }

    #[Route('/{id}', name: 'medecin_disponibilite_show', methods: ['GET'])]
    public function show(Disponibilite $disponibilite): Response
    {
        if ($disponibilite->getMedecin()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        return $this->render('front/medecin/disponibilite/show.html.twig', [
            'disponibilite' => $disponibilite,
        ]);
    }

    #[Route('/{id}', name: 'medecin_disponibilite_delete', methods: ['POST'])]
    public function delete(Request $request, Disponibilite $disponibilite, EntityManagerInterface $em): Response
    {
        if ($disponibilite->getMedecin()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $disponibilite->getId(), $request->request->get('_token'))) {
            $em->remove($disponibilite);
            $em->flush();
        }

        return $this->redirectToRoute('medecin_disponibilite_index');
    }
}
