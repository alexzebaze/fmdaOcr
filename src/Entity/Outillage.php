<?php

namespace App\Entity;

use App\Repository\OutillageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
 
/**
 * @ORM\Entity(repositoryClass=OutillageRepository::class)
 */
class Outillage
{
    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="outillages")
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid", nullable=true, onDelete="SET NULL")
     */
    private $utilisateur;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="outillages")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $entreprise;

    /**
     * @var string
     * @ORM\OneToMany(targetEntity="App\Entity\OutillageAutorization",mappedBy="outillage", cascade={"persist"})
     */
    private $outillagesAuth;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $marque;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gestion;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $mise_en_service;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $derniere_inspection;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $proprietaire;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $modele;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $num_serie;

    /**
     * @ORM\Column(type="boolean")
     */
    private $libre;

    function __construct()
    {
        $this->libre = true;
        $this->outillagesAuth = new ArrayCollection();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(?string $marque): self
    {
        $this->marque = $marque;

        return $this;
    }

    public function getGestion(): ?string
    {
        return $this->gestion;
    }

    public function setGestion(?string $gestion): self
    {
        $this->gestion = $gestion;

        return $this;
    }

    public function getMiseEnService(): ?\DateTimeInterface
    {
        return $this->mise_en_service;
    }

    public function setMiseEnService(?\DateTimeInterface $mise_en_service): self
    {
        $this->mise_en_service = $mise_en_service;

        return $this;
    }

    public function getDerniereInspection(): ?\DateTimeInterface
    {
        return $this->derniere_inspection;
    }

    public function setDerniereInspection(?\DateTimeInterface $derniere_inspection): self
    {
        $this->derniere_inspection = $derniere_inspection;

        return $this;
    }

    public function getProprietaire(): ?string
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?string $proprietaire): self
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;

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

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): self
    {
        $this->modele = $modele;

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

    public function getNumSerie(): ?string
    {
        return $this->num_serie;
    }

    public function setNumSerie(?string $num_serie): self
    {
        $this->num_serie = $num_serie;

        return $this;
    }

    public function serializeFirstImage(){
        $imageArr = unserialize($this->image);
        if($imageArr && count($imageArr) > 0){
            if(array_key_exists(0, $imageArr))
                return $imageArr[0];
        }

        return null;
    }

    public function getLibre(): ?bool
    {
        return $this->libre;
    }

    public function setLibre(bool $libre): self
    {
        $this->libre = $libre;

        return $this;
    }

    /**
     * @return Collection|OutillageAutorization[]
     */
    public function getOutillagesAuth(): Collection
    {
        return $this->outillagesAuth;
    }

    public function getUserAuth()
    {
        $userAuthArr = [];
        foreach ($this->outillagesAuth as $value) {
            if(is_null($value->getDateRetour())){
                $userAuthArr[] = $value->getUtilisateur();
                break;
            }
        }
        if(count($userAuthArr))
            return $userAuthArr[0];
        return null;
    }

    public function addOutillagesAuth(OutillageAutorization $outillagesAuth): self
    {
        if (!$this->outillagesAuth->contains($outillagesAuth)) {
            $this->outillagesAuth[] = $outillagesAuth;
            $outillagesAuth->setOutillage($this);
        }

        return $this;
    }

    public function removeOutillagesAuth(OutillageAutorization $outillagesAuth): self
    {
        if ($this->outillagesAuth->contains($outillagesAuth)) {
            $this->outillagesAuth->removeElement($outillagesAuth);
            // set the owning side to null (unless already changed)
            if ($outillagesAuth->getOutillage() === $this) {
                $outillagesAuth->setOutillage(null);
            }
        }

        return $this;
    }
}
