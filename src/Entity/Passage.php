<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\PassageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=PassageRepository::class)
 */
class Passage
{

    /**
    * @ORM\OneToOne(targetEntity=Achat::class, inversedBy="passage")
    * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
    */
    private $bon_livraison;

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseurs::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $fournisseur;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $entreprise;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=true)
     */
    private $chantier;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Utilisateur")
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid", nullable=true)
     */
    private $utilisateur;


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_detection;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $create_at;

    function __construct()
    {
        $this->date_detection = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDetection(): ?\DateTimeInterface
    {
        return $this->date_detection;
    }

    public function setDateDetection(\DateTimeInterface $date_detection): self
    {
        $this->date_detection = $date_detection;

        return $this;
    }

    public function getFournisseur(): ?Fournisseurs
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseurs $fournisseur): self
    {
        $this->fournisseur = $fournisseur;

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

    public function getChantier(): ?Chantier
    {
        return $this->chantier;
    }

    public function setChantier(?Chantier $chantier): self
    {
        $this->chantier = $chantier;

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

    public function getBonLivraison(): ?Achat
    {
        return $this->bon_livraison;
    }

    public function setBonLivraison(?Achat $bon_livraison): self
    {
        $this->bon_livraison = $bon_livraison;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->create_at;
    }

    public function setCreateAt(?\DateTimeInterface $create_at): self
    {
        $this->create_at = $create_at;

        return $this;
    }
}
