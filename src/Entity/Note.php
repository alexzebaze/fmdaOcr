<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tim as Gedmo;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Observation
 *
 * @ApiResource()
 * @ORM\Entity(repositoryClass=NoteRepository::class)
 * @ORM\Table(name="note")
 * @ORM\Entity
 */
class Note
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
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    private $text;

    /**
     * @var int
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default"="1"})
     */
    private $status = 1;

    /**
     * @var Utilisateur
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Utilisateur", inversedBy="notes")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="uid")
     */
    private $user;


    /**
     * @var Chantier
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Chantier", inversedBy="notes")
     * @ORM\JoinColumn(name="chantier_id", referencedColumnName="chantier_id")
     */
    private $chantier;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $created_at;

    /**
     * @var App\Entity\PartageNote[]
     * @ORM\OneToMany(targetEntity="App\Entity\PartageNote", mappedBy="note", cascade={"persist"})
     */
    private $partageNotes;

    private $thumbnail;

    function __construct()
    {
        $this->created_at = new \DateTime();
        $this->partageNotes = new ArrayCollection();
    }

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
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string|null $text
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return Utilisateur
     */
    public function getUser()
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

    /**
     * @return Chantier
     */
    public function getChantier()
    {
        return $this->chantier;
    }

    /**
     * @param Chantier $chantier
     */
    public function setChantier(Chantier $chantier): void
    {
        $this->chantier = $chantier;
    }

        /**
     * @return Collection|PartageNote[]
     */
    public function getPartageNotes(): Collection
    {
        return $this->partageNotes;
    }

    public function addPartageNote(PartageNote $partageNote): self
    {
        if (!$this->partageNotes->contains($partageNote)) {
            $this->partageNotes[] = $partageNote;
            $partageNote->setNote($this);
        }

        return $this;
    }

    public function removePartageNote(PartageNote $partageNote): self
    {
        if ($this->partageNotes->contains($partageNote)) {
            $this->partageNotes->removeElement($partageNote);
            // set the owning side to null (unless already changed)
            if ($partageNote->getNote() === $this) {
                $partageNote->setNote(null);
            }
        }

        return $this;
    }

    public function userIsInSharing(Utilisateur $user){

        $userIsInSharing = false;

        foreach ($this->getPartageNotes() as $partageNote) {
            if($partageNote->getUser()->getUid() == $user->getUid())
                $userIsInSharing = true;
        }

        return $userIsInSharing ;
    }

    public function getUserInSharing(){

        $userInSharing = [];

        foreach ($this->getPartageNotes() as $partageNote) {
            $userInSharing[] = $partageNote->getUser()->getUid();
        }

        return implode(",",$userInSharing);
    }


    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    /**
     * @param \DateTime $created_at
     */
    public function setCreatedAt(\DateTime $created_at): void
    {
        $this->created_at = $created_at;
    }

}
