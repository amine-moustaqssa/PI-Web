<?php

namespace App\Controller;

use App\Entity\Disponibilite;
use App\Form\DisponibiliteType;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Utilisateur;

#[Route('/admin/disponibilite')]
class DisponibiliteController extends AbstractController
{
    #[Route('/', name: 'app_disponibilite_index', methods: ['GET'])]
    public function index(Request $request, DisponibiliteRepository $disponibiliteRepository): Response
    {
        $user = $this->getUser();

        // 1. On récupère les filtres du formulaire
        $jour = $request->query->get('jour');
        $recurrent = $request->query->get('recurrent');

        // 2. Logique de filtrage par Rôle et par Recherche
        if ($this->isGranted('ROLE_MEDECIN') && !$this->isGranted('ROLE_ADMIN')) {
            // Le médecin est limité à ses propres données + ses filtres
            $disponibilites = $disponibiliteRepository->findByFilters($jour, $recurrent, $user);
        } else {
            // L'admin peut tout filtrer
            $disponibilites = $disponibiliteRepository->findByFilters($jour, $recurrent);
        }

        return $this->render('disponibilite/index.html.twig', [
            'disponibilites' => $disponibilites,
        ]);
    }



    #[Route('/new', name: 'app_disponibilite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \App\Service\HolidayApiService $holidayApi): Response
    {
        if (!$this->isGranted('ROLE_MEDECIN') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Patients cannot create shifts.');
        }

        $disponibilite = new Disponibilite();

        if (!$this->isGranted('ROLE_ADMIN')) {
            /** @var Utilisateur $user */
            $user = $this->getUser();
            $disponibilite->setMedecin($user);
        }

        $form = $this->createForm(DisponibiliteType::class, $disponibilite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($disponibilite->getDateSpecifique()) {
                // Ensure jourSemaine is strictly 1-7 to prevent SQL check constraint violations
                $disponibilite->setJourSemaine((int) $disponibilite->getDateSpecifique()->format('N'));
            }

            if ($disponibilite->getDateSpecifique() && $holidayApi->isHoliday($disponibilite->getDateSpecifique())) {
                $this->addFlash('warning', "⚠️ Attention : Vous essayez de créer un créneau le jour d'une fête nationale. Ce n'est pas permis.");
                return $this->render('disponibilite/new.html.twig', [
                    'disponibilite' => $disponibilite,
                    'form' => $form->createView(),
                ]);
            }

            $entityManager->persist($disponibilite);
            $entityManager->flush();

            return $this->redirectToRoute('app_disponibilite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('disponibilite/new.html.twig', [
            'disponibilite' => $disponibilite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_disponibilite_show', methods: ['GET'])]
    public function show(Disponibilite $disponibilite): Response
    {
        return $this->render('disponibilite/show.html.twig', [
            'disponibilite' => $disponibilite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_disponibilite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Disponibilite $disponibilite, EntityManagerInterface $entityManager, \App\Service\HolidayApiService $holidayApi): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            if (!$this->isGranted('ROLE_MEDECIN') || $disponibilite->getMedecin() !== $this->getUser()) {
                throw new AccessDeniedException('You can only edit your own shifts.');
            }
        }

        if (!$disponibilite->getDateSpecifique() && $disponibilite->getJourSemaine()) {
            $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            $dayName = $days[$disponibilite->getJourSemaine()];
            $disponibilite->setDateSpecifique(new \DateTime("next $dayName"));
        }

        $form = $this->createForm(DisponibiliteType::class, $disponibilite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($disponibilite->getDateSpecifique()) {
                // Ensure jourSemaine is strictly 1-7 to prevent SQL check constraint violations
                $disponibilite->setJourSemaine((int) $disponibilite->getDateSpecifique()->format('N'));
            }

            if ($disponibilite->getDateSpecifique() && $holidayApi->isHoliday($disponibilite->getDateSpecifique())) {
                $this->addFlash('warning', "⚠️ Attention : Vous essayez de modifier un créneau vers un jour de fête nationale. Ce n'est pas permis.");
                return $this->render('disponibilite/edit.html.twig', [
                    'disponibilite' => $disponibilite,
                    'form' => $form->createView(),
                ]);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_disponibilite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('disponibilite/edit.html.twig', [
            'disponibilite' => $disponibilite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_disponibilite_delete', methods: ['POST'])]
    public function delete(Request $request, Disponibilite $disponibilite, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            if (!$this->isGranted('ROLE_MEDECIN') || $disponibilite->getMedecin() !== $this->getUser()) {
                throw new AccessDeniedException('You can only delete your own shifts.');
            }
        }

        if ($this->isCsrfTokenValid('delete' . $disponibilite->getId(), $request->request->get('_token'))) {
            $entityManager->remove($disponibilite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_disponibilite_index', [], Response::HTTP_SEE_OTHER);
    }
}
