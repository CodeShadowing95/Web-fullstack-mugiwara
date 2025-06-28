<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_UUID', fields: ['uuid'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'review', 'product_reviews'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'review', 'product_reviews'])]
    private ?string $uuid = null;

    #[ORM\Column]
    #[Groups(['user:read', 'review', 'product_reviews'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null; // ⚠️ Ne pas exposer dans les groupes publics

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read', 'review', 'product_reviews'])]
    #[MaxDepth(2)]
    private ?Persona $persona = null;

    #[ORM\OneToMany(targetEntity: FarmUser::class, mappedBy: 'user_id')]
    private Collection $farmUsers;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    #[MaxDepth(1)]
    private ?Cart $cart = null;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user')]
    #[Groups(['user:read'])]
    private Collection $orders;

    public function __construct()
    {
        $this->farmUsers = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->persona?->getEmail() ?? '';
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si tu stockes un plainPassword temporaire, purge ici
    }

    public function getPersona(): ?Persona
    {
        return $this->persona;
    }

    public function setPersona(Persona $persona): static
    {
        if ($persona->getUser() !== $this) {
            $persona->setUser($this);
        }
        $this->persona = $persona;
        return $this;
    }

    public function getFarmUsers(): Collection
    {
        return $this->farmUsers;
    }

    public function addFarmUser(FarmUser $farmUser): static
    {
        if (!$this->farmUsers->contains($farmUser)) {
            $this->farmUsers->add($farmUser);
            $farmUser->setUserId($this);
        }
        return $this;
    }

    public function removeFarmUser(FarmUser $farmUser): static
    {
        if ($this->farmUsers->removeElement($farmUser)) {
            if ($farmUser->getUserId() === $this) {
                $farmUser->setUserId(null);
            }
        }
        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): static
    {
        if ($cart->getUser() !== $this) {
            $cart->setUser($this);
        }
        $this->cart = $cart;
        return $this;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }
        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }
        return $this;
    }
}
