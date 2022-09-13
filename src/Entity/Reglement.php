<?php

namespace App\Entity;

use App\Repository\ReglementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReglementRepository::class)
 */
class Reglement
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseurs::class, inversedBy="reglements")
     * @ORM\JoinColumn(nullable=false)
     */
    private $fournisseur;

    /**
     * @ORM\OneToMany(targetEntity=Achat::class, mappedBy="reglement")
     */
    private $achats;

    /**
     * @ORM\OneToMany(targetEntity=Vente::class, mappedBy="reglement")
     */
    private $ventes;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $solde;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prixht;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prixttc;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $due_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\ManyToOne(targetEntity=Tva::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $tva;

    /**
     * @ORM\ManyToOne(targetEntity=Devise::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $devise;

    /**
     * @ORM\ManyToOne(targetEntity=Paiement::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $paiement;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_reglement;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $Commentaire;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $montant_non_encaisse;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $montant_reglement;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $restant_encaisse;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, inversedBy="reglements")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $entreprise;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $document;

    public function __construct()
    {
        $this->achats = new ArrayCollection();
        $this->ventes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSolde(): ?float
    {
        return $this->solde;
    }

    public function setSolde(?float $solde): self
    {
        $this->solde = $solde;

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

    /**
     * @return Collection|Achat[]
     */
    public function getAchats(): Collection
    {
        return $this->achats;
    }

    public function addAchat(Achat $achat): self
    {
        if (!$this->achats->contains($achat)) {
            $this->achats[] = $achat;
            $achat->setReglement($this);
        }

        return $this;
    }

    public function removeAchat(Achat $achat): self
    {
        if ($this->achats->contains($achat)) {
            $this->achats->removeElement($achat);
            // set the owning side to null (unless already changed)
            if ($achat->getReglement() === $this) {
                $achat->setReglement(null);
            }
        }

        return $this;
    }

    public function getPrixht(): ?float
    {
        return $this->prixht;
    }

    public function setPrixht(?float $prixht): self
    {
        $this->prixht = $prixht;

        return $this;
    }

    public function getPrixttc(): ?float
    {
        return $this->prixttc;
    }

    public function setPrixttc(?float $prixttc): self
    {
        $this->prixttc = $prixttc;

        return $this;
    }

    public function getDueAt(): ?\DateTimeInterface
    {
        return $this->due_at;
    }

    public function setDueAt(?\DateTimeInterface $due_at): self
    {
        $this->due_at = $due_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getTva(): ?Tva
    {
        return $this->tva;
    }

    public function setTva(?Tva $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): self
    {
        $this->devise = $devise;

        return $this;
    }

        public function getDateReglement(): ?\DateTimeInterface
    {
        return $this->date_reglement;
    }

    public function setDateReglement(?\DateTimeInterface $date_reglement): self
    {
        $this->date_reglement = $date_reglement;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->Commentaire;
    }

    public function setCommentaire(?string $Commentaire): self
    {
        $this->Commentaire = $Commentaire;

        return $this;
    }

    public function getMontantNonEncaisse(): ?float
    {
        return $this->montant_non_encaisse;
    }

    public function setMontantNonEncaisse(?float $montant_non_encaisse): self
    {
        $this->montant_non_encaisse = $montant_non_encaisse;

        return $this;
    }

    public function getMontantReglement(): ?float
    {
        return $this->montant_reglement;
    }

    public function setMontantReglement(?float $montant_reglement): self
    {
        $this->montant_reglement = $montant_reglement;

        return $this;
    }

    public function getRestantEncaisse(): ?float
    {
        return $this->restant_encaisse;
    }

    public function setRestantEncaisse(?float $restant_encaisse): self
    {
        $this->restant_encaisse = $restant_encaisse;

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): self
    {
        $this->paiement = $paiement;

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

    /**
     * @return Collection|Vente[]
     */
    public function getVente(): Collection
    {
        return $this->ventes;
    }

    public function addVente(Vente $devisPro): self
    {
        if (!$this->ventes->contains($devisPro)) {
            $this->ventes[] = $devisPro;
            $devisPro->setReglement($this);
        }

        return $this;
    }

    public function removeVente(Vente $devisPro): self
    {
        if ($this->ventes->contains($devisPro)) {
            $this->ventes->removeElement($devisPro);
            // set the owning side to null (unless already changed)
            if ($devisPro->getReglement() === $this) {
                $devisPro->setReglement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Vente[]
     */
    public function getVentes(): Collection
    {
        return $this->ventes;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getDocumentSerialize(){
        $documents = unserialize($this->document); 
        if(!empty($documents))
            return $documents;
        return [];
    }
}
