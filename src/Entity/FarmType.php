<?php

namespace App\Entity;

use App\Repository\FarmTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FarmTypeRepository::class)]
class FarmType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["farm"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["farm"])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["farm"])]
    private ?string $description = null;

    /**
     * @var Collection<int, Farm>
     */
    #[ORM\ManyToMany(targetEntity: Farm::class, mappedBy: 'types')]
    private Collection $farms;

    public function __construct()
    {
        $this->farms = new ArrayCollection();
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
     * @return Collection<int, Farm>
     */
    public function getFarms(): Collection
    {
        return $this->farms;
    }

    public function addFarm(Farm $farm): static
    {
        if (!$this->farms->contains($farm)) {
            $this->farms->add($farm);
            $farm->addType($this);
        }

        return $this;
    }

    public function removeFarm(Farm $farm): static
    {
        if ($this->farms->removeElement($farm)) {
            $farm->removeType($this);
        }

        return $this;
    }
}
