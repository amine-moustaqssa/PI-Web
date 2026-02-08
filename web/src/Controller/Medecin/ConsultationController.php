<?php

namespace App\Controller\Medecin;

use App\Entity\Consultation;
use App\Form\ConsultationMedecinType;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin/consultation')]
#[IsGranted('ROLE_MEDECIN')]
final class ConsultationController extends AbstractController
{
    #[Route('/', name: 'medecin_consultation_index', methods: ['GET'])]
    public function index(ConsultationRepository $repository): Response
    {
        return $this->render('medecin/consultation/index.html.twig', [
            'consultations' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'medecin_consultation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $consultation = new Consultation();
        $consultation->setMedecin($this->getUser()); // médecin connecté

        $form = $this->createForm(ConsultationMedecinType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($consultation);
            $em->flush();

            return $this->redirectToRoute('medecin_consultation_index');
        }

        return $this->render('medecin/consultation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'medecin_consultation_show', methods: ['GET'])]
    public function show(Consultation $consultation): Response
    {
        return $this->render('medecin/consultation/show.html.twig', [
            'consultation' => $consultation,
        ]);
    }

    #[Route('/{id}/edit', name: 'medecin_consultation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Consultation $consultation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ConsultationMedecinType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('medecin_consultation_index');
        }

        return $this->render('medecin/consultation/edit.html.twig', [
            'form' => $form,
            'consultation' => $consultation,
        ]);
    }
}
