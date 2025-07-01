<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes\Property;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["review", "product_reviews"])]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Groups(["review", "product_reviews"])]
    #[Assert\NotBlank(message: "Le commentaire ne peut pas être vide")]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "Le commentaire doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(["review", "product_reviews"])]
    #[Assert\NotBlank(message: "La note ne peut pas être vide")]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "La note doit être comprise entre {{ min }} et {{ max }}"
    )]
    private ?int $rating = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["review"])]
    private ?Product $product = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["review", "product_reviews"])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(["review", "product_reviews"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(["review", "product_reviews"])]
    /**
     * @var \DateTimeImmutable|null
     */
    #[\OpenApi\Attributes\Property(type: "string", format: "date-time", nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
} 