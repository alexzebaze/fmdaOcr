<?php

namespace App\Entity;

use App\Repository\ConfigTotalRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConfigTotalRepository::class)
 */
class ConfigTotal
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $ht;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $ttc;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHt(): ?string
    {
        return $this->ht;
    }

    public function setHt(?string $ht): self
    {
        $this->ht = $ht;

        return $this;
    }

    public function getTtc(): ?string
    {
        return $this->ttc;
    }

    public function setTtc(?string $ttc): self
    {
        $this->ttc = $ttc;

        return $this;
    }
}
