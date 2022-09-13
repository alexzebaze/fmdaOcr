<?php
namespace App\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Carbon\Carbon;
use App\Repository\PassageRepository;
use App\Entity\Passage;

class PassageService{
    

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }


    public function createPassage($bl, $utilisateur = null)
    { 

        $passage = new Passage();
        $passage->setUtilisateur($utilisateur);
        $passage->setChantier($bl->getChantier());
        $passage->setFournisseur($bl->getFournisseur());
        $passage->setDateDetection($bl->getFacturedAt());
        $passage->setEntreprise($bl->getEntreprise());
        $passage->setBonLivraison($bl);

        $this->em->persist($passage);
        $this->em->flush();

        return 1;
    } 

    public function sortToDateProch($target, $passages){
        $result = []; $diffSort = []; $tabDiff = [];
        foreach ($passages as $value) {
            $tabDiff[$value->getId()] = abs(strtotime($value->getDateDetection()->format('Y-m-d')) - $target);
        }
        asort($tabDiff);

        foreach ($tabDiff as $key => $value) {
            $diffSort[] = $key;
        }

        foreach ($passages as $value) {
            $index = array_search($value->getId(), $diffSort);
            $result[$index] = $value;
        }

        ksort($result);
        return $result;
    }
}