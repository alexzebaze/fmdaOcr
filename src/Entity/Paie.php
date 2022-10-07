<?php

namespace App\Entity;

use App\Repository\PaieRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use JsonSerializable;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=PaieRepository::class)
 */
class Paie implements JsonSerializable
{
    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="paies")
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid")
     */
    private $utilisateur;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

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
    private $panier;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $trajet;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cout_global;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $salaire_net;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $conges_paye;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $document_file;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heure_normale;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rossum_document_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $date_paie;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="paies", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $entreprise;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $tx_horaire;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $heure_fictif;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $tx_moyen;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_paie2;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTrajet(): ?float
    {
        return $this->trajet;
    }

    public function setTrajet(?float $trajet): self
    {
        $this->trajet = $trajet;

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

    public function getCongesPaye(): ?float
    {
        return $this->conges_paye;
    }

    public function setCongesPaye(?float $conges_paye): self
    {
        $this->conges_paye = $conges_paye;

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

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

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

    public function getRossumDocumentId(): ?string
    {
        return $this->rossum_document_id;
    }

    public function setRossumDocumentId(?string $rossum_document_id): self
    {
        $this->rossum_document_id = $rossum_document_id;

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

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getTxHoraire(): ?float
    {
        return $this->tx_horaire;
    }

    public function setTxHoraire(?float $tx_horaire): self
    {
        $this->tx_horaire = $tx_horaire;

        return $this;
    }

    public function getHeureFictif(): ?float
    {
        return $this->heure_fictif;
    }

    public function setHeureFictif(?float $heure_fictif): self
    {
        $this->heure_fictif = $heure_fictif;

        return $this;
    }

    public function getTxMoyen(): ?float
    {
        return $this->tx_moyen;
    }

    public function setTxMoyen(?float $tx_moyen): self
    {
        $this->tx_moyen = $tx_moyen;

        return $this;
    }

    public function getDatePaie2(): ?\DateTimeInterface
    {
        return $this->date_paie2;
    }

    public function setDatePaie2(?\DateTimeInterface $date_paie2): self
    {
        $this->date_paie2 = $date_paie2;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'date_paie' => $this->date_paie,
            'document_file' => $this->document_file,
            'conges_paye'=> $this->conges_paye,
            'heure_sup_1'=> $this->heure_sup_1,
            'heure_sup_2'=> $this->heure_sup_2,
            'heure_normale'=> $this->heure_normale,
            'panier'=> $this->panier,
            'trajet'=> $this->trajet,
            'cout_global'=> $this->cout_global,
            'salaire_net'=> $this->salaire_net,
            'tx_horaire'=> $this->tx_horaire,
            'utilisateur'=> $this->getUtilisateur()->getUid(),
        );
    }

}
