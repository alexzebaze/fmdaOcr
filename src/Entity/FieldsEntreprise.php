<?php

namespace App\Entity;

use App\Repository\FieldsEntrepriseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FieldsEntrepriseRepository::class)
 */
class FieldsEntreprise
{

    /**
     * @ORM\ManyToOne(targetEntity=Fields::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $colonne;

    /**
     * @ORM\ManyToOne(targetEntity=Page::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $page;


    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $entreprise;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getColonne(): ?Fields
    {
        return $this->colonne;
    }

    public function setColonne(?Fields $colonne): self
    {
        $this->colonne = $colonne;

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

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): self
    {
        $this->page = $page;

        return $this;
    }
}
