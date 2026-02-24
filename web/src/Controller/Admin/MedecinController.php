<?php

namespace App\Controller\Admin;

use App\Entity\Medecin;
use App\Form\AdminMedecinType;
use App\Repository\MedecinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/medecin', name: 'admin_medecin_')]
class MedecinController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(MedecinRepository $medecinRepository, Request $request): Response
    {
        $query = $request->query->get('q');

        if ($query) {
            $medecins = $medecinRepository->createQueryBuilder('m')
                ->leftJoin('m.specialite', 's')
                ->where('m.nom LIKE :q OR m.prenom LIKE :q OR m.email LIKE :q OR m.matricule LIKE :q OR s.nom LIKE :q')
                ->setParameter('q', '%' . $query . '%')
                ->orderBy('m.nom', 'ASC')
                ->getQuery()
                ->getResult();
        } else {
            $medecins = $medecinRepository->findAll();
        }

        return $this->render('admin/medecin/index.html.twig', [
            'medecins' => $medecins,
            'search_query' => $query,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $medecin = new Medecin();
        $form = $this->createForm(AdminMedecinType::class, $medecin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Use CIN as the default password
            $cin = $medecin->getCin();
            if ($cin) {
                $medecin->setPassword($passwordHasher->hashPassword($medecin, $cin));
            }
            $medecin->setRoles(['ROLE_MEDECIN']);
            $medecin->setIsVerified(false);
            $medecin->setMustChangePassword(true);

            $entityManager->persist($medecin);
            $entityManager->flush();

            $this->addFlash('success', 'Le médecin a été créé avec succès. Il devra vérifier son email et changer son mot de passe à la première connexion.');
            return $this->redirectToRoute('admin_medecin_index');
        }

        return $this->render('admin/medecin/new.html.twig', [
            'medecin' => $medecin,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Medecin $medecin): Response
    {
        return $this->render('admin/medecin/show.html.twig', [
            'medecin' => $medecin,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Medecin $medecin, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminMedecinType::class, $medecin, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le médecin a été modifié avec succès.');
            return $this->redirectToRoute('admin_medecin_index');
        }

        return $this->render('admin/medecin/edit.html.twig', [
            'medecin' => $medecin,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Medecin $medecin, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $medecin->getId(), $request->request->get('_token'))) {
            $entityManager->remove($medecin);
            $entityManager->flush();
            $this->addFlash('warning', 'Le médecin a été supprimé.');
        }

        return $this->redirectToRoute('admin_medecin_index');
    }
}
