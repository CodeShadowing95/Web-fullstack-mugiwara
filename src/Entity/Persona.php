<?php

namespace App\Entity;

use App\Repository\PersonaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PersonaRepository::class)]
class Persona
{
    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'persona', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(length: 255)]
    private ?string $email = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $zipCode = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(length: 25, nullable: true)]
    private ?string $city = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    /**
     * @Groups({"persona", "user:read"})
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getZipCode(): ?int
    {
        return $this->zipCode;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setZipCode(?int $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setBirthDate(\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @Groups({"persona", "user:read"})
     */
    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'address' => $this->getAddress(),
            'zipCode' => $this->getZipCode(),
            'city' => $this->getCity(),
            'phoneNumber' => $this->getPhoneNumber(),
            'birthDate' => $this->getBirthDate()?->format('Y-m-d'),
            'gender' => $this->getGender(),
        ];
    }
}
