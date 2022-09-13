<?php

namespace App\Entity;

use App\Repository\SignatureOutillageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SignatureOutillageRepository::class)
 */
class SignatureOutillage
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Utilisateur", inversedBy="signatures")
     * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="uid", nullable=true)
     */
    private $utilisateur;
    
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_signature;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $document;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSignature(): ?\DateTimeInterface
    {
        return $this->date_signature;
    }

    public function setDateSignature(\DateTimeInterface $date_signature): self
    {
        $this->date_signature = $date_signature;

        return $this;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(string $document): self
    {
        $this->document = $document;

        return $this;
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
}
