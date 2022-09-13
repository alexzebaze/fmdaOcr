<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Departement
 * 
 *
 * @ORM\Table(name="departement", indexes={@ORM\Index(name="fk_departement_region", columns={"departement_region"})})
 * @ORM\Entity
 */
class Departement
{
    /**
     * @var int
     *
     * @ORM\Column(name="departement_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $departementId;

    /**
     * @var string
     *
     * @ORM\Column(name="departement_code", type="string", length=3, nullable=false)
     */
    private $departementCode;

    /**
     * @var string
     *
     * @ORM\Column(name="departement_nom", type="string", length=255, nullable=false)
     */
    private $departementNom;

    /**
     * @var \Region
     *
     * @ORM\ManyToOne(targetEntity="Region")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="departement_region", referencedColumnName="region_id")
     * })
     */
    private $departementRegion;

    public function getDepartementId(): ?int
    {
        return $this->departementId;
    }

    public function getDepartementCode(): ?string
    {
        return $this->departementCode;
    }

    public function setDepartementCode(string $departementCode): self
    {
        $this->departementCode = $departementCode;

        return $this;
    }

    public function getDepartementNom(): ?string
    {
        return $this->departementNom;
    }

    public function setDepartementNom(string $departementNom): self
    {
        $this->departementNom = $departementNom;

        return $this;
    }

    public function getDepartementRegion(): ?Region
    {
        return $this->departementRegion;
    }

    public function setDepartementRegion(?Region $departementRegion): self
    {
        $this->departementRegion = $departementRegion;

        return $this;
    }


}
