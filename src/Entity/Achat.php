<?php

namespace App\Entity;

use App\Repository\AchatRepository;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass=AchatRepository::class)
 * @Vich\Uploadable
* @ORM\Table(name="achat")
 */
class Achat implements JsonSerializable
{
    /**
     * @ORM\ManyToOne(targetEntity=Status::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $status;

    /**
    * @ORM\OneToOne(targetEntity=Passage::class, mappedBy="bon_livraison")
    */
    protected $passage;

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
     * @ORM\Column(type="datetime")
     */
    private $facturedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dueAt;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="achats", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $entreprise;

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseurs::class, inversedBy="achats", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $fournisseur;
    
    /**
     * @ORM\ManyToOne(targetEntity=Tva::class, inversedBy="achats", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $tva;

    /**
     * @ORM\ManyToOne(targetEntity=Devise::class, inversedBy="achats", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $devise;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $document_id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier", inversedBy="achats")
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fournisseur_name;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bl_validation;

    /**
     * @ORM\ManyToOne(targetEntity=Reglement::class, inversedBy="achats")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $reglement;

    /**
     * @ORM\ManyToOne(targetEntity=Vente::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $devis;    

    /**
     * @ORM\ManyToOne(targetEntity=Achat::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $devis_pro;

    /**
     * @ORM\ManyToOne(targetEntity=Lot::class, inversedBy="achats", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lot;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $budget;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $automatique;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_generate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $echeance;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $caution;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code_compta;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $due_date_generate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $jour_generate;

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

    public function getFournisseurName(): ?string
    {
        return $this->fournisseur_name;
    }

    public function setFournisseurName(?string $fournisseur_name): self
    {
        $this->fournisseur_name = $fournisseur_name;

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

    public function getDevis(): ?Vente
    {
        return $this->devis;
    }

    public function setDevis(?Vente $devis): self
    {
        $this->devis = $devis;

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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(?float $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    public function getDevisPro(): ?self
    {
        return $this->devis_pro;
    }

    public function setDevisPro(?self $devis_pro): self
    {
        $this->devis_pro = $devis_pro;

        return $this;
    }

    public function getAutomatique(): ?bool
    {
        return $this->automatique;
    }

    public function setAutomatique(?bool $automatique): self
    {
        $this->automatique = $automatique;

        return $this;
    }

    public function getDateGenerate(): ?\DateTimeInterface
    {
        return $this->date_generate;
    }

    public function setDateGenerate(?\DateTimeInterface $date_generate): self
    {
        $this->date_generate = $date_generate;

        return $this;
    }

    public function getEcheance(): ?int
    {
        return $this->echeance;
    }

    public function setEcheance(?int $echeance): self
    {
        $this->echeance = $echeance;

        return $this;
    }

    public function getCaution(): ?string
    {
        return $this->caution;
    }

    public function setCaution(?string $caution): self
    {
        $this->caution = $caution;

        return $this;
    }

    public function getCodeCompta(): ?string
    {
        return $this->code_compta;
    }

    public function setCodeCompta(?string $code_compta): self
    {
        $this->code_compta = $code_compta;

        return $this;
    }

    public function getDueDateGenerate(): ?\DateTimeInterface
    {
        return $this->due_date_generate;
    }

    public function setDueDateGenerate(?\DateTimeInterface $due_date_generate): self
    {
        $this->due_date_generate = $due_date_generate;

        return $this;
    }

    public function getPassage(): ?Passage
    {
        return $this->passage;
    }

    public function setPassage(?Passage $passage): self
    {
        $this->passage = $passage;

        // set (or unset) the owning side of the relation if necessary
        $newBon_livraison = null === $passage ? null : $this;
        if ($passage->getBonLivraison() !== $newBon_livraison) {
            $passage->setBonLivraison($newBon_livraison);
        }

        return $this;
    }

    public function getJourGenerate(): ?int
    {
        return $this->jour_generate;
    }

    public function setJourGenerate(?int $jour_generate): self
    {
        $this->jour_generate = $jour_generate;

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
            'codeCompta'=> $this->code_compta,
            'tva'=> $this->tva,
            'fournisseur'=> $this->fournisseur,
            'chantier'=> $this->chantier,
            'lot'=> $this->lot,
        );
    }

}
