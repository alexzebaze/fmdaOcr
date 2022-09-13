<?php

namespace App\Entity;

use App\Repository\DeviseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DeviseRepository::class)
 */
class Devise
{
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
     * @ORM\Column(type="string", length=255)
     */
    private $symbol;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=Facturation::class, mappedBy="devise")
     */
    private $facturations;

    /**
     * @ORM\OneToMany(targetEntity=Achat::class, mappedBy="devise")
     */
    private $achats;

    /**
     * @ORM\OneToMany(targetEntity=Vente::class, mappedBy="devise")
     */
    private $ventes;

    public function __construct()
    {
        $this->facturations = new ArrayCollection();
        $this->achats = new ArrayCollection();
        $this->ventes = new ArrayCollection();
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

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

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

    /**
     * @return Collection|Facturation[]
     */
    public function getFacturations(): Collection
    {
        return $this->facturations;
    }

    public function addFacturation(Facturation $facturation): self
    {
        if (!$this->facturations->contains($facturation)) {
            $this->facturations[] = $facturation;
            $facturation->setDevise($this);
        }

        return $this;
    }

    public function removeFacturation(Facturation $facturation): self
    {
        if ($this->facturations->contains($facturation)) {
            $this->facturations->removeElement($facturation);
            // set the owning side to null (unless already changed)
            if ($facturation->getDevise() === $this) {
                $facturation->setDevise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Achat[]
     */
    public function getAchat(): Collection
    {
        return $this->achats;
    }

    public function addAchat(Achat $achat): self
    {
        if (!$this->achats->contains($achat)) {
            $this->achats[] = $achat;
            $achat->setDevise($this);
        }

        return $this;
    }

    public function removeAchat(Achat $achat): self
    {
        if ($this->achats->contains($achat)) {
            $this->achats->removeElement($achat);
            // set the owning side to null (unless already changed)
            if ($achat->getDevise() === $this) {
                $achat->setDevise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Achat[]
     */
    public function getAchats(): Collection
    {
        return $this->achats;
    }

    /**
     * @return Collection|Vente[]
     */
    public function getVente(): Collection
    {
        return $this->ventes;
    }

    public function addVente(Vente $devisPro): self
    {
        if (!$this->ventes->contains($devisPro)) {
            $this->ventes[] = $devisPro;
            $devisPro->setDevise($this);
        }

        return $this;
    }

    public function removeVente(Vente $devisPro): self
    {
        if ($this->ventes->contains($devisPro)) {
            $this->ventes->removeElement($devisPro);
            // set the owning side to null (unless already changed)
            if ($devisPro->getDevise() === $this) {
                $devisPro->setDevise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Vente[]
     */
    public function getVentes(): Collection
    {
        return $this->ventes;
    }
}
