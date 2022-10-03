<?php

namespace App\Entity;

use App\Repository\TmpOcrRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TmpOcrRepository::class)
 */
class TmpOcr
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Entreprise")
     * @ORM\JoinColumn(name="entreprise_id", referencedColumnName="id")
     */
    private $entreprise;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $name;
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dossier;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $blocktype;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalTtc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalHt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $total_ht_list;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $total_ttc_list;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $documentId;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
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

    public function toArray(){
        return array(
            "text"=>  $this->getName(),
            "left"=> $this->getPositionLeft(),
            "top"=> $this->getPositionTop(),
            "height"=> $this->getSizeHeight(),
            "width"=> $this->getSizeWidth()
        );
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

    public function getDossier(): ?string
    {
        return $this->dossier;
    }

    public function setDossier(?string $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getBlocktype(): ?string
    {
        return $this->blocktype;
    }

    public function setBlocktype(string $blocktype): self
    {
        $this->blocktype = $blocktype;

        return $this;
    }

    public function getTotalHtList(): ?string
    {
        return $this->total_ht_list;
    }

    public function setTotalHtList(?string $total_ht_list): self
    {
        $this->total_ht_list = $total_ht_list;

        return $this;
    }

    public function getTotalTtcList(): ?string
    {
        return $this->total_ttc_list;
    }

    public function setTotalTtcList(?string $total_ttc_list): self
    {
        $this->total_ttc_list = $total_ttc_list;

        return $this;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): self
    {
        $this->documentId = $documentId;

        return $this;
    }

}
