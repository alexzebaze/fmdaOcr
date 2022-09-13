<?php

namespace App\Entity;

use App\Repository\GalerieRepository;
use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tim as Gedmo;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Observation
 *
 * @ApiResource()
 * @ORM\Entity(repositoryClass=GalerieRepository::class)
 * @ORM\Table(name="galerie")
 * @ORM\Entity
 */
class Galerie
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var string|null
     *
     * @ORM\Column(name="extension", type="string", length=10)
     */
    private $extension;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=false, options={"default"="1"})
     */
    private $type = 1;

    /**
     * @var Utilisateur
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Utilisateur", inversedBy="galeries")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="uid")
     */
    private $user;


    /**
     * @var Chantier
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier", inversedBy="galeries")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id")
     */
    private $chantier;


    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise", inversedBy="photos")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $created_at;

    private $thumbnail;

    /**
     * @ORM\OneToMany(targetEntity=Chantier::class, mappedBy="default_galerie")
     */
    private $chantiers;


    function __construct()
    {
        $this->created_at = new \DateTime();
        $this->chantiers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * @param string|null $nom
     */
    public function setNom(?string $nom): void
    {
        $this->nom = $nom;
    }

    /**
     * @return string|null
     */
    public function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * @param string|null $extension
     */
    public function setExtension(?string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return Utilisateur
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Utilisateur $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return Chantier
     */
    public function getChantier()
    {
        return $this->chantier;
    }

    /**
     * @param Chantier $chantier
     */
    public function setChantier(Chantier $chantier): void
    {
        $this->chantier = $chantier;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    /**
     * @param \DateTime $created_at
     */
    public function setCreatedAt(\DateTime $created_at): void
    {
        $this->created_at = $created_at;
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
     * @return mixed
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param mixed $thumbnail
     */
    public function setThumbnail($thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type == 0 ? null : $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }


    public function getUrl()
    {
        return "/galerie/".$this->entreprise->getId()."/".$this->created_at->format('Y-m-d')."/".$this->nom;
    }

    public function getCompressedUrl()
    {
        return "/galerie/".$this->entreprise->getId()."/".$this->created_at->format('Y-m-d')."/compressed/".$this->nom;
    }

    /**
     * @return Collection|Chantier[]
     */
    public function getChantiers(): Collection
    {
        return $this->chantiers;
    }

    public function getChantierDefaultGalerie(){
        if(count($this->getChantiers())>0){
            return $this->getChantiers()[0];
        }
        return null;
    }

    public function addChantier(Chantier $chantier): self
    {
        if (!$this->chantiers->contains($chantier)) {
            $this->chantiers[] = $chantier;
            $chantier->setDefaultGalerie($this);
        }

        return $this;
    }

    public function removeChantier(Chantier $chantier): self
    {
        if ($this->chantiers->contains($chantier)) {
            $this->chantiers->removeElement($chantier);
            // set the owning side to null (unless already changed)
            if ($chantier->getDefaultGalerie() === $this) {
                $chantier->setDefaultGalerie(null);
            }
        }

        return $this;
    }

}
