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

#[Route('/api')]
class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_show', methods: ['GET'])]
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