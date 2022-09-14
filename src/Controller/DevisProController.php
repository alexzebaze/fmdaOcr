<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use App\Form\AchatType;
use App\Repository\AchatRepository;
use Doctrine\ORM\EntityRepository;
use App\Entity\Achat;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Entity\StatusModule;
use App\Entity\IAZone;
use App\Entity\TmpOcr;
use App\Entity\OcrField;
use App\Entity\ModelDocument;
use App\Entity\EmailDocumentPreview;
use App\Entity\PreferenceField;
use App\Entity\Status;
use App\Entity\Lot;
use App\Form\FournisseursType;
use App\Form\ChantierType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use App\Entity\Fournisseurs;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Repository\ChantierRepository;
use App\Repository\FournisseursRepository;
use App\Repository\EntrepriseRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Service\GlobalService;
use Carbon\Carbon;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use GuzzleHttp\Client;

/**
 * @Route("/devis/pro", name="devis_pro_")
 */
class DevisProController extends Controller
{
    use CommunTrait;
    private $username;
    private $password;
    private $queue_id ;
    private $chantierRepository;
    private $fournisseurRepository;
    private $achatRepository;
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, ChantierRepository $chantierRepository, AchatRepository $achatRepository, FournisseursRepository $fournisseurRepository, SessionInterface $session){
        $this->chantierRepository = $chantierRepository;
        $this->achatRepository = $achatRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session): Response
    {
        $form = $request->request->get('form', null);
        $fournisseurId = $chantierId = null; $lotId = null; $statusId = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $chantierId = (!(int)$form['chantier']) ? null : (int)$form['chantier'];
            $lotId = (!(int)$form['lot']) ? null : (int)$form['lot'];
            $fournisseurId = (!(int)$form['fournisseur']) ? null : (int)$form['fournisseur'];
            $devis_pros = $this->achatRepository->findByfacturedDate($mois, $annee, 'devis_pro', $chantierId, $fournisseurId, ['l.lot', 'ASC'], $lotId);

            $session->set('mois_devis_pro', $mois);
            $session->set('annee_devis_pro', $annee);
            $session->set('chantier_devis_pro', $chantierId);
            $session->set('lot_devis_pro', $lotId);
            $session->set('fournisseur_devis_pro', $fournisseurId);
        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_devis_pro', $day[1]);
            $annee = $session->get('annee_devis_pro', $day[0]);
            $chantierId = $session->get('chantier_devis_pro', null);
            $lotId = $session->get('lot_devis_pro', null);
            $statusId = $session->get('status_devis_pro', null);
            $fournisseurId = $session->get('fournisseur_devis_pro', null);
            $devis_pros = $this->achatRepository->findByfacturedDate($mois, $annee, 'devis_pro', $chantierId, $fournisseurId, ['l.lot', 'ASC'], $lotId, $statusId);

            $session->set('status_devis_pro', null);
            
        }

        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'chantier' => (!is_null($chantierId)) ? $this->chantierRepository->find($chantierId) : "",
            'fournisseur' => (!is_null($fournisseurId)) ?  $this->fournisseurRepository->find($fournisseurId) : "",
            'lot' => (!is_null($lotId)) ?  $entityManager->getRepository(Lot::class)->find($lotId) : "",

        ))
        ->add('mois', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $this->global_s->getAnnee(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('chantier', EntityType::class, array(
            'class' => Chantier::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('c')
                ->andWhere('c.entreprise = :entreprise')
                ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                ->orderBy('c.nameentreprise', 'ASC');
            },
            'required' => false,
            'label' => "Chantier",
            'choice_label' => 'nameentreprise',
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('lot', EntityType::class, array(
            'class' => Lot::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('l')
                ->andWhere('l.entreprise = :entreprise')
                ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                ->orderBy('l.lot', 'ASC');
            },
            'required' => false,
            'label' => "Lot",
            'choice_label' => 'lot',
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('fournisseur', EntityType::class, array(
            'class' => Fournisseurs::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('f')
                ->where('f.entreprise = :entreprise_id')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('f.nom', 'ASC');
            },
            'required' => false,
            'label' => "Fournisseur",
            'choice_label' => 'nom',
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $entityManager = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);

        $adapter = new ArrayAdapter($devis_pros);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        

        $doublon = $entityManager->getRepository(Achat::class)->findDoublon('devis_pro', $this->session->get('entreprise_session_id'));
        $tabDoublon = [];
        foreach ($devis_pros as $value) {
            if(array_search($value->getId(), array_column($doublon, 'id')) !== false) {
                $tabDoublon[] = $value->getId();
            }
        }

        $montant = $entityManager->getRepository(Achat::class)->countMontantByfacturedDate($mois, $annee, 'devis_pro', $chantierId, $fournisseurId, $lotId, $statusId);

        $valChart = [0,0,0,0,0,0,0,0,0,0,0,0];
        $valChart[(int)$mois-1] = number_format($montant['prixttc'], 3, '.', '');

        $full_month = $annee;
        if($mois && !empty($mois) && $annee){
            $full_month = Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY');
        }

        $countDocAttentes = $entityManager->getRepository(EmailDocumentPreview::class)->countByDossier('devis_pro');
        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("DEVIS_FOURNISSEUR");

        $page = $entityManager->getRepository(Page::class)->findOneBy(['cle'=>"DEVIS_FOURNISSEUR"]);
        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }
        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }
        
        return $this->render('devis_pro/index.html.twig', [
            'pager' => $pager,
            'status'=>$status,
            'devis_pros' => $devis_pros,
            'full_month'=> $full_month,
            'tabDoublon' => $tabDoublon,
            'countDocAttentes'=>$countDocAttentes,
            'valChart'=> $valChart,
            'mois'=>$mois,
            'annee'=>$annee,
            'form' => $form->createView(),
            'montant'=>$montant,
            'columns'=>$columns,
            'columnsVisibileId'=>$columnsVisibileIdArr,
            'tabColumns'=>$this->global_s->getTypeFields()
        ]);
    }

    /**
     * @Route("/lot-status/{lotId}/{chantierId}", name="list_by_lot_status", methods={"GET", "POST"})
     */
    public function indexByLot(Request $request, Session $session, $lotId, $chantierId): Response
    {
        $session->set('mois_devis_pro', null);
        $session->set('annee_devis_pro', null);
        if($chantierId)
            $session->set('chantier_devis_pro', $chantierId);
        $session->set('status_devis_pro', 15);
        $session->set('lot_devis_pro', $lotId);

        return $this->redirectToRoute('devis_pro_list');
    }   

    function sortMulti($data, $orders){
        $args = [];
        foreach ($data as $key => $row) {
            foreach ($orders as $index => $order) {
                if (!isset($row[$index])) continue; //Ignore if column does'nt exist
                $args[$index]['d'][$key] = $row[$index]; //Get all values within the column
                $args[$index]['o']       = 'desc' == strtolower($order) ? SORT_DESC : SORT_ASC; //Get the Sort order 'ASC' is the default
            }
        }
        $p = [];
        //Below we need to organize our entries as arguments for array_multisort
        foreach ($args as $arg) {
            $p[] = $arg['d'];
            $p[] = $arg['o'];
        //Below we need to check if column contains only numeric or not.
        //If all values are numeric, then we use numeric sort flag, otherwise NATURAL
        //Manipulate for more conditions supported
            $p[] = count($arg['d']) == count(array_filter($arg['d'], 'is_numeric')) ? SORT_NUMERIC : SORT_NATURAL;
        }
        $p[] = &$data; //Pass by reference
        call_user_func_array('array_multisort', $p); //Call Php's own multisort with parameters in required order.
        return $data; //Our final array sorted.
    }

    /**
     * @Route("/print", name="print")
     */
    public function print(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $day = explode('-', (new \DateTime())->format('Y-m-d'));
        $mois = $this->session->get('mois_devis_pro', $day[1]);
        $annee = $this->session->get('annee_devis_pro', $day[0]);
        $chantierId = $this->session->get('chantier_devis_pro', null);
        $lotId = $this->session->get('lot_devis_pro', null);
        $fournisseurId = $this->session->get('fournisseur_devis_pro', null);
        $devisPros = $this->achatRepository->findByfacturedDate($mois, $annee, 'devis_pro', $chantierId, $fournisseurId, null, $lotId);

        $rowArray = [];
        foreach ($devisPros as $key => $value) {
            $currentDevis = [];
            $currentDevis[] = $value->getFacturedAt()->format('Y-m-d');
            $currentDevis[] = !is_null($value->getFournisseur()) ? $value->getFournisseur()->getNom() : '';
            $currentDevis[] = !is_null($value->getChantier()) ? $value->getChantier()->getNameentreprise() : '';
            $currentDevis[] = (float)$value->getPrixttc();
            $currentDevis[] = $value->getTva()->getValeur();
            $currentDevis[] = (float)$value->getPrixht();
            $currentDevis[] = !is_null($value->getLot()) ? $value->getLot()->getLot() : '';
            $currentDevis[] = !is_null($value->getStatus()) ? $value->getStatus()->getName() : '';

            $rowArray[] = $currentDevis;
        }

        $tri = $request->query->get('tri');
        $column  = array_column($rowArray, $tri);
        array_multisort($column, SORT_ASC, $rowArray);        
        
        array_unshift($rowArray, ["Date Création", "Fournisseur", "Chantier", "TTC", "TVA", "HT", "Lot", "status"]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()
            ->fromArray(
                $rowArray,   
                NULL,        
                'A1'           
            );

        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestDataColumn()) as $col) {
            $spreadsheet->getActiveSheet()
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }
        
        $sheet->setTitle("Liste_devis");

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // In this case, we want to write the file in the public directory
        $publicDirectory = $this->get('kernel')->getProjectDir() . '/public/uploads/devis_excel/';
        try {
            if (!is_dir($publicDirectory)) {
                mkdir($publicDirectory, 0777, true);
            }
        } catch (FileException $e) {}

        // e.g /var/www/project/public/my_first_excel_symfony4.xlsx
        $excelFilepath = $publicDirectory . 'liste_devis.xlsx';

        // Create the file
        $writer->save($excelFilepath);

        // Return a text response to the browser saying that the excel was succesfully created
        return $this->file($excelFilepath, 'liste_devis.xlsx', ResponseHeaderBag::DISPOSITION_INLINE);
    }


    /**
     * @Route("/add-manuel", name="add_manuel")
     */
    public function addDevisManuel(Request $request, Session $session){
        $devis = new Achat();
        $devis->setType('devis_pro');
        $form = $this->createForm(AchatType::class, $devis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            if(!is_null($entreprise))
                $devis->setEntreprise($entreprise);

            /** @var UploadedFile $document_file */
            $uploadedFile = $request->files->get('document_file2');
            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/devis/";
            if ($uploadedFile){
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                } catch (FileException $e) {}

                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugify($originalFilename);
                $extension = $uploadedFile->guessExtension();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
                $uploadedFile->move($dir, $newFilename);
                $devis->setDocumentFile($newFilename);
            } 
            else if(!is_null($session->get('tmp_acr_devis_pro', null))){
                $f = $session->get('tmp_acr_devis_pro', null);
                $fileTab = explode('.', $f);
                $fileTab[count($fileTab)-1] = "pdf";
                $pdf = implode(".", $fileTab);

                if(!is_file($dir.$pdf)){
                    $pdf = str_replace("pdf", "PDF", $pdf);
                }

                $devis->setDocumentFile($pdf);

                // Telecharger le document PDF du projet api ocr vers le site
                $dirSave = "uploads/devis/";
                $filenamedownloaded = $pdf;
                $urlFile = $this->global_s->getBASEURL_OCRAPI().$dirSave.$filenamedownloaded;
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if(!is_file($dir.$filenamedownloaded)){
                        $this->global_s->downloadExternalFile($urlFile, $dirSave, $filenamedownloaded, "pdf");
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', "Le document n'a pas pu etre téléchargé");
                }
            }    

            $devis->setNote($request->request->get('note'));       
            $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
            $devis->setTva($tva);
            $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
            $devis->setDevise($devise);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($devis);
            $entityManager->flush();

            if($request->request->get('submit') == "validate_ia" && $request->request->get('model_document_id')){
                $document = $this->getDoctrine()->getRepository(ModelDocument::class)->find($request->request->get('model_document_id'));
            }
            else if($request->request->get('submit') == "ajuste_ia" && $request->request->get('model_document_id')){
                $document = $this->getDoctrine()->getRepository(ModelDocument::class)->find($request->request->get('model_document_id'));
                //$document->setNbrPage($request->request->get('nbr_page'));

                //suppression des positions defini precedement par l'user
                $this->getDoctrine()->getRepository(IAZone::class)->deleteIAZoneByDocument($document->getId());

                //suppression des positions de reconnaissance de facture
                $this->getDoctrine()->getRepository(OcrField::class)->deleteOcrFieldByDocument($document->getId());
            }
            else{
                $document = new ModelDocument();
                //$document->setNbrPage($request->request->get('nbr_page'));
                $entityManager->persist($document);
                $entityManager->flush();
            }
            if($request->request->get('submit') != "validate_ia"){
                $this->global_s->saveIAField($request, $form, $document);
                $this->global_s->saveOcrField($document, "devis_pro", $session->get('tmp_acr_devis_pro', null));
            }
            
            //delete last ocr tmp data
            $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("devis_pro", $session->get('tmp_acr_devis_pro', null));
            $session->set('tmp_acr_devis_pro', null);
            
            if(!is_null($session->get('email_facture_load_id', null))){
                $docPreview =  $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($session->get('email_facture_load_id', null));

                if(!is_null($docPreview)){ 
                    $entityManager->remove($docPreview);
                    $entityManager->flush();
                }
                $session->set('email_facture_load_id', null);
            } 

            return $this->redirectToRoute('devis_pro_list');
        }

        $lastOcrFile = $session->get('tmp_acr_devis_pro', null);
        $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"devis_pro", "filename"=>$lastOcrFile, 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

        $nbrPage = 0;
        if(is_null($lastOcrFile) || count($tmpOcr) == 0){
            $lastOcrFile = null;
        }

        $fieldPreference = $this->getDoctrine()->getRepository(PreferenceField::class)->findAll();

        if(count($fieldPreference) == 0){
            $this->addFlash('warning', "Veuillez configurer les champs à extraire pour cette entreprise");
        }
        
        $fieldPreferenceArr = [];
        foreach ($fieldPreference as $value) {
            $fieldPreferenceArr[$value->getIdentifiant()] = [
                'type'=>$value->getType(),
                'identifiant'=>$value->getIdentifiant(),
            ];
        }

        return $this->render('devis_pro/add_devis.html.twig', [
            'form' => $form->createView(),
            'devis_pro' => $devis,
            'status'=> array_flip($this->global_s->statusDevisPro()),
            'display_lot'=>true,
            'nbrPage' => $nbrPage,
            'fieldPreference' => $fieldPreferenceArr,
            'tmpOcr'=>$tmpOcr,
            'lastOcrFile'=>$lastOcrFile,
            'add_mode'=>'manuel',
            'fieldsIaZone'=>[],
        ]);
    }

    /**
     * @Route("/launch-ia", name="launch_ia")
     */
    public function launchIa(Request $request){
        $lastOcrFile =  $this->session->get('tmp_acr_devis_pro', "");
        
        $devis = new Achat();
        $devis->setType('devis_pro');
        
        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/devis/".$lastOcrFile;

        $datasResult = $this->global_s->lancerIa($lastOcrFile, $devis, "devis_pro", $dirLandingImg);
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('devis_pro_add_manuel');
        }

        $form = $this->createForm(AchatType::class, $datasResult['devis_pro']);
        $form->handleRequest($request);

        $datasResult['form'] = $form->createView();
        

        $score = $datasResult['score'];
        if($score < 50)
            $status = "error";
        else if($score < 80)
            $status = "warning";
        else if($score >= 80)
            $status = "success";

        $this->addFlash($status, "La reconnaissance du document est de ".$score."%");

        return $this->render('devis_pro/add_devis.html.twig', $datasResult);
    }

    /**
     * @Route("/group-text-by-position", name="group_text_by_position",  methods={"POST"})
     */
    public function groupTextByPosition(Request $request, Session $session){

        $result = $this->global_s->groupTextByPosition($request, $session->get('tmp_acr_devis_pro', null), 'devis_pro');
        return new Response(json_encode($result)); 
    }

    /**
     * @Route("/import-document/{scan}", name="import_document", methods={"POST", "GET"})
     */
    public function loadDocumentToOcr(Request $request, Session $session, $scan= null){

        $document = $request->files->get('document');
        $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/devis/";
        $documentEmail = null;
        if ($document) {
            try {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            } catch (FileException $e) {}

            if ($request->isMethod('post') && $document) {
                $originalFilename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename . date('YmdHis') . '.' . $document->guessExtension();
                $saveFile = $originalFilename . date('YmdHis') . '.jpg';
                $document->move($dir, $newFilename);
            }

        }
        else if($request->isMethod('GET')){
            $documentEmailId = $session->get('email_facture_load_id', null);

            if(!is_null($documentEmailId)){
                $documentEmail = $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($documentEmailId);
                $newFilename = $documentEmail->getDocument();

                if(!$documentEmail->getIsConvert() && !$documentEmail->getExecute()){
                    $filenameRename = uniqid().$newFilename;
                    $documentEmail->setDocument($filenameRename);
                    rename("uploads/devis/".$newFilename, "uploads/devis/".$filenameRename);
                    $newFilename = $filenameRename;

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->flush();
                    $session->set('tmp_acr_devis_pro', $newFilename);
                }

                $name_array = explode('.',$newFilename);
                $file_type=$name_array[sizeof($name_array)-1];
                $nameWithoutExt = str_replace(".".$file_type, "", $newFilename);

                $saveFile = $nameWithoutExt . '.jpg';

                if(!$documentEmail->getIsConvert()){
                    
                    try {
                        $this->global_s->convertPdfToImage2("uploads/devis/", $newFilename, $saveFile);
                        $documentEmail->setIsConvert(true);
                    } catch (Exception $e) {}
                }
                if(!$documentEmail->getExecute()){
                    try {
                        $this->global_s->saveOcrScan("uploads/devis/", $saveFile, "devis_pro", false);
                        $documentEmail->setExecute(true);
                    } catch (Exception $e) {}
                }
            }
            else{
                $this->addFlash("error", "Aucun document fournie");
                return $this->redirectToRoute("devis_pro_add_manuel");
            }
            
        }
        else{
            $this->addFlash("error", "Aucun document fournie");
            return $this->redirectToRoute("devis_pro_add_manuel");
        }

        //delete last ocr tmp data
        //$this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("devis_pro", $session->get('tmp_acr_devis_pro', null));


        $isForm = false;
        $session->set('tmp_acr_devis_pro', $saveFile);
        if( ($request->isMethod('post') && $document) || !is_null($scan) ){
            $this->global_s->convertPdfToImage2("uploads/devis/", $newFilename, $saveFile);
            $isForm = true;
            $this->global_s->saveOcrScan("uploads/devis/", $saveFile, "devis_pro", $isForm);
        }
        else{
            /*if(!$this->global_s->isDocumentConvert($saveFile)){
                $this->global_s->convertPdfToImage2("uploads/devis/", $newFilename, $saveFile);
            }
            if(!$this->global_s->isOcrSave($saveFile, "devis_pro")){
                $this->global_s->saveOcrScan("uploads/devis/", $saveFile, "devis_pro", $isForm);
            }*/
        }
        

        $devis = new Achat();
        $devis->setType('devis_pro');

        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/devis/".$saveFile;

        if(!is_null($documentEmail) && !is_null($documentEmail->getFacturedAt()) && !is_null($documentEmail->getFournisseur()) && !is_null($documentEmail->getDocumentId()) && !is_null($documentEmail->getPrixht()) && !is_null($documentEmail->getPrixttc()) && !is_null($documentEmail->getChantier())){

            $oldDate = 0;
            if(!is_null($documentEmail->getFacturedAt()) && $documentEmail->getFacturedAt()->format('Y') < (new \DateTime())->format('Y')){
                $oldDate = 1;
            }
            
            $tvaVal = 0;$color = "orange";
            $dividende = ((float)$documentEmail->getPrixht() != 0) ? $documentEmail->getPrixht() : 1 ;
            $tva = ( ((float)$documentEmail->getPrixttc() - (float)$documentEmail->getPrixht()) / $dividende )*100;
            $partE = (int)$tva;
            $partD = $tva - $partE;

            if($partE != 0){
                if($partE == 5  and ($partD <= 0.8 and $partD >= 0.2)){
                    $tvaVal = 5.5;
                    $color = "green";
                }
                elseif($partE == 20  and ($partD <= 0.3)){
                    $tvaVal = 20;
                    $color = "green";
                }
                elseif($partE == 19  and ($partD >= 0.7)){
                    $tvaVal = 20;
                    $color = "green";
                }
                elseif($partE == 10  and ($partD <= 0.3)){
                    $tvaVal = 10;
                    $color = "green";
                }
                elseif($partE == 9  and ($partD >= 0.3)){
                    $tvaVal = 10;
                    $color = "green";
                }
                else{
                    $tvaVal = $tva;
                    $color = "red";
                }
            }

            $tva = $this->getDoctrine()->getRepository(Tva::class)->find($tvaVal);
            $devis->setTva($tva);

            $devis->setFournisseur($documentEmail->getFournisseur()); 
            $devis->setChantier($documentEmail->getChantier()); 
            $devis->setDocumentId($documentEmail->getDocumentId()); 
            $devis->setFacturedAt($documentEmail->getFacturedAt()); 
            $devis->setDocumentFile($documentEmail->getDocument()); 
            $devis->setPrixht($documentEmail->getPrixht()); 
            $devis->setPrixttc($documentEmail->getPrixttc()); 
            $devis->setCodeCompta($documentEmail->getFournisseur()->getCodeCompta()); 
            $devis->setLot($documentEmail->getFournisseur()->getLot()); 

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"devis_pro", "filename"=>$session->get('tmp_acr_devis_pro', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

            $datasResult = [
                "devis_pro"=>$devis,
                "lastOcrFile" => $this->global_s->replaceExtenxionFilename($documentEmail->getDocument(), "jpg"),
                "display_lot" => true,
                "ia_launch" => true,
                "add_mode" => "manuel",
                "score" => $documentEmail->getScore(),
                "tvaVal" => $tvaVal."%",
                "oldDate" => $oldDate,
                "color" => $color,
                "nbrPage" => 0,
                "fournisseurfound" => [$documentEmail->getFournisseur()],
                "chantierfound" => [$documentEmail->getChantier()],
                "clientfound" => [$documentEmail->getClient()],
                "dossier" => "devis_pro",
                "tmpOcr"=>$tmpOcr,
                "fieldsIaZone" => []
            ];
        }
        else{
            $client = new Client([
                'base_uri' => $this->global_s->getBASEURL_OCRAPI(),
            ]);

            $response = $client->request('POST', 'ocrapi/launchia', [
                'form_params' => [
                        'dossier' => "devis_pro",
                        'document_file' => $saveFile,
                        'dir_document_file' => "/public/uploads/devis/".$saveFile,
                        'entreprise' => $this->session->get('entreprise_session_id')
                    ]
                ]);

            $datasResult = json_decode($response->getBody()->getContents(), true);

            $devis->setDocumentId($datasResult['devis_pro']['documentId']); 
            $devis->setDocumentFile($datasResult['devis_pro']['documentFile']); 
            $devis->setPrixht($datasResult['devis_pro']['prixht']); 
            $devis->setPrixttc($datasResult['devis_pro']['prixttc']); 

            if(array_key_exists('devis_pro', $datasResult) && array_key_exists("fournisseur", $datasResult['devis_pro']) && !is_null($datasResult['devis_pro']['fournisseur']) ){
                $fournisseurBl = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($datasResult['devis_pro']['fournisseur']['id']);
                $devis->setFournisseur($fournisseurBl); 
                $devis->setCodeCompta($fournisseurBl->getCodeCompta()); 
                $devis->setLot($fournisseurBl->getLot()); 
            }
            if(array_key_exists('devis_pro', $datasResult) && array_key_exists("facturedAt", $datasResult['devis_pro']) && array_key_exists("date", $datasResult['devis_pro']['facturedAt']) ){
                $devis->setFacturedAt(new \Datetime($datasResult['devis_pro']['facturedAt']['date'])); 
            }
            if(array_key_exists('devis_pro', $datasResult) && array_key_exists("chantier", $datasResult['devis_pro']) && !is_null($datasResult['devis_pro']['chantier']) ){
                $chantierBl = $this->getDoctrine()->getRepository(Chantier::class)->find($datasResult['devis_pro']['chantier']['chantierId']);
                $devis->setChantier($chantierBl); 
            }
            if($datasResult["tvaVal"] != ""){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>str_replace("%", "", $datasResult["tvaVal"])]);
                $devis->setTva($tva);
            }
            $datasResult['devis_pro'] = $devis;

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"devis_pro", "filename"=>$session->get('tmp_acr_devis_pro', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);
            $datasResult['tmpOcr'] = $tmpOcr;
        }

        //$datasResult = $this->global_s->lancerIa($saveFile, $devis, "devis_pro", $dirLandingImg);

        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('devis_pro_add_manuel');
        }

        $devis = $datasResult['devis_pro'];
        $chantier = $devis->getChantier() ?? null;

        $form = $this->createForm(AchatType::class, $datasResult['devis_pro'], array('chantier' =>$chantier));
        $form->handleRequest($request);

        $datasResult['form'] = $form->createView();
        $datasResult['document_pdf'] = $newFilename;


        $score = $datasResult['score'];
        if($score < 50)
            $status = "error";
        else if($score < 80)
            $status = "warning";
        else if($score >= 80)
            $status = "success";

        $this->addFlash($status, "La reconnaissance du document est de ".$score."%");

        if(!is_null($documentEmail)){
            $documentEmail->setScore($score);
            $documentEmail->setLu(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($documentEmail);
            $entityManager->flush();     
        }

        return $this->render('devis_pro/add_devis.html.twig', $datasResult);
            
        
    }


    /**
     * @Route("/delete-tmp-ocr", name="delete_tmp_ocr")
     */
    public function deleteTmpOcr(Session $session){
        $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("devis_pro", $session->get('tmp_acr_devis_pro', null));
        $session->set('tmp_acr_devis_pro', null);
           
        return $this->redirectToRoute('devis_pro_add_manuel');
    }

    /**
     * @Route("/post-caution", name="post_caution", methods={"POST"})
     */
    public function postCaution(Request $request){
        $document = $request->files->get('caution');
        $devisId = $request->request->get('devis_id');
        if ($document) {
            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/devis/caution/";
            try {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            } catch (FileException $e) {}

            $originalFilename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = $originalFilename . date('YmdHis') . '.' . $document->guessExtension();
            $document->move($dir, $newFilename);

            $devis = $this->achatRepository->find($devisId);
            $devis->setCaution($newFilename);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
        } 

        return $this->redirectToRoute('devis_pro_list');
    }

    /**
     * @Route("/send-bl", name="send_bl")
     */
    public function sendBl(Request $request, MailerInterface $mailer)
    {
        $error = null; $customMailer = null;
        try{
            $customMailer = $this->global_s->initEmailTransport();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if(!is_null($customMailer) && is_null($error)){

            $devis_pros = $this->getDoctrine()->getRepository(Achat::class)->findByTabId(explode('-', $request->request->get('list-devis-id')), 'devis_pro');
            if ($request->isMethod('POST')) {

                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
                $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();

                $is_send = 0;
                $this->get('session')->getFlashBag()->clear();
                if(filter_var($request->request->get('email_comptable'), FILTER_VALIDATE_EMAIL)){
                    foreach ($devis_pros as $value) {
                        if(!is_null($value['document_file'])){
                            $is_send = 1;
                            $email = (new Email())
                            ->from(new Address($sender_email, $sender_name))
                            ->to($request->request->get('email_comptable'))
                            ->subject('Devis')
                            ->html($request->request->get('content'))
                            ->attachFromPath('uploads/devis/'.$value['document_file']);
                            
                            try {
                                $send = $customMailer->send($email);
                            } catch (\Exception $ex) { $error = $ex->getMessage(); }

                        }
                    }
                    if($is_send){
                        $this->addFlash('success', "Devis envoyé avec success");
                    }
                    else{
                        $this->addFlash('error', "Aucun devis trouvé");
                    }
                    
                }
                else
                    $this->addFlash('error', "Veuillez verifier l'email fourni");
            }
        }
        else{
            if(!is_null($error))
                $this->addFlash('error', $error);
            else
                $this->addFlash('error', "Veuillez configurer les informations d'envoie de mail de cette entreprise");
        }
        return $this->redirectToRoute('devis_pro_list');
    }

    /**
     * @Route("/update-status", name="update_status")
     */
    public function updateStatus(Request $request){
        $devis = $this->achatRepository->find($request->query->get('devisId'));
        $status = $this->getDoctrine()->getRepository(Status::class)->find($request->query->get('statusId'));
        $devis->setStatus($status);
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush(); 
        return new Response(json_encode(['status'=>200]));
    }

    /**
     * @Route("/add", name="add")
     */
    public function new(Request $request)
    {   
        $devis_pro = new Achat();

        $form = $this->createForm(AchatType::class, $devis_pro);

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $devis_pro->setEntreprise($entreprise);

                $devis_pro->setType('devis_pro');
                if(!is_null($devis_pro->getTva()))
                    $devis_pro->setMtva($devis_pro->getTva()->getValeur());
                $entityManager = $this->getDoctrine()->getManager();

                $devis_pro->setNote($request->request->get('note'));
                $entityManager->persist($devis_pro);    
                $entityManager->flush();                

                $this->deleteDocument($devis_pro->getRossumDocumentId());
                return $this->redirectToRoute('devis_pro_list');
            }
        }

        $annotations = [];
        $rossumDossierId = $this->global_s->getQueue('devis_fournisseur');
        if(empty($rossumDossierId)){
            $this->addFlash('error', "Cette entreprise n'a pas d'identifiant pour ce dossier");
            return $this->render('devis_pro/new.html.twig', [
                'form' => $form->createView(),
                'devis_pro' => $devis_pro,
                'status'=> array_flip($this->global_s->statusDevisPro()),
                'rossum_documents'=>[],
                "fieldsIaZone"=>[]
            ]);
        }
        $url = $this->global_s->constructUrl($rossumDossierId);
        while ($url) {
            $response = $this->global_s->makeRequest($url);
            $annotations = array_merge($annotations, $response->results);
            $url = $response->pagination->next;
        }

        $rossum_documents = [];
        if(!empty($annotations)){
            $rossum_documents = $this->global_s->listRossumDoc($annotations, $this->achatRepository->getAllBl('devis_pro'));            
        }
        else
            $this->addFlash('error', "Aucune devis completed");

        return $this->render('devis_pro/new.html.twig', [
            'form' => $form->createView(),
            'devis_pro' => $devis_pro,
            'rossum_documents'=>$rossum_documents,
            "fieldsIaZone"=>[]
        ]);
    }
    
    /**
     * @Route("/{rossum_document_id}/delete-doublon", name="delete_rossum_document_id")
     */
    public function deleteDocumentDuplique(Request $request, $rossum_document_id)
    {
        $this->deleteDocument($rossum_document_id);
        return $this->redirectToRoute('devis_pro_add');
    }

    public function deleteDocument($rossum_document_id){
        $this->global_s->deleteDocument($rossum_document_id);
        return 1;
    }

    /**
     * @Route("/{devis_proId}/edit", name="edit")
     */
    public function edit(Request $request, $devis_proId)
    {
        $devis_pro = $this->getDoctrine()->getRepository(Achat::class)->find($devis_proId);

        $form = $this->createForm(AchatType::class, $devis_pro);
        $form->handleRequest($request);

        $is_document_change = false;
        if ($form->isSubmitted() && $form->isValid()) {

            //$valttc = $devis_pro->getPrixht() + ( ($devis_pro->getTva()->getValeur() * $devis_pro->getPrixht()) / 100);
            //$devis_pro->setPrixttc($valttc); 

            /** @var UploadedFile $document_file */
            $uploadedFile = $request->files->get('document_file2');
            if ($uploadedFile){
                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/devis/";
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                } catch (FileException $e) {}

                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugify($originalFilename);
                $extension = $uploadedFile->guessExtension();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
                $uploadedFile->move($dir, $newFilename);

                if($devis_pro->getDocumentFile() != $newFilename){
                    $is_document_change = true;
                }

                $devis_pro->setDocumentFile($newFilename);
            }

            $devis_pro->setNote($request->request->get('note'));
            if(!is_null($devis_pro->getTva()))
                $devis_pro->setMtva($devis_pro->getTva()->getValeur());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            if($is_document_change){
                return $this->render('devis_pro/edit.html.twig', [
                    'form' => $form->createView(),
                    'devis_pro' => $devis_pro,
                    'status'=> array_flip($this->global_s->statusDevisPro()),
                    'display_lot'=>true,
                    'is_document_change' => true,
                    'fieldsIaZone'=>[],
                ]);
            }
            return $this->redirectToRoute('devis_pro_list');
        }

        return $this->render('devis_pro/edit.html.twig', [
            'form' => $form->createView(),
            'devis_pro' => $devis_pro,
            'status'=> array_flip($this->global_s->statusDevisPro()),
            'display_lot'=>true,
            'fieldsIaZone'=>[],
        ]);
    }    

    /**
     * @Route("/{devis_proId}/delete", name="delete")
     */
    public function delete($devis_proId)
    {
        $devis_pro = $this->getDoctrine()->getRepository(Achat::class)->find($devis_proId);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($devis_pro);
        $entityManager->flush();

        $this->get('session')->getFlashBag()->clear();
        $this->addFlash('success', "Suppression effectuée avec succès");
        return $this->redirectToRoute('devis_pro_list');
    }    

    /**
     * @Route("/checking-data-export", name="checking_data_export")
     */
    public function checkExport(Request $request)
    {
        $url = $this->global_s->constructUrl($this->global_s->getQueue('devis_fournisseur'), $request->request->get('document_id'));
        $response = $this->global_s->makeRequest($url);
        $annotations = $response->results[0];
        $devis_pro = new Achat();
        $url_document = explode('/', $annotations->url);
        $devis_pro->setRossumDocumentId(end($url_document));
        $devis_pro->setDocumentFile($this->global_s->retrieveDocFile($annotations->document->file, $annotations->document->file_name, 'uploads/devis/'));
        
        $fournisseurfound = [];
        foreach ($annotations->content as $content){
            foreach ($content->children as $children) {
                if( ($children->schema_id == "chantier") && !empty($children->value)) {
                    $chantierfound = $this->global_s->findByNameAlpn($children->value, "chantier");
                    if(count($chantierfound) == 0){
                        $datas = ['status'=>500, 'issue'=>'chantier', 'sender_name'=>$children->value, "message"=>"Le chantier ".$children->value." n'existe pas"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                    else{
                        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierfound[0]['id']);
                        $devis_pro->setChantier($chantier);
                    }
                    
                }
                if($children->schema_id == "lots"){
                    $lot = $this->getDoctrine()->getRepository(Lot::class)->findOneBy(["lot"=>$children->value]);
                    if(!is_null($lot))
                        $devis_pro->setLot($lot);
                }
                if($children->schema_id == "sender_name"){
                    $fournisseurfound = $this->global_s->findByNameAlpn($children->value, "fournisseur");
                    if(count($fournisseurfound) == 0){
                        $datas = ['status'=>500, 'issue'=>'fournisseur', 'sender_name'=>$children->value, "message"=>"Le fournisseur ".$children->value." n'existe pas"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                    else{
                        $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurfound[0]['id']);
                        $devis_pro->setFournisseur($fournisseur);
                        if(!is_null($fournisseur)){
                            $lot = $fournisseur->getLot();
                            if(!is_null($lot))
                                $devis_pro->setLot($lot);

                            $devis_pro->setCodeCompta($fournisseur->getCodeCompta());
                        }
                    }
                }
                if( ($children->schema_id == "amount_total_tax") && !empty($children->value)){
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>(float)$children->value]);
                    if(!is_null($tva))
                        $devis_pro->setTva($tva);
                }
                if($children->schema_id == "currency"){
                    $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>$children->value]);
                    if(!is_null($devise))
                        $devis_pro->setDevise($devise);
                }
                if($children->schema_id == "document_id"){
                    $devis_pro->setDocumentId($children->value);
                }
                if( ($children->schema_id == "amount_total_base") && !empty($children->value)){
                    $devis_pro->setPrixht((float)$children->value);
                }
                if( ($children->schema_id == "amount_total") && !empty($children->value) ){
                    $devis_pro->setPrixttc((float)$children->value);
                }
                if($children->schema_id == "date_issue"){
                    $devis_pro->setFacturedAt(new \Datetime($children->value)); 
                }
            }
        }
        $devisExiste = "";
        if( !is_null($devis_pro->getFournisseur()) && !is_null($devis_pro->getFacturedAt()) && !is_null($devis_pro->getPrixht()) ){
            $blExist = $this->achatRepository->findByFFTH($devis_pro->getFournisseur()->getId(), $devis_pro->getFacturedAt(), $devis_pro->getPrixht(), 'devis_pro');
            if($blExist){
                //$datas = ['status'=>500, 'issue'=>'doublon', "message"=>"Une devis similaire existe déjà."];
                //$response = new Response(json_encode($datas));
                //return $response;
                $devisExiste = $blExist['document_file'];
            }
        }

        if(is_null($devis_pro->getTva())){
            $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
            $devis_pro->setTva($tva);
        }
        if(is_null($devis_pro->getDevise())){
            $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
            $devis_pro->setDevise($devise);
        }
        if(is_null($devis_pro->getFacturedAt())){
            $devis_pro->setFacturedAt(new \Datetime());
        }
        if(is_null($devis_pro->getPrixttc()) || $devis_pro->getPrixht()){
            if(is_null($devis_pro->getPrixttc()) && !is_null($devis_pro->getPrixht())){
                $ttc = $devis_pro->getPrixht() + ($devis_pro->getPrixht()*($devis_pro->getTva()->getValeur()/100));
                $devis_pro->setPrixttc($ttc);
            }
            elseif(!is_null($devis_pro->getPrixttc()) && is_null($devis_pro->getPrixht())){
                $ht = $devis_pro->getPrixttc() / (1+ ($devis_pro->getTva()->getValeur()/100));
                $devis_pro->setPrixttc($ht);
            }
        }

        $form = $this->createForm(AchatType::class, $devis_pro);
        $form->handleRequest($request);

        /*
        $tvaVal = $this->global_s->calculTva($devis_pro->getPrixttc(), $devis_pro->getPrixht());
        $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>$tvaVal]);
        if(!is_null($tva))
            $devis_pro->setTva($tva);*/
        $datas = ['status'=>200, "message"=>"", "count_fournisseur"=>count($fournisseurfound)];
        $datas['preview'] = $this->renderView('devis_pro/preview_export.html.twig', [
                'form' => $form->createView(),
                'devis_pro' => $devis_pro, 
                'annotations'=>$annotations,
                'fournisseurfound'=>$fournisseurfound,
                'devisExiste'=> $devisExiste,
                'display_lot'=>true,
                "fieldsIaZone"=>[]
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    public function findByNameAlpn($fournisseurName){
        $fournisseurArr = [];
        $fournisseurfound = $this->fournisseurRepository->findByNameAlpn($fournisseurName);
        if(count($fournisseurfound) > 0){
            foreach ($fournisseurfound as $value) {
                $fournisseurArr[] = $value;
            }
        }
        else{
            //$fournisseurNameAlpn = str_replace("  ", " ", preg_replace("/[^a-zA-Z0-9]+/", " ", $fournisseurName));
            $fournisseurNameAlpn = $fournisseurName;
            $fournisseurTmpArr = explode(" ", $fournisseurNameAlpn);
            $fournisseurTmpArrCpy = $fournisseurTmpArr;
            while (count($fournisseurTmpArr) > 0) {
                if(strlen(implode(" ", $fournisseurTmpArr)) < 4 ){
                    array_pop($fournisseurTmpArr);
                    continue;
                }

                $fournisseurfound = $this->fournisseurRepository->findByNameAlpn(implode(" ", $fournisseurTmpArr));
                if(count($fournisseurfound) > 0){
                    foreach ($fournisseurfound as $value){
                        $fournisseurArr[] = $value;
                    }
                    //break;
                }
                array_pop($fournisseurTmpArr);
            }   
            if(count($fournisseurArr) == 0){
                foreach ($fournisseurTmpArrCpy as $value) {
                    if(strlen($value) < 4 )
                        continue;
                    
                    $fournisseurfound = $this->fournisseurRepository->findByNameAlpn($value);
                    if(count($fournisseurfound) > 0){
                        foreach ($fournisseurfound as $value){
                            $fournisseurArr[] = $value;
                        }
                        //break;
                    }
                }
            }        
        }
        return $fournisseurArr;
    }

    /**
     * @Route("/fournisseur-add", name="fournisseur_add")
     */
    public function addFournisseur(Request $request){
        $em = $this->getDoctrine()->getManager();
        $fournisseur = new Fournisseurs();
        $fournisseur->setDatecrea(new \DateTime());
        $fournisseur->setDatemaj(new \DateTime());
        $form = $this->createForm(FournisseursType::class, $fournisseur);

        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('POST')) {
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager = $this->getDoctrine()->getManager();

                    $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                    if(!is_null($entreprise))
                        $fournisseur->setEntreprise($entreprise);
                
                    $entityManager->persist($fournisseur); 
                    $entityManager->flush();                

                    $datas = ['status'=>200, 'message'=>"Opération éffectuée avec succès", "fournisseur"=>[
                        'id'=>$fournisseur->getId(),
                        'nom'=>$fournisseur->getNom(),
                    ]];
                }
                else
                    $datas = ['status'=>500, 'message'=>"Erreur creation fournisseur"];
            }
            else{
                $datas = ['status'=>200, "message"=>""];
                $datas['form'] = $this->renderView('devis_pro/modal_fournisseur_add.html.twig', [
                        'form' => $form->createView(),
                        'fournisseur' => $fournisseur,
                        'url'=> $this->generateUrl('devis_pro_fournisseur_add', [], UrlGenerator::ABSOLUTE_URL)
                    ]);
            }
            $response = new Response(json_encode($datas));
            return $response;
        }
        return  new Response("Attendez la fin du chargement de la page...");
    }


    /**
     * @Route("/get-by-chantier", name="get_by_chantier")
     */
    public function getAchatByChantier(Request $request){

        $type = $request->request->get('type');
        $tabChantierId = explode('-', $request->request->get('list_chantier_id'));
        $eltSelect = $request->request->get('facture_select');
        $fournisseursSelect = $request->request->get('fournisseurs_select');

        $devis = $this->achatRepository->getAchatByChantier($tabChantierId, $type, explode('-', $fournisseursSelect));

        $devisTarget = $this->achatRepository->find($eltSelect);

        $lot = $this->achatRepository->getLotVente(explode('-', $eltSelect), explode('-', $fournisseursSelect));

        $priceTarget = (is_null($devisTarget)) ? 0 : $devisTarget->getPrixht();
        $devis = $this->global_s->sortVenteByLot($devis, $lot, $priceTarget);
        $datas = ['status'=>200, "message"=>""];
        

        $tagLink = $request->request->get('tagLink');
        if($type == 'devis_pro'){
            $datas['content'] = $this->renderView('devis_pro/modal_devis_fournisseur.html.twig', [
                    'devis' => $devis,
                    'page'=> $request->request->get('page'),
                    'facture_select'=> !empty($eltSelect) ? $eltSelect : "",
                    'tagLink'=>$tagLink,
                    'url'=> $this->generateUrl('devis_client_devis_attach', [], UrlGenerator::ABSOLUTE_URL)
                ]);
        }
        $response = new Response(json_encode($datas));
        return $response;        
    }

}
