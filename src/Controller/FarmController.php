<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Repository\FarmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class FarmController extends AbstractController
{
    #[Route('api/v1/farms', name: 'get_all_farms', methods: ['GET'])]
    public function getAll(FarmRepository $farmFarmRepository, SerializerInterface $serializer): JsonResponse
    {
        $farms = $farmFarmRepository->findAll();
        $jsonData = $serializer->serialize($farms, 'json');
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/farm/{farm}', name: 'get_farm', methods: ['GET'])]
    public function get(Farm $farm, SerializerInterface $serializer): JsonResponse
    {
        $jsonData = $serializer->serialize($farm, 'json');
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/farm', name: 'create_farm', methods: ['POST'])]
    public function create(Request $request, UrlGeneratorInterface $urlGenerator, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $farm = $serializer->deserialize($request->getContent(), Farm::class, 'json');
        $entityManager->persist($farm);
        $entityManager->flush();
        $jsonData = $serializer->serialize($farm, 'json');
        $location = $urlGenerator->generate('get_farm', ['farm' => $farm->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonData, Response::HTTP_CREATED, ["location" => $location], true);
    }

    #[Route("api/v1/farm/{farm}", name:"update_farm", methods: ["PATCH"])]
    public function update(Farm $farm, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse 
    { 
        $updatedFarm = $serializer->deserialize(
            $request->getContent(), Farm::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $farm]
        );
        
        $entityManager->persist($updatedFarm);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT, [], true); 
    }

    #[Route("api/v1/farm/{farm}", name:"delete_farm", methods: ["DELETE"])]
    public function delete(Farm $farm, EntityManagerInterface $entityManager): JsonResponse 
    { 
        $entityManager->remove($farm);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT, []); 
    }
}
