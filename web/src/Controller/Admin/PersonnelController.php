<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Form\AdminPersonnelType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/personnel', name: 'admin_personnel_')]
class PersonnelController extends AbstractController
{
    /**
     * Helper: get all users whose roles JSON contains ROLE_PERSONNEL, with optional search.
     */
    private function findPersonnel(EntityManagerInterface $em, ?string $query = null): array
    {
        $conn = $em->getConnection();
        $sql = "SELECT id FROM Utilisateur WHERE roles LIKE :role";
        $params = ['role' => '%ROLE_PERSONNEL%'];

        if ($query) {
            $sql .= ' AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q OR niveau_acces LIKE :q)';
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

        return $this->render('admin/personnel/index.html.twig', [
            'personnels' => $this->findPersonnel($em, $query),
            'search_query' => $query,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $personnel = new Utilisateur();
        $form = $this->createForm(AdminPersonnelType::class, $personnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $personnel->setPassword($passwordHasher->hashPassword($personnel, $plainPassword));
            }
            $personnel->setRoles(['ROLE_PERSONNEL']);

            $em->persist($personnel);
            $em->flush();

            $this->addFlash('success', 'Le personnel a été créé avec succès.');
            return $this->redirectToRoute('admin_personnel_index');
        }

        return $this->render('admin/personnel/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Utilisateur $personnel): Response
    {
        return $this->render('admin/personnel/show.html.twig', [
            'personnel' => $personnel,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $personnel, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(AdminPersonnelType::class, $personnel, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $personnel->setPassword($passwordHasher->hashPassword($personnel, $plainPassword));
            }

            $em->flush();

            $this->addFlash('success', 'Le personnel a été modifié avec succès.');
            return $this->redirectToRoute('admin_personnel_index');
        }

        return $this->render('admin/personnel/edit.html.twig', [
            'personnel' => $personnel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $personnel, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $personnel->getId(), $request->request->get('_token'))) {
            $em->remove($personnel);
            $em->flush();
            $this->addFlash('warning', 'Le personnel a été supprimé.');
        }

        return $this->redirectToRoute('admin_personnel_index');
    }
}
