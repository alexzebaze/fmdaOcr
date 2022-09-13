<?php

namespace App\Entity;

use App\Repository\NormenclatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NormenclatureRepository::class)
 */
class Normenclature
{
    /**
     * @ORM\ManyToOne(targetEntity=Article::class, inversedBy="normenclatures", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $articleNormenclature;  

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
    */
    private $article_id;  

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $entreprise;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prix_vente_ttc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qte;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rang;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code_article;

    /**
     * @ORM\ManyToOne(targetEntity=Article::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $article_reference;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unite;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prixAchat;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pvUnitHt;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pourcentageMarge;
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getPrixVenteTtc(): ?float
    {
        return $this->prix_vente_ttc;
    }

    public function setPrixVenteTtc(?float $prix_vente_ttc): self
    {
        $this->prix_vente_ttc = $prix_vente_ttc;

        return $this;
    }

    public function getQte(): ?float
    {
        return $this->qte;
    }

    public function setQte(?float $qte): self
    {
        $this->qte = $qte;

        return $this;
    }

    public function getRang(): ?string
    {
        return $this->rang;
    }

    public function setRang(?string $rang): self
    {
        $this->rang = $rang;

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

    public function getCodeArticle(): ?string
    {
        return $this->code_article;
    }

    public function setCodeArticle(?string $code_article): self
    {
        $this->code_article = $code_article;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): self
    {
        $this->unite = $unite;

        return $this;
    }

    public function getArticleId(): ?string
    {
        return $this->article_id;
    }

    public function setArticleId(string $article_id): self
    {
        $this->article_id = $article_id;

        return $this;
    }

    public function getArticleNormenclature(): ?Article
    {
        return $this->articleNormenclature;
    }

    public function setArticleNormenclature(?Article $articleNormenclature): self
    {
        $this->articleNormenclature = $articleNormenclature;

        return $this;
    }

    public function getArticleReference(): ?Article
    {
        return $this->article_reference;
    }

    public function setArticleReference(?Article $article_reference): self
    {
        $this->article_reference = $article_reference;

        return $this;
    }

    public function getPrixAchat(): ?float
    {
        return $this->prixAchat;
    }

    public function setPrixAchat(?float $prixAchat): self
    {
        $this->prixAchat = $prixAchat;

        return $this;
    }

    public function getPvUnitHt(): ?float
    {
        return $this->pvUnitHt;
    }

    public function setPvUnitHt(?float $pvUnitHt): self
    {
        $this->pvUnitHt = $pvUnitHt;

        return $this;
    }

    public function getPourcentageMarge(): ?float
    {
        return $this->pourcentageMarge;
    }

    public function setPourcentageMarge(?float $pourcentageMarge): self
    {
        $this->pourcentageMarge = $pourcentageMarge;

        return $this;
    }
}
