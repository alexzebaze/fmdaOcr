<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Horaire
 *
 * @ApiResource()
 *
 * @ORM\Table(name="horaire")
 * @ORM\Entity(repositoryClass="App\Repository\HoraireRepository")
 */
class Horaire
{
    /**
     * @var int
     *
     * @ORM\Column(name="idsession", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idsession;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datestart", type="datetime", nullable=false)
     */
    private $datestart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateend", type="datetime", nullable=true)
     */
    private $dateend;

    /**
     * @var string|null
     *
     * @Groups({"read", "write"})
     * @ORM\Column(name="time", type="decimal", precision=13, scale=2, nullable=true)
     */
    private $time;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pause", type="decimal", precision=13, scale=2, nullable=true)
     */
    private $pause;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fictif", type="decimal", precision=13, scale=2, nullable=true)
     */
    private $fictif;

    /**
     * @var int
     *
     * @ORM\Column(name="userid", type="integer", nullable=false)
     */
    private $userid;

    /**
     * @var int
     *
     * @ORM\Column(name="chantierid", type="integer", nullable=true)
     */
    private $chantierid;

    /**
     * @var string
     *
     * @ORM\Column(name="fonction", type="text", length=65535, nullable=true)
     */
    private $fonction;

    /**
     * @var string
     *
     * @ORM\Column(name="absence", type="integer", nullable=true)
     */
    private $absence = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Vente::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $devis;

    public function __construct(){
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getIdsession(): int
    {
        return $this->idsession;
    }

    /**
     * @param int $idsession
     */
    public function setIdsession(int $idsession): void
    {
        $this->idsession = $idsession;
    }

    /**
     * @return \DateTime
     */
    public function getDatestart(): ?\DateTime
    {
        return $this->datestart;
    }

    /**
     * @param \DateTime $datestart
     */
    public function setDatestart($datestart): void
    {
        if($datestart instanceof \DateTime) {
            $this->datestart = $datestart;
        }
        else{
            if(strpos($datestart, '/') !== false ) {
                $date = \DateTime::createFromFormat('d/m/Y H:i', $datestart);
                $date->setTime($date->format('H'), $date->format('i'), 0);
                $this->datestart = $date;
            } else {
                $this->datestart = $datestart;
            }    
        }
        
    }

    /**
     * @return \DateTime
     */
    public function getDateend(): ?\DateTime
    {
        return $this->dateend;
    }

    /**
     * @param \DateTime $dateend
     */
    public function setDateend($dateend): void
    {
        if($dateend instanceof \DateTime) {
            $this->dateend = $dateend;
        }
        else{
            if(strpos($dateend, '/') !== false ) {
                $date = \DateTime::createFromFormat('d/m/Y H:i', $dateend);
                $date->setTime($date->format('H'), $date->format('i'), 0);
                $this->dateend = $date;
            } else {
                $this->dateend = $dateend;
            }    
        }
        
    }

    /**
     * @return string|null
     */
    public function getTime(): ?string
    {
        return $this->time;
    }

    /**
     * @param string|null $time
     */
    public function setTime(?string $time): void
    {
        $this->time = $time;
    }

    /**
     * @return string|null
     */
    public function getPause(): ?string
    {
        return $this->pause;
    }
    
    /**
     * @param string|null $pause
     */
    public function setPause(?string $pause): void
    {
        $this->pause = $pause;
    }

    /**
     * @return int
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * @param int $userid
     */
    public function setUserid(int $userid): void
    {
        $this->userid = $userid;
    }

    /**
     * @return int
     */
    public function getChantierid(): ?int
    {
        return $this->chantierid;
    }

    /**
     * @param int $chantierid
     */
    public function setChantierid($chantierid): void
    {
        if(is_object($chantierid)) {
            $this->chantierid = $chantierid->getChantierid();
        } else {
            $this->chantierid = $chantierid;
        }
    }

    /**
     * @return string
     */
    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    /**
     * @param string $fonction
     */
    public function setFonction($fonction): void
    {
        if(is_object($fonction)) {
            $this->fonction = $fonction->getCategory();
        } else {
            $this->fonction = $fonction;
        }
    }

    /**
     * @return string|null
     */
    public function getFictif()
    {
        return $this->fictif;
    }

    /**
     * @param string|null $fictif
     */
    public function setFictif($fictif)
    {
        $this->fictif = $fictif;
    }

    /**
     * @return int
     */
    public function getAbsence()
    {
        return $this->absence;
    }

    /**
     * @param int $absence
     */
    public function setAbsence($absence)
    {
        $this->absence = $absence;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        if($createdAt instanceof \DateTime) {
            $this->createdAt = $createdAt;
        }
        else{
            if(strpos($createdAt, '/') !== false ) {
                $date = \DateTime::createFromFormat('d/m/Y H:i', $createdAt);
                $date->setTime($date->format('H'), $date->format('i'), 0);
                $this->createdAt = $date;
            } else {
                $this->createdAt = $createdAt;
            }    
        }
        
    }

    public function getDevis(): ?Vente
    {
        return $this->devis;
    }

    public function setDevis(?Vente $devis): self
    {
        $this->devis = $devis;

        return $this;
    }

}
