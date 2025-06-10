<?php

namespace App\Controller;

use App\Entity\ProductCategory;
use OpenApi\Attributes as OA;
use App\Repository\ProductCategoryRepository;
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

final class ProductCategoryController extends AbstractController
{
    #[Route('api/v1/product-categories', name: 'api_get_all_product_categories', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns all product categories',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ProductCategory::class))
        )
    )]
    public function getAll(ProductCategoryRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $categories = $repository->findAll();
        $jsonData = $serializer->serialize($categories, 'json',['groups' => ['category']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/product-category/{id}', name: 'api_get_product_category', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns a product category',
        content: new OA\JsonContent(ref: new Model(type: ProductCategory::class))
    )]
    public function get(ProductCategory $category, SerializerInterface $serializer): JsonResponse
    {
        $jsonData = $serializer->serialize($category, 'json', ['groups' => ['category', 'category_details','']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/product-category', name: 'api_create_product_category', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Creates a new product category',
        content: new OA\JsonContent(ref: new Model(type: ProductCategory::class))
    )]
    public function create(
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $category = $serializer->deserialize($request->getContent(), ProductCategory::class, 'json');
        
        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($category);
        $em->flush();

        $jsonData = $serializer->serialize($category, 'json');
        $location = $urlGenerator->generate('api_get_product_category', ['id' => $category->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return new JsonResponse($jsonData, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('api/v1/product-category/{id}', name: 'api_update_product_category', methods: ['PATCH'])]
    #[OA\Response(
        response: 200,
        description: 'Updates a product category',
        content: new OA\JsonContent(ref: new Model(type: ProductCategory::class))
    )]
    public function update(
        ProductCategory $category,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $updatedCategory = $serializer->deserialize(
            $request->getContent(),
            ProductCategory::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $category]
        );

        $errors = $validator->validate($updatedCategory);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/v1/product-category/{id}', name: 'api_delete_product_category', methods: ['DELETE'])]
    public function delete(ProductCategory $category, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($category);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}