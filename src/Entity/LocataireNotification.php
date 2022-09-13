<?php

namespace App\Entity;

use App\Repository\LocataireNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LocataireNotificationRepository::class)
 */
class LocataireNotification
{

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Client")
     * @ORM\JoinColumn(name="client_id",  nullable=true)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Passage")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $passage;

    /**
     * @ORM\ManyToOne(targetEntity=Entreprise::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $entreprise;

    /**
     * @ORM\ManyToOne(targetEntity=Location::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $location;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_send;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $piece_jointe;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sujet;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $receiver;

    public function __construct()
    {
        $this->date_send = new \DateTime();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSend(): ?\DateTimeInterface
    {
        return $this->date_send;
    }

    public function setDateSend(\DateTimeInterface $date_send): self
    {
        $this->date_send = $date_send;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

        return $this;
    }    

    public function getPassage(): ?Passage
    {
        return $this->passage;
    }

    public function setPassage(?Passage $passage): self
    {
        $this->passage = $passage;

        return $this;
    }

    public function getPieceJointe(): ?string
    {
        return $this->piece_jointe;
    }    

    public function getPieceJointeArr()
    {
        $pieceJointe = $this->piece_jointe;
        $result = [];
        if(is_null($pieceJointe) || $pieceJointe != ""){
            $result = unserialize($pieceJointe);
        }
        return $result;
    }

    public function setPieceJointe(?string $piece_jointe): self
    {
        $this->piece_jointe = $piece_jointe;

        return $this;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(?string $sujet): self
    {
        $this->sujet = $sujet;

        return $this;
    }

    public function getReceiver(): ?string
    {
        return $this->receiver;
    }

    public function setReceiver(?string $receiver): self
    {
        $this->receiver = $receiver;

        return $this;
    }
}
