<?php
// Crée ce fichier à : src/Controller/Api/RapportMedicalApiController.php

namespace App\Controller\Api;

use App\Entity\RapportMedical;
use App\Repository\RapportMedicalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/rapports')]
class RapportMedicalApiController extends AbstractController
{
    #[Route('', name: 'api_rapports_list', methods: ['GET'])]
    public function list(
        RapportMedicalRepository $repository,
        SerializerInterface $serializer
    ): JsonResponse {
        $rapports = $repository->findAll();
        
        $data = $serializer->serialize($rapports, 'json', [
            'groups' => ['rapport:read']
        ]);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'api_rapports_show', methods: ['GET'])]
    public function show(
        RapportMedical $rapport,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = $serializer->serialize($rapport, 'json', [
            'groups' => ['rapport:detail']
        ]);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}