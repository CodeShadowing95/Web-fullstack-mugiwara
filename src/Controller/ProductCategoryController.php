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
use App\Repository\MediaRepository;


#[OA\Info(
    version: "1.0.0",
    description: "API for managing product categories",
    title: "Product Categories API"
)]
final class ProductCategoryController extends AbstractController
{
    #[Route('api/public/v1/product-category/{id}/children', name: 'api_get_category_children', methods: ['GET'])]
    #[OA\Tag(name: 'Product Categories')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of parent category',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns all child categories',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ProductCategory::class, groups: ['category']))
        )
    )]
    public function getChildren(ProductCategory $category = null, SerializerInterface $serializer, MediaRepository $mediaRepository): JsonResponse
    {
        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $children = $category->getChildren();
        $childrenArray = [];
        foreach ($children as $child) {
            $thumbnail = null;
            $medias = $mediaRepository->findBy(['entityType' => 'category', 'entityId' => $child->getId()]);
            foreach ($medias as $media) {
                if ($media->getMediaType() && $media->getMediaType()->getSlug() === 'thumbnail') {
                    $thumbnail = $media;
                    break;
                }
            }
            $childData = json_decode($serializer->serialize($child, 'json', ['groups' => ['category', 'category_details']]), true);
            $childData['thumbnail'] = $thumbnail ? json_decode($serializer->serialize($thumbnail, 'json', ['groups' => ['media']]), true) : null;
            $grandChildren = $child->getChildren();
            $grandChildrenArray = [];
            foreach ($grandChildren as $grandChild) {
                $grandChildThumbnail = null;
                $grandChildMedias = $mediaRepository->findBy(['entityType' => 'category', 'entityId' => $grandChild->getId()]);
                foreach ($grandChildMedias as $media) {
                    if ($media->getMediaType() && $media->getMediaType()->getSlug() === 'thumbnail') {
                        $grandChildThumbnail = $media;
                        break;
                    }
                }
                $grandChildData = json_decode($serializer->serialize($grandChild, 'json', ['groups' => ['category', 'category_details']]), true);
                $grandChildData['thumbnail'] = $grandChildThumbnail ? json_decode($serializer->serialize($grandChildThumbnail, 'json', ['groups' => ['media']]), true) : null;
                $grandChildrenArray[] = $grandChildData;
            }
            $childData['children'] = $grandChildrenArray;
            $childrenArray[] = $childData;
        }
        return new JsonResponse($childrenArray, Response::HTTP_OK);
    }

    #[Route('api/v1/product-category/{id}/parent/{parentId}', name: 'api_set_category_parent', methods: ['PUT'])]
    #[OA\Tag(name: 'Product Categories')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of category',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'parentId',
        in: 'path',
        description: 'ID of parent category',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Parent category set successfully'
    )]
    public function setParent(
        ProductCategory $category,
        ProductCategory $parent,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $category->setCategoryParent($parent);
        $em->flush();

        $jsonData = $serializer->serialize($category, 'json', ['groups' => ['category', 'category_details']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/v1/product-category/{id}/parent', name: 'api_remove_category_parent', methods: ['DELETE'])]
    #[OA\Tag(name: 'Product Categories')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of category',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Parent category removed successfully'
    )]
    public function removeParent(ProductCategory $category, EntityManagerInterface $em): JsonResponse
    {
        $category->setCategoryParent(null);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/public/v1/product-categories', name: 'api_get_all_product_categories', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns all product categories',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ProductCategory::class, groups: ['category']))
        )
    )]
    #[OA\Tag(name: 'Product Categories')]
    public function getAll(ProductCategoryRepository $repository, SerializerInterface $serializer, MediaRepository $mediaRepository): JsonResponse
    {
        $categories = $repository->findBy(['categoryParent' => null], ['name' => 'ASC']);
        $categoriesArray = [];
        foreach ($categories as $category) {
            $thumbnail = null;
            $medias = $mediaRepository->findBy(['entityType' => 'category', 'entityId' => $category->getId()]);
            foreach ($medias as $media) {
                if ($media->getMediaType() && $media->getMediaType()->getSlug() === 'thumbnail') {
                    $thumbnail = $media;
                    break;
                }
            }
            $catData = json_decode($serializer->serialize($category, 'json', ['groups' => ['category', 'children']]), true);
            $catData['thumbnail'] = $thumbnail ? json_decode($serializer->serialize($thumbnail, 'json', ['groups' => ['media']]), true) : null;
            $children = $category->getChildren();
            $childrenArray = [];
            foreach ($children as $child) {
                $childThumbnail = null;
                $childMedias = $mediaRepository->findBy(['entityType' => 'category', 'entityId' => $child->getId()]);
                foreach ($childMedias as $media) {
                    if ($media->getMediaType() && $media->getMediaType()->getSlug() === 'thumbnail') {
                        $childThumbnail = $media;
                        break;
                    }
                }
                $childData = json_decode($serializer->serialize($child, 'json', ['groups' => ['category', 'children']]), true);
                $childData['thumbnail'] = $childThumbnail ? json_decode($serializer->serialize($childThumbnail, 'json', ['groups' => ['media']]), true) : null;
                $childrenArray[] = $childData;
            }
            $catData['children'] = $childrenArray;
            $categoriesArray[] = $catData;
        }
        return new JsonResponse($categoriesArray, Response::HTTP_OK);
    }

    #[Route('api/public/v1/product-category/{id}', name: 'api_get_product_category', methods: ['GET'])]
    #[OA\Tag(name: 'Product Categories')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of product category',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a product category with its products',
        content: new OA\JsonContent(ref: new Model(type: ProductCategory::class, groups: ['category', 'category_details', 'children']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Product category not found'
    )]
    public function get(ProductCategory $category = null, SerializerInterface $serializer, MediaRepository $mediaRepository): JsonResponse
    {
        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $thumbnail = null;
        $medias = $mediaRepository->findBy(['entityType' => 'category', 'entityId' => $category->getId()]);
        foreach ($medias as $media) {
            if ($media->getMediaType() && $media->getMediaType()->getSlug() === 'thumbnail') {
                $thumbnail = $media;
                break;
            }
        }
        $catData = json_decode($serializer->serialize($category, 'json', ['groups' => ['category', 'children']]), true);
        $catData['thumbnail'] = $thumbnail ? json_decode($serializer->serialize($thumbnail, 'json', ['groups' => ['media']]), true) : null;
        $children = $category->getChildren();
        $childrenArray = [];
        foreach ($children as $child) {
            $childThumbnail = null;
            $childMedias = $mediaRepository->findBy(['entityType' => 'category', 'entityId' => $child->getId()]);
            foreach ($childMedias as $media) {
                if ($media->getMediaType() && $media->getMediaType()->getSlug() === 'thumbnail') {
                    $childThumbnail = $media;
                    break;
                }
            }
            $childData = json_decode($serializer->serialize($child, 'json', ['groups' => ['category', 'children']]), true);
            $childData['thumbnail'] = $childThumbnail ? json_decode($serializer->serialize($childThumbnail, 'json', ['groups' => ['media']]), true) : null;
            $childrenArray[] = $childData;
        }
        $catData['children'] = $childrenArray;
        return new JsonResponse($catData, Response::HTTP_OK);
    }

    #[Route('api/v1/product-category', name: 'api_create_product_category', methods: ['POST'])]
    #[OA\Tag(name: 'Product Categories')]
    #[OA\RequestBody(
        description: 'Product category data',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Fruits'),
                new OA\Property(property: 'description', type: 'string', example: 'Fresh fruits category')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Product category created successfully',
        content: new OA\JsonContent(ref: new Model(type: ProductCategory::class, groups: ['category']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input'
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
    #[OA\Tag(name: 'Product Categories')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of product category to update',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Fields to update',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Updated Fruits'),
                new OA\Property(property: 'description', type: 'string', example: 'Updated description')
            ]
        )
    )]
    #[OA\Response(
        response: 204,
        description: 'Product category updated successfully'
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input'
    )]
    #[OA\Response(
        response: 404,
        description: 'Product category not found'
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
    #[OA\Tag(name: 'Product Categories')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of product category to delete',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Product category deleted successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'Product category not found'
    )]
    public function delete(ProductCategory $category, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($category);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/public/v1/product-category/{id}/products', name: 'api_get_products_by_category', methods: ['GET'])]
    #[OA\Tag(name: 'Product Categories')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID de la catégorie',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne les produits de la catégorie',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: \App\Entity\Product::class, groups: ['product']))
        )
    )]
    public function getProductsByCategory(ProductCategory $category, SerializerInterface $serializer): JsonResponse
    {
        $products = $category->getProducts();
        $jsonData = $serializer->serialize($products, 'json', ['groups' => ['product']]);
        return new JsonResponse($jsonData, Response::HTTP_OK, [], true);
    }

    #[Route('api/public/v1/product-category/{id}/parents', name: 'api_get_category_parents', methods: ['GET'])]
    #[OA\Tag(name: 'Product Categories')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of parent category',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns all parent categories',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ProductCategory::class, groups: ['category']))
        )
    )]
    public function getParents(ProductCategory $category, SerializerInterface $serializer, MediaRepository $mediaRepository): JsonResponse
    {
        $parents = $category->getParents();
        $parentsArray = [];
        foreach ($parents as $parent) {
            $thumbnail = null;
            $medias = $mediaRepository->findBy(['entityType' => 'category', 'entityId' => $parent->getId()]);
            foreach ($medias as $media) {
                if ($media->getMediaType() && $media->getMediaType()->getSlug() === 'thumbnail') {
                    $thumbnail = $media;
                    break;
                }
            }
            $parentData = json_decode($serializer->serialize($parent, 'json', ['groups' => ['category']]), true);
            $parentData['thumbnail'] = $thumbnail ? json_decode($serializer->serialize($thumbnail, 'json', ['groups' => ['media']]), true) : null;
            $children = $parent->getChildren();
            $childrenArray = [];
            foreach ($children as $child) {
                $childThumbnail = null;
                $childMedias = $mediaRepository->findBy(['entityType' => 'category', 'entityId' => $child->getId()]);
                foreach ($childMedias as $media) {
                    if ($media->getMediaType() && $media->getMediaType()->getSlug() === 'thumbnail') {
                        $childThumbnail = $media;
                        break;
                    }
                }
                $childData = json_decode($serializer->serialize($child, 'json', ['groups' => ['category']]), true);
                $childData['thumbnail'] = $childThumbnail ? json_decode($serializer->serialize($childThumbnail, 'json', ['groups' => ['media']]), true) : null;
                $childrenArray[] = $childData;
            }
            $parentData['children'] = $childrenArray;
            $parentsArray[] = $parentData;
        }
        return new JsonResponse($parentsArray, Response::HTTP_OK);
    }
}
