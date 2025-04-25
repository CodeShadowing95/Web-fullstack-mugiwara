<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use App\Traits\StatisticsPropertiesTraits;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]

class Product
{
    use StatisticsPropertiesTraits;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["farm"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["farm"])]
    #[Assert\NotBlank(message: "Le nom du produit ne peut pas être vide")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(["farm"])]
    #[Assert\NotBlank(message: "La quantité ne peut pas être vide")]
    #[Assert\PositiveOrZero(message: "La quantité doit être positive ou nulle")]
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Farm $farm = null;

    #[ORM\Column]
    #[Groups(["farm"])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(["farm"])]
    private ?float $unitPrice = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Unity $unity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getFarm(): ?Farm
    {
        return $this->farm;
    }

    public function setFarm(?Farm $farm): static
    {
        $this->farm = $farm;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getUnity(): ?Unity
    {
        return $this->unity;
    }

    public function setUnity(?Unity $unity): static
    {
        $this->unity = $unity;

        return $this;
    }
}
