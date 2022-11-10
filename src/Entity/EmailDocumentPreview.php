<?php

namespace App\Entity;

use App\Repository\EmailDocumentPreviewRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EmailDocumentPreviewRepository::class)
 */
class EmailDocumentPreview
{

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid", nullable=true)
     */
    private $utilisateur;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $entreprise;

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseurs::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $fournisseur;
    
    /**
     * @ORM\ManyToOne(targetEntity=Client::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=Tva::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $tva;

    /**
     * @ORM\ManyToOne(targetEntity=Devise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $devise;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=true)
     */
    private $chantier;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $document;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dossier;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $score;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $lu;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $document_id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prixttc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prixht;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $facturedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dueAt;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $conges_paye;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heure_sup_1;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heure_sup_2;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heure_normale;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $trajet;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $panier;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cout_global;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $salaire_net;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $date_paie;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $execute;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $create_at;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sender;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $extension;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sujet;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_convert;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $rotation;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $passageId;


    /**
     * @ORM\ManyToOne(targetEntity=ModelDocument::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $modelDocument;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $documentIdSource;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $documentIdPosition;

    public function __construct()
    {
        $this->lu = false;
        $this->execute = false;
        $this->is_convert = false;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(string $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getDossier(): ?string
    {
        return $this->dossier;
    }

    public function setDossier(string $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

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

    public function getLu(): ?bool
    {
        return $this->lu;
    }

    public function setLu(?bool $lu): self
    {
        $this->lu = $lu;

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

    public function getPrixttc(): ?float
    {
        return $this->prixttc;
    }

    public function setPrixttc(?float $prixttc): self
    {
        $this->prixttc = $prixttc;

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

    public function getFacturedAt(): ?\DateTimeInterface
    {
        return $this->facturedAt;
    }

    public function setFacturedAt(?\DateTimeInterface $facturedAt): self
    {
        $this->facturedAt = $facturedAt;

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

    public function getCongesPaye(): ?float
    {
        return $this->conges_paye;
    }

    public function setCongesPaye(?float $conges_paye): self
    {
        $this->conges_paye = $conges_paye;

        return $this;
    }

    public function getHeureSup1(): ?float
    {
        return $this->heure_sup_1;
    }

    public function setHeureSup1(?float $heure_sup_1): self
    {
        $this->heure_sup_1 = $heure_sup_1;

        return $this;
    }

    public function getHeureSup2(): ?float
    {
        return $this->heure_sup_2;
    }

    public function setHeureSup2(?float $heure_sup_2): self
    {
        $this->heure_sup_2 = $heure_sup_2;

        return $this;
    }

    public function getHeureNormale(): ?float
    {
        return $this->heure_normale;
    }

    public function setHeureNormale(?float $heure_normale): self
    {
        $this->heure_normale = $heure_normale;

        return $this;
    }

    public function getTrajet(): ?float
    {
        return $this->trajet;
    }

    public function setTrajet(?float $trajet): self
    {
        $this->trajet = $trajet;

        return $this;
    }

    public function getPanier(): ?float
    {
        return $this->panier;
    }

    public function setPanier(?float $panier): self
    {
        $this->panier = $panier;

        return $this;
    }

    public function getCoutGlobal(): ?float
    {
        return $this->cout_global;
    }

    public function setCoutGlobal(?float $cout_global): self
    {
        $this->cout_global = $cout_global;

        return $this;
    }

    public function getSalaireNet(): ?float
    {
        return $this->salaire_net;
    }

    public function setSalaireNet(?float $salaire_net): self
    {
        $this->salaire_net = $salaire_net;

        return $this;
    }

    public function getDatePaie(): ?string
    {
        return $this->date_paie;
    }

    public function setDatePaie(?string $date_paie): self
    {
        $this->date_paie = $date_paie;

        return $this;
    }

    public function getExecute(): ?bool
    {
        return $this->execute;
    }

    public function setExecute(?bool $execute): self
    {
        $this->execute = $execute;

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

    public function getFournisseur(): ?Fournisseurs
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseurs $fournisseur): self
    {
        $this->fournisseur = $fournisseur;

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

    public function getChantier(): ?Chantier
    {
        return $this->chantier;
    }

    public function setChantier(?Chantier $chantier): self
    {
        $this->chantier = $chantier;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->create_at;
    }

    public function setCreateAt(?\DateTimeInterface $create_at): self
    {
        $this->create_at = $create_at;

        return $this;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function setSender(?string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(?string $sujet): self
    {
        $this->sujet = $sujet;

        return $this;
    }

    public function getIsConvert(): ?bool
    {
        return $this->is_convert;
    }

    public function setIsConvert(?bool $is_convert): self
    {
        $this->is_convert = $is_convert;

        return $this;
    }

    public function getRotation(): ?bool
    {
        return $this->rotation;
    }

    public function setRotation(?bool $rotation): self
    {
        $this->rotation = $rotation;

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

    public function getPassageId(): ?int
    {
        return $this->passageId;
    }

    public function setPassageId(?int $passageId): self
    {
        $this->passageId = $passageId;

        return $this;
    }

    public function getModelDocument(): ?ModelDocument
    {
        return $this->modelDocument;
    }

    public function setModelDocument(?ModelDocument $modelDocument): self
    {
        $this->modelDocument = $modelDocument;

        return $this;
    }

    public function getDocumentIdSource(): ?int
    {
        return $this->documentIdSource;
    }

    public function setDocumentIdSource(?int $documentIdSource): self
    {
        $this->documentIdSource = $documentIdSource;

        return $this;
    }
   
    public function getDocumentIdPosition(): ?string
    {
        return $this->documentIdPosition;
    }

    public function setDocumentIdPosition(?string $documentIdPosition): self
    {
        $this->documentIdPosition = $documentIdPosition;

        return $this;
    } 
}
