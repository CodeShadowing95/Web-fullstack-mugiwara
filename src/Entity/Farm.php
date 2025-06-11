<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\FarmRepository;
use App\Traits\StatisticsPropertiesTraits;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: FarmRepository::class)]

class Farm
{
    use StatisticsPropertiesTraits;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["farm"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la ferme ne peut pas être vide")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Groups(["farm"])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "L'adresse ne peut pas être vide")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "L'adresse doit contenir au moins {{ limit }} caractères",
        maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Groups(["farm"])]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["farm"])]
    private ?string $description = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'farm')]
    #[Groups(["farm", "farm_products"])]
    private Collection $products;

    /**
     * @var Collection<int, User>
     */
    // #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'farm')]
    // private Collection $role;

    /**
     * @var Collection<int, FarmUser>
     */
    #[ORM\OneToMany(targetEntity: FarmUser::class, mappedBy: 'farm_id')]
    private Collection $farmUsers;

    #[ORM\Column(length: 5)]
    #[Groups(["farm"])]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(["farm"])]
    private ?string $city = null;

    /**
     * @var Collection<int, FarmType>
     */
    #[ORM\ManyToMany(targetEntity: FarmType::class, inversedBy: 'farms')]
    #[Groups(["farm"])]
    private Collection $types;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'farm')]
    private Collection $orders;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        // $this->role = new ArrayCollection();
        $this->farmUsers = new ArrayCollection();
        $this->types = new ArrayCollection();
        $this->orders = new ArrayCollection();
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setFarm($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getFarm() === $this) {
                $product->setFarm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    // public function getRole(): Collection
    // {
    //     return $this->role;
    // }

    // public function addRole(User $role): static
    // {
    //     if (!$this->role->contains($role)) {
    //         $this->role->add($role);
    //         $role->addFarm($this);
    //     }

    //     return $this;
    // }

    // public function removeRole(User $role): static
    // {
    //     if ($this->role->removeElement($role)) {
    //         $role->removeFarm($this);
    //     }

    //     return $this;
    // }

    /**
     * @return Collection<int, FarmUser>
     */
    public function getFarmUsers(): Collection
    {
        return $this->farmUsers;
    }

    public function addFarmUser(FarmUser $farmUser): static
    {
        if (!$this->farmUsers->contains($farmUser)) {
            $this->farmUsers->add($farmUser);
            $farmUser->setFarmId($this);
        }

        return $this;
    }

    public function removeFarmUser(FarmUser $farmUser): static
    {
        if ($this->farmUsers->removeElement($farmUser)) {
            // set the owning side to null (unless already changed)
            if ($farmUser->getFarmId() === $this) {
                $farmUser->setFarmId(null);
            }
        }

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return Collection<int, FarmType>
     */
    public function getTypes(): Collection
    {
        return $this->types;
    }

    public function addType(FarmType $type): static
    {
        if (!$this->types->contains($type)) {
            $this->types->add($type);
        }

        return $this;
    }

    public function removeType(FarmType $type): static
    {
        $this->types->removeElement($type);

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setFarm($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getFarm() === $this) {
                $order->setFarm(null);
            }
        }

        return $this;
    }
}
