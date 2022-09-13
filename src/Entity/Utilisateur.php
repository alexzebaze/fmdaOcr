<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Annotation\ApiFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Utilisateur
 *
 * @ApiResource()
 * @ApiFilter(SearchFilter::class, properties={"entreprise_id": "exact"})
 * @ORM\Entity(repositoryClass=UtilisateurRepository::class)
 * @ORM\Table(name="utilisateur")
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"email"},
 *     message="Cet e-mail est déjà utilisé")
 */
class Utilisateur implements UserInterface, \Serializable
{

    /**
     * @var int
     *
     * @Groups({"write"})
     * @ORM\OneToMany(targetEntity="App\Entity\SignatureOutillage", mappedBy="utilisateur", cascade={"persist"}, orphanRemoval=true)
     */
    private $signatures;

    /**
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Fournisseurs", mappedBy="utilisateurs")
     */
    private $fournisseurs;

    /**
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Planning", mappedBy="utilisateurs")
     */
    private $plannings;

    /**
     * @var int
     *
     * @ORM\Column(name="uid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $uid;


    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=50, nullable=false)
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=50, nullable=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @Assert\Email(
     *     message = "invalid_email"
     * )
     * @ORM\Column(name="email", type="string", length=50, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=100, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @Groups({"user:write"})
     * @ORM\Column(name="password", type="string", length=200, nullable=false)
     */
    private $password;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birth", type="date", nullable=true)
     */
    private $birth;


    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=50, nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=50, nullable=true)
     */
    private $city;

    /**
     * @var int
     *
     * @ORM\Column(name="cp", type="integer", nullable=true)
     */
    private $cp;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=50, nullable=true)
     */
    private $website;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $created = 'CURRENT_TIMESTAMP';


    /**
     * @var int
     *
     * @ORM\Column(name="etat", type="integer", nullable=false)
     */
    private $etat = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="categoryuser", type="integer", nullable=true)
     */
    private $categoryuser;


    /**
     * @var int
     *
     * @ORM\Column(name="poste", type="integer", nullable=true)
     */
    private $poste;


    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=100, nullable=false)
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="verif", type="string", length=3, nullable=false, options={"default"="1"})
     */
    private $verif = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="text", length=4294967295, nullable=true)
     */
    private $image;

    /**
     * @var string
     *
     * @ORM\Column(name="carte_btp", type="string", length=255, nullable=true)
     */
    private $carte_btp;

    /**
     * @var string
     *
     * @ORM\Column(name="contrat", type="string", length=255, nullable=true)
     */
    private $contrat;

    /**
     * @var string
     *
     * @ORM\Column(name="carte_vitale", type="string", length=255, nullable=true)
     */
    private $carte_vitale;

    /**
     * @var string
     *
     * @ORM\Column(name="numero_secu", type="string", length=255, nullable=true)
     */
    private $numero_secu;

    /**
     *
     * @ORM\Column(name="taux_horaire", type="float", precision=10, length=255, nullable=true)
     */
    private $taux_horaire;


    /**
     *
     * @ORM\Column(name="coefficient", type="float", length=255, nullable=true)
     */
    private $coefficient;

    /**
     * @var string
     *
     * @ORM\Column(name="notif", type="string", length=100, nullable=true)
     */
    private $notif;

    /**
     * @var integer
     *
     * @ORM\Column(name="sous_traitant", type="integer", nullable=true)
     */
    private $sous_traitant;
    /**
     * @var integer
     *
     * @ORM\Column(name="trajet", type="integer", nullable=true)
     */
    private $trajet;

    /**
     * @var string
     *
     * @ORM\Column(name="panier", type="integer", nullable=true)
     */
    private $panier;

    /**
     * @var string
     *
     * @ORM\Column(name="heure_hebdo", type="float", nullable=true, options={"default"="35"})
     */
    private $heure_hebdo = 35;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_entree", type="date", nullable=true)
     */
    private $date_entree;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_sortie", type="date", nullable=true)
     */
    private $date_sortie;

    /**
     * @var int
     *
     * @ORM\Column(name="num", type="integer", nullable=true)
     */
    private $num;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Prime",mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    private $prime;


    /**
     * @var HoraireObservation[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\HoraireObservation",mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    private $observation;


    /**
     * @var HoraireValidation[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\HoraireValidation",mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    private $validations;


    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise", inversedBy="utilisateurs")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Configuration", inversedBy="utilisateurs_contrat")
     * @ORM\JoinColumn(name="type_contrat", referencedColumnName="id")
     */
    private $type_contrat;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Galerie",mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    private $galeries;

    /**
     * @Groups({"write","read"})
     * @ORM\OneToMany(targetEntity=Paie::class, mappedBy="utilisateur")
     */
    private $paies;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicule", inversedBy="conducteurs")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $vehicule;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Note",mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    private $notes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $role;

    /**
     * @var string
     * @ORM\OneToMany(targetEntity="App\Entity\Outillage", mappedBy="utilisateur", cascade={"persist"})
     */
    private $outillages;

    /**
     * @var string
     * @ORM\OneToMany(targetEntity="App\Entity\PartageNote", mappedBy="user", cascade={"persist"})
     */
    private $partageNotes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $avatar;


    public function __construct()
    {
        $now = new \DateTime();
        $this->created = $now;
        //$this->lastlogin = $now;
        $this->website = "";
        $this->etat = 1;
        $this->notif = '';
        $this->token = md5(mt_rand());
        $this->prime = new ArrayCollection();
        $this->signatures = new ArrayCollection();
        $this->observation = new ArrayCollection();
        $this->validations = new ArrayCollection();
        $this->galeries = new ArrayCollection();
        $this->paies = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->outillages = new ArrayCollection();
        $this->partageNotes = new ArrayCollection();
        $this->plannings = new ArrayCollection();
        $this->fournisseurs = new ArrayCollection();
    }

    public function getFullAddress() {
        return trim($this->address. " ". $this->cp. " ". $this->city);
    }

    public function getFullAddressUrlEncoded() {
        return urlencode($this->getFullAddress());
    }


    /**
     * @return int
     */
    public function getUid(): ?int
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     */
    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * @return string
     */
    public function getLastname(): ?string
    {
        return strtoupper($this->lastname);
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname): void
    {
        $this->lastname = strtoupper($lastname);
    }

    /**
     * @return string
     */
    public function getFirstname(): ?string
    {
        return ucfirst(strtolower($this->firstname));
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname): void
    {
        $this->firstname = ucfirst(strtolower($firstname));
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
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
    public function getPhone(): ?string
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
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password): void
    {
        $this->password = md5($password);
    }

    /**
     * @return string
     */
    public function getBirth()
    {
        return $this->birth ? $this->birth->format('d/m/Y') : null;
    }

    /**
     * @param string $birth
     */
    public function setBirth($birth): void
    {
        if($birth instanceof \DateTime) {
            $this->birth = $birth;
        }
        else{
            if(strpos($birth, '/') !== false ) {
                $date = \DateTime::createFromFormat('d/m/Y', $birth);
                $date->setTime(0, 0, 0);
                $this->birth = $date;
            } else {
                $this->birth = $birth;
            }
        }
    }

    /**
     * @return string
     */
    public function getDateEntree()
    {
        return $this->date_entree ? $this->date_entree->format('d/m/Y') : null;
    }

    /**
     * @return string
     */
    public function getRawDateEntree()
    {
        return $this->date_entree;
    }

    /**
     * @param string $date_entree
     */
    public function setDateEntree($date_entree): void
    {
        if(strpos($date_entree, '/') !== false ) {
            $date = \DateTime::createFromFormat('d/m/Y', $date_entree);
            $date->setTime(0, 0, 0);
            $this->date_entree = $date;
        } else {
            $this->date_entree = $date_entree;
        }
    }

    /**
     * @return string
     */
    public function getDateSortie()
    {
        return $this->date_sortie ? $this->date_sortie->format('d/m/Y') : null;
    }

    /**
     * @return string
     */
    public function getRawDateSortie()
    {
        return $this->date_sortie;
    }

    /**
     * @param string $date_sortie
     */
    public function setDateSortie($date_sortie): void
    {
        if(strpos($date_sortie, '/') !== false ) {
            $date = \DateTime::createFromFormat('d/m/Y', $date_sortie);
            $date->setTime(0, 0, 0);
            $this->date_sortie = $date;
        } else {
            $this->date_sortie = $date_sortie;
        }
    }

    /**
     * @return string
     */
    public function getAddress(): ?string
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
    public function getCity(): ?string
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
     * @return int
     */
    public function getCp(): ?int
    {
        return $this->cp;
    }

    /**
     * @param int $cp
     */
    public function setCp($cp): void
    {
        $this->cp = $cp;
    }

    /**
     * @return string
     */
    public function getWebsite(): ?string
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
    public function getCreated(): ?\DateTime
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
     * @return int
     */
    public function getEtat(): ?int
    {
        return $this->etat;
    }

    /**
     * @param int $etat
     */
    public function setEtat(int $etat): void
    {
        $this->etat = $etat;
    }

    /**
     * @return int
     */
    public function getCategoryuser(): ?int
    {
        return $this->categoryuser;
    }

    /**
     * @param int $categoryuser
     */
    public function setCategoryuser(int $categoryuser): void
    {
        $this->categoryuser = $categoryuser;
    }

    /**
     * @return int
     */
    public function getPoste(): ?int
    {
        return $this->poste;
    }

    /**
     * @param int $poste
     */
    public function setPoste(int $poste): void
    {
        $this->poste = $poste;
    }

    /**
     * @return string
     */
    public function getToken(): ?string
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
    public function getVerif(): ?string
    {
        return $this->verif;
    }

    /**
     * @param string $verif
     */
    public function setVerif($verif): void
    {
        $this->verif = $verif;
    }

    /**
     * @return string
     */
    public function getImage(): ?string
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
    public function getNotif(): ?string
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
    public function getSousTraitant(): ?int
    {
        return $this->sous_traitant;
    }

    /**
     * @param string $sous_traitant
     */
    public function setSousTraitant(int $sous_traitant): void
    {
        $this->sous_traitant = $sous_traitant;
    }

    /**
     * @return string
     */
    public function getTrajet(): ?int
    {
        return $this->trajet;
    }

    /**
     * @param string $trajet
     */
    public function setTrajet(int $trajet): void
    {
        $this->trajet = $trajet;
    }

    /**
     * @return string
     */
    public function getPanier(): ?int
    {
        return $this->panier;
    }

    /**
     * @param string $panier
     */
    public function setPanier(int $panier): void
    {
        $this->panier = $panier;
    }

    /**
     * @return string
     */
    public function getPrime()
    {
        return $this->prime;
    }

    /**
     * @param string $prime
     */
    public function setPrime($prime): void
    {
        $this->prime = $prime;
    }

    /**
     * @return string
     */
    public function getHeureHebdo(): ?string
    {
        return $this->heure_hebdo;
    }

    /**
     * @param string $heure_hebdo
     */
    public function setHeureHebdo($heure_hebdo): void
    {
        $this->heure_hebdo = $heure_hebdo;
    }

    /**
     * @return string
     */
    public function getCarteBtp()
    {
        return $this->carte_btp;
    }

    /**
     * @param string $carte_btp
     */
    public function setCarteBtp($carte_btp): void
    {
        $this->carte_btp = $carte_btp;
    }

    /**
     * @return string
     */
    public function getContrat()
    {
        return $this->contrat;
    }

    /**
     * @param string $contrat
     */
    public function setContrat($contrat): void
    {
        $this->contrat = $contrat;
    }

    /**
     * @return string
     */
    public function getCarteVitale()
    {
        return $this->carte_vitale;
    }

    /**
     * @param string $carte_vitale
     */
    public function setCarteVitale($carte_vitale): void
    {
        $this->carte_vitale = $carte_vitale;
    }

    /**
     * @return string
     */
    public function getNumeroSecu()
    {
        return $this->numero_secu;
    }

    /**
     * @param string $numero_secu
     */
    public function setNumeroSecu($numero_secu): void
    {
        $this->numero_secu = $numero_secu;
    }

    /**
     * @return string
     */
    public function getTauxHoraire()
    {
        return $this->taux_horaire;
    }

    /**
     * @param string $taux_horaire
     */
    public function setTauxHoraire($taux_horaire): void
    {
        $this->taux_horaire = str_replace(',', '.',$taux_horaire);
    }

    /**
     * @return Configuration
     */
    public function getTypeContrat()
    {
        return $this->type_contrat;
    }

    /**
     * @param Configuration $type_contrat
     */
    public function setTypeContrat($type_contrat): void
    {
        $this->type_contrat = $type_contrat;
    }

    /**
     * @return HoraireObservation[]
     */
    public function getObservation()
    {
        return $this->observation;
    }

    /**
     * @param HoraireObservation[] $observation
     */
    public function setObservation(string $observation): void
    {
        $this->observation = $observation;
    }

    public function getEntreprise()
    {
        return $this->entreprise;
    }

    public function setEntreprise($entreprise): void
    {
        $this->entreprise = $entreprise;
    }

    /**
     * @return HoraireValidation[]
     */
    public function getValidations()
    {
        return $this->validations;
    }

    /**
     * @param HoraireValidation[] $validations
     */
    public function setValidations($validations): void
    {
        $this->validations = $validations;
    }

    /**
     * @return mixed
     */
    public function getCoefficient()
    {
        return $this->coefficient;
    }

    /**
     * @param mixed $coefficient
     */
    public function setCoefficient($coefficient): void
    {
        $this->coefficient = $coefficient;
    }

    /**
     * @return int
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * @param int $num
     */
    public function setNum(int $num): void
    {
        $this->num = $num;
    }


    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function eraseCredentials()
    {
    }
    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function getUsername()
    {
        return $this->email;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->uid,
            $this->email,
            $this->password
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->uid,
            $this->email,
            $this->password
            ) = unserialize($serialized);
    }

    public function addPrime(Prime $prime): self
    {
        if (!$this->prime->contains($prime)) {
            $this->prime[] = $prime;
            $prime->setUser($this);
        }

        return $this;
    }

    public function removePrime(Prime $prime): self
    {
        if ($this->prime->contains($prime)) {
            $this->prime->removeElement($prime);
            // set the owning side to null (unless already changed)
            if ($prime->getUser() === $this) {
                $prime->setUser(null);
            }
        }

        return $this;
    }

    public function addObservation(HoraireObservation $observation): self
    {
        if (!$this->observation->contains($observation)) {
            $this->observation[] = $observation;
            $observation->setUser($this);
        }

        return $this;
    }

    public function removeObservation(HoraireObservation $observation): self
    {
        if ($this->observation->contains($observation)) {
            $this->observation->removeElement($observation);
            // set the owning side to null (unless already changed)
            if ($observation->getUser() === $this) {
                $observation->setUser(null);
            }
        }

        return $this;
    }

    public function addValidation(HoraireValidation $validation): self
    {
        if (!$this->validations->contains($validation)) {
            $this->validations[] = $validation;
            $validation->setUser($this);
        }

        return $this;
    }

    public function removeValidation(HoraireValidation $validation): self
    {
        if ($this->validations->contains($validation)) {
            $this->validations->removeElement($validation);
            // set the owning side to null (unless already changed)
            if ($validation->getUser() === $this) {
                $validation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Galerie[]
     */
    public function getGaleries(): Collection
    {
        return $this->galeries;
    }

    public function addGalery(Galerie $galery): self
    {
        if (!$this->galeries->contains($galery)) {
            $this->galeries[] = $galery;
            $galery->setUser($this);
        }

        return $this;
    }

    public function removeGalery(Galerie $galery): self
    {
        if ($this->galeries->contains($galery)) {
            $this->galeries->removeElement($galery);
            // set the owning side to null (unless already changed)
            if ($galery->getUser() === $this) {
                $galery->setUser(null);
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
            $paie->setUtilisateur($this);
        }

        return $this;
    }

    public function removePaie(Paie $paie): self
    {
        if ($this->paies->contains($paie)) {
            $this->paies->removeElement($paie);
            // set the owning side to null (unless already changed)
            if ($paie->getUtilisateur() === $this) {
                $paie->setUtilisateur(null);
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
            $note->setUser($this);
        }

        return $this;
    }

    public function removeNote(Note $note): self
    {
        if ($this->notes->contains($note)) {
            $this->notes->removeElement($note);
            // set the owning side to null (unless already changed)
            if ($note->getUser() === $this) {
                $note->setUser(null);
            }
        }

        return $this;
    }

    public function __toString(){
        return $this->getLastname()." ".$this->getFirstname();
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): self
    {
        $this->vehicule = $vehicule;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }


    /**
     * @return Collection|PartageNote[]
     */
    public function getPartageNotes(): Collection
    {
        return $this->partageNotes;
    }

    public function addPartageNote(PartageNote $partageNote): self
    {
        if (!$this->partageNotes->contains($partageNote)) {
            $this->partageNotes[] = $partageNote;
            $partageNote->setUser($this);
        }

        return $this;
    }

    public function removePartageNote(PartageNote $partageNote): self
    {
        if ($this->partageNotes->contains($partageNote)) {
            $this->partageNotes->removeElement($partageNote);
            // set the owning side to null (unless already changed)
            if ($partageNote->getUser() === $this) {
                $partageNote->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SignatureOutillage[]
     */
    public function getSignatures(): Collection
    {
        return $this->signatures;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function addSignature(SignatureOutillage $signature): self
    {
        if (!$this->signatures->contains($signature)) {
            $this->signatures[] = $signature;
            $signature->setUtilisateur($this);
        }

        return $this;
    }

    public function removeSignature(SignatureOutillage $signature): self
    {
        if ($this->signatures->contains($signature)) {
            $this->signatures->removeElement($signature);
            // set the owning side to null (unless already changed)
            if ($signature->getUtilisateur() === $this) {
                $signature->setUtilisateur(null);
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
            $fournisseur->addUtilisateur($this);
        }

        return $this;
    }

    public function removeFournisseur(Fournisseurs $fournisseur): self
    {
        if ($this->fournisseurs->contains($fournisseur)) {
            $this->fournisseurs->removeElement($fournisseur);
            $fournisseur->removeUtilisateur($this);
        }

        return $this;
    }

    /**
     * @return Collection|Planning[]
     */
    public function getPlannings(): Collection
    {
        return $this->plannings;
    }

    public function addPlanning(Planning $planning): self
    {
        if (!$this->plannings->contains($planning)) {
            $this->plannings[] = $planning;
            $planning->addUtilisateur($this);
        }

        return $this;
    }

    public function removePlanning(Planning $planning): self
    {
        if ($this->plannings->contains($planning)) {
            $this->plannings->removeElement($planning);
            $planning->removeUtilisateur($this);
        }

        return $this;
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
            $outillage->setUtilisateur($this);
        }

        return $this;
    }

    public function removeOutillage(Outillage $outillage): self
    {
        if ($this->outillages->contains($outillage)) {
            $this->outillages->removeElement($outillage);
            // set the owning side to null (unless already changed)
            if ($outillage->getUtilisateur() === $this) {
                $outillage->setUtilisateur(null);
            }
        }

        return $this;
    }

}
