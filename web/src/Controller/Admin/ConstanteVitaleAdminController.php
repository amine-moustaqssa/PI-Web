<?php

namespace App\Controller\Admin;

use App\Entity\ConstanteVitale;
use App\Form\ConstanteVitaleAdminType;
use App\Repository\ConstanteVitaleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/constante')]
class ConstanteVitaleAdminController extends AbstractController
{
    #[Route('', name: 'admin_constante_index', methods: ['GET'])]
    public function index(ConstanteVitaleRepository $repository): Response
    {
        return $this->render('admin/constante_vitale/index.html.twig', [
            'constantes' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_constante_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $constante = new ConstanteVitale();
        $form = $this->createForm(ConstanteVitaleAdminType::class, $constante);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($constante);
            $em->flush();

            return $this->redirectToRoute('admin_constante_index');
        }

        return $this->render('admin/constante_vitale/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_constante_show', methods: ['GET'])]
    public function show(ConstanteVitale $constante): Response
    {
        return $this->render('admin/constante_vitale/show.html.twig', [
            'constante' => $constante,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_constante_edit', methods: ['GET','POST'])]
    public function edit(Request $request, ConstanteVitale $constante, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ConstanteVitaleAdminType::class, $constante);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_constante_index');
        }

        return $this->render('admin/constante_vitale/edit.html.twig', [
            'form' => $form->createView(),
            'constante' => $constante,
        ]);
    }

    #[Route('/{id}', name: 'admin_constante_delete', methods: ['POST'])]
    public function delete(Request $request, ConstanteVitale $constante, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$constante->getId(), $request->request->get('_token'))) {
            $em->remove($constante);
            $em->flush();
        }

        return $this->redirectToRoute('admin_constante_index');
    }
}
