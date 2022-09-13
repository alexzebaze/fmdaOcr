<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Region
 * 
 *
 * @ORM\Table(name="region")
 * @ORM\Entity
 */
class Region
{
    /**
     * @var int
     *
     * @ORM\Column(name="region_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $regionId;

    /**
     * @var string
     *
     * @ORM\Column(name="region_nom", type="string", length=255, nullable=false)
     */
    private $regionNom;

    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    public function getRegionNom(): ?string
    {
        return $this->regionNom;
    }

    public function setRegionNom(string $regionNom): self
    {
        $this->regionNom = $regionNom;

        return $this;
    }


}
