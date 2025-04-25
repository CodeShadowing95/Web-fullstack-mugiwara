<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_UUID', fields: ['uuid'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $uuid = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Farm>
     */
    // #[ORM\ManyToMany(targetEntity: Farm::class, inversedBy: 'users')]
    // private Collection $farm;

    /**
     * @var Collection<int, FarmUser>
     */
    #[ORM\OneToMany(targetEntity: FarmUser::class, mappedBy: 'user_id')]
    private Collection $farmUsers;

    public function __construct()
    {
        // $this->farm = new ArrayCollection();
        $this->farmUsers = new ArrayCollection();
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->uuid;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Farm>
     */
    // public function getFarm(): Collection
    // {
    //     return $this->farm;
    // }

    // public function addFarm(Farm $farm): static
    // {
    //     if (!$this->farm->contains($farm)) {
    //         $this->farm->add($farm);
    //     }

    //     return $this;
    // }

    // public function removeFarm(Farm $farm): static
    // {
    //     $this->farm->removeElement($farm);

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
            $farmUser->setUserId($this);
        }

        return $this;
    }

    public function removeFarmUser(FarmUser $farmUser): static
    {
        if ($this->farmUsers->removeElement($farmUser)) {
            // set the owning side to null (unless already changed)
            if ($farmUser->getUserId() === $this) {
                $farmUser->setUserId(null);
            }
        }

        return $this;
    }
}
