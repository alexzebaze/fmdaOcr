<?php

namespace App\Entity;

use App\Repository\MetaConfigRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MetaConfigRepository::class)
 */
class MetaConfig
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $mkey;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $entreprise;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMkey(): ?string
    {
        return $this->mkey;
    }

    public function setMkey(string $mkey): self
    {
        $this->mkey = $mkey;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

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
}
