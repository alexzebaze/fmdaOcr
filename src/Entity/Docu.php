<?php

namespace App\Entity;

use App\Repository\DocuRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DocuRepository::class)
 */
class Docu
{
    /**
     * @ORM\ManyToOne(targetEntity=Entdocu::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $entdocu; 

    /**
     * @ORM\ManyToOne(targetEntity=Article::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $article; 

    /**
     * @ORM\ManyToOne(targetEntity=Tva::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $tva; 

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $document_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qte;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pvht;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pvttc;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $type_article;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $remise;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rang;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $offert;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $total_ht;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unite;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commentaire;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $prixAchat;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $marge;

    public function __construct()
    {
        $this->date = new \DateTime();

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentId(): ?string
    {
        return $this->document_id;
    }

    public function setDocumentId(?string $document_id): self
    {
        $this->document_id = $document_id;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    public function getPvht(): ?float
    {
        return $this->pvht;
    }

    public function setPvht(?float $pvht): self
    {
        $this->pvht = $pvht;

        return $this;
    }

    public function getPvttc(): ?float
    {
        return $this->pvttc;
    }

    public function setPvttc(?float $pvttc): self
    {
        $this->pvttc = $pvttc;

        return $this;
    }

    public function getTypeArticle(): ?int
    {
        return $this->type_article;
    }

    public function setTypeArticle(?int $type_article): self
    {
        $this->type_article = $type_article;

        return $this;
    }

    public function getRemise(): ?float
    {
        return $this->remise;
    }

    public function setRemise(?float $remise): self
    {
        $this->remise = $remise;

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

    public function getOffert(): ?bool
    {
        return $this->offert;
    }

    public function setOffert(?bool $offert): self
    {
        $this->offert = $offert;

        return $this;
    }

    public function getEntdocu(): ?Entdocu
    {
        return $this->entdocu;
    }

    public function setEntdocu(?Entdocu $entdocu): self
    {
        $this->entdocu = $entdocu;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTotalHt()
    {
        return $this->total_ht;
    }

    public function setTotalHt($total_ht): self
    {
        $this->total_ht = $total_ht;

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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;

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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

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

    public function getMarge(): ?float
    {
        return $this->marge;
    }

    public function setMarge(?float $marge): self
    {
        $this->marge = $marge;

        return $this;
    }
}
