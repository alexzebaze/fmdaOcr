<?php

namespace App\Entity;

use App\Repository\FournisseursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass=FournisseursRepository::class)
 */
class Fournisseurs implements JsonSerializable
{
    /**
     * @var \Doctrine\Common\Collections\Collection|Utilisateur[]
     *
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Utilisateur")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="fournisseur_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid", onDelete="CASCADE")
     *  })
     */
    private $utilisateurs;

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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datemaj;

    /**
     * @ORM\OneToMany(targetEntity=Facturation::class, mappedBy="fournisseur")
     */
    private $facturations;

    /**
     * @ORM\OneToMany(targetEntity=Achat::class, mappedBy="fournisseur")
     */
    private $achats;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $logo;

    /**
     * @ORM\OneToMany(targetEntity=Reglement::class, mappedBy="fournisseur")
     */
    private $reglements;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $f_facturation;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="fournisseurs")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $entreprise;

    /**
     * @ORM\ManyToOne(targetEntity=Paiement::class, inversedBy="fournisseurs")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $paiement;  

    /**
     * @ORM\ManyToOne(targetEntity=Lot::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $lot;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code_compta;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contact;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tel_contact;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email_contact;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rotation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nom2;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $typeBonLivraison;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $emailFactureElectronique;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $emailBl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $totalConfig;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $totalConfigTtc;

    public function __construct()
    {
        $this->facturations = new ArrayCollection();
        $this->achats = new ArrayCollection();
        $this->reglements = new ArrayCollection();
        $this->utilisateurs = new ArrayCollection();
        $this->datecrea = new \Datetime();
        $this->typeBonLivraison = false;
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
            $facturation->setFournisseur($this);
        }

        return $this;
    }

    public function removeFacturation(Facturation $facturation): self
    {
        if ($this->facturations->contains($facturation)) {
            $this->facturations->removeElement($facturation);
            // set the owning side to null (unless already changed)
            if ($facturation->getFournisseur() === $this) {
                $facturation->setFournisseur(null);
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

    public function addAchat(Achat $achat): self
    {
        if (!$this->achats->contains($achat)) {
            $this->achats[] = $achat;
            $achat->setFournisseur($this);
        }

        return $this;
    }

    public function removeAchat(Achat $achat): self
    {
        if ($this->achats->contains($achat)) {
            $this->achats->removeElement($achat);
            // set the owning side to null (unless already changed)
            if ($achat->getFournisseur() === $this) {
                $achat->setFournisseur(null);
            }
        }

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
            $reglement->setFournisseur($this);
        }

        return $this;
    }

    public function removeReglement(Reglement $reglement): self
    {
        if ($this->reglements->contains($reglement)) {
            $this->reglements->removeElement($reglement);
            // set the owning side to null (unless already changed)
            if ($reglement->getFournisseur() === $this) {
                $reglement->setFournisseur(null);
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

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): self
    {
        $this->paiement = $paiement;

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

    public function getCodeCompta(): ?string
    {
        return $this->code_compta;
    }

    public function setCodeCompta(?string $code_compta): self
    {
        $this->code_compta = $code_compta;

        return $this;
    }

    /**
     * @return Collection|Utilisateur[]
     */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): self
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs[] = $utilisateur;
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        if ($this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->removeElement($utilisateur);
        }

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function getTelContact(): ?string
    {
        return $this->tel_contact;
    }

    public function setTelContact(?string $tel_contact): self
    {
        $this->tel_contact = $tel_contact;

        return $this;
    }

    public function getEmailContact(): ?string
    {
        return $this->email_contact;
    }

    public function setEmailContact(?string $email_contact): self
    {
        $this->email_contact = $email_contact;

        return $this;
    }

    public function getRotation(): ?int
    {
        return $this->rotation;
    }

    public function setRotation(?int $rotation): self
    {
        $this->rotation = $rotation;

        return $this;
    }

    public function getNom2(): ?string
    {
        return $this->nom2;
    }

    public function setNom2(?string $nom2): self
    {
        $this->nom2 = $nom2;

        return $this;
    }

    public function getTypeBonLivraison(): ?bool
    {
        return $this->typeBonLivraison;
    }

    public function setTypeBonLivraison(?bool $typeBonLivraison): self
    {
        $this->typeBonLivraison = $typeBonLivraison;

        return $this;
    }

    public function getEmailFactureElectronique(): ?string
    {
        return $this->emailFactureElectronique;
    }

    public function setEmailFactureElectronique(?string $emailFactureElectronique): self
    {
        $this->emailFactureElectronique = $emailFactureElectronique;

        return $this;
    }

    public function getEmailBl(): ?string
    {
        return $this->emailBl;
    }

    public function setEmailBl(?string $emailBl): self
    {
        $this->emailBl = $emailBl;

        return $this;
    }

    public function getTotalConfig(): ?string
    {
        return $this->totalConfig;
    }

    public function setTotalConfig(?string $totalConfig): self
    {
        $this->totalConfig = $totalConfig;

        return $this;
    }

    public function getTotalConfigTtc(): ?string
    {
        return $this->totalConfigTtc;
    }

    public function setTotalConfigTtc(?string $totalConfigTtc): self
    {
        $this->totalConfigTtc = $totalConfigTtc;

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
