<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tim as Gedmo;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Configuration
 * 
 * @ApiResource()
 * @ORM\Table(name="configuration")
 * @ORM\Entity
 */
class Configuration
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
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10)
     */
    private $type;

    /**
     * @var string|null
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var Utilisateur[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Utilisateur",mappedBy="type_contrat", cascade={"persist"}, orphanRemoval=true)
     */
    private $utilisateurs_contrat;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $created_at;


    function __construct()
    {
        $this->created_at = new \DateTime();
        $this->utilisateurs_contrat = new ArrayCollection();
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
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $description
     */
    public function setDescription(int $description): void
    {
        $this->description = $description;
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

    public function __toString(){
        return $this->getNom();
    }

    /**
     * @return Collection|Utilisateur[]
     */
    public function getUtilisateursContrat(): Collection
    {
        return $this->utilisateurs_contrat;
    }

    public function addUtilisateursContrat(Utilisateur $utilisateursContrat): self
    {
        if (!$this->utilisateurs_contrat->contains($utilisateursContrat)) {
            $this->utilisateurs_contrat[] = $utilisateursContrat;
            $utilisateursContrat->setTypeContrat($this);
        }

        return $this;
    }

    public function removeUtilisateursContrat(Utilisateur $utilisateursContrat): self
    {
        if ($this->utilisateurs_contrat->contains($utilisateursContrat)) {
            $this->utilisateurs_contrat->removeElement($utilisateursContrat);
            // set the owning side to null (unless already changed)
            if ($utilisateursContrat->getTypeContrat() === $this) {
                $utilisateursContrat->setTypeContrat(null);
            }
        }

        return $this;
    }

}
