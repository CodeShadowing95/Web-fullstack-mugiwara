<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'ville_france')]
class VilleFrance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ville'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ville'])]
    private ?string $nom = null;

    #[ORM\Column(length: 10)]
    #[Groups(['ville'])]
    private ?string $code = null;

    #[ORM\Column(length: 10)]
    #[Groups(['ville'])]
    private ?string $codeDepartement = null;

    #[ORM\Column(length: 10)]
    #[Groups(['ville'])]
    private ?string $codeRegion = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['ville'])]
    private array $codesPostaux = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getCodeDepartement(): ?string
    {
        return $this->codeDepartement;
    }

    public function setCodeDepartement(string $codeDepartement): static
    {
        $this->codeDepartement = $codeDepartement;
        return $this;
    }

    public function getCodeRegion(): ?string
    {
        return $this->codeRegion;
    }

    public function setCodeRegion(string $codeRegion): static
    {
        $this->codeRegion = $codeRegion;
        return $this;
    }

    public function getCodesPostaux(): array
    {
        return $this->codesPostaux;
    }

    public function setCodesPostaux(array $codesPostaux): static
    {
        $this->codesPostaux = $codesPostaux;
        return $this;
    }
}