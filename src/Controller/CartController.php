<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Repository\CartRepository;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api')]
class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_show', methods: ['GET'])]
    #[OA\Tag(name: 'Cart')]
    #[OA\Response(
        response: 200,
        description: 'Retourne le panier de l\'utilisateur',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous devez être connecté pour accéder au panier')
            ]
        )
    )]
    public function show(
        CartRepository $cartRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour accéder au panier'], Response::HTTP_UNAUTHORIZED);
        }
        $cart = $cartRepository->findOrCreateByUser($user);

        $cartItems = [];
        foreach ($cart->getCartItems() as $item) {
            $cartItems[] = [
                'id' => $item->getId(),
                'product' => [
                    'id' => $item->getProduct()->getId(),
                    'name' => $item->getProduct()->getName(),
                    'price' => $item->getProduct()->getPrice()
                ],
                'quantity' => $item->getQuantity(),
                'subtotal' => $item->getSubtotal()
            ];
        }

        return new JsonResponse([
            'id' => $cart->getId(),
            'items' => $cartItems,
            'totalAmount' => $cart->getTotalAmount(),
            'totalItems' => $cart->getTotalItems()
        ], Response::HTTP_OK);
    }

    #[Route('/cart/items', name: 'cart_add_item', methods: ['POST'])]
    #[OA\Tag(name: 'Cart')]
    #[OA\RequestBody(
        description: 'Ajoute un article au panier',
        required: true,
        content: new OA\JsonContent(
            required: ['productId'],
            properties: [
                new OA\Property(property: 'productId', type: 'integer', example: 1),
                new OA\Property(property: 'quantity', type: 'integer', example: 2)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Article ajouté au panier',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Item added to cart successfully'),
                new OA\Property(property: 'cartItemId', type: 'integer', example: 10),
                new OA\Property(property: 'quantity', type: 'integer', example: 2)
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Product ID is required')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous devez être connecté pour ajouter des articles au panier')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Produit non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Product not found')
            ]
        )
    )]
    public function addItem(
        Request $request,
        CartRepository $cartRepository,
        CartItemRepository $cartItemRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['productId'])) {
            return new JsonResponse(['error' => 'Product ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $product = $productRepository->find($data['productId']);
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $quantity = $data['quantity'] ?? 1;
        
        if ($quantity <= 0) {
            return new JsonResponse(['error' => 'Quantity must be positive'], Response::HTTP_BAD_REQUEST);
        }

        if ($quantity > $product->getQuantity()) {
            return new JsonResponse(['error' => 'Insufficient stock'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour ajouter des articles au panier'], Response::HTTP_UNAUTHORIZED);
        }
        $cart = $cartRepository->findOrCreateByUser($user);

        // Check if the product is already in the cart
        $cartItem = $cartItemRepository->findByCartAndProduct($cart, $product);
        
        if ($cartItem) {
            $newQuantity = $cartItem->getQuantity() - $quantity;
            if ($newQuantity < 0) {
                return new JsonResponse(['error' => 'Insufficient stock'], Response::HTTP_BAD_REQUEST);
            }
            $cartItem->setQuantity($newQuantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $entityManager->persist($cartItem);
        }

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Item added to cart successfully',
            'cartItemId' => $cartItem->getId(),
            'quantity' => $cartItem->getQuantity()
        ], Response::HTTP_CREATED);
    }

    #[Route('/cart/items/{id}', name: 'cart_update_item', methods: ['PUT'])]
    #[OA\Tag(name: 'Cart')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID de l\'article du panier',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Met à jour la quantité d\'un article du panier',
        required: true,
        content: new OA\JsonContent(
            required: ['quantity'],
            properties: [
                new OA\Property(property: 'quantity', type: 'integer', example: 3)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Quantité de l\'article mise à jour',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Item quantity updated to cart successfully'),
                new OA\Property(property: 'cartItemId', type: 'integer', example: 10),
                new OA\Property(property: 'quantity', type: 'integer', example: 3)
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Valid quantity is required')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous devez être connecté pour modifier le panier')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès refusé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Article du panier non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Cart item not found')
            ]
        )
    )]
    public function updateItem(
        int $id,
        Request $request,
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour modifier le panier'], Response::HTTP_UNAUTHORIZED);
        }
        
        $cartItem = $cartItemRepository->find($id);
        
        if (!$cartItem) {
            return new JsonResponse(['error' => 'Cart item not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the cart item belongs to the current user
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $quantity = $data['quantity'] ?? null;

        if ($quantity === null || $quantity <= 0) {
            return new JsonResponse(['error' => 'Valid quantity is required'], Response::HTTP_BAD_REQUEST);
        }

        if ($quantity > $cartItem->getProduct()->getQuantity()) {
            return new JsonResponse(['error' => 'Insufficient stock'], Response::HTTP_BAD_REQUEST);
        }

        $cartItem->setQuantity($quantity);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Item quantity updated to cart successfully',
            'cartItemId' => $cartItem->getId(),
            'quantity' => $cartItem->getQuantity()
        ], Response::HTTP_OK);
    }

    #[Route('/cart/items/{id}', name: 'cart_remove_item', methods: ['DELETE'])]
    #[OA\Tag(name: 'Cart')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID de l\'article du panier à supprimer',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Article supprimé du panier',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Item removed from cart')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous devez être connecté pour supprimer des articles du panier')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Accès refusé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Access denied')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Article du panier non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Cart item not found')
            ]
        )
    )]
    public function removeItem(
        int $id,
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour supprimer des articles du panier'], Response::HTTP_UNAUTHORIZED);
        }
        
        $cartItem = $cartItemRepository->find($id);
        
        if (!$cartItem) {
            return new JsonResponse(['error' => 'Cart item not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the cart item belongs to the current user
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($cartItem);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Item removed from cart'], Response::HTTP_OK);
    }
}