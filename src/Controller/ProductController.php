<?php

namespace App\Controller;

use App\Entity\Media;
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
use App\Utils\MediaUploader;

final class ProductController extends AbstractController
{
    public function __construct(
        private readonly MediaUploader $mediaUploader
    ) {}

    #[Route('api/public/v1/products', name: 'api_get_all_products', methods: ['GET'])]
    #[OA\Tag(name: 'Products')]
    #[OA\Response(
        response: 200,
        description: 'Retourne tous les produits',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['product', 'product_details']))
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Aucun produit trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Aucun produit trouvé')
            ]
        )
    )]
    #[IsGranted('PUBLIC_ACCESS')]
    public function getAll(ProductRepository $repository, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $products = $repository->findAll();
        $productsWithData = [];

        // Get reviews and average rating for each product
        $reviewRepo = $em->getRepository(\App\Entity\Review::class);

        foreach ($products as $product) {
            // Get reviews and average rating for the product
            $reviews = $reviewRepo->findByProduct($product->getId());
            $averageRating = $reviewRepo->findAverageRatingByProduct($product->getId());

            $productsWithData[] = [
                'product' => $product,
                'averageRating' => $averageRating,
                'reviewsCount' => count($reviews),
            ];
        }

        $jsonData = $serializer->serialize($productsWithData, 'json', ['groups' => ['product', 'categories', 'parent', 'media']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/public/v1/product/{id}', name: 'api_get_product', methods: ['GET'])]
    #[OA\Tag(name: 'Products')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du produit',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne un produit avec ses détails',
        content: new OA\JsonContent(ref: new Model(type: Product::class, groups: ['product', 'product_details']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Produit non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Produit non trouvé')
            ]
        )
    )]
    public function get(Product $product, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        // Get reviews for the product
        $reviewRepo = $em->getRepository(\App\Entity\Review::class);
        $reviews = $reviewRepo->findByProduct($product->getId());
        $averageRating = $reviewRepo->findAverageRatingByProduct($product->getId());

        // Return product with its reviews
        $jsonData = $serializer->serialize(
            [
                'product' => $product,
                'reviews' => $reviews,
                'averageRating' => $averageRating,
                'reviewsCount' => count($reviews),
            ],
            'json',
            ['groups' => ['product', 'media', 'categories', 'parent', 'product_details', 'farm', 'product_reviews']]
        );
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/public/v1/product', name: 'api_create_product', methods: ['POST'])]
    #[OA\Tag(name: 'Products')]
    #[OA\RequestBody(
        description: 'Données du produit',
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'quantity', 'price', 'unitPrice', 'categories'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Tomate'),
                new OA\Property(property: 'quantity', type: 'integer', example: 10),
                new OA\Property(property: 'price', type: 'number', example: 2.5),
                new OA\Property(property: 'unitPrice', type: 'number', example: 0.25),
                new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'integer'), example: [1,2])
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Produit créé avec succès',
        content: new OA\JsonContent(ref: new Model(type: Product::class, groups: ['product']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Entrée invalide',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Invalid data format')
            ]
        )
    )]
    public function create(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {

        $data = $request->request->get('data');
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data format'], Response::HTTP_BAD_REQUEST);
        }
        $product = $serializer->deserialize($data, Product::class, 'json');

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($product);
        $em->flush();

        if ($request->files->has('images')) {
            $uploadedFiles = $request->files->get('images');
            if (is_array($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $this->mediaUploader->upload($uploadedFile, 'media', 'product', $product->getId(), 'image');
                }
            }
        }

        $jsonData = $serializer->serialize($product, 'json', ['groups' => ['product']]);
        $location = $urlGenerator->generate('api_get_product', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonData, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('api/v1/product/{id}', name: 'api_update_product', methods: ['PATCH'])]
    #[OA\Tag(name: 'Products')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du produit à mettre à jour',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Champs à mettre à jour',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Tomate cerise'),
                new OA\Property(property: 'quantity', type: 'integer', example: 20),
                new OA\Property(property: 'price', type: 'number', example: 3.0),
                new OA\Property(property: 'unitPrice', type: 'number', example: 0.15),
                new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'integer'), example: [1])
            ]
        )
    )]
    #[OA\Response(
        response: 204,
        description: 'Produit mis à jour avec succès'
    )]
    #[OA\Response(
        response: 400,
        description: 'Entrée invalide',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Erreur de validation')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Produit non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Produit non trouvé')
            ]
        )
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

        // Handle file upload if present
        if ($request->files->has('image')) {
            $uploadedFile = $request->files->get('image');
            $media = $this->mediaUploader->upload($uploadedFile, 'products', 'product', $product->getId());
            $updatedProduct->setImage($media);
        }

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
        description: 'ID du produit à supprimer',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Produit supprimé avec succès'
    )]
    #[OA\Response(
        response: 404,
        description: 'Produit non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Produit non trouvé')
            ]
        )
    )]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
