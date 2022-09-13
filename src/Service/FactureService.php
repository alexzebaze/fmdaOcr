<?php
namespace App\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Carbon\Carbon;
use App\Repository\AchatRepository;

class FactureService{
    
    private $achatRepository;

    public function __construct(EntityManagerInterface $em, AchatRepository $achatRepository) {
        $this->em = $em;
        $this->achatRepository = $achatRepository;
    }


    public function factureGenerated()
    { 

        $factureDueDate = [];
        $facturesGen = $this->achatRepository->getFactureGenerated();
        foreach ($facturesGen as $value) {
            $newFacture = clone $value;            
            $newFacture->setAutomatique(false);

            $dateFactured = $value->getDateGenerate()->format('Y-m-d');
            $newFacture->setFacturedAt(new \DateTime($dateFactured));
            $this->em->persist($newFacture);

            if( !is_null($value->getDueDateGenerate()) && $value->getDueDateGenerate()->format('Y-m-d') <= (new \Datetime())->format('Y-m-d')){
                $factureDueDate[] = $value->getId();
            }
            else if( is_null($value->getDueDateGenerate()) || (!is_null($value->getDueDateGenerate()) && $value->getDueDateGenerate()->format('Y-m-d') > (new \Datetime())->format('Y-m-d')) ){                
                $date = $value->getDateGenerate();
                $date->add(new \DateInterval('P'.$value->getEcheance().'M'));
                
                $jourGenerate = is_null($value->getJourGenerate()) ? 1 : $value->getJourGenerate();
                $date = $date->format('Y-m').'-'.$jourGenerate;

                $value->setDateGenerate(new \DateTime($date));
            }
        }
        $this->em->flush();

        if(count($factureDueDate))
            $this->achatRepository->StopFactureDueDatePass($factureDueDate);
        
        return 1;
    } 
}