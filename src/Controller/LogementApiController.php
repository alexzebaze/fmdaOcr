<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use App\Entity\Logement;
use App\Entity\Location;
use App\Entity\Entreprise;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use App\Repository\ChantierRepository;
use App\Repository\EntrepriseRepository;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

/**
 * @Route("/logement-api")
 */
class LogementApiController extends Controller
{   
    use CommunTrait;

    private $global_s;
    private $entrepriseRepository;
    private $logementRepository;
    private $chantierRepository;

    public function __construct(ChantierRepository $chantierRepository, EntrepriseRepository $entrepriseRepository, LogementRepository $logementRepository, SessionInterface $session, GlobalService $global_s){
        $this->entrepriseRepository = $entrepriseRepository;
        $this->chantierRepository = $chantierRepository;
        $this->logementRepository = $logementRepository;
        $this->session = $session;
        $this->global_s = $global_s;
    }

    /**
     * @Route("/group-by-entreprise-chantier", name="group_by_entreprise_chantier", methods={"GET"})
     */
    public function groupByEntrepriseChantierXhr(Request $request): Response
    {
        $entreprises = $this->entrepriseRepository->findAll();
        $logements = [];
        foreach ($entreprises as $entreprise) {
            $logementItem = [
                "entreprise_id"=>$entreprise->getId(),
                "entreprise_name"=>$entreprise->getName(),
            ];

            $chantiers = $this->chantierRepository->findBy(["entreprise"=>$entreprise->getId(), 'status'=>1]);

            $logementItem['chantiers'] = [];
            foreach ($chantiers as $chantier) {
                $chantierItem = [];
                $chantierItem['chantier_id'] = $chantier->getChantierId();
                $chantierItem['chantier_name'] = $chantier->getNameentreprise();

                $logs = $this->logementRepository->findBy(['chantier'=>$chantier->getChantierId(), 'entreprise'=>$entreprise->getId()]);
                foreach ($logs as $log) {
                    $chantierItem['logements'][] = $this->buildInfoLogement($log);
                }
                $logementItem['chantiers'][] = $chantierItem;
            }
            
            $logements[] = $logementItem;
        }
        $datas = ['status'=>200, 'datas'=>$logements];
        $response = new Response(json_encode($datas));

        return $response;
    }

    /**
     * @Route("/get-by-entreprise", name="get_by_entreprise", methods={"GET"})
     */
    public function getByEntrepriseXhr(Request $request): Response
    {
        $entrepriseId = $request->query->get('entreprise_id');
        if(!$entrepriseId){
            $datas = ['status'=>300, "message"=>"entreprise requise"];
            $response = new Response(json_encode($datas));
            return $response;
        }
        $entreprise = $this->entrepriseRepository->find($request->query->get('entreprise_id'));
        $logementsArr = [];
        $logements = $this->logementRepository->findBy(['entreprise'=>$entreprise->getId()]);
        foreach ($logements as $log) {
            $logementsArr[] = $this->buildInfoLogement($log);
        }        

        $datas = ['status'=>200, 'datas'=>$logementsArr];
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/get-detail", name="get_detail", methods={"GET"})
     */
    public function getLogementDetailXhr(Request $request): Response
    {
        $logementId = $request->query->get('logement_id');
        if(!$logementId){
            $datas = ['status'=>300, "message"=>"logement requise"];
            $response = new Response(json_encode($datas));
            return $response;
        }
        $logement = $this->logementRepository->find(['id'=>$logementId]);
        if(!is_null($logement))
            $logement = $this->buildInfoLogement($logement);
        else
            $logement = [];
                
        $datas = ['status'=>200, 'datas'=>$logement];
        $response = new Response(json_encode($datas));
        return $response;
    }

    public function buildInfoLogement($log){

        $photos = array_map(function($val) use ($log){
            return  $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL)."uploads/logement/".$log->getEntreprise()->getId()."/".$val;
        }, $log->unserializePhotos());
        
        return [
                'id'=>$log->getId(),
                'chantier'=>[
                    'id'=>$log->getChantier()->getChantierId(),
                    'name'=>$log->getChantier()->getNameentreprise(),
                ],
                'identifiant'=>$log->getIdentifiant(),
                'batiment'=>$log->getBatiment(),
                'escalier'=>$log->getEscalier(),
                'etage'=>$log->getEtage(),
                'status'=>$log->getStatus(),
                'superficie'=>$log->getSuperficie(),
                'nombre_piece'=>$log->getNombrePiece(),
                'nombre_chambre'=>$log->getNombreChambre(),
                'annee_construction'=>$log->getAnneeConstruction(),
                'web'=>$log->getWeb(),
                'permalien'=>$log->getPermalien(),
                'stationnement'=>$log->getStationnement(),
                'cave'=>$log->getCave(),
                'superficie_cave'=>$log->getSuperficieCave(),
                'nombre_wc'=>$log->getNombreWc(),
                'exposition'=>$log->getExposition(),
                'balcon'=>$log->getBalcon(),
                'superficie_balcon'=>$log->getSuperficieBalcon(),
                'terrasse'=>$log->getTerrasse(),
                'superficie_terrasse'=>$log->getSuperficieTerrasse(),
                'cellier'=>$log->getCellier(),
                'normes'=>$log->getNormes(),
                'description'=>$log->getDescription(),
                'notes'=>$log->getNotes(),
                'photos'=>$photos
        ];
    }

}
