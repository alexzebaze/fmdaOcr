<?php

namespace App\Entity;

use App\Repository\FournisseurCompteRenduRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FournisseurCompteRenduRepository::class)
 */
class FournisseurCompteRendu
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @ORM\ManyToOne(targetEntity=CompteRendu::class, inversedBy="fournisseurs", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $compteRendu;

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseurs::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $fournisseur;

    /**
     * @ORM\ManyToOne(targetEntity=Status::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $status;

    public function getCompteRendu(): ?CompteRendu
    {
        return $this->compteRendu;
    }

    public function setCompteRendu(?CompteRendu $compteRendu): self
    {
        $this->compteRendu = $compteRendu;

        return $this;
    }

    public function getFournisseur(): ?Fournisseurs
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseurs $fournisseur): self
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

        return $this;
    }

}
