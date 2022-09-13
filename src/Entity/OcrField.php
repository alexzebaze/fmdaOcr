<?php

namespace App\Entity;

use App\Repository\OcrFieldRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OcrFieldRepository::class)
 */
class OcrField
{

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise")
     */
    private $entreprise;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ModelDocument")
     */
    private $document;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", length=255)
     */
    private $positionLeft;
    /**
     * @ORM\Column(type="float", length=255)
     */
    private $positionTop;
    /**
     * @ORM\Column(type="float", length=255)
     */
    private $sizeWidth;
    /**
     * @ORM\Column(type="float", length=255)
     */
    private $sizeHeight;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dossier;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbr_page_doc;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $rotation;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getPositionLeft()
    {
        return $this->positionLeft;
    }

    /**
     * @param mixed $positionLeft
     */
    public function setPositionLeft($positionLeft): void
    {
        $this->positionLeft = $positionLeft;
    }

    /**
     * @return mixed
     */
    public function getPositionTop()
    {
        return $this->positionTop;
    }

    /**
     * @param mixed $positionTop
     */
    public function setPositionTop($positionTop): void
    {
        $this->positionTop = $positionTop;
    }

    /**
     * @return mixed
     */
    public function getSizeWidth()
    {
        return $this->sizeWidth;
    }

    /**
     * @param mixed $sizeWidth
     */
    public function setSizeWidth($sizeWidth): void
    {
        $this->sizeWidth = $sizeWidth;
    }

    /**
     * @return mixed
     */
    public function getSizeHeight()
    {
        return $this->sizeHeight;
    }

    /**
     * @param mixed $sizeHeight
     */
    public function setSizeHeight($sizeHeight): void
    {
        $this->sizeHeight = $sizeHeight;
    }

    public function getDossier(): ?string
    {
        return $this->dossier;
    }

    public function setDossier(string $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getNbrPageDoc(): ?int
    {
        return $this->nbr_page_doc;
    }

    public function setNbrPageDoc(int $nbr_page_doc): self
    {
        $this->nbr_page_doc = $nbr_page_doc;

        return $this;
    }

     public function getDocument(): ?ModelDocument
    {
        return $this->document;
    }

    public function setDocument(?ModelDocument $document): self
    {
        $this->document = $document;

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

    public function getRotation(): ?bool
    {
        return $this->rotation;
    }

    public function setRotation(?bool $rotation): self
    {
        $this->rotation = $rotation;

        return $this;
    }
}
