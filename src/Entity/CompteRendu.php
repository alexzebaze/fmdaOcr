<?php

namespace App\Entity;

use App\Repository\CompteRenduRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CompteRenduRepository::class)
 */
class CompteRendu
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier", inversedBy="compte_rendus")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=true)
     */
    private $chantier;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Remarque",mappedBy="compte_rendu", cascade={"persist"})
     */
    private $remarques;

    /**
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FournisseurCompteRendu",mappedBy="compteRendu", cascade={"persist"})
     */
    private $fournisseurs;

    /**
     * @var string
     * @ORM\ManyToOne(targetEntity=Galerie::class, inversedBy="chantiers")
     */
    private $default_galerie;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phase;

    /**
     * @ORM\Column(type="integer")
     */
    private $numero_visite;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_visite;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ouvrier;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $archived;

    public function __construct()
    {
        $this->remarques = new ArrayCollection();
        $this->fournisseurs = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPhase(): ?string
    {
        return $this->phase;
    }

    public function setPhase(?string $phase): self
    {
        $this->phase = $phase;

        return $this;
    }

    public function getNumeroVisite(): ?int
    {
        return $this->numero_visite;
    }

    public function setNumeroVisite(int $numero_visite): self
    {
        $this->numero_visite = $numero_visite;

        return $this;
    }

    public function getDateVisite(): ?\DateTimeInterface
    {
        return $this->date_visite;
    }

    public function setDateVisite(?\DateTimeInterface $date_visite): self
    {
        $this->date_visite = $date_visite;

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

    /**
     * @return Collection|Remarque[]
     */
    public function getRemarques(): Collection
    {
        return $this->remarques;
    }

    public function addRemarque(Remarque $remarque): self
    {
        if (!$this->remarques->contains($remarque)) {
            $this->remarques[] = $remarque;
            $remarque->setCompteRendu($this);
        }

        return $this;
    }

    public function removeRemarque(Remarque $remarque): self
    {
        if ($this->remarques->contains($remarque)) {
            $this->remarques->removeElement($remarque);
            // set the owning side to null (unless already changed)
            if ($remarque->getCompteRendu() === $this) {
                $remarque->setCompteRendu(null);
            }
        }

        return $this;
    }

    public function getOuvrier(): ?string
    {
        return $this->ouvrier;
    }

    public function setOuvrier(?string $ouvrier): self
    {
        $this->ouvrier = $ouvrier;

        return $this;
    }

    public function getArchived(): ?int
    {
        return $this->archived;
    }

    public function setArchived(?int $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return Collection|FournisseurCompteRendu[]
     */
    public function getFournisseurs(): Collection
    {
        return $this->fournisseurs;
    }

    public function addFournisseur(FournisseurCompteRendu $fournisseur): self
    {
        if (!$this->fournisseurs->contains($fournisseur)) {
            $this->fournisseurs[] = $fournisseur;
            $fournisseur->setCompteRendu($this);
        }

        return $this;
    }

    public function removeFournisseur(FournisseurCompteRendu $fournisseur): self
    {
        if ($this->fournisseurs->contains($fournisseur)) {
            $this->fournisseurs->removeElement($fournisseur);
            // set the owning side to null (unless already changed)
            if ($fournisseur->getCompteRendu() === $this) {
                $fournisseur->setCompteRendu(null);
            }
        }

        return $this;
    }

    public function getDefaultGalerie()
    {
        return $this->default_galerie;
    }

    public function setDefaultGalerie(?Galerie $default_galerie): self
    {
        $this->default_galerie = $default_galerie;

        return $this;
    }
}
