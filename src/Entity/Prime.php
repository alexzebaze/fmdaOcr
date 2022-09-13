<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Prime
 * 
 *
 * @ORM\Table(name="prime")
 * @ORM\Entity
 */
class Prime
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="annee", type="string", length=255)
     */
    private $annee;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mois", type="string", length=255)
     */
    private $mois;

    /**
     * @var float|null
     *
     * @ORM\Column(name="prime", type="float")
     */
    private $prime;

    /**
     * @var Utilisateur[]
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Utilisateur", inversedBy="prime")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid")
     */
    private $user;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getAnnee(): ?string
    {
        return $this->annee;
    }

    /**
     * @param string|null $annee
     */
    public function setAnnee(?string $annee): void
    {
        $this->annee = $annee;
    }

    /**
     * @return string|null
     */
    public function getMois(): ?string
    {
        return $this->mois;
    }

    /**
     * @param string|null $mois
     */
    public function setMois(?string $mois): void
    {
        $this->mois = $mois;
    }

    /**
     * @return float|null
     */
    public function getPrime(): ?float
    {
        return $this->prime;
    }

    /**
     * @param float|null $prime
     */
    public function setPrime(?float $prime): void
    {
        $this->prime = $prime;
    }

    /**
     * @return Utilisateur[]
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Utilisateur[] $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }



}
