<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Form\AdminTitulaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/titulaire', name: 'admin_titulaire_')]
class TitulaireController extends AbstractController
{
    /**
     * Helper: get all users whose roles JSON contains ROLE_TITULAIRE, with optional search.
     */
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

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $query = $request->query->get('q');

        return $this->render('admin/titulaire/index.html.twig', [
            'titulaires' => $this->findTitulaires($em, $query),
            'search_query' => $query,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $titulaire = new Utilisateur();
        $form = $this->createForm(AdminTitulaireType::class, $titulaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $titulaire->setPassword($passwordHasher->hashPassword($titulaire, $plainPassword));
            }
            $titulaire->setRoles(['ROLE_TITULAIRE']);

            $em->persist($titulaire);
            $em->flush();

            $this->addFlash('success', 'Le client a été créé avec succès.');
            return $this->redirectToRoute('admin_titulaire_index');
        }

        return $this->render('admin/titulaire/new.html.twig', [
            'titulaire' => $titulaire,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Utilisateur $titulaire): Response
    {
        return $this->render('admin/titulaire/show.html.twig', [
            'titulaire' => $titulaire,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $titulaire, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(AdminTitulaireType::class, $titulaire, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $titulaire->setPassword($passwordHasher->hashPassword($titulaire, $plainPassword));
            }

            $em->flush();

            $this->addFlash('success', 'Le client a été modifié avec succès.');
            return $this->redirectToRoute('admin_titulaire_index');
        }

        return $this->render('admin/titulaire/edit.html.twig', [
            'titulaire' => $titulaire,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $titulaire, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $titulaire->getId(), $request->request->get('_token'))) {
            $em->remove($titulaire);
            $em->flush();
            $this->addFlash('warning', 'Le client a été supprimé.');
        }

        return $this->redirectToRoute('admin_titulaire_index');
    }
}
