<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fkuser
 *
 * @ORM\Table(name="fkuser")
 * @ORM\Entity
 */
class Fkuser
{
    /**
     * @var int
     *
     * @ORM\Column(name="idfkuser", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idfkuser;

    /**
     * @var int
     *
     * @ORM\Column(name="fkuser", type="integer", nullable=false)
     */
    private $fkuser;

    /**
     * @var int
     *
     * @ORM\Column(name="fkreceiver", type="integer", nullable=false)
     */
    private $fkreceiver;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    private $date;

    public function getIdfkuser(): ?int
    {
        return $this->idfkuser;
    }

    public function getFkuser(): ?int
    {
        return $this->fkuser;
    }

    public function setFkuser(int $fkuser): self
    {
        $this->fkuser = $fkuser;

        return $this;
    }

    public function getFkreceiver(): ?int
    {
        return $this->fkreceiver;
    }

    public function setFkreceiver(int $fkreceiver): self
    {
        $this->fkreceiver = $fkreceiver;

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


}
