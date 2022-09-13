<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Entreprise
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * 
 * @ORM\Table(name="entreprise")
 * @ORM\Entity(repositoryClass=EntrepriseRepository::class)
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"code"},
 *     message="Ce code est déjà utilisé")
 */
class Entreprise
{
    /**
     * @var \Doctrine\Common\Collections\Collection|Menu[]
     *
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Menu")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="menu_id", referencedColumnName="id", onDelete="CASCADE")
     *  })
     */
    private $menus;


    /**
     * @var \Doctrine\Common\Collections\Collection|Admin[]
     *
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Admin")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="admin_id", referencedColumnName="uid", onDelete="CASCADE")
     *  })
     */
    private $admins;


    /**
     * @var int
     *
     * @Groups({"read", "write"})
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="code", type="string", length=50, unique=true, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="type", type="string", length=50, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="bank", type="string", length=255, nullable=true)
     */
    private $bank;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="director", type="string", length=255, nullable=true)
     */
    private $director;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="phone_director", type="string", length=255, nullable=true)
     */
    private $phone_director;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="phone", type="string", length=30, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var int
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="cp", type="text", length=30, nullable=true)
     */
    private $cp;


    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="siret", type="string", length=50, nullable=true)
     */
    private $siret;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="tva", type="string", length=50, nullable=true)
     */
    private $tva;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="ape", type="string", length=50, nullable=true)
     */
    private $ape;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="website", type="string", length=255, nullable=true)
     */
    private $website;

    /**
     * @var \DateTime
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="created", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $created = 'CURRENT_TIMESTAMP';


    /**
     * @var int
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="fax", type="text", length=20, nullable=true)
     */
    private $fax;

    /**
     * @var string
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="rib", type="text", length=255, nullable=true)
     */
    private $rib;

    /**
     * @var int
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="logo", type="text", length=255, nullable=true)
     */
    private $logo;


    /**
     * @var int
     *
     * @Groups({"write"})
     @ ApiProperty(attributes={"fetchEager": false})
     * @ORM\OneToMany(targetEntity="App\Entity\Utilisateur", mappedBy="entreprise", cascade={"persist"}, orphanRemoval=true)
     */
    private $utilisateurs;

    /**
     * @var Admin[]
     *
     * @Groups({"write"})
     * @ApiProperty(attributes={"fetchEager": false})
     * @ORM\OneToMany(targetEntity="App\Entity\Admin", mappedBy="entreprise", cascade={"persist"}, orphanRemoval=true)
     */
    private $directeurs;

    /**
     * @var Chantier[]
     *
     * @Groups({"write"})
     * @ApiProperty(attributes={"fetchEager": false})
     * @ORM\OneToMany(targetEntity="App\Entity\Chantier", mappedBy="entreprise", cascade={"persist"}, orphanRemoval=true)
     */
    private $chantiers;

    /**
     * @var Category[]
     *
     * @Groups({"write"})
     * @ApiProperty(attributes={"fetchEager": false})
     * @ORM\OneToMany(targetEntity="App\Entity\Category", mappedBy="entreprise", cascade={"persist"}, orphanRemoval=true)
     */
    private $categories;

    /**
     * @var Galerie[]
     *
     * @Groups({"write"})
     * @ApiProperty(attributes={"fetchEager": false})
     * @ORM\OneToMany(targetEntity="App\Entity\Galerie", mappedBy="entreprise", cascade={"persist"}, orphanRemoval=true)
     */
    private $photos;

    /**
     * @Groups("write")
     * @ORM\OneToMany(targetEntity=Achat::class, mappedBy="entreprise")
     */
    private $achats;

    /**
     * @Groups("write")
     * @ORM\OneToMany(targetEntity=Vente::class, mappedBy="entreprise")
     */
    private $ventes;

    /**
     * @Groups({"write"})
     * @ApiProperty(attributes={"fetchEager": false})
     * @ORM\OneToMany(targetEntity=Paie::class, mappedBy="entreprise")
     */
    private $paies;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Fournisseurs",mappedBy="entreprise")
     */
    private $fournisseurs;    

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Outillage",mappedBy="entreprise")
     */
    private $outillages;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Reglement",mappedBy="entreprise")
     */
    private $reglements;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\RossumConfig",mappedBy="entreprise")
     */
    private $rossum_configs;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    private $sender_mail;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string|null
     */
    private $sender_name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $logo_facture;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $gestionLotChantier;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->code = uniqid();
        $this->utilisateurs = new ArrayCollection();
        $this->directeurs = new ArrayCollection();
        $this->chantiers = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->photos = new ArrayCollection();
        $this->achats = new ArrayCollection();
        $this->paies = new ArrayCollection();
        $this->fournisseurs = new ArrayCollection();
        $this->reglements = new ArrayCollection();
        $this->rossum_configs = new ArrayCollection();
        $this->ventes = new ArrayCollection();
        $this->outillages = new ArrayCollection();
        $this->menus = new ArrayCollection();
        $this->admins = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId( $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @param string $bank
     */
    public function setBank($bank): void
    {
        $this->bank = $bank;
    }

    /**
     * @return string
     */
    public function getDirector()
    {
        return $this->director;
    }

    /**
     * @param string $director
     */
    public function setDirector($director): void
    {
        $this->director = $director;
    }

    /**
     * @return string
     */
    public function getPhoneDirector()
    {
        return $this->phone_director;
    }

    /**
     * @param string $phone_director
     */
    public function setPhoneDirector($phone_director): void
    {
        $this->phone_director = $phone_director;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address): void
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCp()
    {
        return $this->cp;
    }

    /**
     * @param string $cp
     */
    public function setCp($cp): void
    {
        $this->cp = $cp;
    }

    /**
     * @return string
     */
    public function getSiret()
    {
        return $this->siret;
    }

    /**
     * @param string $siret
     */
    public function setSiret($siret): void
    {
        $this->siret = $siret;
    }

    /**
     * @return string
     */
    public function getTva()
    {
        return $this->tva;
    }

    /**
     * @param string $tva
     */
    public function setTva($tva): void
    {
        $this->tva = $tva;
    }

    /**
     * @return string
     */
    public function getApe()
    {
        return $this->ape;
    }

    /**
     * @param string $ape
     */
    public function setApe($ape): void
    {
        $this->ape = $ape;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $website
     */
    public function setWebsite($website): void
    {
        $this->website = $website;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(\DateTime $created): void
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @param string $fax
     */
    public function setFax($fax): void
    {
        $this->fax = $fax;
    }

    /**
     * @return string
     */
    public function getRib()
    {
        return $this->rib;
    }

    /**
     * @param string $rib
     */
    public function setRib($rib): void
    {
        $this->rib = $rib;
    }

    /**
     * @return int
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param int $logo
     */
    public function setLogo( $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code): void
    {
        $this->code = $code;
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
            $utilisateur->setEntreprise($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        if ($this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->removeElement($utilisateur);
            // set the owning side to null (unless already changed)
            if ($utilisateur->getEntreprise() === $this) {
                $utilisateur->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Admin[]
     */
    public function getDirecteurs(): Collection
    {
        return $this->directeurs;
    }

    public function addDirecteur(Admin $directeur): self
    {
        if (!$this->directeurs->contains($directeur)) {
            $this->directeurs[] = $directeur;
            $directeur->setEntreprise($this);
        }

        return $this;
    }

    public function removeDirecteur(Admin $directeur): self
    {
        if ($this->directeurs->contains($directeur)) {
            $this->directeurs->removeElement($directeur);
            // set the owning side to null (unless already changed)
            if ($directeur->getEntreprise() === $this) {
                $directeur->setEntreprise(null);
            }
        }

        return $this;
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
            $chantier->setEntreprise($this);
        }

        return $this;
    }

    public function removeChantier(Chantier $chantier): self
    {
        if ($this->chantiers->contains($chantier)) {
            $this->chantiers->removeElement($chantier);
            // set the owning side to null (unless already changed)
            if ($chantier->getEntreprise() === $this) {
                $chantier->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->setEntreprise($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
            // set the owning side to null (unless already changed)
            if ($category->getEntreprise() === $this) {
                $category->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Galerie[]
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Galerie $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos[] = $photo;
            $photo->setEntreprise($this);
        }

        return $this;
    }

    public function removePhoto(Galerie $photo): self
    {
        if ($this->photos->contains($photo)) {
            $this->photos->removeElement($photo);
            // set the owning side to null (unless already changed)
            if ($photo->getEntreprise() === $this) {
                $photo->setEntreprise(null);
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
            $achat->setEntreprise($this);
        }

        return $this;
    }

    public function removeAchat(Achat $achat): self
    {
        if ($this->achats->contains($achat)) {
            $this->achats->removeElement($achat);
            // set the owning side to null (unless already changed)
            if ($achat->getEntreprise() === $this) {
                $achat->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Paie[]
     */
    public function getPaies(): Collection
    {
        return $this->paies;
    }

    public function addPaie(Paie $paie): self
    {
        if (!$this->paies->contains($paie)) {
            $this->paies[] = $paie;
            $paie->setEntreprise($this);
        }

        return $this;
    }

    public function removePaie(Paie $paie): self
    {
        if ($this->paies->contains($paie)) {
            $this->paies->removeElement($paie);
            // set the owning side to null (unless already changed)
            if ($paie->getEntreprise() === $this) {
                $paie->setEntreprise(null);
            }
        }

        return $this;
    }

    public function __toString(){
        return $this->getName();
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
            $fournisseur->setEntreprise($this);
        }

        return $this;
    }

    public function removeFournisseur(Fournisseurs $fournisseur): self
    {
        if ($this->fournisseurs->contains($fournisseur)) {
            $this->fournisseurs->removeElement($fournisseur);
            // set the owning side to null (unless already changed)
            if ($fournisseur->getEntreprise() === $this) {
                $fournisseur->setEntreprise(null);
            }
        }

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
            $reglement->setEntreprise($this);
        }

        return $this;
    }

    public function removeReglement(Reglement $reglement): self
    {
        if ($this->reglements->contains($reglement)) {
            $this->reglements->removeElement($reglement);
            // set the owning side to null (unless already changed)
            if ($reglement->getEntreprise() === $this) {
                $reglement->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|RossumConfig[]
     */
    public function getRossumConfigs(): Collection
    {
        return $this->rossum_configs;
    }

    public function addRossumConfig(RossumConfig $rossumConfig): self
    {
        if (!$this->rossum_configs->contains($rossumConfig)) {
            $this->rossum_configs[] = $rossumConfig;
            $rossumConfig->setEntreprise($this);
        }

        return $this;
    }

    public function removeRossumConfig(RossumConfig $rossumConfig): self
    {
        if ($this->rossum_configs->contains($rossumConfig)) {
            $this->rossum_configs->removeElement($rossumConfig);
            // set the owning side to null (unless already changed)
            if ($rossumConfig->getEntreprise() === $this) {
                $rossumConfig->setEntreprise(null);
            }
        }

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
            $devisPro->setEntreprise($this);
        }

        return $this;
    }

    public function removeVente(Vente $devisPro): self
    {
        if ($this->ventes->contains($devisPro)) {
            $this->ventes->removeElement($devisPro);
            // set the owning side to null (unless already changed)
            if ($devisPro->getEntreprise() === $this) {
                $devisPro->setEntreprise(null);
            }
        }

        return $this;
    }

    public function getSenderMail()
    {
        return $this->sender_mail;
    }

    public function setSenderMail( $sender_mail): self
    {
        $this->sender_mail = $sender_mail;

        return $this;
    }

    public function getSenderName()
    {
        return $this->sender_name;
    }

    public function setSenderName( $sender_name): self
    {
        $this->sender_name = $sender_name;

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
     * @return Collection|Outillage[]
     */
    public function getOutillages(): Collection
    {
        return $this->outillages;
    }

    public function addOutillage(Outillage $outillage): self
    {
        if (!$this->outillages->contains($outillage)) {
            $this->outillages[] = $outillage;
            $outillage->setEntreprise($this);
        }

        return $this;
    }

    public function removeOutillage(Outillage $outillage): self
    {
        if ($this->outillages->contains($outillage)) {
            $this->outillages->removeElement($outillage);
            // set the owning side to null (unless already changed)
            if ($outillage->getEntreprise() === $this) {
                $outillage->setEntreprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Menu[]
     */
    public function getMenus(): Collection
    {
        return $this->menus;
    }

    public function addMenu(Menu $menu): self
    {
        if (!$this->menus->contains($menu)) {
            $this->menus[] = $menu;
        }

        return $this;
    }

    public function removeMenu(Menu $menu): self
    {
        if ($this->menus->contains($menu)) {
            $this->menus->removeElement($menu);
        }

        return $this;
    }

    /**
     * @return Collection|Admin[]
     */
    public function getAdmins(): Collection
    {
        return $this->admins;
    }

    public function addAdmin(Admin $admin): self
    {
        if (!$this->admins->contains($admin)) {
            $this->admins[] = $admin;
        }

        return $this;
    }

    public function removeAdmin(Admin $admin): self
    {
        if ($this->admins->contains($admin)) {
            $this->admins->removeElement($admin);
        }

        return $this;
    }

    public function getLogoFacture(): ?string
    {
        return $this->logo_facture;
    }

    public function setLogoFacture(?string $logo_facture): self
    {
        $this->logo_facture = $logo_facture;

        return $this;
    }

    public function getGestionLotChantier(): ?bool
    {
        return $this->gestionLotChantier;
    }

    public function setGestionLotChantier(?bool $gestionLotChantier): self
    {
        $this->gestionLotChantier = $gestionLotChantier;

        return $this;
    }

}
