<?php

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PageRepository::class)
 */
class Page
{
    
    /**
     * @var \Doctrine\Common\Collections\Collection|Fields[]
     *
     * @ORM\ManyToMany(targetEntity="Fields")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
            @ORM\JoinColumn(name="field_id", referencedColumnName="id", onDelete="CASCADE")
     *  })
     */
    private $fields;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $libelle;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $cle;

    public function __construct()
    {
        $this->fournisseurs = new ArrayCollection();
        $this->fields = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getCle(): ?string
    {
        return $this->cle;
    }

    public function setCle(string $cle): self
    {
        $this->cle = $cle;

        return $this;
    }

    /**
     * @return Collection|Fields[]
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function addField(Fields $field): self
    {
        if (!$this->fields->contains($field)) {
            $this->fields[] = $field;
        }

        return $this;
    }

    public function removeField(Fields $field): self
    {
        if ($this->fields->contains($field)) {
            $this->fields->removeElement($field);
        }

        return $this;
    }
}
