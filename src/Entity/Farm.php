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

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["farm"])]
    private ?string $region = null;

    #[ORM\Column(type: "json", nullable: true)]
    #[Groups(["farm"])]
    private array $coordinates = [];

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(["farm"])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["farm"])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["farm"])]
    private ?string $website = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(["farm"])]
    private ?string $farmSize = null;

    #[ORM\Column(type: "json", nullable: true)]
    #[Groups(["farm"])]
    private array $mainProducts = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["farm"])]
    private ?string $seasonality = null;

    #[ORM\Column(type: "json", nullable: true)]
    #[Groups(["farm"])]
    private array $deliveryZones = [];

    #[ORM\Column(type: "json", nullable: true)]
    #[Groups(["farm"])]
    private array $deliveryMethods = [];

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(["farm"])]
    private ?string $minimumOrder = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["farm"])]
    private ?string $profileImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["farm"])]
    private ?string $avatar = null;

    #[ORM\Column(type: "json", nullable: true)]
    #[Groups(["farm"])]
    private array $galleryImages = [];

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
     * @var Collection<int, User>
     */
    // #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'farm')]
    // private Collection $role;

    /**
     * @var Collection<int, FarmUser>
     */
    #[ORM\OneToMany(targetEntity: FarmUser::class, mappedBy: 'farm')]
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


    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;
        return $this;
    }

    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

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
            $farmUser->setFarm($this);
        }

        return $this;
    }

    public function removeFarmUser(FarmUser $farmUser): static
    {
        if ($this->farmUsers->removeElement($farmUser)) {
            // set the owning side to null (unless already changed)
            if ($farmUser->getFarm() === $this) {
                $farmUser->setFarm(null);
            }
        }

        return $this;
     }

     public function setCoordinates(array $coordinates): static
     {
        $this->coordinates = $coordinates;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getFarmSize(): ?string
    {
        return $this->farmSize;
    }

    public function setFarmSize(?string $farmSize): static
    {
        $this->farmSize = $farmSize;
        return $this;
    }

    public function getMainProducts(): array
    {
        return $this->mainProducts;
    }

    public function setMainProducts(array $mainProducts): static
    {
        $this->mainProducts = $mainProducts;
        return $this;
    }

    public function getSeasonality(): ?string
    {
        return $this->seasonality;
    }

    public function setSeasonality(?string $seasonality): static
    {
        $this->seasonality = $seasonality;
        return $this;
    }

    public function getDeliveryZones(): array
    {
        return $this->deliveryZones;
    }

    public function setDeliveryZones(array $deliveryZones): static
    {
        $this->deliveryZones = $deliveryZones;
        return $this;
    }

    public function getDeliveryMethods(): array
    {
        return $this->deliveryMethods;
    }

    public function setDeliveryMethods(array $deliveryMethods): static
    {
        $this->deliveryMethods = $deliveryMethods;
        return $this;
    }

    public function getMinimumOrder(): ?string
    {
        return $this->minimumOrder;
    }

    public function setMinimumOrder(?string $minimumOrder): static
    {
        $this->minimumOrder = $minimumOrder;
        return $this;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getGalleryImages(): array
    {
        return $this->galleryImages;
    }

    public function setGalleryImages(array $galleryImages): static
    {
        $this->galleryImages = $galleryImages;
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
