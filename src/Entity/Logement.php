<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogementRepository::class)
 */
class Logement
{
    
    /**
     * @ORM\ManyToMany(targetEntity="Client")
     */
    private $acquereurs;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=true)
     */
    private $chantier;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $identifiant;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $batiment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $escalier;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $etage;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $numero;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ville;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code_postal;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $region;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pays;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $superficie;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nombre_piece;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nombre_chambre;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $annee_construction;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $photos;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prix;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $web;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $permalien;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stationnement;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cave;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $superficie_cave;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $exposition;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $balcon;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $superficie_balcon;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $superficie_terrasse;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $terrasse;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cellier;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $normes;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nombre_wc;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $etat;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $annee_acquisition;

    public function __construct()
    {
        $this->acquereurs = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifiant(): ?string
    {
        return $this->identifiant;
    }

    public function setIdentifiant(?string $identifiant): self
    {
        $this->identifiant = $identifiant;

        return $this;
    }

    public function getBatiment(): ?string
    {
        return $this->batiment;
    }

    public function setBatiment(?string $batiment): self
    {
        $this->batiment = $batiment;

        return $this;
    }

    public function getEscalier(): ?string
    {
        return $this->escalier;
    }

    public function setEscalier(?string $escalier): self
    {
        $this->escalier = $escalier;

        return $this;
    }

    public function getEtage(): ?string
    {
        return $this->etage;
    }

    public function setEtage(?string $etage): self
    {
        $this->etage = $etage;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->code_postal;
    }

    public function setCodePostal(?string $code_postal): self
    {
        $this->code_postal = $code_postal;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getSuperficie(): ?int
    {
        return $this->superficie;
    }

    public function setSuperficie(?int $superficie): self
    {
        $this->superficie = $superficie;

        return $this;
    }

    public function getNombrePiece(): ?string
    {
        return $this->nombre_piece;
    }

    public function setNombrePiece(?string $nombre_piece): self
    {
        $this->nombre_piece = $nombre_piece;

        return $this;
    }

    public function getNombreChambre(): ?int
    {
        return $this->nombre_chambre;
    }

    public function setNombreChambre(?int $nombre_chambre): self
    {
        $this->nombre_chambre = $nombre_chambre;

        return $this;
    }

    public function getAnneeConstruction(): ?int
    {
        return $this->annee_construction;
    }

    public function setAnneeConstruction(?int $annee_construction): self
    {
        $this->annee_construction = $annee_construction;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

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

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getPhotos(): ?string
    {
        return $this->photos;
    }

    public function setPhotos(?string $photos): self
    {
        $this->photos = $photos;

        return $this;
    }

    public function unserializePhotos(){
        $imageArr = unserialize($this->photos); 
        if(!empty($imageArr))
            return $imageArr;
        return [];
    }
    public function __toString(){
        return $this->identifiant."- ".$this->type." - ".$this->code_postal;
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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setWeb(?string $web): self
    {
        $this->web = $web;

        return $this;
    }

    public function getPermalien(): ?string
    {
        return $this->permalien;
    }

    public function setPermalien(?string $permalien): self
    {
        $this->permalien = $permalien;

        return $this;
    }

    public function getStationnement(): ?string
    {
        return $this->stationnement;
    }

    public function setStationnement(?string $stationnement): self
    {
        $this->stationnement = $stationnement;

        return $this;
    }

    public function getCave(): ?string
    {
        return $this->cave;
    }

    public function setCave(?string $cave): self
    {
        $this->cave = $cave;

        return $this;
    }

    public function getSuperficieCave(): ?string
    {
        return $this->superficie_cave;
    }

    public function setSuperficieCave(?string $superficie_cave): self
    {
        $this->superficie_cave = $superficie_cave;

        return $this;
    }

    public function getExposition(): ?string
    {
        return $this->exposition;
    }

    public function setExposition(?string $exposition): self
    {
        $this->exposition = $exposition;

        return $this;
    }

    public function getBalcon(): ?string
    {
        return $this->balcon;
    }

    public function setBalcon(?string $balcon): self
    {
        $this->balcon = $balcon;

        return $this;
    }

    public function getSuperficieBalcon(): ?string
    {
        return $this->superficie_balcon;
    }

    public function setSuperficieBalcon(?string $superficie_balcon): self
    {
        $this->superficie_balcon = $superficie_balcon;

        return $this;
    }

    public function getSuperficieTerrasse(): ?string
    {
        return $this->superficie_terrasse;
    }

    public function setSuperficieTerrasse(?string $superficie_terrasse): self
    {
        $this->superficie_terrasse = $superficie_terrasse;

        return $this;
    }

    public function getTerrasse(): ?string
    {
        return $this->terrasse;
    }

    public function setTerrasse(?string $terrasse): self
    {
        $this->terrasse = $terrasse;

        return $this;
    }

    public function getCellier(): ?string
    {
        return $this->cellier;
    }

    public function setCellier(?string $cellier): self
    {
        $this->cellier = $cellier;

        return $this;
    }

    public function getNormes(): ?string
    {
        return $this->normes;
    }

    public function setNormes(?string $normes): self
    {
        $this->normes = $normes;

        return $this;
    }

    public function getNombreWc(): ?int
    {
        return $this->nombre_wc;
    }

    public function setNombreWc(?int $nombre_wc): self
    {
        $this->nombre_wc = $nombre_wc;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getAnneeAcquisition(): ?string
    {
        return $this->annee_acquisition;
    }

    public function setAnneeAcquisition(?string $annee_acquisition): self
    {
        $this->annee_acquisition = $annee_acquisition;

        return $this;
    }

    /**
     * @return Collection|Client[]
     */
    public function getAcquereurs(): Collection
    {
        return $this->acquereurs;
    }

    public function addAcquereur(Client $acquereur): self
    {
        if (!$this->acquereurs->contains($acquereur)) {
            $this->acquereurs[] = $acquereur;
        }

        return $this;
    }

    public function removeAcquereur(Client $acquereur): self
    {
        if ($this->acquereurs->contains($acquereur)) {
            $this->acquereurs->removeElement($acquereur);
        }

        return $this;
    }
}
