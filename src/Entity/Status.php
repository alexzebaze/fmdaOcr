<?php

namespace App\Entity;

use App\Repository\StatusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StatusRepository::class)
 */
class Status
{
    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="status")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $entreprise;

    /**
     * @var int
     *
     * @ORM\OneToMany(targetEntity="App\Entity\StatusModule", mappedBy="status", cascade={"persist"})
     */
    private $statusModules;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    public function __construct()
    {
        $this->statusModules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * @return Collection|StatusModule[]
     */
    public function getStatusModules(): Collection
    {
        return $this->statusModules;
    }

    public function addStatusModule(StatusModule $statusModule): self
    {
        if (!$this->statusModules->contains($statusModule)) {
            $this->statusModules[] = $statusModule;
            $statusModule->setStatus($this);
        }

        return $this;
    }

    public function removeStatusModule(StatusModule $statusModule): self
    {
        if ($this->statusModules->contains($statusModule)) {
            $this->statusModules->removeElement($statusModule);
            // set the owning side to null (unless already changed)
            if ($statusModule->getStatus() === $this) {
                $statusModule->setStatus(null);
            }
        }

        return $this;
    }


}
