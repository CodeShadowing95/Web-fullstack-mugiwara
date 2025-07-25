<?php

namespace App\Entity;

use App\Repository\PersonaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PersonaRepository::class)]
class Persona
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'persona', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?string $address = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?int $zipCode = null;

    #[ORM\Column(length: 25, nullable: true)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(["persona", "user:read", "review", "product_reviews"])]
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

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

    public function getZipCode(): ?int
    {
        return $this->zipCode;
    }

    public function setZipCode(?int $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

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
