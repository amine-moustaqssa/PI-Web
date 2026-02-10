<?php 

namespace App\Controller\Infirmier; 

use App\Entity\ConstanteVitale; 
use App\Entity\Consultation; 
use App\Form\ConstanteVitaleInfirmierType; 
use App\Repository\ConstanteVitaleRepository; 
use App\Repository\ConsultationRepository; 
use Doctrine\ORM\EntityManagerInterface; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; 
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response; 
use Symfony\Component\Routing\Attribute\Route; 

#[Route('/infirmier/consultation/{id}/constantes')]
class ConstanteVitaleInfirmierController extends AbstractController 
{
    #[Route('', name: 'infirmier_constante_index', methods: ['GET'])]
    public function index(
        Consultation $consultation,
        ConstanteVitaleRepository $constanteRepository
    ): Response {
        return $this->render('infirmier/constante_vitale/index.html.twig', [
            'consultation' => $consultation,
            'constantes' => $constanteRepository->findBy(['consultation_id' => $consultation->getId()]),
        ]);
    }

    #[Route('/new', name: 'infirmier_constante_new', methods: ['GET','POST'])]
    public function new(
        Request $request,
        Consultation $consultation,
        EntityManagerInterface $em
    ): Response {
        // Si consultation est terminée → interdiction de créer
        if ($consultation->getStatut() === 'Terminée') {
            $this->addFlash('error', 'Impossible d’ajouter une constante, consultation terminée.');
            return $this->redirectToRoute('infirmier_constante_index', ['id' => $consultation->getId()]);
        }

        $constante = new ConstanteVitale();
        $constante->setConsultationId($consultation);

        $form = $this->createForm(ConstanteVitaleInfirmierType::class, $constante);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($constante);
            $em->flush();
            return $this->redirectToRoute('infirmier_constante_index', ['id' => $consultation->getId()]);
        }

        return $this->render('infirmier/constante_vitale/new.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation
        ]);
    }

    #[Route('/{constanteId}/edit', name: 'infirmier_constante_edit', methods: ['GET','POST'])]
    public function edit(
        Request $request,
        Consultation $consultation,
        ConstanteVitale $constanteId,
        EntityManagerInterface $em
    ): Response {
        // Interdit si consultation terminée
        if ($consultation->getStatut() === 'Terminée') {
            $this->addFlash('error', 'Impossible de modifier, consultation terminée.');
            return $this->redirectToRoute('infirmier_constante_index', ['id' => $consultation->getId()]);
        }

        $form = $this->createForm(ConstanteVitaleInfirmierType::class, $constanteId);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('infirmier_constante_index', ['id' => $consultation->getId()]);
        }

        return $this->render('infirmier/constante_vitale/edit.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation
        ]);
    }
}
