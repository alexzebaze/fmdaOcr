<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Repository\ChantierOrderRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

/**
 * ChantierOrder
 * 
 * @ApiResource(
 *      attributes={
 *          "order"={"order_num": "ASC"},
 *          "pagination_enabled"=false
 *      }
 * )
 * @ApiFilter(SearchFilter::class, properties={"utilisateur": "exact"})
 * @ORM\Entity(repositoryClass=ChantierOrderRepository::class)
 */
class ChantierOrder
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class)
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid", nullable=false))
     */
    private $utilisateur;

    /**
     * @ORM\ManyToOne(targetEntity=Chantier::class)
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id", nullable=false)
     */
    private $chantier;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $order_num;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getChantier(): ?Chantier
    {
        return $this->chantier;
    }

    public function setChantier(?Chantier $chantier): self
    {
        $this->chantier = $chantier;

        return $this;
    }

    public function getOrderNum(): ?int
    {
        return $this->order_num;
    }

    public function setOrderNum(?int $order_num): self
    {
        $this->order_num = $order_num;

        return $this;
    }
}
