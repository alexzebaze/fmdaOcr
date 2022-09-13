<?php

namespace App\Entity;

use App\Repository\CtPlateauRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CtPlateauRepository::class)
 */
class CtPlateau
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateAchat;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class)
     */
    private $client;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $reglement;

    /**
     * @ORM\ManyToOne(targetEntity=Docu::class)
     */
    private $docu;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateAchat(): ?\DateTimeInterface
    {
        return $this->dateAchat;
    }

    public function setDateAchat(\DateTimeInterface $dateAchat): self
    {
        $this->dateAchat = $dateAchat;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getReglement(): ?float
    {
        return $this->reglement;
    }

    public function setReglement(?float $reglement): self
    {
        $this->reglement = $reglement;

        return $this;
    }

    public function getDocu(): ?Docu
    {
        return $this->docu;
    }

    public function setDocu(?Docu $docu): self
    {
        $this->docu = $docu;

        return $this;
    }
}
