<?php

namespace App\Controller;

use App\Entity\Product;
use OpenApi\Attributes as OA;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductController extends AbstractController
{
    #[Route('api/public/v1/products', name: 'api_get_all_products', methods: ['GET'])]
    #[OA\Tag(name: 'Products')]
    #[OA\Response(
        response: 200,
        description: 'Returns all products',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['product', 'product_details']))
        )
    )]
    #[IsGranted('PUBLIC_ACCESS')]
    public function getAll(ProductRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $products = $repository->findAll();
        $jsonData = $serializer->serialize($products, 'json', ['groups' => ['product', 'category']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/product/{id}', name: 'api_get_product', methods: ['GET'])]
    #[OA\Tag(name: 'Products')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of product',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a product with its details',
        content: new OA\JsonContent(ref: new Model(type: Product::class, groups: ['product', 'product_details']))
    )]
    public function get(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $jsonData = $serializer->serialize($product, 'json', ['groups' => ['product']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/product', name: 'api_create_product', methods: ['POST'])]
    #[OA\Tag(name: 'Products')]
    #[OA\RequestBody(
        description: 'Product data',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'quantity', type: 'integer'),
                new OA\Property(property: 'price', type: 'number'),
                new OA\Property(property: 'unitPrice', type: 'number'),
                new OA\Property(property: 'category', type: 'array', items: new OA\Items(type: 'integer'))
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Product created successfully'
    )]
    public function create(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        $product = $serializer->deserialize($request->getContent(), Product::class, 'json');

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($product);
        $em->flush();

        $jsonData = $serializer->serialize($product, 'json', ['groups' => ['product']]);
        $location = $urlGenerator->generate('api_get_product', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return new JsonResponse($jsonData, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('api/v1/product/{id}', name: 'api_update_product', methods: ['PATCH'])]
    #[OA\Tag(name: 'Products')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of product to update',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Product updated successfully'
    )]
    public function update(
        Product $product,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $updatedProduct = $serializer->deserialize(
            $request->getContent(),
            Product::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $product]
        );

        $errors = $validator->validate($updatedProduct);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/v1/product/{id}', name: 'api_delete_product', methods: ['DELETE'])]
    #[OA\Tag(name: 'Products')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of product to delete',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Product deleted successfully'
    )]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}