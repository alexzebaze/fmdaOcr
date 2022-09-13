<?php

namespace App\Entity;

use App\Repository\VehiculeKilometrageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VehiculeKilometrageRepository::class)
 */
class VehiculeKilometrage
{

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicule", inversedBy="kilometrages")
     * @ORM\JoinColumn(name="vehicule_id", referencedColumnName="id")
     */
    private $vehicule;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_releve;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $quantite;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReleve(): ?\DateTimeInterface
    {
        return $this->date_releve;
    }

    public function setDateReleve(?\DateTimeInterface $date_releve): self
    {
        $this->date_releve = $date_releve;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(?string $quantite): self
    {
        $this->quantite = $quantite;

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

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): self
    {
        $this->vehicule = $vehicule;

        return $this;
    }
}
