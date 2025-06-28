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

#[Route('/api/reviews')]
class ReviewController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('', name: 'app_review_index', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepository): JsonResponse
    {
        $reviews = $reviewRepository->findAll();
        
        $data = $this->serializer->serialize($reviews, 'json', ['groups' => 'review']);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/product/{id}', name: 'app_review_product', methods: ['GET'])]
    public function getProductReviews(int $id, ReviewRepository $reviewRepository): JsonResponse
    {
        $reviews = $reviewRepository->findByProduct($id);
        
        $data = $this->serializer->serialize($reviews, 'json', ['groups' => 'product_reviews']);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/user', name: 'app_review_user', methods: ['GET'])]
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

    #[Route('', name: 'app_review_create', methods: ['POST'])]
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

    #[Route('/{id}', name: 'app_review_show', methods: ['GET'])]
    public function show(int $id, ReviewRepository $reviewRepository): JsonResponse
    {
        $review = $reviewRepository->find($id);
        
        if (!$review) {
            return new JsonResponse(['error' => 'Review non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($review, 'json', ['groups' => 'review']);
        
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_review_update', methods: ['PUT'])]
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

    #[Route('/{id}', name: 'app_review_delete', methods: ['DELETE'])]
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