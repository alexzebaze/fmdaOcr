<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Fournisseurs;
use App\Entity\OcrField;
use App\Entity\EmailDocumentPreview ;
use App\Repository\MetaConfigRepository;
use App\Entity\Chantier;
use App\Entity\Previsionel;
use App\Entity\PrevisionelCategorie;
use App\Entity\Achat;
use App\Entity\Lot;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Repository\ChantierRepository;
use App\Repository\LogementRepository;
use App\Repository\LotRepository;
use App\Repository\AchatRepository;
use App\Entity\Entreprise;
use App\Form\FournisseursType;
use App\Service\GlobalService;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Route("/fournisseurs", name="fournisseur_")
 */
class FournisseursController extends Controller
{
    private $global_s;
    private $session;
    private $chantierRepository;
    private $metaConfigRepository;
    private $logementRepository;
    private $lotRepository;
    private $achatRepository;

    public function __construct(GlobalService $global_s, LogementRepository $logementRepository,SessionInterface $session, ChantierRepository $chantierRepository, AchatRepository $achatRepository, MetaConfigRepository $metaConfigRepository, LotRepository $lotRepository){
        $this->metaConfigRepository = $metaConfigRepository;
        $this->logementRepository = $logementRepository;
        $this->global_s = $global_s;
        $this->session = $session;
        $this->chantierRepository = $chantierRepository;
        $this->achatRepository = $achatRepository;
        $this->lotRepository = $lotRepository;
    }

    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function index(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $fournisseurs = $entityManager->getRepository(Fournisseurs::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]); 
        
        $adapter = new ArrayAdapter($fournisseurs);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(100000);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        

        $page = $entityManager->getRepository(Page::class)->findOneBy(['cle'=>"FOURNISSEUR"]);

        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }
        
        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }

        return $this->render('fournisseurs/index.html.twig', [
            'pager' => $pager,
            'fournisseurs' => $fournisseurs,
            'code_compta' => $this->global_s->getTabCodeCompta(),
            'columns'=>$columns,
            'columnsVisibileId'=>$columnsVisibileIdArr,
            'tabColumns'=>$this->global_s->getTypeFields()
        ]);
    }

    /**
     * @Route("/find", name="find", methods={"GET"})
     */
    public function findFournisseur(Request $request)
    {
        $dossier = $request->query->get('dossier');
        $filename = $request->query->get('filename');
        $fournisseurName = $request->query->get('fournisseur');

        $found = 0;
        $fournisseurArr = [];
        if($dossier != "" && $filename != ""){
            
            $entityfound = [];

            $filenamepdf = str_replace('jpg', 'pdf', $filename);
            $sender = $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->findOneBy(['dossier'=>$dossier, "document"=>$filenamepdf]);

            $expediteurExiste = false;
            if(!is_null($sender) && ($dossier == "facturation" || $dossier == "bon_livraison"))
                $expediteurExiste = $this->getDoctrine()->getRepository(Fournisseurs::class)->getWithEmailExist(strtolower($sender->getSender()), $dossier);

            if($expediteurExiste){
                $fournisseurArr["1"] = [$expediteurExiste];
            }
            else{  
                if(isset($fournisseurName) && $fournisseurName != ""){
                    $fournisseurSelect = $this->getDoctrine()->getRepository(Fournisseurs::class)->findOneBy(['nom'=>$request->query->get('fournisseur')]);

                    $firstEltDocument = $this->getDoctrine()->getRepository(OcrField::class)->getFirstEltDocument($dossier, $this->session->get('entreprise_session_id'), $filename);

                    if($fournisseurSelect->getNom2()){
                        $entityfound = $this->getDoctrine()->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $fournisseurName, $firstEltDocument['id'], $fournisseurSelect->getNom2(), 30);
                    }
                    
                    if(count($entityfound) > 0){
                        if(array_search($fournisseurSelect->getId(), array_column($fournisseurArr, 'id')) === false) {
                            $fournisseurArr["1"] = ['id'=>$fournisseurSelect->getId(), 'name'=>$fournisseurSelect->getNom()];
                        }
                        $found = 1;
                    }
                }
                else{

                    $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->getByEntrepriseOdreByBl($this->session->get('entreprise_session_id'));

                    $firstEltDocument = $this->getDoctrine()->getRepository(OcrField::class)->getFirstEltDocument($dossier, $this->session->get('entreprise_session_id'), $filename);


                    foreach ($fournisseurs as $value) {
                        if(strtolower($value['nom']) == 'a definir' || strtolower($value['nom']) == 'fmda construction')
                            continue;


                        $priority = "1";
                        if(strpos(strtolower($value['nom']), "france") !== false){// exception pour le fournisseur france air
                            $entityfound = $this->getDoctrine()->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $value['nom'], $firstEltDocument['id'], "", 30);
                        }
                        else{
                            $entityfound = $this->getDoctrine()->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $value['nom'], $firstEltDocument['id'], "", 30);

                            if(count($entityfound) == 0){
                                $entityfound = $this->getDoctrine()->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $value['nom'], $firstEltDocument['id'], $value['nom2'], 30);

                                $priority = "2";
                            }
                        }

                        if(count($entityfound) > 0){
                            if(array_search($value['id'], array_column($fournisseurArr, 'id')) === false) {
                                $fournisseurArr[$priority][] = ['id'=>$value['id'], 'name'=>$value['nom']];
                            }
                            //break;
                        }
                    }

                    if(count($fournisseurArr) > 0){
                        if(array_key_exists("1", $fournisseurArr) && count($fournisseurArr["1"]) > 0)
                            $fournisseurArr = $fournisseurArr["1"];
                        elseif(array_key_exists("2", $fournisseurArr))
                            $fournisseurArr = $fournisseurArr["2"];
                        $found = 1;
                    }
                }
            }
        }

        $response = new Response(json_encode([
            'count'=>$found,
            'fournisseurs'=>$fournisseurArr
        ]));
        return $response;
    }

    /**
     * @Route("/save-rotation", name="save_rotation")
     */
    public function saveRotation(Request $request)
    {
        $rotation = $request->query->get('rotation');
        $fournisseurId = $request->query->get('fournisseur');
        $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurId);
        $fournisseur->setRotation($rotation);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        return new Response(json_encode(['status'=>200]));
    }

    /**
     * @Route("/new-xhr", name="new_xhr", methods={"GET","POST"})
     */
    public function loadFormXhr(Request $request): Response
    {
        $fournisseur = new Fournisseurs(); 
        
        $form = $this->createForm(FournisseursType::class, $fournisseur);
        $form->handleRequest($request);

        if ($request->isMethod('post')) {
            if($form->isSubmitted() && $form->isValid()){

                $entityManager = $this->getDoctrine()->getManager();

                /** @var UploadedFile $logo */
                $uploadedFile = $form['logo']->getData();
                if ($uploadedFile){
                    $newFilename = $this->global_s->saveImage($uploadedFile, '/public/uploads/logo_fournisseur/');
                    $fournisseur->setLogo($newFilename);
                }

                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $fournisseur->setEntreprise($entreprise);
                
                $fournisseur->setNom(strtoupper($fournisseur->getNom()));
                $entityManager->persist($fournisseur);
                $entityManager->flush();

                $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

                $fournisseursArr = [];
                foreach ($fournisseurs as $key => $value) {
                    $fournisseursArr[] = [
                        'nom' => $value->getNom(),
                        'id' => $value->getId()
                    ];
                }
                $datas = [
                    'status'=>200, 
                    "message"=>"", 
                    'fournisseurs'=> $fournisseursArr,
                    'fournisseur_id'=>$fournisseur->getId()
                ];
                
                return new Response(json_encode($datas));
            }
            else{
                $datas = [
                    'status'=>500, 
                    "message"=>"Veuillez remplir correctement le formulaire", 
                ];
                
                return new Response(json_encode($datas));
            }
        }

        $datas = ['status'=>200, "message"=>""];
        $datas['content'] = $this->renderView('fournisseurs/_form.html.twig', [
            'form' => $form->createView(),
            'fournisseur'=>$fournisseur
        ]);
        return new Response(json_encode($datas));

    }

    /**
     * @Route("/add", name="add")
     */
    public function new(Request $request)
    {
        $fournisseur = new Fournisseurs();
        $fournisseur->setDatecrea(new \DateTime());
        $fournisseur->setDatemaj(new \DateTime());

        $form = $this->createForm(FournisseursType::class, $fournisseur, array('entreprise' => $this->session->get('entreprise_session_id')));

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                
                $entityManager = $this->getDoctrine()->getManager();

                /** @var UploadedFile $logo */
                $uploadedFile = $form['logo']->getData();
                if ($uploadedFile){
                    $newFilename = $this->global_s->saveImage($uploadedFile, '/public/uploads/logo_fournisseur/');
                    $fournisseur->setLogo($newFilename);
                }

                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $fournisseur->setEntreprise($entreprise);
                

                $fournisseur->setNom(strtoupper($fournisseur->getNom()));
                $entityManager->persist($fournisseur);
                $entityManager->flush();                

                return $this->redirectToRoute('fournisseur_list');
            }
        }

        return $this->render('fournisseurs/add.html.twig', [
            'form' => $form->createView(),
            'fournisseur' => $fournisseur
        ]);
    }

    /**
     * @Route("/add-budget", name="prevision_add_budget")
     */
    public function addBudget(Request $request)
    {   
        $lotId = $request->query->get('lot_id');
        $chantierId = $request->query->get('chantier_id');
        $budget = $request->query->get('budget');

        $previsionel = $this->getDoctrine()->getRepository(Previsionel::class)->findOneBy(['lot'=> $lotId, 'chantier'=>$chantierId]);

        if(is_null($previsionel))
            $previsionel = new Previsionel();

        if($budget){
            $budget = str_replace(",", '.', $budget);
            $budget = str_replace(" ", '', $budget);
            $previsionel->setBudget(trim($budget));
        }
        else
            $previsionel->setBudget(null);

        $lot =  $this->getDoctrine()->getRepository(Lot::class)->find($lotId);
        $previsionel->setLot($lot);

        $chantier =  $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);
        $previsionel->setChantier($chantier);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($previsionel);
        $entityManager->flush();  

        return new Response(json_encode(['status'=>200, "success request"]));
    }

    /**
     * @Route("/{fournisseurId}/edit", name="edit")
     */
    public function edit(Request $request, $fournisseurId)
    {
        $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurId);
        $fournisseur->setDatemaj(new \DateTime());
        $form = $this->createForm(FournisseursType::class, $fournisseur, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logo */
            $uploadedFile = $form['logo']->getData();
            if ($uploadedFile){
                $newFilename = $this->global_s->saveImage($uploadedFile, '/public/uploads/logo_fournisseur/');
                $fournisseur->setLogo($newFilename);
            }
            $fournisseur->setNom(strtoupper($fournisseur->getNom()));
            
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('fournisseur_list');
        }

        return $this->render('fournisseurs/edit.html.twig', [
            'form' => $form->createView(),
            'fournisseur' => $fournisseur
        ]);
    }


    /**
     * @Route("/{fournisseurId}/delete", name="delete")
     */
    public function delete(Request $request, $fournisseurId)
    {
        $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurId);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($fournisseur);
        $entityManager->flush();

        return $this->redirectToRoute('fournisseur_list');
    }    

    /**
     * @Route("/toggle-categorie-previsionel-status/{categorie_id}/{chantier_id}", name="toggle_categorie_previsionel_status")
     */
    public function toggleCategoriePrevisionelStatus($categorie_id, $chantier_id){
        $categorie = $this->getDoctrine()->getRepository(PrevisionelCategorie::class)->find($categorie_id);
        $categorie->setStatus( (1-$categorie->getStatus()) );
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
        return $this->redirectToRoute('fournisseur_previsionner', ['chantier_id'=>$chantier_id]);
    }

    public function getDureeCategorie($chantierId){
        $categories = $this->getDoctrine()->getRepository(PrevisionelCategorie::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $duree = [];
        foreach ($categories as $value) {
            $duree[$value->getId()] =  $this->getDoctrine()->getRepository(Previsionel::class)->getDureeCategorie($value->getId(), $chantierId);
        }
        return $duree;
    }

    public function getCategorieDataGraph($categories, $chantierParam){
        $dateInterval = [1, 12];
        $tabAnnee = [2021, 2022];

        $valChart = [];$tabBudget = []; $CHART_YEAR = []; $CHART_SUM_MOIS = [];$tabSumMois = [];
        $initTabSum = [];
        for ($i=$dateInterval[0]; $i <= $dateInterval[1]; $i++) { 
            $tabBudget[$i] = "";
            $initTabSum[$i] = 0;
        }


        $categoriesLot = [];
        foreach ($tabAnnee as $year) {
            $valChart = []; $tabSumMois = $initTabSum;
            foreach ($categories as $val) {
                $currentCat = ['nom_categorie'=>$val->getNom(), 'budget'=>$tabBudget, "sumbudget"=>0];

                if(!$val->getStatus())
                    continue;
                
                for ($i=1; $i <=12 ; $i++) { 
                    $lots = $this->getDoctrine()
                                ->getRepository(Previsionel::class)->getLotByMonth($val->getId(), $this->global_s->addZeroToDay($i), $year, $chantierParam);
                    $sumbudget = 0;
                    if(count($lots) > 0){
                        foreach ($lots as $key => $lot) {
                            $moisDebut =  (int)(new \DateTime($lot['date_debut']))->format('m');                    
                            $moisFin =  (int)(new \DateTime($lot['date_fin']))->format('m');
                             $yearDebut =  (int)(new \DateTime($lot['date_debut']))->format('Y');                    
                                $yearFin =  (int)(new \DateTime($lot['date_fin']))->format('Y');

                            if($yearDebut == $yearFin){
                                $diffMois = $moisFin - $moisDebut + 1;               
                            }
                            else
                                $diffMois = (12 - $moisDebut+1) + $moisFin;
                               
                            $sumbudget += ($lot['budget']/$diffMois);
                        }
                        if(array_key_exists($i, $currentCat['budget'])){
                            $currentCat['budget'][$i] = round($sumbudget, 2);
                            $tabSumMois[$i] += $sumbudget;
                        }
                    }
                }

                $allLots =  $this->getDoctrine()->getRepository(Lot::class)->findBy(['previsionel_categorie'=>$val->getId()], ['lot'=>'ASC']);
                $TotalbudgetLot=0;
                foreach ($allLots as $value) {
                    $previsionel = $this->getDoctrine()
                                ->getRepository(Previsionel::class)->findOneBy(['chantier'=>$chantierParam, 'lot'=>$value->getId()]);

                    if(!is_null($previsionel))
                        $TotalbudgetLot += $previsionel->getBudget();
                }   
                $currentCat['sumbudget'] = round($TotalbudgetLot, 2);
                $currentCat['data']= array_values($currentCat['budget']);
                $valChart[] = $currentCat;
                
            }

            $CHART_YEAR[] = $valChart;
            $CHART_SUM_MOIS[] = $tabSumMois;
        }
        return ['CHART_YEAR'=>$CHART_YEAR, 'CHART_SUM_MOIS'=>$CHART_SUM_MOIS];
    }

    public function getLotByMonth($categorieId, $chantierId){

    }

    /**
     * @Route("/budget/{chantier_id}", name="previsionner")
     * @Route("/budget-print", name="previsionner_print")
     */
    public function budget(Request $request, $chantier_id=null)
    {   
        $chantiers = $this->chantierRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['nameentreprise'=>'ASC']);

        $DATA_EXCEL = [];
        $devis = [];
        $previsionelLot = [];
        $categoriesLot = [];
        $previsionelChantier = [];  
        $lotsWithoutCateg =  $this->lotRepository->getLotsWithoutCateg();

        $totalDevis = 0; $totalFacture = 0; $Totalbudget=0;$TotalbudgetLots = 0;
        $categories = [];

        $chantierParam = (!is_null($chantier_id)) ? $chantier_id : $request->query->get('chantier_id');

        $categoriesLot = []; $dateCatGraph = [];

        $chantier = null;
        if($chantierParam){
            $chantier = $this->chantierRepository->find($chantierParam);
        }

        $dureeCategorie = $this->getDureeCategorie($chantierParam);
        if($chantierParam){

            $categories = $this->getDoctrine()->getRepository(PrevisionelCategorie::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
            $dateCatGraph = $this->getCategorieDataGraph($categories, $chantierParam)['CHART_YEAR'];
            $CHART_SUM_MOIS = $this->getCategorieDataGraph($categories, $chantierParam)['CHART_SUM_MOIS'];

            foreach ($categories as $val) {
                if(!$val->getStatus())
                    continue;
                
                $previsionelLotCategorie = [];
                $lots =  $this->getDoctrine()->getRepository(Lot::class)->findBy(['previsionel_categorie'=>$val->getId()], ['num'=>'ASC']);
                $totalDevisLot = 0; $totalFactureLot = 0; $TotalbudgetLot=0;
                foreach ($lots as $value) {
                    $provCurrent=[];
                    
                    $provCurrent['previsionel'] = $this->getDoctrine()
                            ->getRepository(Previsionel::class)->findOneBy(['chantier'=>$chantierParam, 'lot'=>$value->getId()]);
                    
                    $provCurrent['lot'] = $value;
                    $provCurrent['sum_ht'] =  $this->achatRepository->sumMontantAchatValideByTypeByStatus($value->getId(), $chantierParam, 'devis_pro')['sum_ht'];
                    $provCurrent['sum_ht_facture'] =  $this->achatRepository->sumMontantAchatValideByTypeByStatus($value->getId(), $chantierParam, 'facturation')['sum_ht'];
                    $provCurrent['count_devis'] = $this->achatRepository->countDevisByChantierAndStatus($chantierParam);

                    $totalDevisLot += $provCurrent['sum_ht'];
                    $totalFactureLot += $provCurrent['sum_ht_facture'];
                    if(!is_null($provCurrent['previsionel']))
                        $TotalbudgetLot += ($provCurrent['previsionel'])->getBudget();

                    $previsionelLotCategorie[] = $provCurrent;
                }   
                
                $TotalbudgetLots+=$TotalbudgetLot;

                $categoriesLot[] =  [
                    'categorie'=>$val,
                    'totalDevisLot'=>$totalDevisLot,
                    'totalFactureLot'=>$totalFactureLot,
                    'TotalbudgetLot'=>$TotalbudgetLot,
                    'lots' => $previsionelLotCategorie
                ];   
                 
            }

            if($request->query->get('print_excel')){
                $DATA_EXCEL[] = ["CATEGORIE", "DATE", "BUDGET", "AVANCEMENT", "DEVIS", "AVANCEMENT", "FACTURE"];

                $dataCategorie = $this->buildDataCategorie($categoriesLot, $dureeCategorie, $chantier);
                $DATA_EXCEL = array_merge($DATA_EXCEL, $dataCategorie['DATA_EXCEL_WITH_TOTAUX']);
                $DATA_EXCEL[] = ["", "", "", "", "", "", ""];
                $DATA_EXCEL[] = ["", "", "", "", "", "", ""];

                $DATA_EXCEL = array_merge($DATA_EXCEL, $this->buildDataLots($dataCategorie['DATA_EXCEL'], $categoriesLot));

            }
            
            /*$lots =  $this->getDoctrine()->getRepository(Lot::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['num'=>'ASC']);
            foreach ($lots as $value) {
                $provCurrent=[];
                
                $provCurrent['previsionel'] = $this->getDoctrine()
                        ->getRepository(Previsionel::class)->findOneBy(['chantier'=>$chantierParam, 'lot'=>$value->getId()]);
                

                $provCurrent['lot'] = $value;
                $provCurrent['sum_ht'] =  $this->achatRepository->sumMontantAchatValideByTypeByStatus($value->getId(), $chantierParam, 'devis_pro')['sum_ht'];
                $provCurrent['sum_ht_facture'] =  $this->achatRepository->sumMontantAchatValideByTypeByStatus($value->getId(), $chantierParam, 'facturation')['sum_ht'];
                $provCurrent['count_devis'] = $this->achatRepository->countDevisByChantierAndStatus($chantierParam);
                
                $previsionelLot[] = $provCurrent;

                $totalDevis += $provCurrent['sum_ht'];
                $totalFacture += $provCurrent['sum_ht_facture'];
                if(!is_null($provCurrent['previsionel']))
                    $Totalbudget += ($provCurrent['previsionel'])->getBudget();
            } */
        }
        else{
            $previsionelGrChantier = $this->getDoctrine()->getRepository(Previsionel::class)->getPrevisionGroupByChantier();
            
            $DATA_EXCEL[] = ["CHANTIER", "BUDGET", "AVANCEMENT", "DEVIS", "AVANCEMENT", "FACTURE"];
            foreach ($chantiers as $value) {
                $provCurrent['chantier'] = $value;

                $key = array_search($value->getChantierId(), array_column($previsionelGrChantier, 'chantier_id'));
                if($key !== false) {
                    $provCurrent['budget'] = $previsionelGrChantier[$key]['budget'];
                }
                else{
                    $provCurrent['budget'] = 0;
                }
                $provCurrent['sum_ht'] =  $this->achatRepository->sumMontantAchatValideByChantierAndType($value->getChantierId(), 'devis_pro')['sum_ht'];
                $provCurrent['sum_ht_facture'] =  $this->achatRepository->sumMontantAchatValideByChantierAndType($value->getChantierId(), 'facturation')['sum_ht'];
                $previsionelChantier[] = $provCurrent;

                $totalDevis += $provCurrent['sum_ht'];
                $totalFacture += $provCurrent['sum_ht_facture'];
                $Totalbudget += $provCurrent['budget'];

                if($request->query->get('print_excel')){
                    $CURRENT_DATA_EXCEL = [];
                    $CURRENT_DATA_EXCEL[] = $provCurrent['chantier']->getNameentreprise();
                    $CURRENT_DATA_EXCEL[] = round($provCurrent['budget'], 2);

                    $budget = $provCurrent['budget'];
                    $devis = $provCurrent['sum_ht'] ? $provCurrent['sum_ht'] : 0;
                    $evolutionDevis = "";
                    if((int)$devis == 0 || (int)$budget == 0){
                        $evolutionDevis = '0%';
                    }
                    else if($devis <= $budget){
                        $evolutionDevis = (int)(($devis/$budget)*100).'%';
                    }
                    else if($devis > $budget){
                        $evolutionDevis = (int)(($devis/$budget)*100).'%';
                    }
                    $CURRENT_DATA_EXCEL[] = $evolutionDevis;
                    $CURRENT_DATA_EXCEL[] = $provCurrent['sum_ht'] ? round($provCurrent['sum_ht'], 2) : 0;

                    $budget2 = $provCurrent['sum_ht'];
                    $devis2 = $provCurrent['sum_ht_facture'] ? $provCurrent['sum_ht_facture'] : 0;
                    $evolutionFacture = "";
                    if((int)$devis2 == 0 || (int)$budget2 == 0){
                        $evolutionFacture = '0%';
                    }
                    else if($devis2 <= $budget2){
                        $evolutionFacture = (int)(($devis2/$budget2)*100).'%';
                    }
                    else if($devis2 > $budget2){
                        $evolutionFacture = (int)(($devis2/$budget2)*100).'%';
                    }
                    $CURRENT_DATA_EXCEL[] = $evolutionFacture;
                    $CURRENT_DATA_EXCEL[] = $provCurrent['sum_ht_facture'] ? round($provCurrent['sum_ht_facture'], 2) : 0;

                    $DATA_EXCEL[] = $CURRENT_DATA_EXCEL;
                }

                if($request->query->get('print_excel'))
                    $DATA_EXCEL[] = ["TOTAUX", $Totalbudget, "", $totalDevis, "", $totalFacture];
            }


        }
        if($request->query->get('print')){
            $template = 'fournisseurs/budget_print.html.twig';
        }
        elseif($request->query->get('print2')){
            $template = 'fournisseurs/budget_print2.html.twig';
        }
        elseif($request->query->get('print_excel')){
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet()
                ->fromArray(
                    $DATA_EXCEL,   
                    NULL,        
                    'A1'           
                );

            foreach (range('A', $spreadsheet->getActiveSheet()->getHighestDataColumn()) as $col) {
                $spreadsheet->getActiveSheet()
                    ->getColumnDimension($col)
                    ->setAutoSize(true);
            }
            
            $chantierName = (!is_null($chantier)) ? $chantier->getNameentreprise() : "";
            $sheet->setTitle("BUDGET ".$chantierName);

            // Create your Office 2007 Excel (XLSX Format)
            $writer = new Xlsx($spreadsheet);

            // In this case, we want to write the file in the public directory
            $publicDirectory = $this->get('kernel')->getProjectDir() . '/public/uploads/budget/';
            try {
                if (!is_dir($publicDirectory)) {
                    mkdir($publicDirectory, 0777, true);
                }
            } catch (FileException $e) {}

            $fileNameSave = 'RECAPITULATIF_BUDGET_CHANTIER_'.$chantierName.'.xlsx';

            $excelFilepath = $publicDirectory . $fileNameSave;

            // Create the file
            $writer->save($excelFilepath);

            // Return a text response to the browser saying that the excel was succesfully created
            return $this->file($excelFilepath, $fileNameSave, ResponseHeaderBag::DISPOSITION_INLINE);

        }
        else{
            $template = 'fournisseurs/budget.html.twig';
        }

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        if(count($dateCatGraph) == 0){
            $dateCatGraph = [[], []];
            $CHART_SUM_MOIS = [[], []];
        }
        return $this->render($template, [
            'chantiers'=> $chantiers,
            'categories'=> $categories,
            'chantierId'=>$chantierParam,
            'chantier'=>$chantier,
            'dateCatGraph'=>$dateCatGraph,
            'CHART_SUM_MOIS'=>$CHART_SUM_MOIS,
            'dureeCategorie'=>$dureeCategorie,
            'devis'=> $devis,
            'lotsPrevisionel'=>$previsionelLot,
            'categoriesLot'=>$categoriesLot,
            'lotsWithoutCateg'=>$lotsWithoutCateg,
            'previsionelChantier'=>$previsionelChantier,
            'totalDevis'=>$totalDevis,
            'totalFacture'=>$totalFacture,
            'Totalbudget'=>$Totalbudget,
            'TotalbudgetLots'=>$TotalbudgetLots,
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

    public function buildDataCategorie($datas, $dureeCategorie, $chantier){

        $DATA_EXCEL_WITH_TOTAUX= []; 
        $DATA_EXCEL = []; 

        $totaux = ['budget'=>0, 'devis'=>0, 'facture'=>0];
        foreach ($datas as $data) {
            $categorie = $data['categorie'];
            $CURRENT_DATA_EXCEL = []; 
            $CURRENT_DATA_EXCEL[] = $categorie->getNom();

            $dateInterval = "";
            if(!is_null($dureeCategorie[$categorie->getId()]['debut'])){
                $full_dateStart = Carbon::parse( (new \DateTime($dureeCategorie[$categorie->getId()]['debut']))->format('Y-m-d'))->locale('fr')->isoFormat('D MMM YYYY');
                $full_dateEnd = Carbon::parse( (new \DateTime($dureeCategorie[$categorie->getId()]['fin']))->format('Y-m-d'))->locale('fr')->isoFormat('D MMMM YYYY');
                $dateInterval =  $full_dateStart." - ".$full_dateEnd;
            }
            $CURRENT_DATA_EXCEL[] = $dateInterval;

            $CURRENT_DATA_EXCEL[] = (float)round($data['TotalbudgetLot'], 2);
            
            $budget = $data['TotalbudgetLot'];
            $devis = $data['totalDevisLot'];
            $evolutionDevis = "";
            if((int)$devis == 0 || (int)$budget == 0){
                $evolutionDevis = '0%';
            }
            else if($devis <= $budget){
                $evolutionDevis = (int)(($devis/$budget)*100).'%';
            }
            else if($devis > $budget){
                $evolutionDevis = (int)(($devis/$budget)*100).'%';
            }
            $CURRENT_DATA_EXCEL[] = $evolutionDevis;

            $CURRENT_DATA_EXCEL[] = (float)round($data['totalDevisLot'], 2);

            $budget2 = $data['totalDevisLot'];
            $devisFact = $data['totalFactureLot'];
            $evolutionFacture = "";
            if((int)$devisFact == 0 || (int)$budget2 == 0){
                $evolutionFacture = '0%';
            }
            else if($devisFact <= $budget2){
                $evolutionFacture = (int)(($devisFact/$budget2)*100).'%';
            }
            else if($devisFact > $budget2){
                $evolutionFacture = (int)(($devisFact/$budget2)*100).'%';
            }
            $CURRENT_DATA_EXCEL[] = $evolutionFacture;
            
            $CURRENT_DATA_EXCEL[] = (float)round($data['totalFactureLot'], 2);

            $totaux['budget'] += $data['TotalbudgetLot'];
            $totaux['devis'] += $data['totalDevisLot'];
            $totaux['facture'] += $data['totalFactureLot'];

            $DATA_EXCEL[] = $CURRENT_DATA_EXCEL;
        }

        foreach ($DATA_EXCEL as $v) {
            $DATA_EXCEL_WITH_TOTAUX[] = $v;
        }

        $DATA_EXCEL_WITH_TOTAUX[] = ['TOTAUX', "", (float)$totaux['budget'], "", (float)$totaux['devis'], "", (float)$totaux['facture']];

        $diviseur = $chantier->getM2() ?? 1;

        $DATA_EXCEL_WITH_TOTAUX[] = ['M2 '.$chantier->getM2(), "", (float)round( ($totaux['budget']/$diviseur), 2), "", "", "", ""];

        return ['DATA_EXCEL_WITH_TOTAUX'=> $DATA_EXCEL_WITH_TOTAUX, 'DATA_EXCEL'=>$DATA_EXCEL];
    }

    public function buildDataLots($dataCateg, $categoriesLot){

        $DATA_EXCEL = [];
        for ($i = 0; $i< count($dataCateg); $i++) {
            $DATA_EXCEL[] = $dataCateg[$i];
            $DATA_EXCEL[] = ["LOT", "Date", "BUDGET", "AVANCEMENT", "DEVIS", "AVANCEMENT", "FACTURE"];
            foreach ($categoriesLot[$i]['lots'] as $prevision) {
                $CURRENT_DATA_EXCEL = [];
                $CURRENT_DATA_EXCEL[] = (!is_null($prevision['lot'])) ? ($prevision['lot'])->getLot() : "";

                $dateInterval = "";
                if(!is_null($prevision['previsionel']) && !is_null($prevision['previsionel']->getDateDebut())){
                    $full_dateStart = Carbon::parse( $prevision['previsionel']->getDateDebut()->format('Y-m-d'))->locale('fr')->isoFormat('D MMM YYYY');
                    $full_dateEnd = Carbon::parse( $prevision['previsionel']->getDateFin()->format('Y-m-d'))->locale('fr')->isoFormat('D MMMM YYYY');
                    $dateInterval =  $full_dateStart." - ".$full_dateEnd;
                }
                $CURRENT_DATA_EXCEL[] = $dateInterval;

                $budget = (!is_null($prevision['previsionel'])) ? $prevision['previsionel']->getBudget() : 0;
                $CURRENT_DATA_EXCEL[] = (float)round($budget, 2);

                $devis = $prevision['sum_ht'];
                $evolutionDevis = "";
                if((int)$devis == 0 || (int)$budget == 0){
                    $evolutionDevis = '0%';
                }
                else if($devis <= $budget){
                    $evolutionDevis = (int)(($devis/$budget)*100).'%';
                }
                else if($devis > $budget){
                    $evolutionDevis = (int)(($devis/$budget)*100).'%';
                }
                $CURRENT_DATA_EXCEL[] = $evolutionDevis;

                $devisSum = "";
                if($prevision['count_devis']){
                    $devisSum = $prevision['sum_ht'] ? (float)round($prevision['sum_ht'], 2) : 0; 
                }
                $CURRENT_DATA_EXCEL[] = $devisSum;


                $devis2 = $prevision['sum_ht_facture'] ??  0 ;
                $budget2 = $prevision['sum_ht'];
                $evolutionFacture = "";
                if((int)$devis2 == 0 || (int)$budget2 == 0){
                    $evolutionFacture = '0%';
                }
                else if($devis2 <= $budget2){
                    $evolutionFacture = (int)(($devis2/$budget2)*100).'%';
                }
                else if($devis2 > $budget2){
                    $evolutionFacture = (int)(($devis2/$budget2)*100).'%';
                }
                $CURRENT_DATA_EXCEL[] = $evolutionFacture;

                $CURRENT_DATA_EXCEL[] = round($prevision['sum_ht_facture'], 2);                

                $DATA_EXCEL[] = $CURRENT_DATA_EXCEL;
            }
            $DATA_EXCEL[] = ["", "", "", "", "", "", ""];
            $DATA_EXCEL[] = ["", "", "", "", "", "", ""];
        }

        return $DATA_EXCEL;
        
    }


    /**
     * @Route("/previsionnel/{chantier_id}", name="previsionner_2")
     * @Route("/previsionnel-print", name="previsionner_print_2")
     */
    public function previsionner(Request $request, $chantier_id=null)
    {   
        $chantiers = $this->chantierRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['nameentreprise'=>'ASC']);

        $devis = [];
        $previsionelLot = [];
        $categoriesLot = [];
        $previsionelChantier = [];  
        $lotsWithoutCateg =  $this->lotRepository->getLotsWithoutCateg();

        $totalDevis = 0; $totalFacture = 0; $Totalbudget=0;$TotalbudgetLots = 0;
        $categories = [];

        $chantierParam = (!is_null($chantier_id)) ? $chantier_id : $request->query->get('chantier_id');

        $categoriesLot = []; $dateCatGraph = [];
        if($chantierParam){

            $categories = $this->getDoctrine()->getRepository(PrevisionelCategorie::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
            $dateCatGraph = $this->getCategorieDataGraph($categories, $chantierParam)['CHART_YEAR'];
            $CHART_SUM_MOIS = $this->getCategorieDataGraph($categories, $chantierParam)['CHART_SUM_MOIS'];

            foreach ($categories as $val) {
                if(!$val->getStatus())
                    continue;
                
                $previsionelLotCategorie = [];
                $lots =  $this->getDoctrine()->getRepository(Lot::class)->findBy(['previsionel_categorie'=>$val->getId()], ['num'=>'ASC']);
                $totalDevisLot = 0; $totalFactureLot = 0; $TotalbudgetLot=0;
                foreach ($lots as $value) {
                    $provCurrent=[];
                    
                    $provCurrent['previsionel'] = $this->getDoctrine()
                            ->getRepository(Previsionel::class)->findOneBy(['chantier'=>$chantierParam, 'lot'=>$value->getId()]);
                    
                    $provCurrent['lot'] = $value;
                    $provCurrent['sum_ht'] =  $this->achatRepository->sumMontantAchatValideByTypeByStatus($value->getId(), $chantierParam, 'devis_pro')['sum_ht'];
                    $provCurrent['sum_ht_facture'] =  $this->achatRepository->sumMontantAchatValideByTypeByStatus($value->getId(), $chantierParam, 'facturation')['sum_ht'];
                    $provCurrent['count_devis'] = $this->achatRepository->countDevisByChantierAndStatus($chantierParam);

                    $totalDevisLot += $provCurrent['sum_ht'];
                    $totalFactureLot += $provCurrent['sum_ht_facture'];
                    if(!is_null($provCurrent['previsionel']))
                        $TotalbudgetLot += ($provCurrent['previsionel'])->getBudget();

                    $previsionelLotCategorie[] = $provCurrent;
                }   
                
                $TotalbudgetLots+=$TotalbudgetLot;

                $categoriesLot[] =  [
                    'categorie'=>$val,
                    'totalDevisLot'=>$totalDevisLot,
                    'totalFactureLot'=>$totalFactureLot,
                    'TotalbudgetLot'=>$TotalbudgetLot,
                    'lots' => $previsionelLotCategorie
                ];           
            }

            $lots =  $this->getDoctrine()->getRepository(Lot::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['num'=>'ASC']);
            foreach ($lots as $value) {
                    $provCurrent=[];
                    
                    $provCurrent['previsionel'] = $this->getDoctrine()
                            ->getRepository(Previsionel::class)->findOneBy(['chantier'=>$chantierParam, 'lot'=>$value->getId()]);
                    

                    $provCurrent['lot'] = $value;
                    $provCurrent['sum_ht'] =  $this->achatRepository->sumMontantAchatValideByTypeByStatus($value->getId(), $chantierParam, 'devis_pro')['sum_ht'];
                    $provCurrent['sum_ht_facture'] =  $this->achatRepository->sumMontantAchatValideByTypeByStatus($value->getId(), $chantierParam, 'facturation')['sum_ht'];
                    $provCurrent['count_devis'] = $this->achatRepository->countDevisByChantierAndStatus($chantierParam);
                    
                    $previsionelLot[] = $provCurrent;

                    $totalDevis += $provCurrent['sum_ht'];
                    $totalFacture += $provCurrent['sum_ht_facture'];
                    if(!is_null($provCurrent['previsionel']))
                        $Totalbudget += ($provCurrent['previsionel'])->getBudget();
            } 
        }
        $template = 'fournisseurs/previsionnel.html.twig';

        $chantier = null;
        if($chantierParam){
            $chantier = $this->chantierRepository->find($chantierParam);
        }

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $logements = $this->logementRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantierParam, 'type'=>'vente']);

        return $this->render($template, [
            'logements'=> $logements,
            'chantiers'=> $chantiers,
            'categories'=> $categories,
            'chantierId'=>$chantierParam,
            'chantier'=>$chantier,
            'dureeCategorie'=>$this->getDureeCategorie($chantierParam),
            'devis'=> $devis,
            'lotsPrevisionel'=>$previsionelLot,
            'categoriesLot'=>$categoriesLot,
            'lotsWithoutCateg'=>$lotsWithoutCateg,
            'previsionelChantier'=>$previsionelChantier,
            'totalDevis'=>$totalDevis,
            'totalFacture'=>$totalFacture,
            'Totalbudget'=>$Totalbudget,
            'TotalbudgetLots'=>$TotalbudgetLots,
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


