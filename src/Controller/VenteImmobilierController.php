<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use App\Entity\Chantier;
use App\Entity\Logement;
use App\Entity\Entreprise;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ChantierRepository;
use App\Repository\LogementRepository;

class VenteImmobilierController extends AbstractController
{
	use CommunTrait;

    private $chantierRepository;
    private $logementRepository;
    private $session;

    public function __construct(ChantierRepository $chantierRepository, LogementRepository $logementRepository, SessionInterface $session){
        $this->chantierRepository = $chantierRepository;
        $this->logementRepository = $logementRepository;
        $this->session = $session;
    }

    /**
     * @Route("/immobilier-vente", name="immobilier_vente")
     */
    public function vente(Request $request)
    {
    	$user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $chantiers = $this->chantierRepository->getChantierByVenteType();

        /*if($request->get('q')){
            $chantiers = $chantiers->andWhere('LOWER(ch.nameentreprise) LIKE :nameentreprise')
                ->setParameter('nameentreprise', strtolower($request->get('q'))."%");
        }
        $chantiers = $chantiers->orderBy('ch.chantierId', 'desc')
            ->getQuery()
            ->getResult();*/

        $chantierArr = [];
        foreach ($chantiers as $value) {
            if($value->getStatus() || (!$value->getStatus() && count($value->getLogements()))){
                $chantierArr[] = $value;
            }
        }
        return $this->render('immobilier_vente/index.html.twig', [
            'chantiers' => $chantierArr,
            'q' => $request->get('q'),
        ]);
    }

    /**
     * @Route("/immobilier-vente-by-chantier/{chantier_id}", name="immobilier_vente_logement_by_chantier")
     */
    public function venteLogement(Request $request, $chantier_id)
    {
    	$user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);

        $logements = $this->logementRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantier_id]);
        return $this->render('immobilier_vente/logement.html.twig', [
            'logements' => $logements,
            'chantier_id' => $chantier_id
        ]);
    }

    /**
     * @Route("/vente-logement-print/{chantier_id}", name="vente_logement_print")
     */
    public function venteLogementPrint(Request $request, $chantier_id)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);

        $logements = $this->logementRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantier_id]);

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $chantier = $this->chantierRepository->find($chantier_id);

        return $this->render('immobilier_vente/logement_print.html.twig', [
            'logements' => $logements,
            'chantier'=>$chantier,
            'entreprise'=>[
                'nom'=>$entreprise->getName(),
                'adresse'=>$entreprise->getAddress(),
                'ville'=>$entreprise->getCity(),
                'postalcode'=>$entreprise->getCp(),
                'phone'=>$entreprise->getPhone(),
                'email'=>$entreprise->getEmail()
            ]
        ]);
    }
}
