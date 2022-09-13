<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass=ClientRepository::class)
 */
class Client implements JsonSerializable
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $adresse;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ville;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pays;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $teltwo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telecopie;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $diversone;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $diverstwo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $web;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $siret;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tva;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @ORM\Column(type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $datecrea;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datemaj;

    /**
     * @ORM\OneToMany(targetEntity=Vente::class, mappedBy="client")
     */
    private $ventes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $logo;

    /**
     * @ORM\OneToMany(targetEntity=Reglement::class, mappedBy="client")
     */
    private $reglements;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $f_facturation;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="client")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $entreprise;

    /**
     * @ORM\ManyToMany(targetEntity="Chantier")
     * @ORM\JoinTable(name="chantier_client",
     *      joinColumns={@ORM\JoinColumn(name="client_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="chantier",    
     *   referencedColumnName="chantier_id")})
     */
    
    private $chantiers;

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
    private $m2;

    /**
     * @ORM\OneToMany(targetEntity=RapportProspect::class, mappedBy="prospect")
     */
    private $rapport;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_naissance;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lieu_naissance;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cni;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $display;

    public function __construct()
    {
        $this->display = true;
        $this->facturations = new ArrayCollection();
        $this->achats = new ArrayCollection();
        $this->reglements = new ArrayCollection();
        $this->ventes = new ArrayCollection();
        $this->chantiers = new ArrayCollection();
        $this->rapport = new ArrayCollection();
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCp(): ?int
    {
        return $this->cp;
    }

    public function setCp(?int $cp): self
    {
        $this->cp = $cp;

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

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getTelone(): ?string
    {
        return $this->telone;
    }

    public function setTelone(string $telone): self
    {
        $this->telone = $telone;

        return $this;
    }

    public function getTeltwo(): ?string
    {
        return $this->teltwo;
    }

    public function setTeltwo(?string $teltwo): self
    {
        $this->teltwo = $teltwo;

        return $this;
    }

    public function getTelecopie(): ?string
    {
        return $this->telecopie;
    }

    public function setTelecopie(?string $telecopie): self
    {
        $this->telecopie = $telecopie;

        return $this;
    }

    public function getDiversone(): ?string
    {
        return $this->diversone;
    }

    public function setDiversone(?string $diversone): self
    {
        $this->diversone = $diversone;

        return $this;
    }

    public function getDiverstwo(): ?string
    {
        return $this->diverstwo;
    }

    public function setDiverstwo(?string $diverstwo): self
    {
        $this->diverstwo = $diverstwo;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

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

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getTva(): ?string
    {
        return $this->tva;
    }

    public function setTva(?string $tva): self
    {
        $this->tva = $tva;

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

    public function getDatecrea(): ?\DateTimeInterface
    {
        return $this->datecrea;
    }

    public function setDatecrea(\DateTimeInterface $datecrea): self
    {
        $this->datecrea = $datecrea;

        return $this;
    }

    public function getDatemaj(): ?\DateTimeInterface
    {
        return $this->datemaj;
    }

    public function setDatemaj(\DateTimeInterface $datemaj): self
    {
        $this->datemaj = $datemaj;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return Collection|Reglement[]
     */
    public function getReglements(): Collection
    {
        return $this->reglements;
    }

    public function addReglement(Reglement $reglement): self
    {
        if (!$this->reglements->contains($reglement)) {
            $this->reglements[] = $reglement;
            $reglement->setClient($this);
        }

        return $this;
    }

    public function removeReglement(Reglement $reglement): self
    {
        if ($this->reglements->contains($reglement)) {
            $this->reglements->removeElement($reglement);
            // set the owning side to null (unless already changed)
            if ($reglement->getClient() === $this) {
                $reglement->setClient(null);
            }
        }

        return $this;
    }

    public function getFFacturation(): ?float
    {
        return $this->f_facturation;
    }

    public function setFFacturation(?float $f_facturation): self
    {
        $this->f_facturation = $f_facturation;

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
            $devisPro->setClient($this);
        }

        return $this;
    }

    public function removeVente(Vente $devisPro): self
    {
        if ($this->ventes->contains($devisPro)) {
            $this->ventes->removeElement($devisPro);
            // set the owning side to null (unless already changed)
            if ($devisPro->getClient() === $this) {
                $devisPro->setClient(null);
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

    /**
     * @return Collection|Chantier[]
     */
    public function getChantiers(): Collection
    {
        return $this->chantiers;
    }

    public function addChantier(Chantier $chantier): self
    {
        if (!$this->chantiers->contains($chantier)) {
            $this->chantiers[] = $chantier;
        }

        return $this;
    }

    public function removeChantier(Chantier $chantier): self
    {
        if ($this->chantiers->contains($chantier)) {
            $this->chantiers->removeElement($chantier);
        }

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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getM2(): ?string
    {
        return $this->m2;
    }

    public function setM2(?string $m2): self
    {
        $this->m2 = $m2;

        return $this;
    }

    /**
     * @return Collection|RapportProspect[]
     */
    public function getRapport(): Collection
    {
        return $this->rapport;
    }

    public function addRapport(RapportProspect $rapport): self
    {
        if (!$this->rapport->contains($rapport)) {
            $this->rapport[] = $rapport;
            $rapport->setProspect($this);
        }

        return $this;
    }

    public function removeRapport(RapportProspect $rapport): self
    {
        if ($this->rapport->contains($rapport)) {
            $this->rapport->removeElement($rapport);
            // set the owning side to null (unless already changed)
            if ($rapport->getProspect() === $this) {
                $rapport->setProspect(null);
            }
        }

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(?\DateTimeInterface $date_naissance): self
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieu_naissance;
    }

    public function setLieuNaissance(?string $lieu_naissance): self
    {
        $this->lieu_naissance = $lieu_naissance;

        return $this;
    }

    public function getCni(): ?string
    {
        return $this->cni;
    }

    public function setCni(?string $cni): self
    {
        $this->cni = $cni;

        return $this;
    }

    public function getDisplay(): ?bool
    {
        return $this->display;
    }

    public function setDisplay(?bool $display): self
    {
        $this->display = $display;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'nom'=> $this->nom
        );
    }

}
