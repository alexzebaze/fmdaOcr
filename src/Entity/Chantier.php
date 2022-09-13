<?php

namespace App\Entity;

use App\Entity\Galerie;
use Swagger\Annotations as SWG;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ChantierRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use ApiPlatform\Core\Annotation\ApiFilter;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use JsonSerializable;

/**
 * Chantier
 * 
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}},
 *     attributes={"pagination_enabled"=false}
 * )
 * @ApiFilter(SearchFilter::class, properties={"entreprise": "exact"})
 * @ORM\Entity(repositoryClass=ChantierRepository::class)
 * @ORM\Table(name="chantier")
 * @ORM\Entity
 */
class Chantier implements JsonSerializable
{

    /**
     * @var \Doctrine\Common\Collections\Collection|Fournisseurs[]
     *
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Fournisseurs")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
            @ORM\JoinColumn(name="fournisseur_id", referencedColumnName="id", onDelete="CASCADE")
     *  })
     */
    private $fournisseurs;

    /**
     * @ORM\ManyToMany(targetEntity="Client", mappedBy="chantiers")
     */
    private $clients;

    /**
     * @var int
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="chantier_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $chantierId;

    /**
     * @var string
     * @Groups({"read", "write"})
     *
     * @ORM\Column(name="email", type="string", length=50, nullable=false)
     */
    private $email = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="phone", type="string", length=100, nullable=false)
     */
    private $phone = "";

    /**
     * @var int
     * @Groups({"read", "write"})
     * @ORM\Column(name="numero", type="integer", nullable=true)
     */
    private $numero;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="address", type="string", length=50, nullable=true)
     */
    private $address;

    /**
     * @var int
     * @Groups({"read", "write"})
     * @ORM\Column(name="distance", type="string", nullable=true)
     */
    private $distance;

    /**
     * @var int
     * @Groups({"read", "write"})
     * @ORM\Column(name="temps_route", type="string", nullable=true)
     */
    private $temps_route;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="city", type="string", length=50, nullable=true)
     */
    private $city;

    /**
     * @var int
     * @Groups({"read", "write"})
     * @ORM\Column(name="cp", type="string", nullable=true)
     */
    private $cp;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="dep", type="string", length=50, nullable=false)
     */
    private $dep = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="region", type="string", length=50, nullable=false)
     */
    private $region = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="lat", type="decimal", precision=40, scale=15, nullable=false)
     */
    private $lat = 0;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="lng", type="decimal", precision=40, scale=15, nullable=false)
     */
    private $lng = 0;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="nameentreprise", type="string", length=50, nullable=false)
     */
    private $nameentreprise = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="website", type="string", length=50, nullable=false)
     */
    private $website = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="categoryuser", type="string", length=100, nullable=false)
     */
    private $categoryuser = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="lun", type="string", length=20, nullable=false)
     */
    private $lun = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="mar", type="string", length=20, nullable=false)
     */
    private $mar = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="mer", type="string", length=20, nullable=false)
     */
    private $mer = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="jeu", type="string", length=20, nullable=false)
     */
    private $jeu = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="ven", type="string", length=20, nullable=false)
     */
    private $ven = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="sam", type="string", length=20, nullable=false)
     */
    private $sam = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="dim", type="string", length=20, nullable=false)
     */
    private $dim = "";

    /**
     * @var
     * @Groups({"read", "write"})
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private $created;

    /**
     * @var
     * @Groups({"read", "write"})
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     * @Groups({"read", "write"})
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default"="1"})
     */
    private $status = '1';

    /**
     * @var int
     * @Groups({"read", "write"})
     * @ORM\Column(name="admin", type="integer", nullable=false)
     */
    private $admin = 0;

    /**
     * @var int
     * @Groups({"read", "write"})
     * @ORM\Column(name="fkuser", type="integer", nullable=false)
     */
    private $fkuser = 0;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="token", type="string", length=100, nullable=false)
     */
    private $token = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="image", type="string", length=300, nullable=false)
     */
    private $image = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="notif", type="string", length=100, nullable=false)
     */
    private $notif = "";

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\Column(name="link", type="text", length=65535, nullable=false)
     */
    private $link = "";

    /**
     * @var bool
     * @Groups({"read", "write"})
     * @ORM\Column(name="published", type="boolean", nullable=false, options={"default"="1"})
     */
    private $published = true;


    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\Galerie",mappedBy="chantier", cascade={"persist"}, orphanRemoval=true)
     */
    private $galeries;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\Logement",mappedBy="chantier", cascade={"persist"}, orphanRemoval=true)
     */
    private $logements;

    /**
     * @var Entreprise
     * @Groups({"read", "write"})
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise", inversedBy="chantiers")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;

    /**
     * @var string
     *
     * @Groups("write")
     * @ORM\OneToMany(targetEntity="App\Entity\Achat",mappedBy="chantier", cascade={"persist"}, orphanRemoval=true)
     */
    private $achats;

    /**
     * @var string
     *
     * @Groups("write")
     * @ORM\OneToMany(targetEntity="App\Entity\AssuranceAttestation",mappedBy="chantier", cascade={"persist"}, orphanRemoval=true)
     */
    private $attestations;

    /**
     * @var string
     *
     * @Groups("write")
     * @ORM\OneToMany(targetEntity="App\Entity\AssuranceContrat",mappedBy="chantier", cascade={"persist"}, orphanRemoval=true)
     */
    private $contrats;

    /**
     * @var string
     *
     * @Groups("write")
     * @ORM\OneToMany(targetEntity="App\Entity\AssuranceQuittance",mappedBy="chantier", cascade={"persist"}, orphanRemoval=true)
     */
    private $quittances;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\CompteRendu",mappedBy="chantier", cascade={"persist"})
     */
    private $compte_rendus;

    /**
     * @var string
     *
     * @Groups("write")
     * @ORM\OneToMany(targetEntity="App\Entity\Vente",mappedBy="chantier", cascade={"persist"}, orphanRemoval=true)
     */
    private $ventes;

     /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\Note",mappedBy="chantier", cascade={"persist"}, orphanRemoval=true)
     */
    private $notes;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $m2;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_signature_compromis;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prix_acquisition;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $frais_agence;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $frais_negociation;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $frais_bancaire;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $frais_huissier;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prorata_taxe_fonciere;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prorata_copropriete;

    /**
     * @var string
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity=Galerie::class, inversedBy="chantiers")
     */
    private $default_galerie;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_signature_acte;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ignoreIa;

    /**
     * Chantier constructor.
     * @param string $email
     */
    public function __construct()
    {
        if($this->created == null)
            $this->created = new \DateTime();
        $this->updated = new \DateTime();
        $this->galeries = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->achats = new ArrayCollection();
        $this->ventes = new ArrayCollection();
        $this->compte_rendus = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->logements = new ArrayCollection();
        $this->quittances = new ArrayCollection();
        $this->contrats = new ArrayCollection();
        $this->attestations = new ArrayCollection();
        $this->fournisseurs = new ArrayCollection();
    }

    public function getFullAddress() {
        return trim($this->numero. " ".$this->address. " ". $this->cp. " ". $this->city);
    }

    public function getFullAddressUrlEncoded() {
        return urlencode($this->getFullAddress());
    }

    /**
     * @return int
     */
    public function getChantierId()
    {
        return $this->chantierId;
    }

    /**
     * @param int $chantierId
     */
    public function setChantierId(int $chantierId): void
    {
        $this->chantierId = $chantierId;
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
     * @return int
     */
    public function getNumero()
    {
        return $this->numero == 0 ? null : $this->numero;
    }

    /**
     * @param int $numero
     */
    public function setNumero(int $numero): void
    {
        $this->numero = $numero;
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
    public function getDistance()
    {
        return $this->distance == 0 ? null : $this->distance;
    }

    /**
     * @param string $distance
     */
    public function setDistance(string $distance): void
    {
        $this->distance = $distance;
    }

    /**
     * @return string
     */
    public function getTempsRoute()
    {
        return $this->temps_route == 0 ? null : $this->temps_route;
    }

    /**
     * @param string $temps_route
     */
    public function setTempsRoute(string $temps_route): void
    {
        $this->temps_route = $temps_route;
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
        return $this->cp == 0 ? null : $this->cp;
    }

    /**
     * @param string $cp
     */
    public function setCp(string $cp): void
    {
        $this->cp = $cp;
    }

    /**
     * @return string
     */
    public function getDep()
    {
        return $this->dep;
    }

    /**
     * @param string $dep
     */
    public function setDep($dep): void
    {
        $this->dep = $dep;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion($region): void
    {
        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param string $lat
     */
    public function setLat($lat): void
    {
        $this->lat = $lat;
    }

    /**
     * @return string
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param string $lng
     */
    public function setLng($lng): void
    {
        $this->lng = $lng;
    }

    /**
     * @return string
     */
    public function getNameentreprise()
    {
        return $this->nameentreprise;
    }

    /**
     * @param string $nameentreprise
     */
    public function setNameentreprise($nameentreprise): void
    {
        $this->nameentreprise = $nameentreprise;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
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
     * @return string
     */
    public function getCategoryuser()
    {
        return $this->categoryuser;
    }

    /**
     * @param string $categoryuser
     */
    public function setCategoryuser($categoryuser): void
    {
        $this->categoryuser = $categoryuser;
    }

    /**
     * @return string
     */
    public function getLun()
    {
        return $this->lun;
    }

    /**
     * @param string $lun
     */
    public function setLun($lun): void
    {
        $this->lun = $lun;
    }

    /**
     * @return string
     */
    public function getMar()
    {
        return $this->mar;
    }

    /**
     * @param string $mar
     */
    public function setMar($mar): void
    {
        $this->mar = $mar;
    }

    /**
     * @return string
     */
    public function getMer()
    {
        return $this->mer;
    }

    /**
     * @param string $mer
     */
    public function setMer($mer): void
    {
        $this->mer = $mer;
    }

    /**
     * @return string
     */
    public function getJeu()
    {
        return $this->jeu;
    }

    /**
     * @param string $jeu
     */
    public function setJeu($jeu): void
    {
        $this->jeu = $jeu;
    }

    /**
     * @return string
     */
    public function getVen()
    {
        return $this->ven;
    }

    /**
     * @param string $ven
     */
    public function setVen($ven): void
    {
        $this->ven = $ven;
    }

    /**
     * @return string
     */
    public function getSam()
    {
        return $this->sam;
    }

    /**
     * @param string $sam
     */
    public function setSam($sam): void
    {
        $this->sam = $sam;
    }

    /**
     * @return string
     */
    public function getDim()
    {
        return $this->dim;
    }

    /**
     * @param string $dim
     */
    public function setDim($dim): void
    {
        $this->dim = $dim;
    }

    /**
     * @return
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param  $created
     */
    public function setCreated($created): void
    {
        $this->created = $created;
    }

    /**
     * @return
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param  $updated
     */
    public function setUpdated($updated): void
    {
        $this->updated = $updated;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @param int $admin
     */
    public function setAdmin(int $admin): void
    {
        $this->admin = $admin;
    }

    /**
     * @return int
     */
    public function getFkuser()
    {
        return $this->fkuser;
    }

    /**
     * @param int $fkuser
     */
    public function setFkuser(int $fkuser): void
    {
        $this->fkuser = $fkuser;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image): void
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getNotif()
    {
        return $this->notif;
    }

    /**
     * @param string $notif
     */
    public function setNotif($notif): void
    {
        $this->notif = $notif;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link): void
    {
        $this->link = $link;
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * @param bool $published
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    /**
     * @return Entreprise
     */
    public function getEntreprise(): Entreprise
    {
        return $this->entreprise;
    }

    /**
     * @param Entreprise $entreprise
     */
    public function setEntreprise(Entreprise $entreprise): void
    {
        $this->entreprise = $entreprise;
    }

    /**
     * @return Collection|Galerie[]
     */
    public function getGaleries(): Collection
    {
        return $this->galeries;
    }

    public function getPublished(): ?bool
    {
        return $this->published;
    }

    public function addGalery(Galerie $galery): self
    {
        if (!$this->galeries->contains($galery)) {
            $this->galeries[] = $galery;
            $galery->setChantier($this);
        }

        return $this;
    }

    public function removeGalery(Galerie $galery): self
    {
        if ($this->galeries->contains($galery)) {
            $this->galeries->removeElement($galery);
            // set the owning side to null (unless already changed)
            if ($galery->getChantier() === $this) {
                $galery->setChantier(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Note[]
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setChantier($this);
        }

        return $this;
    }

    public function removeNote(Note $note): self
    {
        if ($this->notes->contains($note)) {
            $this->notes->removeElement($note);
            // set the owning side to null (unless already changed)
            if ($note->getChantier() === $this) {
                $note->setChantier(null);
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
            $achat->setChantier($this);
        }

        return $this;
    }

    public function removeAchat(Achat $achat): self
    {
        if ($this->achats->contains($achat)) {
            $this->achats->removeElement($achat);
            // set the owning side to null (unless already changed)
            if ($achat->getChantier() === $this) {
                $achat->setChantier(null);
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
            $devisPro->setChantier($this);
        }

        return $this;
    }

    public function removeVente(Vente $devisPro): self
    {
        if ($this->ventes->contains($devisPro)) {
            $this->ventes->removeElement($devisPro);
            // set the owning side to null (unless already changed)
            if ($devisPro->getChantier() === $this) {
                $devisPro->setChantier(null);
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
     * @return Collection|CompteRendu[]
     */
    public function getCompteRendus(): Collection
    {
        return $this->compte_rendus;
    }

    public function addCompteRendu(CompteRendu $compteRendu): self
    {
        if (!$this->compte_rendus->contains($compteRendu)) {
            $this->compte_rendus[] = $compteRendu;
            $compteRendu->setChantier($this);
        }

        return $this;
    }

    public function removeCompteRendu(CompteRendu $compteRendu): self
    {
        if ($this->compte_rendus->contains($compteRendu)) {
            $this->compte_rendus->removeElement($compteRendu);
            // set the owning side to null (unless already changed)
            if ($compteRendu->getChantier() === $this) {
                $compteRendu->setChantier(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): self
    {
        if (!$this->clients->contains($client)) {
            $this->clients[] = $client;
            $client->addChantier($this);
        }

        return $this;
    }

    public function removeClient(Client $client): self
    {
        if ($this->clients->contains($client)) {
            $this->clients->removeElement($client);
            $client->removeChantier($this);
        }

        return $this;
    }

    public function getM2(): ?int
    {
        return $this->m2;
    }

    public function setM2(?int $m2): self
    {
        $this->m2 = $m2;

        return $this;
    }

    /**
     * @return Collection|Logement[]
     */
    public function getLogements(): Collection
    {
        return $this->logements;
    }

    public function addLogement(Logement $logement): self
    {
        if (!$this->logements->contains($logement)) {
            $this->logements[] = $logement;
            $logement->setChantier($this);
        }

        return $this;
    }

    public function removeLogement(Logement $logement): self
    {
        if ($this->logements->contains($logement)) {
            $this->logements->removeElement($logement);
            // set the owning side to null (unless already changed)
            if ($logement->getChantier() === $this) {
                $logement->setChantier(null);
            }
        }

        return $this;
    }

    public function getDateSignatureCompromis(): ?\DateTimeInterface
    {
        return $this->date_signature_compromis;
    }

    public function setDateSignatureCompromis(?\DateTimeInterface $date_signature_compromis): self
    {
        $this->date_signature_compromis = $date_signature_compromis;

        return $this;
    }

    public function getPrixAcquisition(): ?float
    {
        return $this->prix_acquisition;
    }

    public function setPrixAcquisition(?float $prix_acquisition): self
    {
        $this->prix_acquisition = $prix_acquisition;

        return $this;
    }

    public function getFraisAgence(): ?float
    {
        return $this->frais_agence;
    }

    public function setFraisAgence(?float $frais_agence): self
    {
        $this->frais_agence = $frais_agence;

        return $this;
    }

    public function getFraisNegociation(): ?float
    {
        return $this->frais_negociation;
    }

    public function setFraisNegociation(?float $frais_negociation): self
    {
        $this->frais_negociation = $frais_negociation;

        return $this;
    }

    public function getFraisBancaire(): ?float
    {
        return $this->frais_bancaire;
    }

    public function setFraisBancaire(?float $frais_bancaire): self
    {
        $this->frais_bancaire = $frais_bancaire;

        return $this;
    }

    public function getFraisHuissier(): ?float
    {
        return $this->frais_huissier;
    }

    public function setFraisHuissier(?float $frais_huissier): self
    {
        $this->frais_huissier = $frais_huissier;

        return $this;
    }

    public function getProrataTaxeFonciere(): ?float
    {
        return $this->prorata_taxe_fonciere;
    }

    public function setProrataTaxeFonciere(?float $prorata_taxe_fonciere): self
    {
        $this->prorata_taxe_fonciere = $prorata_taxe_fonciere;

        return $this;
    }

    public function getProrataCopropriete(): ?float
    {
        return $this->prorata_copropriete;
    }

    public function setProrataCopropriete(float $prorata_copropriete): self
    {
        $this->prorata_copropriete = $prorata_copropriete;

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

    public function getDateSignatureActe(): ?\DateTimeInterface
    {
        return $this->date_signature_acte;
    }

    public function setDateSignatureActe(?\DateTimeInterface $date_signature_acte): self
    {
        $this->date_signature_acte = $date_signature_acte;

        return $this;
    }

        /**
     * @return Collection|AssuranceAttestation[]
     */
    public function getAttestations(): Collection
    {
        return $this->attestations;
    }

    public function addAttestation(AssuranceAttestation $attestation): self
    {
        if (!$this->attestations->contains($attestation)) {
            $this->attestations[] = $attestation;
            $attestation->setChantier($this);
        }

        return $this;
    }

    public function removeAttestation(AssuranceAttestation $attestation): self
    {
        if ($this->attestations->contains($attestation)) {
            $this->attestations->removeElement($attestation);
            // set the owning side to null (unless already changed)
            if ($attestation->getChantier() === $this) {
                $attestation->setChantier(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AssuranceContrat[]
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(AssuranceContrat $contrat): self
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats[] = $contrat;
            $contrat->setChantier($this);
        }

        return $this;
    }

    public function removeContrat(AssuranceContrat $contrat): self
    {
        if ($this->contrats->contains($contrat)) {
            $this->contrats->removeElement($contrat);
            // set the owning side to null (unless already changed)
            if ($contrat->getChantier() === $this) {
                $contrat->setChantier(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AssuranceQuittance[]
     */
    public function getQuittances(): Collection
    {
        return $this->quittances;
    }

    public function addQuittance(AssuranceQuittance $quittance): self
    {
        if (!$this->quittances->contains($quittance)) {
            $this->quittances[] = $quittance;
            $quittance->setChantier($this);
        }

        return $this;
    }

    public function removeQuittance(AssuranceQuittance $quittance): self
    {
        if ($this->quittances->contains($quittance)) {
            $this->quittances->removeElement($quittance);
            // set the owning side to null (unless already changed)
            if ($quittance->getChantier() === $this) {
                $quittance->setChantier(null);
            }
        }

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

    public function getIgnoreIa(): ?bool
    {
        return $this->ignoreIa;
    }

    public function setIgnoreIa(?bool $ignoreIa): self
    {
        $this->ignoreIa = $ignoreIa;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'chantierId' => $this->chantierId,
            'nameentreprise'=> $this->nameentreprise
        );
    }
}
