<?php

namespace App\Controller;
use App\Entity\Pers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PersController extends AbstractController
{
    /**
     * @Route("/person/list", name="person_list", methods={"GET"})
     */
    public function list()
    {	
    	$entityManager = $this->getDoctrine()->getManager();
    	$personnes = $entityManager->getRepository(Pers::class)->findAll();
    	$persArr = [];
    	foreach ($personnes as $value) {
    		$persArr[] = ['id' => $value->getId(), 'nom' => $value->getNom(), 'prenom' => $value->getPrenom(), 'email' => $value->getEmail(), 'password' => $value->getPassword()];
    	}
    	$datas = ['status'=>200, "message"=>"", "datas"=>$persArr];
    	$response = new Response(json_encode($datas));
        return $response;
    }
}
