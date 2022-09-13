<?php
namespace App\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Carbon\Carbon;

class MenuService{
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

	public function orderMenu($tabMenu){
        $result = []; $diffSort = []; $tabDiff = [];
        foreach ($tabMenu as $value) {
            $tabDiff[$value->getId()] = $value->getRang();
        }
        asort($tabDiff);
        
        foreach ($tabDiff as $key => $value) {
            $diffSort[] = $key;
        }

        foreach ($tabMenu as $value) {
            $index = array_search($value->getId(), $diffSort);
            $result[$index] = $value;
        }

        ksort($result);
        return $result;
    }

}