<?php

namespace App\Entity;

use App\Repository\VenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Carbon\Carbon;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass=VenteRepository::class)
 */
class Vente implements JsonSerializable
{
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
     * @ORM\Column(type="datetime")
     */
    private $facturedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dueAt;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="ventes", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $entreprise;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="ventes", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=Tva::class, inversedBy="ventes", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $tva;

    /**
     * @ORM\ManyToOne(targetEntity=Devise::class, inversedBy="ventes", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $devise;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $document_id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier", inversedBy="ventes")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=true)
     */
    private $chantier;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rossum_document_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $document_file;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $mtva;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $acompte;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $solde;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $export_compta;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_export_compta;    

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $export_quittance;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_export_quittance;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bl_validation;

    /**
     * @ORM\ManyToOne(targetEntity=Reglement::class, inversedBy="ventes")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $reglement;

    /**
     * @ORM\ManyToOne(targetEntity=Lot::class, inversedBy="ventes", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $lot;

    /**
     * @ORM\ManyToOne(targetEntity=Vente::class, inversedBy="factures", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $devis;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Vente",mappedBy="devis", cascade={"persist"})
     */
    private $factures;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $attestation;

    public function __construct()
    {
        $this->factures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setFacturedAt(\DateTimeInterface $facturedAt): self
    {
        $this->facturedAt = $facturedAt;

        return $this;
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

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDueAt(): ?\DateTimeInterface
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTimeInterface $dueAt): self
    {
        $this->dueAt = $dueAt;

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

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

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

    public function getChantier(): ?Chantier
    {
        return $this->chantier;
    }

    public function setChantier(?Chantier $chantier): self
    {
        $this->chantier = $chantier;

        return $this;
    }

    public function getRossumDocumentId(): ?string
    {
        return $this->rossum_document_id;
    }

    public function setRossumDocumentId(?string $rossum_document_id): self
    {
        $this->rossum_document_id = $rossum_document_id;

        return $this;
    }

    public function getDocumentFile(): ?string
    {
        return $this->document_file;
    }

    public function setDocumentFile(?string $document_file): self
    {
        $this->document_file = $document_file;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

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

    public function getMtva(): ?float
    {
        return $this->mtva;
    }

    public function setMtva(?float $mtva): self
    {
        $this->mtva = $mtva;

        return $this;
    }

    public function getAcompte(): ?int
    {
        return $this->acompte;
    }

    public function setAcompte(?int $acompte): self
    {
        $this->acompte = $acompte;

        return $this;
    }

    public function getSolde(): ?int
    {
        return $this->solde;
    }

    public function setSolde(?int $solde): self
    {
        $this->solde = $solde;

        return $this;
    }

    public function getExportCompta(): ?int
    {
        return $this->export_compta;
    }

    public function setExportCompta(?int $export_compta): self
    {
        $this->export_compta = $export_compta;

        return $this;
    }

    public function getDateExportCompta(): ?\DateTimeInterface
    {
        return $this->date_export_compta;
    }

    public function setDateExportCompta(?\DateTimeInterface $date_export_compta): self
    {
        $this->date_export_compta = $date_export_compta;

        return $this;
    }

    public function getReglement(): ?Reglement
    {
        return $this->reglement;
    }

    public function setReglement(?Reglement $reglement): self
    {
        $this->reglement = $reglement;

        return $this;
    }

    public function getBlValidation(): ?string
    {
        return $this->bl_validation;
    }

    public function setBlValidation(?string $bl_validation): self
    {
        $this->bl_validation = $bl_validation;

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
    public function __toString(){
        $lot = !is_null($this->getLot()) ? ' - '.$this->getLot()->getLot() : "";
        return '#'.$this->getDocumentId().'-'.($this->getClient() ? $this->getClient()->getNom() : "").$lot.' - '.$this->getPrixttc().$this->getDevise()->getSymbol()." - ".Carbon::parse(($this->getFacturedAt())->format('Y-m-d'))->locale('fr')->isoFormat('MMMM YYYY');
    }

    public function getDevis(): ?self
    {
        return $this->devis;
    }

    public function setDevis(?self $devis): self
    {
        $this->devis = $devis;

        return $this;
    }

    /**
     * @return Collection|Vente[]
     */
    public function getFactures(): Collection
    {
        return $this->factures;
    }

    public function addFacture(Vente $facture): self
    {
        if (!$this->factures->contains($facture)) {
            $this->factures[] = $facture;
            $facture->setDevis($this);
        }

        return $this;
    }

    public function removeFacture(Vente $facture): self
    {
        if ($this->factures->contains($facture)) {
            $this->factures->removeElement($facture);
            // set the owning side to null (unless already changed)
            if ($facture->getDevis() === $this) {
                $facture->setDevis(null);
            }
        }

        return $this;
    }

    public function getAttestation(): ?string
    {
        return $this->attestation;
    }

    public function setAttestation(?string $attestation): self
    {
        $this->attestation = $attestation;

        return $this;
    }

    public function getExportQuittance(): ?int
    {
        return $this->export_quittance;
    }

    public function setExportQuittance(?int $export_quittance): self
    {
        $this->export_quittance = $export_quittance;

        return $this;
    }

    public function getDateExportQuittance(): ?\DateTimeInterface
    {
        return $this->date_export_quittance;
    }

    public function setDateExportQuittance(?\DateTimeInterface $date_export_quittance): self
    {
        $this->date_export_quittance = $date_export_quittance;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'type' => $this->type,
            'documentId'=> $this->document_id,
            'facturedAt'=> $this->facturedAt,
            'documentFile'=> $this->document_file,
            'prixht'=> $this->prixht,
            'prixttc'=> $this->prixttc,
            'tva'=> $this->tva,
            'client'=> $this->client,
            'chantier'=> $this->chantier,
            'lot'=> $this->lot,
        );
    }

}
