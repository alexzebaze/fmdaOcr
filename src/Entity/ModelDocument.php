<?php

namespace App\Entity;

use App\Repository\ModelDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass=ModelDocumentRepository::class)
 */
class ModelDocument implements JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbr_page;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbrPage(): ?int
    {
        return $this->nbr_page;
    }

    public function setNbrPage(int $nbr_page): self
    {
        $this->nbr_page = $nbr_page;

        return $this;
    }

        public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'nbr_page' => $this->nbr_page
        );
    }
}