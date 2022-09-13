<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LocationRepository::class)
 */
class Location
{

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Logement")
     * @ORM\JoinColumn(nullable=true)
     */
    private $logement;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\LocationPaiement",mappedBy="location", cascade={"persist"})
     */
    private $paiements;


    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="App\Entity\ReleveLocation",mappedBy="location", cascade={"persist"})
     */
    private $releves;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $locataire;

    /**
     * @ORM\ManyToOne(targetEntity=Paiement::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $paiement;

    /**
     * @ORM\ManyToOne(targetEntity=Chantier::class)
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=true)
     */
    private $bien;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $identifiant;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $utilisation;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $debut_bail;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fin_bail;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duree_bail;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $renouvellement;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_paiement;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $periodicite;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $generateur_loyer;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $loyer_hc;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $echeance_paiement;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $loyer_charge_comprise;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $loyer_charge;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $charge_provision_forfait;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $tva;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $depot_garantie;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $logement_paiement;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $frais_retard;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $loyer_montant;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $indice_referentiel_1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $indice_referentiel_2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $revision_automatique;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $periode;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $frais_retard_percent;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $is_loyer_reference;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $is_loyer_majore;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $loyer_reference;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $loyer_majore;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $complement_loyer;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $complement_description;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fin_dernier_bail;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $dernier_loyer;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_versement;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_derniere_revision;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $is_loyer_reevalue;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $mandat_sepa;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $etat_lieux;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $diagnostique;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $attestation_assurance;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cheque_depot_garantie;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offre_location_signe;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $facture_eau;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $Location;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bail;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $plan;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $diag_plomb;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $certificat_mesurage;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $diag_amiante;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->releves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifiant(): ?string
    {
        return $this->identifiant;
    }

    public function setIdentifiant(string $identifiant): self
    {
        $this->identifiant = $identifiant;

        return $this;
    }

    public function getUtilisation(): ?int
    {
        return $this->utilisation;
    }

    public function setUtilisation(int $utilisation): self
    {
        $this->utilisation = $utilisation;

        return $this;
    }

    public function getDebutBail(): ?\DateTimeInterface
    {
        return $this->debut_bail;
    }

    public function setDebutBail(?\DateTimeInterface $debut_bail): self
    {
        $this->debut_bail = $debut_bail;

        return $this;
    }

    public function getFinBail(): ?\DateTimeInterface
    {
        return $this->fin_bail;
    }

    public function setFinBail(?\DateTimeInterface $fin_bail): self
    {
        $this->fin_bail = $fin_bail;

        return $this;
    }

    public function getDureeBail(): ?int
    {
        return $this->duree_bail;
    }

    public function setDureeBail(int $duree_bail): self
    {
        $this->duree_bail = $duree_bail;

        return $this;
    }

    public function getRenouvellement(): ?bool
    {
        return $this->renouvellement;
    }

    public function setRenouvellement(?bool $renouvellement): self
    {
        $this->renouvellement = $renouvellement;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->date_paiement;
    }

    public function setDatePaiement(?\DateTimeInterface $date_paiement): self
    {
        $this->date_paiement = $date_paiement;

        return $this;
    }

    public function getPeriodicite(): ?\DateTimeInterface
    {
        return $this->periodicite;
    }

    public function setPeriodicite(?\DateTimeInterface $periodicite): self
    {
        $this->periodicite = $periodicite;

        return $this;
    }

    public function getGenerateurLoyer(): ?int
    {
        return $this->generateur_loyer;
    }

    public function setGenerateurLoyer(?int $generateur_loyer): self
    {
        $this->generateur_loyer = $generateur_loyer;

        return $this;
    }

    public function getLoyerHc(): ?float
    {
        return $this->loyer_hc;
    }

    public function setLoyerHc(?float $loyer_hc): self
    {
        $this->loyer_hc = $loyer_hc;

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

    public function getBien(): ?Chantier
    {
        return $this->bien;
    }

    public function setBien(?Chantier $bien): self
    {
        $this->bien = $bien;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEcheancePaiement(): ?string
    {
        return $this->echeance_paiement;
    }

    public function setEcheancePaiement(?string $echeance_paiement): self
    {
        $this->echeance_paiement = $echeance_paiement;

        return $this;
    }

    public function getLoyerChargeComprise(): ?float
    {
        return $this->loyer_charge_comprise;
    }

    public function setLoyerChargeComprise(?float $loyer_charge_comprise): self
    {
        $this->loyer_charge_comprise = $loyer_charge_comprise;

        return $this;
    }

    public function getLoyerCharge(): ?float
    {
        return $this->loyer_charge;
    }

    public function setLoyerCharge(?float $loyer_charge): self
    {
        $this->loyer_charge = $loyer_charge;

        return $this;
    }

    public function getChargeProvisionForfait(): ?int
    {
        return $this->charge_provision_forfait;
    }

    public function setChargeProvisionForfait(?int $charge_provision_forfait): self
    {
        $this->charge_provision_forfait = $charge_provision_forfait;

        return $this;
    }

    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(?float $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getDepotGarantie(): ?float
    {
        return $this->depot_garantie;
    }

    public function setDepotGarantie(?float $depot_garantie): self
    {
        $this->depot_garantie = $depot_garantie;

        return $this;
    }

    public function getLogementPaiement(): ?float
    {
        return $this->logement_paiement;
    }

    public function setLogementPaiement(?float $logement_paiement): self
    {
        $this->logement_paiement = $logement_paiement;

        return $this;
    }

    public function getFraisRetard(): ?float
    {
        return $this->frais_retard;
    }

    public function setFraisRetard(?float $frais_retard): self
    {
        $this->frais_retard = $frais_retard;

        return $this;
    }

    public function getLoyerMontant(): ?float
    {
        return $this->loyer_montant;
    }

    public function setLoyerMontant(?float $loyer_montant): self
    {
        $this->loyer_montant = $loyer_montant;

        return $this;
    }

    public function getIndiceReferentiel1(): ?string
    {
        return $this->indice_referentiel_1;
    }

    public function setIndiceReferentiel1(?string $indice_referentiel_1): self
    {
        $this->indice_referentiel_1 = $indice_referentiel_1;

        return $this;
    }

    public function getIndiceReferentiel2(): ?string
    {
        return $this->indice_referentiel_2;
    }

    public function setIndiceReferentiel2(?string $indice_referentiel_2): self
    {
        $this->indice_referentiel_2 = $indice_referentiel_2;

        return $this;
    }

    public function getRevisionAutomatique(): ?int
    {
        return $this->revision_automatique;
    }

    public function setRevisionAutomatique(?int $revision_automatique): self
    {
        $this->revision_automatique = $revision_automatique;

        return $this;
    }

    public function getPeriode()
    {
        return $this->periode;
    }

    public function setPeriode($periode): self
    {
        $this->periode = $periode;

        return $this;
    }

    public function getFraisRetardPercent(): ?float
    {
        return $this->frais_retard_percent;
    }

    public function setFraisRetardPercent(?float $frais_retard_percent): self
    {
        $this->frais_retard_percent = $frais_retard_percent;

        return $this;
    }

    public function getIsLoyerReference(): ?int
    {
        return $this->is_loyer_reference;
    }

    public function setIsLoyerReference(?int $is_loyer_reference): self
    {
        $this->is_loyer_reference = $is_loyer_reference;

        return $this;
    }

    public function getIsLoyerMajore(): ?int
    {
        return $this->is_loyer_majore;
    }

    public function setIsLoyerMajore(?int $is_loyer_majore): self
    {
        $this->is_loyer_majore = $is_loyer_majore;

        return $this;
    }

    public function getLoyerReference(): ?float
    {
        return $this->loyer_reference;
    }

    public function setLoyerReference(?float $loyer_reference): self
    {
        $this->loyer_reference = $loyer_reference;

        return $this;
    }

    public function getLoyerMajore(): ?float
    {
        return $this->loyer_majore;
    }

    public function setLoyerMajore(?float $loyer_majore): self
    {
        $this->loyer_majore = $loyer_majore;

        return $this;
    }

    public function getComplementLoyer(): ?float
    {
        return $this->complement_loyer;
    }

    public function setComplementLoyer(?float $complement_loyer): self
    {
        $this->complement_loyer = $complement_loyer;

        return $this;
    }

    public function getComplementDescription(): ?string
    {
        return $this->complement_description;
    }

    public function setComplementDescription(?string $complement_description): self
    {
        $this->complement_description = $complement_description;

        return $this;
    }

    public function getFinDernierBail(): ?int
    {
        return $this->fin_dernier_bail;
    }

    public function setFinDernierBail(?int $fin_dernier_bail): self
    {
        $this->fin_dernier_bail = $fin_dernier_bail;

        return $this;
    }

    public function getDernierLoyer(): ?float
    {
        return $this->dernier_loyer;
    }

    public function setDernierLoyer(?float $dernier_loyer): self
    {
        $this->dernier_loyer = $dernier_loyer;

        return $this;
    }

    public function getDateVersement(): ?\DateTimeInterface
    {
        return $this->date_versement;
    }

    public function setDateVersement(?\DateTimeInterface $date_versement): self
    {
        $this->date_versement = $date_versement;

        return $this;
    }

    public function getDateDerniereRevision(): ?\DateTimeInterface
    {
        return $this->date_derniere_revision;
    }

    public function setDateDerniereRevision(?\DateTimeInterface $date_derniere_revision): self
    {
        $this->date_derniere_revision = $date_derniere_revision;

        return $this;
    }

    public function getIsLoyerReevalue(): ?int
    {
        return $this->is_loyer_reevalue;
    }

    public function setIsLoyerReevalue(?int $is_loyer_reevalue): self
    {
        $this->is_loyer_reevalue = $is_loyer_reevalue;

        return $this;
    }

    public function getLocataire(): ?Client
    {
        return $this->locataire;
    }

    public function setLocataire(?Client $locataire): self
    {
        $this->locataire = $locataire;

        return $this;
    }

    /**
     * @return Collection|LocationPaiement[]
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(LocationPaiement $paiement): self
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements[] = $paiement;
            $paiement->setLocation($this);
        }

        return $this;
    }

    public function removePaiement(LocationPaiement $paiement): self
    {
        if ($this->paiements->contains($paiement)) {
            $this->paiements->removeElement($paiement);
            // set the owning side to null (unless already changed)
            if ($paiement->getLocation() === $this) {
                $paiement->setLocation(null);
            }
        }

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

    public function getLogement(): ?Logement
    {
        return $this->logement;
    }

    public function setLogement(?Logement $logement): self
    {
        $this->logement = $logement;

        return $this;
    }

    public function getMandatSepa(): ?string
    {
        return $this->mandat_sepa;
    }

    public function setMandatSepa(?string $mandat_sepa): self
    {
        $this->mandat_sepa = $mandat_sepa;

        return $this;
    }

    public function getEtatLieux(): ?string
    {
        return $this->etat_lieux;
    }

    public function setEtatLieux(string $etat_lieux): self
    {
        $this->etat_lieux = $etat_lieux;

        return $this;
    }

    public function getDiagnostique(): ?string
    {
        return $this->diagnostique;
    }

    public function setDiagnostique(?string $diagnostique): self
    {
        $this->diagnostique = $diagnostique;

        return $this;
    }

    public function getAttestationAssurance(): ?string
    {
        return $this->attestation_assurance;
    }

    public function setAttestationAssurance(?string $attestation_assurance): self
    {
        $this->attestation_assurance = $attestation_assurance;

        return $this;
    }

    public function getChequeDepotGarantie(): ?string
    {
        return $this->cheque_depot_garantie;
    }

    public function setChequeDepotGarantie(?string $cheque_depot_garantie): self
    {
        $this->cheque_depot_garantie = $cheque_depot_garantie;

        return $this;
    }

    public function getOffreLocationSigne(): ?string
    {
        return $this->offre_location_signe;
    }

    public function setOffreLocationSigne(?string $offre_location_signe): self
    {
        $this->offre_location_signe = $offre_location_signe;

        return $this;
    }

    /**
     * @return Collection|ReleveLocation[]
     */
    public function getReleves(): Collection
    {
        return $this->releves;
    }

    public function addReleve(ReleveLocation $releve): self
    {
        if (!$this->releves->contains($releve)) {
            $this->releves[] = $releve;
            $releve->setLocation($this);
        }

        return $this;
    }

    public function removeReleve(ReleveLocation $releve): self
    {
        if ($this->releves->contains($releve)) {
            $this->releves->removeElement($releve);
            // set the owning side to null (unless already changed)
            if ($releve->getLocation() === $this) {
                $releve->setLocation(null);
            }
        }

        return $this;
    }

    public function getFactureEau(): ?string
    {
        return $this->facture_eau;
    }

    public function setFactureEau(?string $facture_eau): self
    {
        $this->facture_eau = $facture_eau;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->Location;
    }

    public function setLocation(?string $Location): self
    {
        $this->Location = $Location;

        return $this;
    }

    public function getBail(): ?string
    {
        return $this->bail;
    }

    public function setBail(?string $bail): self
    {
        $this->bail = $bail;

        return $this;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setPlan(?string $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getDiagPlomb(): ?string
    {
        return $this->diag_plomb;
    }

    public function setDiagPlomb(?string $diag_plomb): self
    {
        $this->diag_plomb = $diag_plomb;

        return $this;
    }

    public function getCertificatMesurage(): ?string
    {
        return $this->certificat_mesurage;
    }

    public function setCertificatMesurage(?string $certificat_mesurage): self
    {
        $this->certificat_mesurage = $certificat_mesurage;

        return $this;
    }

    public function getDiagAmiante(): ?string
    {
        return $this->diag_amiante;
    }

    public function setDiagAmiante(?string $diag_amiante): self
    {
        $this->diag_amiante = $diag_amiante;

        return $this;
    }

}
