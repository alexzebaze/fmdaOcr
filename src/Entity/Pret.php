<?php

namespace App\Entity;

use App\Repository\PretRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PretRepository::class)
 */
class Pret
{
    const EVENEMENT = [
        '1'=>'Differé',
        '2'=>'Déblocage',
        '3'=>'Incident'
    ]; 

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;    

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=true)
     */
    private $bien;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Amortissement",mappedBy="pret", cascade={"persist"})
     */
    private $amortissements;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Banque")
     * @ORM\JoinColumn(nullable=true)
    */
    private $banque;
    
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $echeance;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_deblocage;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $capital;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $montant_echeance;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $differe;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duree;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $taux;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $capital_restant;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_fin;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contrat;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $montant_echeance_1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duree_differe;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cout_interet;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cout_assurance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cout_total;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $taux_assurance;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_premiere_echeance;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $debut_prelevement_interet;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $interet1;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $remboursement1;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $montantDeblocage;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $evenement;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateDiffere;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dureeDiffere2;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $rembourssementInteret;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $montantDeblocageDiffere;

    public function __construct()
    {
        $this->amortissements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEcheance(): ?int
    {
        return $this->echeance;
    }

    public function setEcheance(?int $echeance): self
    {
        $this->echeance = $echeance;

        return $this;
    }

    public function getDateDeblocage(): ?\DateTimeInterface
    {
        return $this->date_deblocage;
    }

    public function setDateDeblocage(?\DateTimeInterface $date_deblocage): self
    {
        $this->date_deblocage = $date_deblocage;

        return $this;
    }

    public function getCapital(): ?float
    {
        return $this->capital;
    }

    public function setCapital(?float $capital): self
    {
        $this->capital = $capital;

        return $this;
    }

    public function getMontantEcheance(): ?float
    {
        return $this->montant_echeance;
    }

    public function setMontantEcheance(?float $montant_echeance): self
    {
        $this->montant_echeance = $montant_echeance;

        return $this;
    }

    public function getDiffere(): ?int
    {
        return $this->differe;
    }

    public function setDiffere(?int $differe): self
    {
        $this->differe = $differe;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): self
    {
        $this->duree = $duree;

        return $this;
    }

    public function getTaux(): ?float
    {
        return $this->taux;
    }

    public function setTaux(?float $taux): self
    {
        $this->taux = $taux;

        return $this;
    }

    public function getCapitalRestant(): ?float
    {
        return $this->capital_restant;
    }

    public function setCapitalRestant(?float $capital_restant): self
    {
        $this->capital_restant = $capital_restant;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTimeInterface $date_fin): self
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getContrat(): ?string
    {
        return $this->contrat;
    }

    public function setContrat(?string $contrat): self
    {
        $this->contrat = $contrat;

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

    public function getBanque(): ?Banque
    {
        return $this->banque;
    }

    public function setBanque(?Banque $banque): self
    {
        $this->banque = $banque;

        return $this;
    }

    /**
     * @return Collection|Amortissement[]
     */
    public function getAmortissements(): Collection
    {
        return $this->amortissements;
    }

    public function addAmortissement(Amortissement $amortissement): self
    {
        if (!$this->amortissements->contains($amortissement)) {
            $this->amortissements[] = $amortissement;
            $amortissement->setPret($this);
        }

        return $this;
    }

    public function removeAmortissement(Amortissement $amortissement): self
    {
        if ($this->amortissements->contains($amortissement)) {
            $this->amortissements->removeElement($amortissement);
            // set the owning side to null (unless already changed)
            if ($amortissement->getPret() === $this) {
                $amortissement->setPret(null);
            }
        }

        return $this;
    }

    public function getBien(): ?Chantier
    {
        return $this->bien;
    }

    public function setBien(?Chantier $bien): self
    {
        $this->bien = $bien;

        return $this;
    }

    public function getMontantEcheance1(): ?string
    {
        return $this->montant_echeance_1;
    }

    public function setMontantEcheance1(?string $montant_echeance_1): self
    {
        $this->montant_echeance_1 = $montant_echeance_1;

        return $this;
    }

    public function getDureeDiffere(): ?int
    {
        return $this->duree_differe;
    }

    public function setDureeDiffere(?int $duree_differe): self
    {
        $this->duree_differe = $duree_differe;

        return $this;
    }

    public function getCoutInteret(): ?float
    {
        return $this->cout_interet;
    }

    public function setCoutInteret(?float $cout_interet): self
    {
        $this->cout_interet = $cout_interet;

        return $this;
    }

    public function getCoutAssurance(): ?float
    {
        return $this->cout_assurance;
    }

    public function setCoutAssurance(?float $cout_assurance): self
    {
        $this->cout_assurance = $cout_assurance;

        return $this;
    }

    public function getCoutTotal(): ?float
    {
        return $this->cout_total;
    }

    public function setCoutTotal(?float $cout_total): self
    {
        $this->cout_total = $cout_total;

        return $this;
    }

    public function getTauxAssurance(): ?float
    {
        return $this->taux_assurance;
    }

    public function setTauxAssurance(?float $taux_assurance): self
    {
        $this->taux_assurance = $taux_assurance;

        return $this;
    }

    public function getDatePremiereEcheance(): ?\DateTimeInterface
    {
        return $this->date_premiere_echeance;
    }

    public function setDatePremiereEcheance(?\DateTimeInterface $date_premiere_echeance): self
    {
        $this->date_premiere_echeance = $date_premiere_echeance;

        return $this;
    }

    public function getDebutPrelevementInteret(): ?\DateTimeInterface
    {
        return $this->debut_prelevement_interet;
    }

    public function setDebutPrelevementInteret(?\DateTimeInterface $debut_prelevement_interet): self
    {
        $this->debut_prelevement_interet = $debut_prelevement_interet;

        return $this;
    }

    public function getInteret1(): ?float
    {
        return $this->interet1;
    }

    public function setInteret1(?float $interet1): self
    {
        $this->interet1 = $interet1;

        return $this;
    }

    public function getRemboursement1(): ?float
    {
        return $this->remboursement1;
    }

    public function setRemboursement1(?float $remboursement1): self
    {
        $this->remboursement1 = $remboursement1;

        return $this;
    }

    public function getMontantDeblocage(): ?float
    {
        return $this->montantDeblocage;
    }

    public function setMontantDeblocage(?float $montantDeblocage): self
    {
        $this->montantDeblocage = $montantDeblocage;

        return $this;
    }

    public function getEvenement(): ?int
    {
        return $this->evenement;
    }

    public function setEvenement(?int $evenement): self
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getDateDiffere(): ?\DateTimeInterface
    {
        return $this->dateDiffere;
    }

    public function setDateDiffere(?\DateTimeInterface $dateDiffere): self
    {
        $this->dateDiffere = $dateDiffere;

        return $this;
    }

    public function getDureeDiffere2(): ?int
    {
        return $this->dureeDiffere2;
    }

    public function setDureeDiffere2(?int $dureeDiffere2): self
    {
        $this->dureeDiffere2 = $dureeDiffere2;

        return $this;
    }

    public function getRembourssementInteret(): ?bool
    {
        return $this->rembourssementInteret;
    }

    public function setRembourssementInteret(?bool $rembourssementInteret): self
    {
        $this->rembourssementInteret = $rembourssementInteret;

        return $this;
    }

    public function getMontantDeblocageDiffere(): ?float
    {
        return $this->montantDeblocageDiffere;
    }

    public function setMontantDeblocageDiffere(?float $montantDeblocageDiffere): self
    {
        $this->montantDeblocageDiffere = $montantDeblocageDiffere;

        return $this;
    }
}
