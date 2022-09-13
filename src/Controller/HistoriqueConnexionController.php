<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\HistoriqueConnexion;
use App\Repository\HistoriqueConnexionRepository;

class HistoriqueConnexionController extends AbstractController
{
    /**
     * @Route("/historique/connexion", name="historique_connexion")
     */
    public function index(HistoriqueConnexionRepository $historiqueRepository)
    {

    	$historiques = $historiqueRepository->findBy([], ['id'=>'DESC']);
        return $this->render('historique_connexion/index.html.twig', [
        	'historiques'=>$historiques
        ]);
    }
}
