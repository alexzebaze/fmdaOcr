<?php

namespace App\Entity;

use App\Repository\VehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=VehiculeRepository::class)
 */
class Vehicule
{

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\VehiculeKilometrage",mappedBy="vehicule", cascade={"persist"})
     */
    private $kilometrages;


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $immatriculation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $marque;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $modele;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type_carburant;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $conso_moyenne;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cout_moyen;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $logo_marque;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $carte_totale;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $carte_grise;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_service;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_ctr_tech;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Utilisateur",mappedBy="vehicule")
     */
    private $conducteurs;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $financement;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $assurance;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $debut_credit_bail;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fin_credit_bail;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $status;

    public function __construct()
    {
        $this->conducteurs = new ArrayCollection();
        $this->kilometrages = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): self
    {
        $this->immatriculation = $immatriculation;

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

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): self
    {
        $this->modele = $modele;

        return $this;
    }

    public function getTypeCarburant(): ?string
    {
        return $this->type_carburant;
    }

    public function setTypeCarburant(?string $type_carburant): self
    {
        $this->type_carburant = $type_carburant;

        return $this;
    }

    public function getConsoMoyenne()
    {
        return $this->conso_moyenne;
    }

    public function setConsoMoyenne($conso_moyenne): self
    {
        $this->conso_moyenne = $conso_moyenne;

        return $this;
    }

    public function getCoutMoyen(): ?float
    {
        return $this->cout_moyen;
    }

    public function setCoutMoyen(?float $cout_moyen): self
    {
        $this->cout_moyen = $cout_moyen;

        return $this;
    }

    public function getLogoMarque(): ?string
    {
        return $this->logo_marque;
    }

    public function setLogoMarque(?string $logo_marque): self
    {
        $this->logo_marque = $logo_marque;

        return $this;
    }

    public function getCarteTotale(): ?string
    {
        return $this->carte_totale;
    }

    public function setCarteTotale(?string $carte_totale): self
    {
        $this->carte_totale = $carte_totale;

        return $this;
    }

    public function getCarteGrise(): ?string
    {
        return $this->carte_grise;
    }

    public function setCarteGrise(?string $carte_grise): self
    {
        $this->carte_grise = $carte_grise;

        return $this;
    }

    public function getDateService(): ?\DateTimeInterface
    {
        return $this->date_service;
    }

    public function setDateService(?\DateTimeInterface $date_service): self
    {
        $this->date_service = $date_service;

        return $this;
    }

    public function getDateCtrTech(): ?\DateTimeInterface
    {
        return $this->date_ctr_tech;
    }

    public function setDateCtrTech(?\DateTimeInterface $date_ctr_tech): self
    {
        $this->date_ctr_tech = $date_ctr_tech;

        return $this;
    }

    /**
     * @return Collection|Utilisateur[]
     */
    public function getConducteurs(): Collection
    {
        return $this->conducteurs;
    }

    public function addConducteur(Utilisateur $conducteur): self
    {
        if (!$this->conducteurs->contains($conducteur)) {
            $this->conducteurs[] = $conducteur;
            $conducteur->setVehicule($this);
        }

        return $this;
    }

    public function removeConducteur(Utilisateur $conducteur): self
    {
        if ($this->conducteurs->contains($conducteur)) {
            $this->conducteurs->removeElement($conducteur);
            // set the owning side to null (unless already changed)
            if ($conducteur->getVehicule() === $this) {
                $conducteur->setVehicule(null);
            }
        }

        return $this;
    }

    public function getFinancement(): ?int
    {
        return $this->financement;
    }

    public function setFinancement(?int $financement): self
    {
        $this->financement = $financement;

        return $this;
    }

    /**
     * @return Collection|VehiculeKilometrage[]
     */
    public function getKilometrages(): Collection
    {
        return $this->kilometrages;
    }

    public function addKilometrage(VehiculeKilometrage $kilometrage): self
    {
        if (!$this->kilometrages->contains($kilometrage)) {
            $this->kilometrages[] = $kilometrage;
            $kilometrage->setVehicule($this);
        }

        return $this;
    }

    public function removeKilometrage(VehiculeKilometrage $kilometrage): self
    {
        if ($this->kilometrages->contains($kilometrage)) {
            $this->kilometrages->removeElement($kilometrage);
            // set the owning side to null (unless already changed)
            if ($kilometrage->getVehicule() === $this) {
                $kilometrage->setVehicule(null);
            }
        }

        return $this;
    }

    public function getAssurance(): ?string
    {
        return $this->assurance;
    }

    public function setAssurance(?string $assurance): self
    {
        $this->assurance = $assurance;

        return $this;
    }

    public function getDebutCreditBail(): ?\DateTimeInterface
    {
        return $this->debut_credit_bail;
    }

    public function setDebutCreditBail(?\DateTimeInterface $debut_credit_bail): self
    {
        $this->debut_credit_bail = $debut_credit_bail;

        return $this;
    }

    public function getFinCreditBail(): ?\DateTimeInterface
    {
        return $this->fin_credit_bail;
    }

    public function setFinCreditBail(?\DateTimeInterface $fin_credit_bail): self
    {
        $this->fin_credit_bail = $fin_credit_bail;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
