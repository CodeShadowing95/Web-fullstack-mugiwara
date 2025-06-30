<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[Groups(["farm_products","product","category_details"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["farm_products","product","category_details"])]
    #[Assert\NotBlank(message: "Le nom du produit ne peut pas être vide")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(["farm_products","category_details"])]
    #[Assert\NotBlank(message: "La quantité ne peut pas être vide")]
    #[Assert\PositiveOrZero(message: "La quantité doit être positive ou nulle")]
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(["product_details", "farm"])]
    private ?Farm $farm = null;

    #[ORM\Column]
    #[Groups(["farm_products","product", "category_details"])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(["product", "farm_products", "category_details"])]
    private ?float $unitPrice = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(["product", "farm_products", "category_details"])]
    private ?Unity $unity = null;

    /**
     * @var Collection<int, ProductCategory>
     */
    #[ORM\ManyToMany(targetEntity: ProductCategory::class, inversedBy: 'products')]
    #[Groups(["product_details","product"])]
    private Collection $categories;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'products')]
    #[Groups(["farm_products","product","category_details"])]
    private Collection $tags;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(["farm_products","product","category_details"])]
    private bool $featured = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["product", "farm_products", "category_details"])]
    private ?string $origin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(["product", "farm_products", "category_details"])]
    private ?string $longDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["product", "farm_products", "category_details"])]
    private ?string $conservation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["product", "farm_products", "category_details"])]
    private ?string $preparationAdvice = null;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Review::class, orphanRemoval: true)]
    #[Groups(["product_reviews"])]
    private Collection $reviews;

    #[ORM\Column(nullable: true)]
    #[Groups(["farm_products","product", "category_details"])]
    private ?float $oldPrice = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(["product", "farm_products", "category_details"])]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(["product", "farm_products", "category_details"])]
    private ?int $stock = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, ProductCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(ProductCategory $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }
        return $this;
    }

    public function removeCategory(ProductCategory $category): static
    {
        $this->categories->removeElement($category);
        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function setTags(Collection $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(?string $origin): static
    {
        $this->origin = $origin;
        return $this;
    }

    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    public function setLongDescription(?string $longDescription): static
    {
        $this->longDescription = $longDescription;
        return $this;
    }

    public function getConservation(): ?string
    {
        return $this->conservation;
    }

    public function setConservation(?string $conservation): static
    {
        $this->conservation = $conservation;
        return $this;
    }

    public function getPreparationAdvice(): ?string
    {
        return $this->preparationAdvice;
    }

    public function setPreparationAdvice(?string $preparationAdvice): static
    {
        $this->preparationAdvice = $preparationAdvice;
        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setProduct($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getProduct() === $this) {
                $review->setProduct(null);
            }
        }

        return $this;
    }

    public function getAverageRating(): ?float
    {
        if ($this->reviews->isEmpty()) {
            return null;
        }

        $totalRating = 0;
        foreach ($this->reviews as $review) {
            $totalRating += $review->getRating();
        }

        return round($totalRating / $this->reviews->count(), 1);
    }

    public function getReviewsCount(): int
    {
        return $this->reviews->count();
    }

    public function getOldPrice(): ?float
    {
        return $this->oldPrice;
    }

    public function setOldPrice(?float $oldPrice): static
    {
        $this->oldPrice = $oldPrice;
        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }
}
