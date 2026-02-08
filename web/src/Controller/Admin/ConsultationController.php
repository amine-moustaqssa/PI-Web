<?php

namespace App\Controller\Admin;

use App\Entity\Consultation;
use App\Form\ConsultationAdminType;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/consultation')]
#[IsGranted('ROLE_ADMIN')]
final class ConsultationController extends AbstractController
{
    #[Route('/', name: 'admin_consultation_index', methods: ['GET'])]
    public function index(ConsultationRepository $repository): Response
    {
        return $this->render('admin/consultation/index.html.twig', [
            'consultations' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_consultation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $consultation = new Consultation();
        $form = $this->createForm(ConsultationAdminType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($consultation);
            $em->flush();

            return $this->redirectToRoute('admin_consultation_index');
        }

        return $this->render('admin/consultation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_consultation_show', methods: ['GET'])]
    public function show(Consultation $consultation): Response
    {
        return $this->render('admin/consultation/show.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_consultation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Consultation $consultation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ConsultationAdminType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_consultation_index');
        }

        return $this->render('admin/consultation/edit.html.twig', [
            'form' => $form,
            'consultation' => $consultation,
        ]);
    }

    #[Route('/{id}', name: 'admin_consultation_delete', methods: ['POST'])]
    public function delete(Request $request, Consultation $consultation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$consultation->getId(), $request->get('_token'))) {
            $em->remove($consultation);
            $em->flush();
        }

        return $this->redirectToRoute('admin_consultation_index');
    }
}
