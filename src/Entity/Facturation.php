<?php

namespace App\Entity;

use App\Repository\FacturationRepository;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=FacturationRepository::class)
 * @Vich\Uploadable
 */
class Facturation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numero;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prixht;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prixttc;

    /**
     * @Vich\UploadableField(mapping="facturation_file", fileNameProperty="imageName") 
     * @var File|null
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    private $imageName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;          

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $facturedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dueAt;

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseurs::class, inversedBy="facturations", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $fournisseur;

    /**
     * @ORM\ManyToOne(targetEntity=Tva::class, inversedBy="facturations", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $tva;

    /**
     * @ORM\ManyToOne(targetEntity=Devise::class, inversedBy="facturations", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $devise;

    /**
     * @ORM\ManyToOne(targetEntity=Paiement::class, inversedBy="facturations", fetch="EAGER")
     */
    private $reglement;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getPrixht(): ?float
    {
        return $this->prixht;
    }

    public function setPrixht(?float $prixht): self
    {
        $this->prixht = $prixht;

        return $this;
    }

    public function getPrixttc(): ?float
    {
        return $this->prixttc;
    }

    public function setPrixttc(?float $prixttc): self
    {
        $this->prixttc = $prixttc;

        return $this;
    }

    public function getFacturedAt(): ?\DateTimeInterface
    {
        return $this->facturedAt;
    }

    public function setFacturedAt($facturedAt): self
    {
        $this->facturedAt = $facturedAt;

        return $this;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDueAt(): ?\DateTimeInterface
    {
        return $this->dueAt;
    }

    public function setDueAt($dueAt): self
    {
        $this->dueAt = $dueAt;

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

    public function getTva(): ?Tva
    {
        return $this->tva;
    }

    public function setTva(?Tva $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): self
    {
        $this->devise = $devise;

        return $this;
    }

    public function getReglement(): ?Paiement
    {
        return $this->reglement;
    }

    public function setReglement(?Paiement $reglement): self
    {
        $this->reglement = $reglement;

        return $this;
    }
}
