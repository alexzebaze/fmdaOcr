<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * @ORM\Entity(repositoryClass=PlanningRepository::class)
 */
class Planning
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tache;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $debut;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datefin;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $fichier;

    /**
     * @var \Doctrine\Common\Collections\Collection|Utilisateur[]
     *
     * @Groups({"write"})
     * @ORM\ManyToMany(targetEntity="Utilisateur")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="planning_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid", onDelete="CASCADE")
     *  })
     */
    private $utilisateurs;

    /**
     * @Groups({"write"})
     * @ORM\ManyToOne(targetEntity=Status::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $status;

    /**
     * @Groups({"write"})
     * @ORM\ManyToOne(targetEntity=Vente::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $devis;

    /**
     * @Groups({"write"})
     * @ORM\ManyToOne(targetEntity=PlaningStatus::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $status_p;

    /**
     * @Groups({"write"})
     * @ORM\ManyToOne(targetEntity=PlanningCategory::class, inversedBy="plannings", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $planning_categorie;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rang;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $Emplacement;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTache(): ?string
    {
        return $this->tache;
    }

    public function setTache(string $tache): self
    {
        $this->tache = $tache;

        return $this;
    }

    public function getDebut(): ?\DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut($debut): self
    {
        $this->debut = $debut;

        return $this;
    }

    public function getDatefin(): ?\DateTimeInterface
    {
        return $this->datefin;
    }

    public function setDatefin($datefin): self
    {
        $this->datefin = $datefin;

        return $this;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(?string $fichier): self
    {
        $this->fichier = $fichier;

        return $this;
    }

    /**
     * @return Collection|Utilisateur[]
     */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): self
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs[] = $utilisateur;
            //$utilisateur->addPlanning($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        if ($this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->removeElement($utilisateur);
            //$utilisateur->removePlanning($this);
        }

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

    public function getPlanningCategorie(): ?PlanningCategory
    {
        return $this->planning_categorie;
    }

    public function setPlanningCategorie(?PlanningCategory $planning_categorie): self
    {
        $this->planning_categorie = $planning_categorie;

        return $this;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getRang(): ?int
    {
        return $this->rang;
    }

    public function setRang(?int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    public function getStatusP(): ?PlaningStatus
    {
        return $this->status_p;
    }

    public function setStatusP(?PlaningStatus $status_p): self
    {
        $this->status_p = $status_p;

        return $this;
    }

    public function getEmplacement(): ?string
    {
        return $this->Emplacement;
    }

    public function setEmplacement(?string $Emplacement): self
    {
        $this->Emplacement = $Emplacement;

        return $this;
    }

    public function getDevis(): ?Vente
    {
        return $this->devis;
    }

    public function setDevis(?Vente $devis): self
    {
        $this->devis = $devis;

        return $this;
    }
}
