<?php

namespace App\Entity;

use App\Repository\OutillageAutorizationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OutillageAutorizationRepository::class)
 */
class OutillageAutorization
{
    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid")
     */
    private $utilisateur;

    /**
     * @ORM\ManyToOne(targetEntity=Outillage::class)
     */
    private $outillage;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_depart;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_retour;

    /**
     * @ORM\Column(type="boolean")
     */
    private $autorisation;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $document_signe;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $periode;

    public function __construct()
    {
        $this->date_depart = new \DateTime();
        $this->autorisation = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->date_depart;
    }

    public function setDateDepart(\DateTimeInterface $date_depart): self
    {
        $this->date_depart = $date_depart;

        return $this;
    }

    public function getDateRetour(): ?\DateTimeInterface
    {
        return $this->date_retour;
    }

    public function setDateRetour(?\DateTimeInterface $date_retour): self
    {
        $this->date_retour = $date_retour;

        return $this;
    }

    public function getAutorisation(): ?bool
    {
        return $this->autorisation;
    }

    public function setAutorisation(bool $autorisation): self
    {
        $this->autorisation = $autorisation;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getOutillage(): ?Outillage
    {
        return $this->outillage;
    }

    public function setOutillage(?Outillage $outillage): self
    {
        $this->outillage = $outillage;

        return $this;
    }

    public function getDocumentSigne(): ?string
    {
        return $this->document_signe;
    }

    public function setDocumentSigne(?string $document_signe): self
    {
        $this->document_signe = $document_signe;

        return $this;
    }

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(?string $periode): self
    {
        $this->periode = $periode;

        return $this;
    }
}
