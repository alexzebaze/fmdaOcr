<?php

namespace App\Entity;

use App\Repository\UserHoraireAbsentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserHoraireAbsentRepository::class)
 */
class UserHoraireAbsent
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Utilisateur")
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid", nullable=false)
     */
    private $utilisateur;


    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $entreprise;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_horaire;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateHoraire(): ?\DateTimeInterface
    {
        return $this->date_horaire;
    }

    public function setDateHoraire(\DateTimeInterface $date_horaire): self
    {
        $this->date_horaire = $date_horaire;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

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
    
}
