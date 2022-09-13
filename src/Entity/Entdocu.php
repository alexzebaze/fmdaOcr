<?php

namespace App\Entity;

use App\Repository\EntdocuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EntdocuRepository::class)
 */
class Entdocu
{
    /**
     * @ORM\ManyToOne(targetEntity=Client::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $client;  
    
    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $entreprise;  

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=true)
     */
    private $chantier;

    /**
     * @ORM\ManyToOne(targetEntity=Lot::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $lot;  

    /**
     * @ORM\ManyToOne(targetEntity=Status::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $status;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $type_docu;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $document_id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $create_at;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description_tva;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_ht;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_tva_0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_tva_55;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_tva_10;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_tva_20;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_ttc;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $info;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $remise;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_net_ht;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $remise_percent;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description_travaux;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $echeance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $recapHtTva5;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $recapHtTva20;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $recapHtTva10;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $locked;

    public function __construct()
    {
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeDocu(): ?int
    {
        return $this->type_docu;
    }

    public function setTypeDocu(?int $type_docu): self
    {
        $this->type_docu = $type_docu;

        return $this;
    }

    public function getDocumentId(): ?string
    {
        return $this->document_id;
    }

    public function setDocumentId(?string $document_id): self
    {
        $this->document_id = $document_id;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->create_at;
    }

    public function setCreateAt($create_at): self
    {
        $this->create_at = $create_at;

        return $this;
    }

    public function getDescriptionTva(): ?string
    {
        return $this->description_tva;
    }

    public function setDescriptionTva(?string $description_tva): self
    {
        $this->description_tva = $description_tva;

        return $this;
    }

    public function getTotalHt(): ?float
    {
        return $this->total_ht;
    }

    public function setTotalHt(?float $total_ht): self
    {
        $this->total_ht = $total_ht;

        return $this;
    }

    public function getTotalTva0(): ?float
    {
        return $this->total_tva_0;
    }

    public function setTotalTva0(?float $total_tva_0): self
    {
        $this->total_tva_0 = $total_tva_0;

        return $this;
    }

    public function getTotalTva55(): ?float
    {
        return $this->total_tva_55;
    }

    public function setTotalTva55(?float $total_tva_55): self
    {
        $this->total_tva_55 = $total_tva_55;

        return $this;
    }

    public function getTotalTva10(): ?float
    {
        return $this->total_tva_10;
    }

    public function setTotalTva10(?float $total_tva_10): self
    {
        $this->total_tva_10 = $total_tva_10;

        return $this;
    }

    public function getTotalTva20(): ?float
    {
        return $this->total_tva_20;
    }

    public function setTotalTva20(?float $total_tva_20): self
    {
        $this->total_tva_20 = $total_tva_20;

        return $this;
    }

    public function getTotalTtc(): ?float
    {
        return $this->total_ttc;
    }

    public function setTotalTtc(?float $total_ttc): self
    {
        $this->total_ttc = $total_ttc;

        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): self
    {
        $this->info = $info;

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

    public function getChantier(): ?Chantier
    {
        return $this->chantier;
    }

    public function setChantier(?Chantier $chantier): self
    {
        $this->chantier = $chantier;

        return $this;
    }

    public function getLot(): ?Lot
    {
        return $this->lot;
    }

    public function setLot(?Lot $lot): self
    {
        $this->lot = $lot;

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

    public function getRemise(): ?float
    {
        return $this->remise;
    }

    public function setRemise(?float $remise): self
    {
        $this->remise = $remise;

        return $this;
    }

    public function getTotalNetHt(): ?float
    {
        return $this->total_net_ht;
    }

    public function setTotalNetHt(?float $total_net_ht): self
    {
        $this->total_net_ht = $total_net_ht;

        return $this;
    }

    public function getRemisePercent(): ?float
    {
        return $this->remise_percent;
    }

    public function setRemisePercent(?float $remise_percent): self
    {
        $this->remise_percent = $remise_percent;

        return $this;
    }

    public function getDescriptionTravaux()
    {
        return $this->description_travaux;
    }

    public function setDescriptionTravaux($description_travaux): self
    {
        $this->description_travaux = $description_travaux;

        return $this;
    }

    public function getEcheance(): ?\DateTimeInterface
    {
        return $this->echeance;
    }

    public function setEcheance(?\DateTimeInterface $echeance): self
    {
        $this->echeance = $echeance;

        return $this;
    }

    public function getRecapHtTva5(): ?float
    {
        return $this->recapHtTva5;
    }

    public function setRecapHtTva5(?float $recapHtTva5): self
    {
        $this->recapHtTva5 = $recapHtTva5;

        return $this;
    }

    public function getRecapHtTva20(): ?float
    {
        return $this->recapHtTva20;
    }

    public function setRecapHtTva20(?float $recapHtTva20): self
    {
        $this->recapHtTva20 = $recapHtTva20;

        return $this;
    }

    public function getRecapHtTva10(): ?float
    {
        return $this->recapHtTva10;
    }

    public function setRecapHtTva10(?float $recapHtTva10): self
    {
        $this->recapHtTva10 = $recapHtTva10;

        return $this;
    }

    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked): self
    {
        $this->locked = $locked;

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
