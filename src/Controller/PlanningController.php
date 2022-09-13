<?php

namespace App\Controller;

use App\Entity\Chantier;
use App\Entity\Horaire;
use App\Entity\Planning;
use App\Entity\Entreprise;
use App\Entity\StatusModule;
use App\Entity\PlanningCategory;
use App\Repository\HoraireRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\ChantierRepository;
use App\Repository\StatusRepository;
use App\Repository\PlanningRepository;
use App\Repository\VenteRepository;
use App\Repository\PlanningCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Carbon\Carbon;

/**
    * @Route("/planning", name="planning_")
*/
class PlanningController extends AbstractController
{	
    private $global_s;
    private $entrepriseRepository;
    private $horaireRepository;
    private $session;
    private $utilisateurRepository;
    private $statusRepository;
    private $planningRepository;
    private $planningCategoryRepository;
    private $venteRepository;

    public function __construct(ChantierRepository $chantierRepository, HoraireRepository $horaireRepository, EntrepriseRepository $entrepriseRepository, SessionInterface $session, GlobalService $global_s, UtilisateurRepository $utilisateurRepository, PlanningRepository $planningRepository, PlanningCategoryRepository $planningCategoryRepository, StatusRepository $statusRepository, VenteRepository $venteRepository){

        $this->entrepriseRepository = $entrepriseRepository;
        $this->planningCategoryRepository = $planningCategoryRepository;
        $this->chantierRepository = $chantierRepository;
        $this->horaireRepository = $horaireRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->planningRepository = $planningRepository;
        $this->statusRepository = $statusRepository;
        $this->venteRepository = $venteRepository;
        $this->session = $session;
        $this->global_s = $global_s;
    }

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('planning/index.html.twig', []);
    }

    /**
     * @Route("/print-planning-list", name="print_list")
     */
    public function printPlanningList(Request $request)
    {
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $users = "";
        if ($request->isMethod('post')) {
            $users = implode(",", $request->request->get('user'));
        }
        return $this->render('planning/print_list.html.twig', [
            'users'=>$users,
            'print'=>1,
            'entreprise'=>[
                'nom'=>$entreprise->getName(),
                'adresse'=>$entreprise->getAddress(),
                'ville'=>$entreprise->getCity(),
                'postalcode'=>$entreprise->getCp(),
                'phone'=>$entreprise->getPhone(),
                'email'=>$entreprise->getEmail(),
                'logo'=>$entreprise->getLogo(),
            ]
        ]);
    }

        /**
     * @Route("/load-xhr-print", name="load_xhr_print")
    */
    public function loadPlanningXhrToPrint(Request $request){
        $users = null;
        if( ($request->query->get('users_print') != "") && $request->query->get('users_print') != "undefined" ){
            $users = explode(",", $request->query->get('users_print'));
        }

        $categories = $this->planningCategoryRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'archive'=>false]);

        $taches = [];
        foreach ($categories as $value) {
            $tachesTmp = $taches;
            $responses = $this->getPlanningByCategory($value, (new \Datetime())->format('Y-m-d'), $users, (new \Datetime())->format('Y-m-d'));
            $taches = array_merge($tachesTmp, $responses['planning']['taches']);
        }
        $column  = array_column($taches, 'date_debut_strtotime');
        array_multisort($column, SORT_ASC, $taches);  

        $tachesArr = [];
        $currentCatId = 0; 
        foreach ($taches as $value) {
            if($currentCatId == $value["planning_categorie"]["categorie_id"]){
                $lastTacheInsert = $tachesArr[count($tachesArr) -1 ];
                $lastTacheInsert['taches'][] = $value;
                $tachesArr[count($tachesArr) -1 ] = $lastTacheInsert;
            }
            else{
                $currentTache = $value["planning_categorie"];
                $currentTache["taches"][] = $value;
                $tachesArr[] = $currentTache;
            }
            $currentCatId = $value["planning_categorie"]["categorie_id"]; 
        }
        

        $dateStart = "";
        $dateEnd = "";
        $currentYear = "";
        if(count($tachesArr)){

            $date_start = count($tachesArr[0]['taches']) ? $tachesArr[0]['taches'][0]['date_start'] : null;
            $date_end = count($tachesArr[count($tachesArr)-1]['taches']) ? $tachesArr[count($tachesArr)-1]['taches'][0]['date_end'] : null;

            $dateStart =  !is_null($date_start) ? (new \Datetime($date_start))->format("d/m") : "";
            $dateEnd = !is_null($date_end) ? (new \Datetime($date_end))->format("d/m") : "";
            $currentYear = !is_null($date_start) ? (new \Datetime($date_start))->format("Y") : "";
        }
        

        $responses['status'] = 200;
        $responses['informations'] = [
            "title"=>"PLANNING DU ". $dateStart." AU ".$dateEnd." ".$currentYear
        ];
        $responses['plannings'] = $tachesArr;
        $responses = new Response(json_encode($responses));
        return $responses;     
    }


    /**
     * @Route("/load-xhr", name="load_xhr")
    */
    public function getPlanningXhr(Request $request){
        $pas = 1;
        $offset = $request->query->get('offset'); 
        
        $chantiers = []; $status = [];
        if(!$offset){
            $chantiers = $this->chantierRepository->getAllChantier();
            $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("PLANNING");
        }

        $categories = $this->planningCategoryRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'archive'=>false], null, $pas, $offset);
        $categorie = new PlanningCategory();
        if(!count($categories)){
            $responses = ['status'=>300, 'message'=>"fin planning", 'chantiers'=>$chantiers];
            $response = new Response(json_encode($responses));
            return $response;  
        } 
        $categorie = $categories[0];

        $users = null;
        if( ($request->query->get('users_print') != "") && $request->query->get('users_print') != "undefined" ){
            $users = explode(",", $request->query->get('users_print'));
        }
        if(($request->query->get('print') != "") && $request->query->get('print') != "undefined")
            $responses = $this->getPlanningByCategory($categorie, (new \Datetime())->format('Y-m-d'), $users);
        else
            $responses = $this->getPlanningByCategory($categorie, null, $users);
        
        $responses['status'] = 200;
        $responses['status_planning'] = $status;
        $responses['chantiers'] = $chantiers;
        $responses['offset'] = $offset+$pas;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    public function getPlanningByCategory($categorie, $dateStart = null, $users = null, $dateFin = null){
        
        /* impression all planning home by tab user */
        if(!is_null($users) && count($users)){
            $plannings = $this->planningRepository->getAllPlanningByTabUserByCategorie($categorie->getId(), $users, $dateStart, $dateFin);
        }
        else{
            if(is_null($dateStart)){
                $plannings = $this->planningRepository->findBy(['planning_categorie'=>$categorie->getId()], ['debut'=>'ASC']);    
            }
            else{
                $plannings = $this->planningRepository->getAllPlanningByCategorieFromDate($categorie->getId(), $dateStart, $dateFin);   
            }
        }
        
        $planningsArr = [];
        $planningsArr['taches'] = [];

        $newTache = []; $userPlanified = [];
        foreach ($plannings as $value) {
            $tache = $this->buildPlanningTache($value);

            if($tache['diffDay'] == 0)
                $newTache[] = $tache;
            else
                $planningsArr['taches'][] = $tache;

            $userPlanified = array_merge($userPlanified, $tache['users']);
        } 

        $userPlanifiedUnique = [];
        foreach ($userPlanified as $value) {
            if (array_search($value['id'], array_column($userPlanifiedUnique, 'id')) === FALSE){
                $userPlanifiedUnique[] = $value;
            }
        }
        $devis = $this->venteRepository->findBy(['type'=>"devis_client", 'chantier'=>$categorie->getChantier()->getChantierId()]);

        $devisArr = [];
        foreach ($devis as $value) {
            $devisItem = [];
            $devis_tostring = "";

            $devisItem['id'] = $value->getId();
            if($value->getLibelle())
                $devisItem['id'] = $value->getId();
            if($value->getPrixht()){
                $devisItem['prixht'] = round($value->getPrixht(), 2)."€";
            }
            if($value->getClient()){
                $devisItem['client'] = $value->getClient()->getNom();
            }
            if($value->getRossumDocumentId()){
                $devisItem['document_id'] = $value->getRossumDocumentId();
            }
            $devisItem['to_string'] = $value->__toString();

            $devisArr[] = $devisItem;
        }

        $planningsCompleted = array_merge($planningsArr['taches'], $newTache);

        $planningsArr['taches'] = array_map(function($tache) use ($categorie, $devisArr, $userPlanifiedUnique){
            $tache["planning_categorie"] = [
                'categorie' => $categorie->getChantier()->getNameentreprise(),
                'user_planified' => $userPlanifiedUnique,
                'new_created' => false,
                'categorie_id' => $categorie->getId(),
                'collapse' => $categorie->getCollapse(),
                'categorie_color' => $categorie->getColor(),
                'chantier_id' => $categorie->getChantier()->getChantierId(),
                'devis_chantier' => $devisArr
            ];
            return $tache;
        }, $planningsCompleted);

        $planningsArr['categorie'] = $categorie->getChantier()->getNameentreprise();
        $planningsArr['user_planified'] = $userPlanifiedUnique;
        $planningsArr['new_created'] = false;
        $planningsArr['categorie_id'] = $categorie->getId();
        $planningsArr['collapse'] = $categorie->getCollapse();
        $planningsArr['categorie_color'] = $categorie->getColor();
        $planningsArr['chantier_id'] = $categorie->getChantier()->getChantierId();
        $planningsArr['devis_chantier'] = $devisArr; 
        $responses['planning'] = $planningsArr; 

        return $responses;
    }

    /**
     * @Route("/update-collapse", name="update_collapse")
    */
    public function collapseAction(Request $request){
        $categorieId = $request->query->get('categorie_id');
        $collapse = $request->query->get('collapse');
        if($categorieId){
            $categorie = $this->planningCategoryRepository->find($categorieId);
            if($collapse == "close"){
                $categorie->setCollapse(true);
            }
            else {
                $categorie->setCollapse(false);
            }
        }
        else{
            $this->planningCategoryRepository->updateCollapse($collapse);
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = []; // $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/create-tache-xhr", name="create_tache_xhr")
    */
    public function createTacheXhr(Request $request){
        $planning = new Planning();
        $planning->setTache($request->request->get('categorie'));
        $categorie = $this->planningCategoryRepository->find($request->query->get('categorie_id'));
        $planning->setPlanningCategorie($categorie);
        $status = $this->statusRepository->findOneBy(['name'=>'En Attente']);
        $planning->setStatus($status);

        $em = $this->getDoctrine()->getManager();
        $em->persist($planning);
        $em->flush();  

        //$response = ['status'=>200, 'tache' => $this->buildPlanningTache($planning)];
        //return new Response(json_encode($response));
        
        $responses = $this->getPlanningByCategory($categorie);
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/create-new-xhr", name="create_new_xhr")
    */
    public function createNewPlanningXhr(Request $request){
        $categorie = new PlanningCategory();
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        if(!is_null($entreprise))
            $categorie->setEntreprise($entreprise);

        $chantier = $this->chantierRepository->find($request->query->get('chantier_id'));
        if(is_null($chantier)){
            return new Response(json_encode(['status'=>500, "message'=>'ce chantier n'existe pas"]));
        }
        $categorie->setChantier($chantier);
        $em = $this->getDoctrine()->getManager();
        $em->persist($categorie);
        $em->flush();  

        $planning = [
            'categorie'=> $categorie->getChantier()->getNameentreprise(),
            'new_created'=> true,
            'chantier_id'=> $categorie->getChantier()->getChantierId(),
            'categorie_id'=> $categorie->getId(),
            'taches'=> []
        ];
        $response = ['status'=>200, 'planning' => $planning];
        return new Response(json_encode($response));
    }

    /**
     * @Route("/update-new-xhr", name="update_new_xhr")
    */
    public function updateNewXhr(Request $request){
        $categorie = $this->planningCategoryRepository->find($request->query->get('categorie_id'));
        $chantier = $this->chantierRepository->find($request->request->get('chantier_id'));
        $categorie->setChantier($chantier);
        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $planning = [
            'categorie'=> $categorie->getChantier()->getNameentreprise(),
            'new_created'=> false,
            'chantier_id'=> $categorie->getChantier()->getChantierId(),
            'categorie_id'=> $categorie->getId(),
            'taches'=> []
        ];
        $response = ['status'=>200, 'planning' => $planning];
        return new Response(json_encode($response));
    }

    /**
     * @Route("/update-planning-date", name="update_date")
    */
    public function updatePlanningDate(Request $request){
        $planning = $this->planningRepository->find($request->query->get('tache_id'));
        $date = explode('au', $request->query->get('date'));
        if(count($date)){
            $planning->setDebut(new \Datetime($date[0]));
            if(count($date) > 1){
                $planning->setDatefin(new \Datetime($date[1]));
            }
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/update-tache-name", name="update_tache_name")
    */
    public function updateTacheName(Request $request){
        $planning = $this->planningRepository->find($request->query->get('planning_id'));
        $name = $request->query->get('name');

        if($name != ""){
            $planning->setTache($name);
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/get-by-id", name="get_by_id")
    */
    public function getPlanningById(Request $request){
        $categorie = $this->planningCategoryRepository->find($request->query->get('categorieId'));
        $responses = $this->getPlanningByCategory($categorie);
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/update-emplacement", name="update_emplacement")
    */
    public function updateEmplacement(Request $request){
        $planning = $this->planningRepository->find($request->query->get('planning_id'));
        $name = $request->query->get('name');

        if($name != ""){
            $planning->setEmplacement($name);
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/delete-tache", name="delete_tache")
    */
    public function deleteTache(Request $request){
        $planning = $this->planningRepository->find($request->query->get('tache_id'));
        $em = $this->getDoctrine()->getManager();

        if(!is_null($planning))
            $em->remove($planning);
        $em->flush();  

        $responses = $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/config-color-planning", name="config_color")
    */
    public function changePlanningConfigColor(Request $request){
        $planningCat = $this->planningCategoryRepository->find($request->query->get('planning_id'));
        $planningCat->setColor($request->query->get('color'));
        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = $this->getPlanningByCategory($planningCat);
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/delete-planning", name="delete")
    */
    public function deletePlanning(Request $request){
        $planningCat = $this->planningCategoryRepository->find($request->query->get('planning_id'));
        
        $em = $this->getDoctrine()->getManager();
        if(!is_null($planningCat))
            $em->remove($planningCat);
        $em->flush();    

        $responses = ['status'=>200, "planning_id"=>$request->query->get('planning_id')];
        return new Response(json_encode($responses));
    }

    /**
     * @Route("/archive-planning", name="archive")
    */
    public function archivePlanning(Request $request){
        $planningCat = $this->planningCategoryRepository->find($request->query->get('planning_id'));
        $em = $this->getDoctrine()->getManager();
        $planningCat->setArchive(true);
        $em->flush();    

        $responses = ['status'=>200, "planning_id"=>$request->query->get('planning_id')];
        return new Response(json_encode($responses));
    }

    /**
     * @Route("/update-planning-status", name="update_status")
    */
    public function updatePlanningStatus(Request $request){
        $planning = $this->planningRepository->find($request->query->get('tache_id'));

        if($request->query->get('status_id')){
            $status = $this->statusRepository->find($request->query->get('status_id'));
        }
        if(!is_null($status))
            $planning->setStatus($status);

        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/change-devis", name="change_devis")
    */
    public function changeDevis(Request $request){
        $planning = $this->planningRepository->find($request->query->get('tache_id'));

        $devis = $this->venteRepository->find($request->query->get('devis_id'));
        if(!is_null($devis))
            $planning->setDevis($devis);

        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    public function buildPlanningTache($planning){
        $currentPlanning = [];
        $userArr = [];
        foreach ($planning->getUtilisateurs() as $val) {
            $currentUser = [];
            $currentUser['id'] = $val->getUid();
            $currentUser['lastname'] = utf8_encode($val->getLastName());
            $currentUser['firstname'] = utf8_encode($val->getFirstName());
            $currentUser['email'] = utf8_encode($val->getEmail());

            if(!is_null($val->getImage())){
                $currentUser['avatar'] = $this->global_s->compressbase64(str_replace("data:image/jpeg;base64,", "", $val->getImage()), 0.5);
            }
            else
                $currentUser['avatar'] = $val->getImage();

            $userArr[] = $currentUser;
        }

        $dateStart = !is_null($planning->getDebut()) ? $planning->getDebut()->format('Y-m-d') : null;
        $dateEnd = !is_null($planning->getDatefin()) ? $planning->getDatefin()->format('Y-m-d') : null;
        
        $dateInterval = ""; $fulldateInterval=""; $dateCalendar = ""; $date_calendar = "";
        if(!is_null($dateStart) && !is_null($dateEnd)){
            $dateCalendar = $dateStart.' au '.$dateEnd;
            $full_dateStart = Carbon::parse($dateStart)->locale('fr')->isoFormat('D MMM YYYY');
            $full_dateStartfr = Carbon::parse($dateStart)->locale('fr')->isoFormat('D MMMM YYYY');

            $full_dateEnd = Carbon::parse($dateEnd)->locale('fr')->isoFormat('D MMM YYYY');
            
            if( (explode(' ', $full_dateStart))[2] == (explode(' ', $full_dateEnd))[2] ){
                if( (explode(' ', $full_dateStart))[1] ==  (explode(' ', $full_dateEnd))[1]){
                    if( (explode(' ', $full_dateStart))[0] ==  (explode(' ', $full_dateEnd))[0]){
                        $dateInterval = (explode(' ', $full_dateStart))[1].' '.(explode(' ', $full_dateStart))[0];
                        $fulldateInterval = "<b>".strtoupper((explode(' ', $full_dateStartfr))[1]).'</b>'.' '.Carbon::parse($dateStart)->locale('fr')->isoFormat('dddd D');
                    }
                    else{
                        $dateInterval = (explode(' ', $full_dateStart))[1].' '.(explode(' ', $full_dateStart))[0].'-'.(explode(' ', $full_dateEnd))[0].' ';
                        $fulldateInterval = "<b>".strtoupper((explode(' ', $full_dateStartfr))[1]).'</b>'.' '.Carbon::parse($dateStart)->locale('fr')->isoFormat('dddd D').' au '.Carbon::parse($dateEnd)->locale('fr')->isoFormat('dddd D');
                    }
                    
                }
                else{
                    $dateInterval = (explode(' ', $full_dateStart))[1].' '.(explode(' ', $full_dateStart))[0].' - '.(explode(' ', $full_dateEnd))[1].' '.(explode(' ', $full_dateEnd))[0];
                    $fulldateInterval = "<b>".strtoupper(Carbon::parse($dateStart)->locale('fr')->isoFormat('MMMM')).'</b> '.Carbon::parse($dateStart)->locale('fr')->isoFormat('dddd D').' au '."<b>".strtoupper(Carbon::parse($dateEnd)->locale('fr')->isoFormat('MMMM')).'</b> '.Carbon::parse($dateEnd)->locale('fr')->isoFormat('dddd D');
                }
            }
            $date_calendar = $planning->getDebut()->format('Y-m-d')." au ".$planning->getDatefin()->format('Y-m-d'); 
        }
        elseif(!is_null($dateStart)){
            $full_dateStart = Carbon::parse($dateStart)->locale('fr')->isoFormat('D MMM YYYY');
            $dateInterval = (explode(' ', $full_dateStart))[1].' '.(explode(' ', $full_dateStart))[0];
            $fulldateInterval = Carbon::parse($dateStart)->locale('fr')->isoFormat('dddd D MMMM');
            $date_calendar = $planning->getDebut()->format('Y-m-d')." au ".$planning->getDebut()->format('Y-m-d'); 
        }

        $currentPlanning['id'] = $planning->getId(); 
        $currentPlanning['tache'] = ucfirst($planning->getTache()); 

        if(!is_null($planning->getDevis())){

            $devis = $planning->getDevis();
            $devisItem = [];
            $devisItem['id'] = $devis->getId();
            if($devis->getLibelle())
                $devisItem['id'] = $devis->getId();
            if($devis->getPrixht()){
                $devisItem['prixht'] = round($devis->getPrixht(), 2)."€";
            }
            if($devis->getClient()){
                $devisItem['client'] = $devis->getClient()->getNom();
            }
            if($devis->getRossumDocumentId()){
                $devisItem['document_id'] = $devis->getRossumDocumentId();
            }
            $devis_tostring = implode(' - ', ["#".$devisItem['document_id'], $devisItem['prixht'], $devisItem['client']]);

            $currentPlanning['devis_planning'] = $devis_tostring;             
        }

        $currentPlanning['dateCalendar'] = $dateCalendar; 
        $currentPlanning['emplacement'] = $planning->getEmplacement(); 

        $status = [];
        if(!is_null($planning->getStatus())){
            $status = [
                'id'=>$planning->getStatus()->getId(),
                'color'=>$planning->getStatus()->getColor(),
                'nom'=>$planning->getStatus()->getName()
            ]; 
        }
        $currentPlanning['status'] = $status;
        $currentPlanning['rang'] = $planning->getRang();
        
        $currentPlanning['date_debut_strtotime'] = strtotime($dateStart); 
        $currentPlanning['date_start'] = !is_null($planning->getDebut()) ? $planning->getDebut()->format('Y-m-d') : null;; 
        $currentPlanning['date_end'] = !is_null($planning->getDebut()) ? $planning->getDatefin()->format('Y-m-d') : null;; 
        $currentPlanning['dateInterval'] = $dateInterval; 
        $currentPlanning['fulldateInterval'] = $fulldateInterval; 
        $currentPlanning['date_calendar'] = $date_calendar;
        if(!is_null($dateStart)){
            $currentPlanning['diffDay'] = Carbon::parse($dateStart)->locale('fr')->diffInDays(Carbon::parse($dateEnd)->locale('fr'))+1; 
        }
        else{
            $currentPlanning['diffDay'] = 0; 
        }
        $currentPlanning['users'] = $userArr; 

        $utilisateurs = $this->utilisateurRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'etat'=>1]);
        $userDispo = [];
        foreach ($utilisateurs as $user) {
            if (array_search($user->getUid(), array_column($currentPlanning['users'], 'id')) === FALSE){
                $currentUser = [];
                $currentUser['id'] = $user->getUid();
                $currentUser['lastname'] = utf8_encode($user->getLastName());
                $currentUser['firstname'] = utf8_encode($user->getFirstName());
                $currentUser['full_name'] = utf8_encode($user->getLastName().' '.$user->getFirstName());
                $currentUser['email'] = utf8_encode($user->getEmail());
                $currentUser['avatar'] = $user->getImage();
                $userDispo[] = $currentUser;    
            }
            
        }
        $currentPlanning['utilisateurs_dispo'] = $userDispo;

        return $currentPlanning;
    }

    /**
     * @Route("/add-user", name="add_user")
    */
    public function addUser(Request $request){
        $planning = $this->planningRepository->find($request->query->get('planning_id'));
        $user = $this->utilisateurRepository->find($request->query->get('user_id'));
        $planning->addUtilisateur($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($planning);
        $em->flush();  

        $responses = []; // $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }    

    /**
     * @Route("/clear-date", name="clear_date")
    */
    public function clearDate(Request $request){
        $planning = $this->planningRepository->find($request->query->get('planning_id'));
        $planning->setDebut(null);
        $planning->setDatefin(null);

        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = []; // $this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }    

    /**
     * @Route("/update-position", name="update_position_tache")
    */
    public function updateOrder(Request $request){
        $orders = explode(',', $request->request->get('order'));
        $categorieId = $request->query->get('categorie_id');
        
        $ordersArr = [];
        foreach ($orders as $value) {
            $ordersArr[] = [explode('_', $value)[0], explode('_', $value)[1]];
        }
        $categoriePlanning = $this->planningCategoryRepository->find($categorieId);
        foreach ($ordersArr as $value) {
            if($value[0]){
                $planning = $this->planningRepository->find($value[0]);
                $planning->setRang($value[1]);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();  

        $responses = $this->getPlanningByCategory($categoriePlanning);
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/remove-user", name="remove_user")
    */
    public function removeUser(Request $request){
        $planning = $this->planningRepository->find($request->query->get('planning_id'));
        $user = $this->utilisateurRepository->find($request->query->get('user_id'));
        $planning->removeUtilisateur($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($planning);
        $em->flush();  

        $responses = []; //$this->getPlanningByCategory($planning->getPlanningCategorie());
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/get-by-category", name="get_by_category")
    */
    public function getByCategory(Request $request){
        $categoriePlanning = $this->planningCategoryRepository->find($request->query->get('categorie_id'));

        $responses = $this->getPlanningByCategory($categoriePlanning);
        $responses['status'] = 200;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/print", name="print")
    */
    public function printPlanning(Request $request){
        $categoriePlanning = $this->planningCategoryRepository->find($request->query->get('categorieId'));
        $responses = $this->getPlanningByCategory($categoriePlanning);
        return $this->render('planning/print_planning.html.twig', ['planning'=>$responses['planning']]);
    }

    /**
     * @Route("/print-timeline", name="print_timeline")
    */
    public function printTimeline(Request $request){
        $tabUserId = ""; $categorieId = "";
        if ($request->isMethod('post')) {
            if(!$request->request->get('all-chantier'))
                $categorieId = $request->request->get('planningId');

            if($request->request->get('user'))
                $tabUserId = implode(',', $request->request->get('user'));
        }
        else{
            $categorieId = $request->query->get('categorie_print');
        }
        
        return $this->render('planning/timeline_print.html.twig', [
            'categorieId'=>$categorieId,
            'tabUserId'=>$tabUserId
        ]);
    }

    /**
     * @Route("/print-individual", name="print_individual")
    */
    public function printPlanningIndividual(Request $request){

        $users = $request->request->get('user');
        $plannings = [];
        $currentDate = (new \DateTime())->format('Y-m')."-01 00:00:00";
        foreach ($users as $value) {
            $currentTaches = [];
            $userPrint = $this->utilisateurRepository->find($value);

            if($request->request->get('all-chantier')){
                $currentTaches = $this->planningRepository->getAllPlanningByUser($value, $currentDate);
                $taches = [];
                foreach ($currentTaches as $val) {
                    $taches[$val->getPlanningCategorie()->getChantier()->getNameentreprise()][] = $this->buildPlanningTache($val);
                }
            }
            else{
                $currentTaches = $this->planningRepository->getPlanningByUserAndPlanning($value, $request->request->get('planningId'), $currentDate);

                $taches = [];
                foreach ($currentTaches as $val) {
                    $taches[] = $this->buildPlanningTache($val);
                }
            }
            if(count($currentTaches)){
                $plannings[] = [
                    'categorie'=>($currentTaches[0])->getPlanningCategorie()->getChantier()->getNameentreprise(),
                    'user'=> utf8_encode($userPrint->getFirstName().' '.$userPrint->getLastName()),
                    'taches'=> $taches
                ];
            }
        }        

        if($request->request->get('all-chantier')){
            return $this->render('planning/print_planning.html.twig', ['all_chantier'=> 1, 'plannings'=>$plannings]);
        }
        else{
            return $this->render('planning/print_planning.html.twig', ['individuel'=> 1, 'plannings'=>$plannings]);
        }
    }





    public function generationYear($direction, $dateReference, $sautDate, $echeance){
        $timelines = [];
        if($direction){ // direction is enough to condition
            if($direction == "right"){
                for ($i = ($dateReference + 1); $i <= ($dateReference+$sautDate); $i++) {
                    $timelines = array_merge($timelines, $this->buildDataHead($i, $echeance, $direction));
                }
            }
            else if($direction == "left"){
                for ($i = $dateReference-$sautDate; $i < $dateReference; $i++) {
                    $timelines = array_merge($timelines, $this->buildDataHead($i, $echeance, $direction));
                }
            }
        }
        else{
            if($echeance == "TRIMESTRIELLE"){
                for ($year=2021; $year < 2025 ; $year++) { 
                    $timelines = array_merge($timelines, $this->buildDataHead($year, $echeance));
                }  
            }
            elseif($echeance == "JOURNALIERE"){
                for ($year=2021; $year < 2023 ; $year++) { 
                    $timelines = array_merge($timelines, $this->buildDataHead($year, $echeance));
                }  
            }  
        }

        return $timelines;
    }

    public function buildDataHead($year, $echeance, $direction=null){
        $currentTimeline = []; $TIMELINES = [];
        if($echeance == "JOURNALIERE"){
            if(is_null($direction)){
                for ($month=1; $month <= 12; $month++) { 
                    $countDaysMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    
                    for ($day=1; $day <= $countDaysMonth ; $day++) { 
                        $currentDay = new \DateTime($year.'-'.$month.'-'.$day);
                        //pour les années où le 1er janvier est en fin de semaine
                        if($day <= 7 && $currentDay->format("W") == 53);
                        else{
                            $currentTimeline[$currentDay->format("W")]['semaine'] = $currentDay->format("W");
                            $currentTimeline[$currentDay->format("W")]['sub_head'][] = $day;
                            $currentTimeline[$currentDay->format("W")]['date'][] = $currentDay->format('Y-m-d');
                        }
                    }
                }
                
                /* completer le premier mois avec les dernier jour de l'annee precedente */
                $sizeFirst = count($currentTimeline['01']['sub_head']);
                if(count($currentTimeline['01']['sub_head']) < 7){
                    for ($k=0; $k < (7-$sizeFirst) ; $k++) { 
                        array_unshift($currentTimeline['01']['sub_head'], (31-$k));
                        array_unshift($currentTimeline['01']['date'], ($year-1).'-'.'12'.'-'.(31-$k));
                    }
                }
                /* completer la derniere semaine de l'annee avec les jr de l'annee qui suit */
                if(array_key_exists('53', $currentTimeline)){
                    $sizeLast = count($currentTimeline['53']['sub_head']);
                    if(count($currentTimeline['53']['sub_head']) < 7){
                        for ($k=1; $k <= (7-$sizeLast) ; $k++) { 
                            $currentTimeline['53']['sub_head'][] = $k;
                            $currentTimeline['53']['date'][] = ($year+1).'-'.'01'.'-'.$k;
                        }
                    }
                }

                foreach ($currentTimeline as $key => $value) {
                    $startDate = Carbon::parse($value['date'][0])->locale('fr')->isoFormat('MMM D');
                    $startEnd = Carbon::parse($value['date'][count($value['date'])-1])->locale('fr')->isoFormat('MMM D');

                    $value['head'] = "<b>Semaine ".(int)$value['semaine'].'</b> '.$startDate.' - '.$startEnd;
                    $value['debut'] = $startDate;
                    $value['fin'] = $startEnd;
                    $value['echeance'] = $echeance;
                    $TIMELINES[] = $value;
                }
            }
            else{

            }
        }
        elseif($echeance == "TRIMESTRIELLE"){
            if(is_null($direction)){
                $TIMELINES[] = [
                    'echeance'=>$echeance,
                    'head'=>'<b>'.$year.'</b>',
                    'year'=>$year,
                    'sub_head'=>["Q1", "Q2", "Q3", "Q4"],
                    'months'=>$this->getGroupMonthByYear($year),
                ];
            }
            else{
                $TIMELINES[] = [
                    'echeance'=>$echeance,
                    'head'=>'<b>'.$year.'</b>',
                    'year'=>$year,
                    'sub_head'=>["Q1", "Q2", "Q3", "Q4"],
                    'months'=>$this->getGroupMonthByYear($year)
                ];
            }
        }
        return $TIMELINES;
    }

    public function getGroupMonthByYear($year){
        $tabMonth = [];
        for ($i=1; $i <= 12 ; $i = $i+3) { 
            $monthsCel = [];
            for ($j=$i; $j < $i+3; $j++) { 
                $countDaysMonth = cal_days_in_month(CAL_GREGORIAN, $j, $year);
                for ($k=1; $k <= $countDaysMonth ; $k++) { 
                    $mois = ($j<10) ? '0'.$j : $j;
                    $day = ($k<10) ? '0'.$k : $k;

                    $monthsCel[] = $year.'-'.$mois.'-'.$day;
                }
            }
            $tabMonth[] = $monthsCel;
        }
        return $tabMonth;
    }

    public function generationTimeline($direction, $dateReference, $sautDate, $count){
        $timelines = [];
        if($direction){ // direction is enough to condition
            if($direction == "right"){
                for ($i = ($dateReference + 1); $i <= ($dateReference+$sautDate); $i++) {                    
                    if( $count%2 ){
                        $timelines[] = [
                            'year'=>$i,
                            'q' => ["tache Q1-Q2 annee ".$i, "", "", ""],
                        ];
                    }
                    else{
                        $timelines[] = [
                            'year'=>$i,
                            'q' => ["", "", "tache Q3-Q4 annee ".$i, ""],
                        ];
                    }
                }
            }
            else if($direction == "left"){
                for ($i = ($dateReference-$sautDate); $i < $dateReference; $i++) {
                    if( $count%2 ){
                        $timelines[] = [
                            'year'=>$i,
                            'q' => ["tache Q1-Q2 anne ".$i, "", "", ""],
                        ];
                    }
                    else{
                        $timelines[] = [
                            'year'=>$i,
                            'q' => ["", "", "tache Q3-Q4 anne ".$i, ""],
                        ];
                    }
                }
            }
        }
        else{
            for ($i=2021; $i < 2025 ; $i++) { 

                if( $count%2 ){
                    $timelines[] = [
                        'year'=>$i,
                        'q' => ["tache Q1-Q2 anne ".$i, "", "", ""],
                    ];
                }
                else{
                    $timelines[] = [
                        'year'=>$i,
                        'q' => ["", "", "tache Q3-Q4 anne ".$i, ""],
                    ];
                }
            }
        }

        return $timelines;
    }

    public function buildTimeline($direction, $dateReference, $sautDate, $user, $echeance, $categorieId){
        $timelines = []; $initTache = [];
        if($echeance == "JOURNALIERE"){
            for ($i=0; $i < 7 ; $i++) { 
                $initTache[] = [];
            }
        }
        elseif($echeance == "TRIMESTRIELLE"){
            for ($i=0; $i < 4 ; $i++) { 
                $initTache[] = [];
            }
        }

        if($direction){ // direction is enough to condition
            if($direction == "right"){
                $plannings = $this->planningRepository->getPlanningByUser(($dateReference + 1), ($dateReference+$sautDate), $user->getUid(), $categorieId);
                
                /* initialisation de la timeline à recuperer */
                for ($i= ($dateReference + 1); $i <= ($dateReference+$sautDate) ; $i++) { 
                    $timelines[] = [ 'year'=>$i, 'taches' => $initTache];
                }
            }
            else if($direction == "left"){
                $plannings = $this->planningRepository->getPlanningByUser(($dateReference-$sautDate), ($dateReference-1), $user->getUid(), $categorieId);
                
                /* initialisation de la timeline à recuperer */
                for ($i= ($dateReference-$sautDate); $i <= ($dateReference-1) ; $i++) { 
                    $timelines[] = [ 'year'=>$i, 'taches' => $initTache];
                }
            }
        }
        else{
            if($echeance == "TRIMESTRIELLE"){
                $plannings = $this->planningRepository->getPlanningByUser(2021, 2025, $user->getUid(), $categorieId);
                /* initialisation de la timeline à recuperer */
                for ($i=2021; $i < 2025 ; $i++) { 
                    $timelines[] = [ 'year'=>$i, 'taches' => $initTache ];
                }
                foreach ($plannings as $value) {
                    $debut = new \Datetime($value['debut']);
                    $index = array_search($debut->format('Y'), array_column($timelines, 'year'));

                    $cellRang = $this->getCellRangByMonth($debut->format('m'));
                    if($index !== FALSE && $cellRang >= 0){
                        $timelines[$index]['taches'][$cellRang][] = [
                            'year'=>$debut->format('Y'),
                            'id'=>$value['id'],
                            'tache'=>$value['tache'],
                            'categorieId'=>$value['categorieId'],
                            'color'=> $value['color'],
                            'debut'=>$debut->format('Y-m-d'),
                            'date_fin'=>(new \Datetime($value['dateFin']))->format('Y-m-d'),
                            'cellRang'=>$cellRang,
                            'identify_cell'=>$debut->format('Y').'_'.$cellRang.'_'.$user->getUid()
                        ];
                    }
                } 
            }
            elseif($echeance == "JOURNALIERE"){
                $plannings = $this->planningRepository->getPlanningByUser(2021, 2023, $user->getUid(), $categorieId);
                /* initialisation de la timeline à recuperer */
                for ($i=2021; $i < 2023 ; $i++) { 
                    for ($month=1; $month <= 12; $month++) { 
                        $countDaysMonth = cal_days_in_month(CAL_GREGORIAN, $month, $i);
                        
                        for ($day=1; $day <= $countDaysMonth ; $day++) { 
                            $currentDay = new \DateTime($i.'-'.$month.'-'.$day);
                            //pour les années où le 1er janvier est en fin de semaine
                            if($day <= 7 && $currentDay->format("W") == 53);
                            else{
                                $currentTimeline[$currentDay->format("W")]['semaine'] = $currentDay->format("W");
                                $currentTimeline[$currentDay->format("W")]['taches'][] = [];
                                $currentTimeline[$currentDay->format("W")]['date'][] = $currentDay->format('Y-m-d');
                            }
                        }
                    }
                    
                    /* completer le premier mois avec les dernier jour de l'annee precedente */
                    $sizeFirst = count($currentTimeline['01']['taches']);
                    if(count($currentTimeline['01']['taches']) < 7){
                        for ($k=0; $k < (7-$sizeFirst) ; $k++) { 
                            array_unshift($currentTimeline['01']['taches'], []);
                            array_unshift($currentTimeline['01']['date'], ($i-1).'-'.'12'.'-'.(31-$k));
                        }
                    }


                    /* completer la derniere semaine de l'annee avec les jr de l'annee qui suit */
                    if(array_key_exists('53', $currentTimeline)){
                        $sizeLast = count($currentTimeline['53']['taches']);
                        if(count($currentTimeline['53']['taches']) < 7){
                            for ($k=1; $k <= (7-$sizeLast) ; $k++) { 
                                $currentTimeline['53']['taches'][] = [];
                                $currentTimeline['53']['date'][] = ($i+1).'-'.'01'.'-'.$k;
                            }
                        }
                    }

                    foreach ($currentTimeline as $key => $value) {
                        $startDate = Carbon::parse($value['date'][0])->locale('fr')->isoFormat('MMM D');
                        $startEnd = Carbon::parse($value['date'][count($value['date'])-1])->locale('fr')->isoFormat('MMM D');

                        $value['head'] = "<b>Semaine ".(int)$value['semaine'].'</b> '.$startDate.' - '.$startEnd;

                        $value['year_semaine'] = explode('-', $value['date'][0])[0].'-'.(int)$value['semaine'];
                        $timelines[] = $value;
                    }
                }
                foreach ($plannings as $value) {
                    $debut = new \Datetime($value['debut']);
                    $index = array_search($debut->format('Y').'-'.(int)$debut->format("W"), array_column($timelines, 'year_semaine'));

                    $cellRang = array_search($debut->format('Y-m-d'), $timelines[$index]['date']);

                    if($index !== FALSE && $cellRang !== FALSE  ){
                        $timelines[$index]['taches'][$cellRang][] = [
                            'year'=>$debut->format('Y'),
                            'id'=>$value['id'],
                            'tache'=>$value['tache'],
                            'color'=> $value['color'],
                            'debut'=>$debut->format('Y-m-d'),
                            'date_fin'=>(new \Datetime($value['dateFin']))->format('Y-m-d'),
                            'identify_cell'=>$debut->format('Y').'_'.$debut->format("W").'_'.$debut->format('d').'_'.$user->getUid()
                        ];
                    }
                }   
            }
        }

        return $timelines;     
    }

    public function getCellRangByMonth($month){
        $month = (int)$month;
        if( 1 <= $month  && $month <= 3)
            return 0;
        elseif( 4 <= $month  && $month <= 6)
            return 1;
        elseif( 7 <= $month  && $month <= 9)
            return 2;
        elseif( 10 <= $month  && $month <= 12)
            return 3;

        return -1;
    }

    /**
     * @Route("/get-timeline", name="get_timeline")
    */
    public function getTimeline(Request $request){

        $echeance = $request->query->get('echeance');

        if($request->query->get('echeance'))
            $echeance = $request->query->get('echeance');

        $direction = $request->query->get('direction');
        $dateReference = $request->query->get('dateReference');
        $sautDate = $request->query->get('sautDate');

        $categorieId = null;
        if( ($request->query->get('categorie_print') != "") && $request->query->get('categorie_print') != "undefined" ){
            $categorieId = $request->query->get('categorie_print');
        }
        if( ($request->query->get('user_print') != "") && $request->query->get('user_print') != "undefined" ){
            $utilisateurs = $this->utilisateurRepository->getByTabUser( $this->session->get('entreprise_session_id'), $request->query->get('user_print'));
        }
        else{
            $utilisateurs = $this->utilisateurRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'etat'=>1]);
        }
        $utilisateursArr = []; $timelines = [];

        $i = 0;
        foreach ($utilisateurs as $user) {
            $currentUser = [];
            $currentUser['id'] = $user->getUid();
            $currentUser['lastname'] = utf8_encode($user->getLastName());
            $currentUser['firstname'] = utf8_encode($user->getFirstName());
            $currentUser['full_name'] = utf8_encode($user->getLastName().' '.$user->getFirstName());
            $currentUser['email'] = utf8_encode($user->getEmail());
            $currentUser['avatar'] = $user->getImage();
            $utilisateursArr[] = $currentUser;   
            $timelines[] = [
                'user'=> $currentUser,
                'timelines'=> $this->buildTimeline($direction, $dateReference, $sautDate, $user, $echeance, $categorieId)
                //'timelines'=> $this->generationTimeline($direction, $dateReference, $sautDate, $i++)
            ];
        }

        $responses['status'] = 200;
        $responses['timelines'] = $timelines;
        $responses['year'] = $this->generationYear($direction, $dateReference, $sautDate, $echeance);
        $responses = new Response(json_encode($responses));
        return $responses; 
    }
}
