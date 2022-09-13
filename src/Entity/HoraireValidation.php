<?php

namespace App\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Validation
 * 
 *
 * @ORM\Table(name="horaire_validation")
 * @ORM\Entity
 */
class HoraireValidation
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
     * @var boolean|null
     *
     * @ORM\Column(name="is_valide", type="boolean", nullable=true)
     */
    private $isValide;

    /**
     * @var Utilisateur
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Utilisateur", inversedBy="validations")
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
     * @return bool|null
     */
    public function getIsValide(): ?bool
    {
        return $this->isValide;
    }

    /**
     * @param bool|null $isValide
     */
    public function setIsValide(?bool $isValide): void
    {
        $this->isValide = $isValide;
    }

    /**
     * @return Utilisateur
     */
    public function getUser(): Utilisateur
    {
        return $this->user;
    }

    /**
     * @param Utilisateur $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }


}
