<?php

namespace App\Controller;

use App\Entity\Farm;
use OpenApi\Attributes as OA;
use App\Repository\FarmRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class FarmController extends AbstractController
{
    #[Route('api/v1/farms', name: 'api_get_all_farm', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns a list of all farms',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Farm::class, groups: ['farm','stats']))
        )
    )]
    /**
     * Summary of getAll
     * @param \App\Repository\FarmRepository $farmFarmRepository
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @return JsonResponse
     */
    public function getAll(FarmRepository $farmFarmRepository, SerializerInterface $serializer): JsonResponse
    {
        $farms = $farmFarmRepository->findAll();
        $jsonData = $serializer->serialize($farms, 'json', ['groups' => ['farm', 'stats']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/farm/{id}', name: 'api_get_farm', methods: ['GET'])]
    #[OA\Parameter(
        name: 'farm',
        in: 'path',
        description: 'ID of the farm',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a farm',
        content: new OA\JsonContent(ref: new Model(type: Farm::class, groups: ['farm','stats']))
    )]
    /**
     * Summary of get
     * @param \App\Entity\Farm $farm
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @return JsonResponse
     */
    public function get(Farm $farm, SerializerInterface $serializer): JsonResponse
    {
        $jsonData = $serializer->serialize($farm, 'json', ['groups' => ['farm','stats']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/farm', name: 'api_create_farm', methods: ['POST'])]
    #[OA\Parameter(
        name: 'name',
        in: 'body',
        description: 'Name of the farm',
        required: true,
        schema: new OA\Schema(ref: new Model(type: Farm::class)), 
    )]
    #[OA\Parameter(
        name: 'address',
        in: 'body',
        description: 'Address of the farm',
        required: true,
        schema: new OA\Schema(ref: new Model(type: Farm::class)),
    )]
    #[OA\Parameter(
        name: 'description',
        in: 'body',
        description: 'Description of the farm',
        required: true,
        schema: new OA\Schema(ref: new Model(type: Farm::class)),
    )]
    #[OA\Property(
        property: 'products',
        type: 'array',
        items: new OA\Items(type: 'integer')
    )]
    #[OA\Response(
        response: 201,
        description: 'Returns the created farm',
        content: new OA\JsonContent(ref: new Model(type: Farm::class))
    )]
    /**
     * Summary of create
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator
     * @param \App\Repository\ProductRepository $productRepository
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @return JsonResponse
     */
    public function create(Request $request, UrlGeneratorInterface $urlGenerator, ProductRepository $productRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $farm = $serializer->deserialize($request->getContent(), Farm::class, 'json');
        $productsData = $request->toArray()['products'] ?? [];
        foreach ($productsData as $productId) {
            $product = $productRepository->find($productId);
            if ($product) {
                $farm->addProduct($product);
            }
        }
        $farm->setStatus('on');
        $errors = $validator->validate($farm);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($farm);
        $entityManager->flush();
        $jsonData = $serializer->serialize($farm, 'json');
        $location = $urlGenerator->generate('get_farm', ['farm' => $farm->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonData, Response::HTTP_CREATED, ["location" => $location], true);
    }

    #[Route("api/v1/farm/{farm}", name:"api_update_farm", methods: ["PATCH"])]
    /**
     * Summary of update
     * @param \App\Entity\Farm $farm
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Repository\ProductRepository $productRepository
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @return JsonResponse
     */
    public function update(Farm $farm, Request $request, ProductRepository $productRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse 
    {
        $updatedFarm = $serializer->deserialize(
            $request->getContent(), Farm::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $farm]
        );
        
        $productsData = $request->toArray()['products'] ?? [];
        $farm->getProducts()->clear();
        foreach ($productsData as $productId) {
            $product = $productRepository->find($productId);
            if ($product) {
                $farm->addProduct($product);
            }
        }
        $errors = $validator->validate($updatedFarm);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        
        $entityManager->persist($updatedFarm);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT, [], true);
    }

    #[Route("api/v1/farm/{farm}", name:"api_delete_farm", methods: ["DELETE"])]
    /**
     * Summary of delete
     * @param \App\Entity\Farm $farm
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function delete(Farm $farm, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        
        if(isset($requestData['hard']) && $requestData['hard'] === true) {
            $entityManager->remove($farm);
        } else {
            $farm->softDelete();
        }
        
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT, []);
    }
}
