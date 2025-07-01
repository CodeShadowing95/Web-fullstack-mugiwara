<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

class ReviewController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('/api/public/v1/reviews', name: 'api_get_all_reviews', methods: ['GET'])]
    #[OA\Tag(name: 'Reviews')]
    #[OA\Response(
        response: 200,
        description: 'Retourne toutes les reviews',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Review::class, groups: ['review']))
        )
    )]
    public function index(ReviewRepository $reviewRepository): JsonResponse
    {
        $reviews = $reviewRepository->findAll();

        $data = $this->serializer->serialize($reviews, 'json', ['groups' => 'review']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/api/public/v1/product/{id}/reviews', name: 'api_get_product_reviews', methods: ['GET'])]
    #[OA\Tag(name: 'Reviews')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du produit',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne les reviews d\'un produit',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Review::class, groups: ['product_reviews']))
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
    public function getProductReviews(int $id, ReviewRepository $reviewRepository): JsonResponse
    {
        $reviews = $reviewRepository->findByProduct($id);

        $data = $this->serializer->serialize($reviews, 'json', ['groups' => 'product_reviews']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/api/public/v1/user/reviews', name: 'api_get_user_reviews', methods: ['GET'])]
    #[OA\Tag(name: 'Reviews')]
    #[OA\Response(
        response: 200,
        description: 'Retourne les reviews de l\'utilisateur courant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Review::class, groups: ['review']))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Utilisateur non authentifié')
            ]
        )
    )]
    public function getUserReviews(Request $request, ReviewRepository $reviewRepository): JsonResponse
    {
        $user = $this->getUserFromToken($request);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $reviews = $reviewRepository->findByUser($user->getId());

        $data = $this->serializer->serialize($reviews, 'json', ['groups' => 'review']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/api/public/v1/review', name: 'api_create_review', methods: ['POST'])]
    #[OA\Tag(name: 'Reviews')]
    #[OA\RequestBody(
        description: 'Données de la review',
        required: true,
        content: new OA\JsonContent(
            required: ['productId', 'comment', 'rating'],
            properties: [
                new OA\Property(property: 'productId', type: 'integer', example: 1),
                new OA\Property(property: 'comment', type: 'string', example: 'Super produit !'),
                new OA\Property(property: 'rating', type: 'integer', example: 5)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Review créée avec succès',
        content: new OA\JsonContent(ref: new Model(type: Review::class, groups: ['review']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Données manquantes ou invalides',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Données manquantes'),
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), example: ['Le commentaire ne peut pas être vide'])
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Utilisateur non authentifié')
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
    #[OA\Response(
        response: 409,
        description: 'Review déjà existante',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous avez déjà laissé une review pour ce produit')
            ]
        )
    )]
    public function create(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $user = $this->getUserFromToken($request);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['productId'], $data['comment'], $data['rating'])) {
            return new JsonResponse(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $product = $productRepository->find($data['productId']);

        if (!$product) {
            return new JsonResponse(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur a déjà laissé une review pour ce produit
        $existingReview = $this->entityManager->getRepository(Review::class)
            ->findReviewByUserAndProduct($user->getId(), $product->getId());

        if ($existingReview) {
            return new JsonResponse(['error' => 'Vous avez déjà laissé une review pour ce produit'], Response::HTTP_CONFLICT);
        }

        $review = new Review();
        $review->setProduct($product);
        $review->setUser($user);
        $review->setComment($data['comment']);
        $review->setRating($data['rating']);

        $errors = $this->validator->validate($review);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        $data = $this->serializer->serialize($review, 'json', ['groups' => 'review']);

        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/public/v1/review/{id}', name: 'api_get_review', methods: ['GET'])]
    #[OA\Tag(name: 'Reviews')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID de la review',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne une review',
        content: new OA\JsonContent(ref: new Model(type: Review::class, groups: ['review']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Review non trouvée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Review non trouvée')
            ]
        )
    )]
    public function show(int $id, ReviewRepository $reviewRepository): JsonResponse
    {
        $review = $reviewRepository->find($id);

        if (!$review) {
            return new JsonResponse(['error' => 'Review non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($review, 'json', ['groups' => 'review']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/api/public/v1/review/{id}', name: 'api_update_review', methods: ['PUT'])]
    #[OA\Tag(name: 'Reviews')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID de la review à modifier',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Champs à modifier',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'comment', type: 'string', example: 'Nouveau commentaire'),
                new OA\Property(property: 'rating', type: 'integer', example: 4)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Review modifiée avec succès',
        content: new OA\JsonContent(ref: new Model(type: Review::class, groups: ['review']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), example: ['Le commentaire ne peut pas être vide'])
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Utilisateur non authentifié')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès non autorisé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Accès non autorisé')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Review non trouvée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Review non trouvée')
            ]
        )
    )]
    public function update(int $id, Request $request, ReviewRepository $reviewRepository): JsonResponse
    {
        $user = $this->getUserFromToken($request);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $review = $reviewRepository->find($id);

        if (!$review) {
            return new JsonResponse(['error' => 'Review non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est le propriétaire de la review
        if ($review->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['comment'])) {
            $review->setComment($data['comment']);
        }

        if (isset($data['rating'])) {
            $review->setRating($data['rating']);
        }

        $errors = $this->validator->validate($review);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $data = $this->serializer->serialize($review, 'json', ['groups' => 'review']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/api/public/v1/review/{id}', name: 'api_delete_review', methods: ['DELETE'])]
    #[OA\Tag(name: 'Reviews')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID de la review à supprimer',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Review supprimée avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Review supprimée avec succès')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Utilisateur non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Utilisateur non authentifié')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès non autorisé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Accès non autorisé')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Review non trouvée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Review non trouvée')
            ]
        )
    )]
    public function delete(int $id, Request $request, ReviewRepository $reviewRepository): JsonResponse
    {
        $user = $this->getUserFromToken($request);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $review = $reviewRepository->find($id);

        if (!$review) {
            return new JsonResponse(['error' => 'Review non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est le propriétaire de la review
        if ($review->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($review);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Review supprimée avec succès'], Response::HTTP_OK);
    }

    private function getUserFromToken(Request $request): ?User
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authorizationHeader, 7);

        try {
            $payload = $this->jwtManager->parse($token);
            $userId = $payload['id'] ?? null;

            if ($userId) {
                return $this->entityManager->getRepository(User::class)->find($userId);
            }
        } catch (JWTDecodeFailureException $e) {
            return null;
        }

        return null;
    }
}
