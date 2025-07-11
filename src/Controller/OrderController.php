<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api')]
class OrderController extends AbstractController
{
    #[Route('/cart/validate', name: 'order_validate_cart', methods: ['POST'])]
    #[OA\Tag(name: 'Orders')]
    #[OA\Response(
        response: 201,
        description: 'Commandes créées avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Orders created successfully'),
                new OA\Property(property: 'orders', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Panier vide ou quantité invalide',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Cart is empty')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous devez être connecté pour valider votre panier')
            ]
        )
    )]
    public function validateCart(
        CartRepository $cartRepository,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour valider votre panier'], Response::HTTP_UNAUTHORIZED);
        }
        $cart = $cartRepository->findByUser($user);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            return new JsonResponse(['error' => 'Cart is empty'], Response::HTTP_BAD_REQUEST);
        }

        if ($cart->getTotalItems() < 1) {
            return new JsonResponse(['error' => 'Cart quantity must be at least 1'], Response::HTTP_BAD_REQUEST);
        }

        // Group cart items by farm
        $itemsByFarm = [];
        foreach ($cart->getCartItems() as $cartItem) {
            $farm = $cartItem->getProduct()->getFarm();
            if (!$farm) {
                continue;
            }
            
            $farmId = $farm->getId();
            if (!isset($itemsByFarm[$farmId])) {
                $itemsByFarm[$farmId] = [
                    'farm' => $farm,
                    'items' => []
                ];
            }
            $itemsByFarm[$farmId]['items'][] = $cartItem;
        }

        $orders = [];

        // Create an order for each farm
        foreach ($itemsByFarm as $farmData) {
            $order = new Order();
            $order->setUser($user);
            $order->setFarm($farmData['farm']);
            $order->setOrderNumber($orderRepository->generateOrderNumber());
            $order->setOrderStatus(Order::STATUS_VALIDATED);
            $order->setStatus('on');

            // Create order items
            foreach ($farmData['items'] as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setProduct($cartItem->getProduct());
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setUnitPrice((string) $cartItem->getProduct()->getPrice());
                
                $order->addOrderItem($orderItem);
            }

            // Calculate total amount
            $order->calculateTotalAmount();

            $entityManager->persist($order);
            $orders[] = $order;
        }

        // Clear the cart
        foreach ($cart->getCartItems() as $cartItem) {
            $entityManager->remove($cartItem);
        }

        $entityManager->flush();

        $ordersResponse = [];
        foreach ($orders as $order) {
            $orderItems = [];
            foreach ($order->getOrderItems() as $item) {
                $orderItems[] = [
                    'id' => $item->getId(),
                    'product' => [
                        'id' => $item->getProduct()->getId(),
                        'name' => $item->getProduct()->getName(),
                        'price' => $item->getProduct()->getPrice(),
                        'unitPrice' => $item->getProduct()->getUnitPrice(),
                        'quantity' => $item->getProduct()->getQuantity()
                    ],
                    'quantity' => $item->getQuantity(),
                    'unitPrice' => $item->getUnitPrice(),
                    'totalPrice' => $item->getTotalPrice()
                ];
            }

            $ordersResponse[] = [
                'id' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'status' => $order->getOrderStatus(),
                'totalAmount' => $order->getTotalAmount(),
                'farmId' => $order->getFarm()->getId(),
                'farmName' => $order->getFarm()->getName(),
                'orderItems' => $orderItems,
                'createdAt' => $order->getCreatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse([
            'message' => 'Orders created successfully',
            'orders' => $ordersResponse
        ], Response::HTTP_CREATED);
    }

    #[Route('/orders', name: 'order_list', methods: ['GET'])]
    #[OA\Tag(name: 'Orders')]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des commandes de l\'utilisateur',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(type: 'object')
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous devez être connecté pour voir vos commandes')
            ]
        )
    )]
    public function list(
        OrderRepository $orderRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour voir vos commandes'], Response::HTTP_UNAUTHORIZED);
        }
        $orders = $orderRepository->findByUser($user);

        $ordersResponse = [];
        foreach ($orders as $order) {
            $orderItems = [];
            foreach ($order->getOrderItems() as $item) {
                $orderItems[] = [
                    'id' => $item->getId(),
                    'product' => [
                        'id' => $item->getProduct()->getId(),
                        'name' => $item->getProduct()->getName(),
                        'price' => $item->getProduct()->getPrice(),
                        'unitPrice' => $item->getProduct()->getUnitPrice(),
                        'quantity' => $item->getProduct()->getQuantity()
                    ],
                    'quantity' => $item->getQuantity(),
                    'unitPrice' => $item->getUnitPrice(),
                    'totalPrice' => $item->getTotalPrice()
                ];
            }

            $ordersResponse[] = [
                'id' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'status' => $order->getOrderStatus(),
                'totalAmount' => $order->getTotalAmount(),
                'farmId' => $order->getFarm()->getId(),
                'farmName' => $order->getFarm()->getName(),
                'orderItems' => $orderItems,
                'createdAt' => $order->getCreatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($ordersResponse, Response::HTTP_OK);
    }

    #[Route('/orders/{id}', name: 'order_show', methods: ['GET'])]
    #[OA\Tag(name: 'Orders')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID de la commande',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne le détail d\'une commande',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(
        response: 401,
        description: 'Non authentifié',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Vous devez être connecté pour voir les détails d\'une commande')
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
        description: 'Commande non trouvée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Order not found')
            ]
        )
    )]
    public function show(
        int $id,
        OrderRepository $orderRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour voir les détails d\'une commande'], Response::HTTP_UNAUTHORIZED);
        }
        
        $order = $orderRepository->find($id);

        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the order belongs to the current user
        if ($order->getUser() !== $user) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $orderItems = [];
        foreach ($order->getOrderItems() as $item) {
            $orderItems[] = [
                'id' => $item->getId(),
                'product' => [
                    'id' => $item->getProduct()->getId(),
                    'name' => $item->getProduct()->getName(),
                    'price' => $item->getProduct()->getPrice(),
                    'unitPrice' => $item->getProduct()->getUnitPrice(),
                    'quantity' => $item->getProduct()->getQuantity()
                ],
                'quantity' => $item->getQuantity(),
                'unitPrice' => $item->getUnitPrice(),
                'totalPrice' => $item->getTotalPrice()
            ];
        }

        $orderResponse = [
            'id' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'status' => $order->getOrderStatus(),
            'totalAmount' => $order->getTotalAmount(),
            'farmId' => $order->getFarm()->getId(),
            'farmName' => $order->getFarm()->getName(),
            'orderItems' => $orderItems,
            'createdAt' => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $order->getUpdatedAt()?->format('Y-m-d H:i:s')
        ];

        return new JsonResponse($orderResponse, Response::HTTP_OK);
    }
}