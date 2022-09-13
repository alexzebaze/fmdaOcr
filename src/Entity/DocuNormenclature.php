<?php

namespace App\Entity;

use App\Repository\DocuNormenclatureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DocuNormenclatureRepository::class)
 */
class DocuNormenclature
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
    private $codeArticle;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $libelle;

    /**
     * @ORM\Column(type="float")
     */
    private $prixAchat;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prixVente;

    /**
     * @ORM\Column(type="float")
     */
    private $qte;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ttMarge;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ttPrixAchat;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ttPrixVente;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unite;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $marge;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pourcentageMarge;

    /**
     * @ORM\ManyToOne(targetEntity=Entdocu::class, inversedBy="docuNormenclatures")
     * @ORM\JoinColumn(nullable=false)
     */
    private $devis;

    /**
     * @ORM\ManyToOne(targetEntity=Article::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $articleReference;

    /**
     * @ORM\ManyToOne(targetEntity=Article::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $articleNormenclature;  

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $marge_brut;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeArticle(): ?string
    {
        return $this->codeArticle;
    }

    public function setCodeArticle(string $codeArticle): self
    {
        $this->codeArticle = $codeArticle;

        return $this;
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

    public function getPrixAchat(): ?float
    {
        return $this->prixAchat;
    }

    public function setPrixAchat(float $prixAchat): self
    {
        $this->prixAchat = $prixAchat;

        return $this;
    }

    public function getPrixVente(): ?string
    {
        return $this->prixVente;
    }

    public function setPrixVente(?string $prixVente): self
    {
        $this->prixVente = $prixVente;

        return $this;
    }

    public function getQte(): ?float
    {
        return $this->qte;
    }

    public function setQte(float $qte): self
    {
        $this->qte = $qte;

        return $this;
    }

    public function getTtMarge(): ?float
    {
        return $this->ttMarge;
    }

    public function setTtMarge(?float $ttMarge): self
    {
        $this->ttMarge = $ttMarge;

        return $this;
    }

    public function getTtPrixAchat(): ?float
    {
        return $this->ttPrixAchat;
    }

    public function setTtPrixAchat(?float $ttPrixAchat): self
    {
        $this->ttPrixAchat = $ttPrixAchat;

        return $this;
    }

    public function getTtPrixVente(): ?float
    {
        return $this->ttPrixVente;
    }

    public function setTtPrixVente(float $ttPrixVente): self
    {
        $this->ttPrixVente = $ttPrixVente;

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

    public function getMarge(): ?float
    {
        return $this->marge;
    }

    public function setMarge(?float $marge): self
    {
        $this->marge = $marge;

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

    public function getDevis(): ?Entdocu
    {
        return $this->devis;
    }

    public function setDevis(?Entdocu $devis): self
    {
        $this->devis = $devis;

        return $this;
    }

    public function getArticleReference(): ?Article
    {
        return $this->articleReference;
    }

    public function setArticleReference(?Article $articleReference): self
    {
        $this->articleReference = $articleReference;

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

    public function getMargeBrut(): ?float
    {
        return $this->marge_brut;
    }

    public function setMargeBrut(?float $marge_brut): self
    {
        $this->marge_brut = $marge_brut;

        return $this;
    }
}
