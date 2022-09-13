<?php

namespace App\Entity;

use App\Repository\RemarqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;


/**
 * @ORM\Entity(repositoryClass=RemarqueRepository::class)
 */
class Remarque
{
    /**
     * @ORM\ManyToOne(targetEntity=CompteRendu::class, inversedBy="remarques")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
    */
    private $compte_rendu;

    /**
     * @var \Doctrine\Common\Collections\Collection|Fournisseurs[]
     *
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Fournisseurs")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="remarque_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
            @ORM\JoinColumn(name="fournisseur_id", referencedColumnName="id", onDelete="CASCADE")
     *  })
     */
    private $fournisseurs;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_post;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $num;

    /**
     * @ORM\ManyToOne(targetEntity=Status::class)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
    */
    private $status;

    public function __construct()
    {
        $this->fournisseurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getDatePost(): ?\DateTimeInterface
    {
        return $this->date_post;
    }

    public function setDatePost(\DateTimeInterface $date_post): self
    {
        $this->date_post = $date_post;

        return $this;
    }

    public function getCompteRendu(): ?CompteRendu
    {
        return $this->compte_rendu;
    }

    public function setCompteRendu(?CompteRendu $compte_rendu): self
    {
        $this->compte_rendu = $compte_rendu;

        return $this;
    }

    public function getNum(): ?int
    {
        return $this->num;
    }

    public function setNum(?int $num): self
    {
        $this->num = $num;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

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

}
