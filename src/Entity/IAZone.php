<?php

namespace App\Entity;

use App\Repository\IAZoneRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=IAZoneRepository::class)
 */
class IAZone
{

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ModelDocument")
     */
    private $document;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PreferenceField")
     */
    private $field;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $position_top;

    /**
     * @ORM\Column(type="float")
     */
    private $position_left;

    /**
     * @ORM\Column(type="float")
     */
    private $size_width;

    /**
     * @ORM\Column(type="float")
     */
    private $size_height;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPositionTop(): ?float
    {
        return $this->position_top;
    }

    public function setPositionTop(float $position_top): self
    {
        $this->position_top = $position_top;

        return $this;
    }

    public function getPositionLeft(): ?float
    {
        return $this->position_left;
    }

    public function setPositionLeft(float $position_left): self
    {
        $this->position_left = $position_left;

        return $this;
    }

    public function getSizeWidth(): ?float
    {
        return $this->size_width;
    }

    public function setSizeWidth(float $size_width): self
    {
        $this->size_width = $size_width;

        return $this;
    }

    public function getSizeHeight(): ?float
    {
        return $this->size_height;
    }

    public function setSizeHeight(float $size_height): self
    {
        $this->size_height = $size_height;

        return $this;
    }

    public function getField(): ?PreferenceField
    {
        return $this->field;
    }

    public function setField(?PreferenceField $field): self
    {
        $this->field = $field;

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
}
