<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Admin
 * 
 * @ORM\Table(name="admin")
 * @ORM\Entity
 */
class Admin implements UserInterface, \Serializable
{
    /**
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Entreprise", mappedBy="admins")
     */
    private $entreprises;

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
     * @ORM\Column(name="civ", type="string", length=10, nullable=true)
     */
    private $civ;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=50, nullable=true)
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
     * @ORM\Column(name="password", type="string", length=200, nullable=false)
     */
    private $password;

    /**
     * @var int
     *
     * @ORM\Column(name="numero", type="integer", nullable=true)
     */
    private $numero;

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
     * @ORM\Column(name="dep", type="string", length=50, nullable=true)
     */
    private $dep;

    /**
     * @var string
     *
     * @ORM\Column(name="region", type="string", length=50, nullable=true)
     */
    private $region;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=50, nullable=true)
     */
    private $website;

    /**
     * @var string
     *
     * @ORM\Column(name="categoryuser", type="string", length=100, nullable=true)
     */
    private $categoryuser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $created = 'CURRENT_TIMESTAMP';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true, options={"default"="1"})
     */
    private $status = '1';

    /**
     * @var int
     *
     * @ORM\Column(name="admin", type="integer", nullable=true)
     */
    private $admin;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=100, nullable=true)
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="verif", type="string", length=3, nullable=true, options={"default"="1"})
     */
    private $verif = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="notif", type="string", length=100, nullable=true)
     */
    private $notif;


    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise", inversedBy="directeurs")
     * @ORM\JoinColumn(nullable=true)
     */
    private $entreprise;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $role;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $resetting;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $super_admin;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $ipList;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $allIp;
    
    public function __construct()
    {
        $this->entreprises = new ArrayCollection();
        $this->created = new \DateTime();
        $this->allIp = false;
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

    public function getRoles()
    {
        return array('ROLE_ADMIN');
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

    public function getPassword()
    {
        return $this->password;
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

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getCiv(): ?string
    {
        return $this->civ;
    }

    public function setCiv(string $civ): self
    {
        $this->civ = $civ;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone( $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress( $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity( $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCp()
    {
        return $this->cp;
    }

    public function setCp( $cp): self
    {
        $this->cp = $cp;

        return $this;
    }

    public function getDep()
    {
        return $this->dep;
    }

    public function setDep( $dep): self
    {
        $this->dep = $dep;

        return $this;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function setRegion( $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getWebsite()
    {
        return $this->website;
    }

    public function setWebsite( $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getCategoryuser(): ?string
    {
        return $this->categoryuser;
    }

    public function setCategoryuser(string $categoryuser): self
    {
        $this->categoryuser = $categoryuser;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getLastlogin(): ?\DateTimeInterface
    {
        return $this->lastlogin;
    }

    public function setLastlogin(?\DateTimeInterface $lastlogin): self
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAdmin(): ?int
    {
        return $this->admin;
    }

    public function setAdmin(int $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getVerif(): ?string
    {
        return $this->verif;
    }

    public function setVerif(string $verif): self
    {
        $this->verif = $verif;

        return $this;
    }

    public function getNotif(): ?string
    {
        return $this->notif;
    }

    public function setNotif(string $notif): self
    {
        $this->notif = $notif;

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
     * @return Collection|Entreprise[]
     */
    public function getEntreprises(): Collection
    {
        return $this->entreprises;
    }

    public function addEntreprise(Entreprise $entreprise): self
    {
        if (!$this->entreprises->contains($entreprise)) {
            $this->entreprises[] = $entreprise;
            $entreprise->addAdmin($this);
        }

        return $this;
    }

    public function removeEntreprise(Entreprise $entreprise): self
    {
        if ($this->entreprises->contains($entreprise)) {
            $this->entreprises->removeElement($entreprise);
            $entreprise->removeAdmin($this);
        }

        return $this;
    }

    public function removeAllEntreprise(): self
    {
        $allAntreprises = $this->entreprises;
        foreach ($allAntreprises as $value) {
            $this->removeEntreprise($value);
        }

        return $this;
    }

    public function getResetting(): ?string
    {
        return $this->resetting;
    }

    public function setResetting(?string $resetting): self
    {
        $this->resetting = $resetting;

        return $this;
    }

    public function getSuperAdmin(): ?bool
    {
        return $this->super_admin;
    }

    public function setSuperAdmin(?bool $super_admin): self
    {
        $this->super_admin = $super_admin;

        return $this;
    }

    public function getIpList(): ?string
    {
        return $this->ipList;
    }

    public function setIpList(string $ipList): self
    {
        $this->ipList = $ipList;

        return $this;
    }

    public function getAllIp(): ?bool
    {
        return $this->allIp;
    }

    public function setAllIp(?bool $allIp): self
    {
        $this->allIp = $allIp;

        return $this;
    }

}
