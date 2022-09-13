<?php

namespace App\Entity;

use App\Repository\LotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Lot
 * 
 * @ApiResource()
 * @ORM\Entity(repositoryClass=GalerieRepository::class)
 * @ORM\Table(name="lot")
 * @ORM\Entity
 */
class Lot
{

    /**
     * @ORM\ManyToOne(targetEntity=PrevisionelCategorie::class, inversedBy="lots", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $previsionel_categorie;

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
     * @ORM\Column(name="lot", type="string", length=100, nullable=false)
     */
    private $lot;

    /**
     * @var int
     *
     * @ORM\Column(name="label", type="integer", nullable=true)
     */
    private $label;

    /**
     * @var int
     *
     * @ORM\Column(name="num", type="integer", nullable=true)
     */
    private $num;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise", inversedBy="categories")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;

    /**
     * @ORM\OneToMany(targetEntity=Achat::class, mappedBy="lot")
     */
    private $achats;

    /**
     * @ORM\OneToMany(targetEntity=Vente::class, mappedBy="lot")
     */
    private $ventes;

    public function __construct()
    {
        $this->achats = new ArrayCollection();
        $this->ventes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLot(): ?string
    {
        return $this->lot;
    }

    public function setLot(string $lot): self
    {
        $this->lot = $lot;

        return $this;
    }

    public function getLabel(): ?int
    {
        return $this->label;
    }

    public function setLabel(?int $label): self
    {
        $this->label = $label;

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
            $achat->setLot($this);
        }

        return $this;
    }

    public function removeAchat(Achat $achat): self
    {
        if ($this->achats->contains($achat)) {
            $this->achats->removeElement($achat);
            // set the owning side to null (unless already changed)
            if ($achat->getLot() === $this) {
                $achat->setLot(null);
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

    public function addVente(Vente $vente): self
    {
        if (!$this->ventes->contains($vente)) {
            $this->ventes[] = $vente;
            $vente->setLot($this);
        }

        return $this;
    }

    public function removeVente(Vente $vente): self
    {
        if ($this->ventes->contains($vente)) {
            $this->ventes->removeElement($vente);
            // set the owning side to null (unless already changed)
            if ($vente->getLot() === $this) {
                $vente->setLot(null);
            }
        }

        return $this;
    }

    public function getPrevisionelCategorie(): ?PrevisionelCategorie
    {
        return $this->previsionel_categorie;
    }

    public function setPrevisionelCategorie(?PrevisionelCategorie $previsionel_categorie): self
    {
        $this->previsionel_categorie = $previsionel_categorie;

        return $this;
    }


}
