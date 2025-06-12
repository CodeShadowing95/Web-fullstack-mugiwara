<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['cart', 'cart_details', 'cart_item'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['cart', 'cart_details', 'cart_item'])]
    private ?Product $product = null;

    #[ORM\Column]
    #[Groups(['cart', 'cart_details', 'cart_item'])]
    #[Assert\Positive(message: 'La quantité doit être positive')]
    #[Assert\NotNull(message: 'La quantité est requise')]
    private ?int $quantity = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    #[Groups(['cart', 'cart_details', 'cart_item'])]
    public function getSubtotal(): float
    {
        return $this->product ? $this->product->getPrice() * $this->quantity : 0;
    }
}