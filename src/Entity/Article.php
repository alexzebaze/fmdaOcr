<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ArticleRepository::class)
 */
class Article
{
    /**
     * @ORM\ManyToOne(targetEntity=Fabricant::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $fabricant;    

    /**
     * @ORM\OneToMany(targetEntity=Normenclature::class, mappedBy="articleNormenclature")
     */
    private $normenclatures;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $entreprise;


    /**
     * @var \Doctrine\Common\Collections\Collection|Fournisseurs[]
     *
     * @ORM\ManyToMany(targetEntity="Fournisseurs")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="article_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
            @ORM\JoinColumn(name="fournisseur_id", referencedColumnName="id", onDelete="CASCADE")
     *  })
     */
    private $fournisseurs;


    /**
     * @ORM\ManyToOne(targetEntity=Lot::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $lot;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code_article_fournisseur;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unite_mesure;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prix_achat;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $marge_brut;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prix_vente_ht;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prix_vente_ttc;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $sommeil;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @ORM\ManyToOne(targetEntity=Tva::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $taux_tva;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pourcentage_marge;

    public function __construct()
    {
        $this->fournisseurs = new ArrayCollection();
        $this->type = 1;
        $this->normenclatures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUniteMesure(): ?string
    {
        return $this->unite_mesure;
    }

    public function setUniteMesure(?string $unite_mesure): self
    {
        $this->unite_mesure = $unite_mesure;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getPrixAchat(): ?float
    {
        return $this->prix_achat;
    }

    public function setPrixAchat(?float $prix_achat): self
    {
        $this->prix_achat = $prix_achat;

        return $this;
    }

    public function getMargeBrut(): ?float
    {
        return $this->marge_brut;
    }

    public function setMargeBrut(?float $marge_brut): self
    {
        $this->marge_brut = $marge_brut;

        return $this;
    }

    public function getPrixVenteHt(): ?float
    {
        return $this->prix_vente_ht;
    }

    public function setPrixVenteHt(?float $prix_vente_ht): self
    {
        $this->prix_vente_ht = $prix_vente_ht;

        return $this;
    }

    public function getPrixVenteTtc(): ?float
    {
        return $this->prix_vente_ttc;
    }

    public function setPrixVenteTtc(?float $prix_vente_ttc): self
    {
        $this->prix_vente_ttc = $prix_vente_ttc;

        return $this;
    }

    public function getSommeil(): ?bool
    {
        return $this->sommeil;
    }

    public function setSommeil(?bool $sommeil): self
    {
        $this->sommeil = $sommeil;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection|Fournisseurs[]
     */
    public function getFournisseurs(): Collection
    {
        return $this->fournisseurs;
    }

    public function addFournisseur(Fournisseurs $fournisseur): self
    {
        if (!$this->fournisseurs->contains($fournisseur)) {
            $this->fournisseurs[] = $fournisseur;
        }

        return $this;
    }

    public function removeFournisseur(Fournisseurs $fournisseur): self
    {
        if ($this->fournisseurs->contains($fournisseur)) {
            $this->fournisseurs->removeElement($fournisseur);
        }

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

    public function fournisseursToRemove($new){
        $tabFourId = [];
        foreach ($this->fournisseurs as $value) {
            if(!in_array($value->getId(), $new)){
                $this->fournisseurs->removeElement($value);
                $tabFourId[] = $value->getId();
            }
        }

        return $tabFourId;
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

    public function getPourcentageMarge(): ?float
    {
        return $this->pourcentage_marge;
    }

    public function setPourcentageMarge(?float $pourcentage_marge): self
    {
        $this->pourcentage_marge = $pourcentage_marge;

        return $this;
    }

    public function getTauxTva(): ?Tva
    {
        return $this->taux_tva;
    }

    public function setTauxTva(?Tva $taux_tva): self
    {
        $this->taux_tva = $taux_tva;

        return $this;
    }

    public function getCodeArticleFournisseur(): ?string
    {
        return $this->code_article_fournisseur;
    }

    public function setCodeArticleFournisseur(?string $code_article_fournisseur): self
    {
        $this->code_article_fournisseur = $code_article_fournisseur;

        return $this;
    }

    public function getFabricant(): ?Fabricant
    {
        return $this->fabricant;
    }

    public function setFabricant(?Fabricant $fabricant): self
    {
        $this->fabricant = $fabricant;

        return $this;
    }

    /**
     * @return Collection|Normenclature[]
     */
    public function getNormenclatures(): Collection
    {
        return $this->normenclatures;
    }

    public function addNormenclature(Normenclature $normenclature): self
    {
        if (!$this->normenclatures->contains($normenclature)) {
            $this->normenclatures[] = $normenclature;
            $normenclature->setArticleNormenclature($this);
        }

        return $this;
    }

    public function removeNormenclature(Normenclature $normenclature): self
    {
        if ($this->normenclatures->contains($normenclature)) {
            $this->normenclatures->removeElement($normenclature);
            // set the owning side to null (unless already changed)
            if ($normenclature->getArticleNormenclature() === $this) {
                $normenclature->setArticleNormenclature(null);
            }
        }

        return $this;
    }
}
