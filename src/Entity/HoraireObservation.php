<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

/**
 * Observation
 *
 * @ORM\Table(name="horaire_observation")
 * @ORM\Entity
 */
class HoraireObservation
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
     * @var string|null
     *
     * @ORM\Column(name="cout_global", type="string", length=255, nullable=true)
     */
    private $cout_global;

    /**
     * @var string|null
     *
     * @ORM\Column(name="observation", type="text", nullable=true)
     */
    private $observation;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Utilisateur", inversedBy="observation")
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
     * @return string|null
     */
    public function getCoutGlobal(): ?string
    {
        return $this->cout_global;
    }

    /**
     * @param string|null $cout_global
     */
    public function setCoutGlobal(?string $cout_global): void
    {
        $this->cout_global = $cout_global;
    }

    /**
     * @return string|null
     */
    public function getObservation(): ?string
    {
        return $this->observation;
    }

    /**
     * @param string|null $observation
     */
    public function setObservation($observation): void
    {
        $this->observation = $observation;
    }

    /**
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }


}
