<?php

namespace App\Controller;

use Doctrine\ORM\EntityRepository;
use App\Entity\Paie;
use App\Entity\Utilisateur;
use App\Form\PaieType;
use App\Repository\PaieRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Entreprise;
use App\Entity\EmailDocumentPreview;
use App\Entity\Horaire;
use App\Entity\PreferenceField;
use App\Entity\IAZone;
use App\Entity\OcrField;
use App\Entity\ModelDocument;
use App\Entity\TmpOcr;
use App\Repository\EntrepriseRepository;
use App\Service\GlobalService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mime\Address;
use GuzzleHttp\Client as GuzzleHttpClient;

/**
 * @Route("/paie", name="paie_")
 */
class PaieController extends AbstractController
{
    private $global_s;
    private $paieRepository;
    private $utilisateurRepository;
    private $session;
    private $params;

    public function __construct(ParameterBagInterface $params, GlobalService $global_s, PaieRepository $paieRepository, UtilisateurRepository $utilisateurRepository, SessionInterface $session){
        $this->global_s = $global_s;
        $this->paieRepository = $paieRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->session = $session;
        $this->params = $params;
    }

    /**
     * @Route("/get-user-tx/{paidId}", name="get_user_tx", methods={"GET"})
     */
    public function getUserTx($paidId){
        $paie = $this->paieRepository->find($paidId);
        $annee = explode(" ", $paie->getDatePaie())[1];
        $listPaie = $this->paieRepository->getUserTxYear($paie->getUtilisateur()->getUid(), $annee);

        $datas = ['status'=>200, 'datas'=>$listPaie];
        $response = new Response(json_encode($datas));
        return $response; 
    }

    /**
     * @Route("/get-user-tx-moyen/{paidId}", name="get_user_tx_moyen", methods={"GET"})
     */
    public function getUserTxMoyen($paidId){
        $paie = $this->paieRepository->find($paidId);
        $annee = explode(" ", $paie->getDatePaie())[1];
        $listPaie = $this->paieRepository->getUserTxMoyenYear($paie->getUtilisateur()->getUid());

        usort($listPaie, function($a, $b) {
            return $a['id'] - $b['id'];
        });
        $avg = $this->getAVGtxMoyen($paie->getUtilisateur());

        $datas = ['status'=>200, 'datas'=>$listPaie, 'avg'=>round($avg['avg'],2)];
        $response = new Response(json_encode($datas));
        return $response; 
    }

    public function getAVGtxMoyen($user){
        return $this->paieRepository->getUserAVGTxMoyenYear($user->getUid());
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, PaieRepository $paieRepository, Session $session): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        
        // $paies = $this->paieRepository->findAll();
        // foreach ($paies as $value) {

        //     // $datePaie2 = $this->buildDateByDateTextFr($value->getDatePaie());
        //     // $value->setDatePaie2($datePaie2);
        //     if($value->getDatePaie2()){
        //         $recapUserTxMoyenYear = $this->paieRepository->recapUserTxMoyenYear2($value->getUtilisateur()->getUid(), $value->getDatePaie2());
        //         $value->setTxMoyen($recapUserTxMoyenYear['avg']);
                
        //     }

        // }

        // $entityManager = $this->getDoctrine()->getManager();
        // $entityManager->flush();
        // die();

        $form = $request->request->get('form', null);
        $moisList =array_flip($this->global_s->getMois());
        $utilisateurId = null;

        if ($form) {
            $mois = (!(int)$form['mois']) ? null : $this->global_s->stripAccents(strtolower((int)$form['mois']));
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $monthText = !is_null($mois) ? $moisList[$mois] : "";
            $utilisateurId = (!(int)$form['utilisateur']) ? null : (int)$form['utilisateur'];

            $paies = $paieRepository->getByDate($monthText, $annee, $utilisateurId);

            $session->set('mois_paie', $mois);
            $session->set('annee_paie', $annee);
            $session->set('utilisateur_paie', $utilisateurId);
        } else {
            $day = date('Y-m-d',strtotime('-1 month',strtotime((new \DateTime())->format('Y-m-d'))));
            $day = explode('-', $day);
            $mois = (int)$session->get('mois_paie', $day[1]);
            $annee = $session->get('annee_paie', $day[0]);
            $monthText = $mois ? $moisList[$mois] : "";
            $utilisateurId = $session->get('utilisateur_paie', null);

            $paies = $paieRepository->getByDate($monthText, $annee, $utilisateurId);
        }
        $sumCoutGlobal = $this->paieRepository->sumCoutGloble($monthText, $annee, $utilisateurId);
        
        $tabHoraire = [];
        $moisList2 = $this->global_s->getMoisFull();

        foreach ($paies as $value) {
            $mois2 = $moisList2[explode(' ', $this->global_s->stripAccents($value->getDatePaie()))[0]];
            $annee2 = explode(' ', $value->getDatePaie())[1];
            $tabHoraire[$value->getId()] = $this->getDoctrine()->getRepository(Horaire::class)->getGeneraleTime($value->getUtilisateur()->getUid(), $mois2, $annee2);
        }

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'utilisateur' => (!is_null($utilisateurId)) ?  $this->utilisateurRepository->find($utilisateurId) : ""
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
        ->add('utilisateur', EntityType::class, array(
            'class' => Utilisateur::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('u')
                ->where('u.entreprise = :entreprise_id')
                ->andWhere('u.etat = 1')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('u.lastname', 'ASC');
            },
            'required' => false,
            'label' => "Utilisateur",
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $full_month = $annee;
        if($mois && !empty($mois) && $annee)
            $full_month = Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY');

        $countDocAttentes = $entityManager->getRepository(EmailDocumentPreview::class)->countByDossier('paie');

        return $this->render('paie/index.html.twig', [
            'paies' => $paies,
            'full_month'=> $full_month,
            'sumCoutGlobal'=> $sumCoutGlobal,
            'countDocAttentes'=>$countDocAttentes,
            'form' => $form->createView(),
            'tabHoraire'=>$tabHoraire
        ]);
    }

    public function buildDateByDateTextFr($date){
        if($date){
            $moisList = $this->global_s->getMoisFull();
            $mois = $moisList[explode(' ', $this->global_s->stripAccents($date))[0]];
            $annee = explode(' ', $date)[1];

            return new \DateTime($annee.'-'.$mois.'-28');
        }
        return null;
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request, PaieRepository $paieRepository): Response
    {
        $this->global_s->getLoginToken();
        $paie = new Paie();
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            if(!is_null($entreprise))
                $paie->setEntreprise($entreprise);

            $datePaie = $form->get('date_paie')->getData();
            if($datePaie){
                $moisList = $this->global_s->getMoisFull();
                $mois = $moisList[explode(' ', $this->global_s->stripAccents($datePaie))[0]];
                $annee = explode(' ', $datePaie)[1];
                $heureFictif = $this->getDoctrine()->getRepository(Horaire::class)->getGeneraleHourFictif($form->get('utilisateur')->getData()->getUid(), $mois, $annee);  
                if($heureFictif)
                    $paie->setHeureFictif($heureFictif);

                if($heureFictif!= 0){
                    $paie->setTxHoraire(((float)$form->get('cout_global')->getData())/$heureFictif);
                }
                else{
                    $paie->setTxHoraire(0);
                }

                if($paie->getDatePaie()){
                    $txMoyenAvg = $this->paieRepository->getUserAVGTxMoyenYear($paie->getUtilisateur()->getUid());
                    $paie->setTxMoyen($txMoyenAvg['avg']);
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($paie);
            $entityManager->flush();

            $this->deleteDocument($paie->getRossumDocumentId());
            return $this->redirectToRoute('paie_list');
        }

        $annotations = [];
        $rossumDossierId = $this->global_s->getQueue('paie');
        if(empty($rossumDossierId)){
            $this->addFlash('error', "Cette entreprise n'a pas d'identifiant pour ce dossier");
                return $this->render('paie/new.html.twig', [
                'paie' => $paie,
                'form' => $form->createView(),
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
            $rossum_documents = $this->global_s->listRossumDoc($annotations, $this->paieRepository->getAllPaie());      
        }
        else
            $this->addFlash('error', "Aucune fiche de paie completed");

        return $this->render('paie/new.html.twig', [
            'paie' => $paie,
            'form' => $form->createView(),
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
        return $this->redirectToRoute('bon_livraison_add');
    }

    public function deleteDocument($rossum_document_id){
        $this->global_s->deleteDocument($rossum_document_id);
        return 1;
    }

    /**
     * @Route("/checking-data-export", name="checking_data_export")
     */
    public function checkExport(Request $request)
    {
        $url = $this->global_s->constructUrl($this->global_s->getQueue('paie'), $request->request->get('document_id'));
        $response = $this->global_s->makeRequest($url);
        $annotations = $response->results[0];
        $fichePaie = new Paie();
        $url_document = explode('/', $annotations->url);
        $fichePaie->setRossumDocumentId(end($url_document));
        $fichePaie->setDocumentFile($this->global_s->retrieveDocFile($annotations->document->file, $annotations->document->file_name));
        
        foreach ($annotations->content as $content){
            foreach ($content->children as $children) {
                if( ($children->schema_id == "conges_paye") && !empty($children->value)){
                    $fichePaie->setCongesPaye($children->value);
                }
                if($children->schema_id == "h25" && !empty($children->value)){
                    $fichePaie->setHeureSup1($children->value);
                }
                if($children->schema_id == "h50" && !empty($children->value)){
                    $fichePaie->setHeureSup2($children->value);
                }
                if($children->schema_id == "hnormal" && !empty($children->value)){
                    $fichePaie->setHeureNormale($children->value);
                }
                if($children->schema_id == "panier_repas" && !empty($children->value)){
                    $fichePaie->setPanier($children->value);
                }
                if($children->schema_id == "trajet" && !empty($children->value)){
                    $fichePaie->setTrajet($children->value);
                }
                if($children->schema_id == "cout_global" && !empty($children->value)){
                    $fichePaie->setCoutGlobal($children->value);
                }
                if($children->schema_id == "salaire_net" && !empty($children->value)){
                    $fichePaie->setSalaireNet($children->value);
                }
                if($children->schema_id == "date_month" && !empty($children->value)){
                    $fichePaie->setDatePaie($children->value);

                    $datePaie2 = $this->buildDateByDateTextFr($children->value);
                    $fichePaie->setDatePaie2($datePaie2);
                }
                if($children->schema_id == "sender_name"){
                    $sender_name = strtolower($children->value);
                    $sender_name = str_replace("monsieur ", "", $sender_name);
                    $sender_name = str_replace("madame ", "", $sender_name);

                    $utilisateur = $this->utilisateurRepository->getOneLikeName($sender_name);
                    //dd($utilisateur);
                    if(!is_null($utilisateur))
                        $fichePaie->setUtilisateur($utilisateur);
                    else{
                        $datas = ['status'=>500, 'issue'=>'utilisateur', 'sender_name'=>$children->value, "message"=>"L'utilisateur ".$children->value." n'existe pas"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                }
            }
        }

        if(is_null($fichePaie->getDatePaie())){
            $fichePaie->setDatePaie("");
        }

        if(!is_null($fichePaie->getUtilisateur())){
            $em = $this->getDoctrine()->getManager();

            $moisList = $this->global_s->getMoisFull();
            $mois = $moisList[explode(' ', $this->global_s->stripAccents($fichePaie->getDatePaie()))[0]];
            $annee = explode(' ', $fichePaie->getDatePaie())[1];
            $heureFictif = $this->getDoctrine()->getRepository(Horaire::class)->getGeneraleHourFictif($fichePaie->getUtilisateur()->getUid(), $mois, $annee);  
            if($heureFictif){
                $fichePaie->setHeureFictif($heureFictif);
            }

            if($heureFictif!= 0){
                $fichePaie->setTxHoraire(((float)$fichePaie->getCoutGlobal())/$heureFictif);
            }
            else{
                $fichePaie->setTxHoraire(0);
            }
        }
        
        $form = $this->createForm(PaieType::class, $fichePaie);
        $form->handleRequest($request);

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('paie/preview_export.html.twig', [
                'form' => $form->createView(),
                'fichePaie' => $fichePaie, 
                'annotations'=>$annotations,
                "fieldsIaZone"=>[]
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    public function listRossumDoc($annotations){

        $paie = [];
        if($this->paieRepository->getAllPaie()){
            $fichePaies = $this->paieRepository->getAllPaie();
            foreach ($fichePaies as $value) {
                $paie[] = $value['rossum_document_id'];
            }
        }
        $annotationsArr = [];
        foreach ($annotations as $annotation){
            $data = ['document_id'=>"", 'customer'=>"", "status"=>""];
            $url_document = explode('/', $annotation->url);
            $data['document_id'] = end($url_document);

            /* ne lister ques les documents pas encore exporté en BD */
            if(in_array($data['document_id'], $paie))
                continue;

            $data['status'] = $annotation->status;
            foreach ($annotation->content as $content){
                foreach ($content->children as $children) {
                    if($children->schema_id == "sender_name"){
                        $data['customer'] = $children->value;
                        break;
                    }
                }
                if (!empty($data['customer']))
                    break;
            }
            $annotationsArr[] = $data;
        }
        return $annotationsArr;
    }

    public function getUserMonthWork($userId, $mois, $annee){
        $tabMonths = $this->paieRepository->getUserMonthWork($userId, $mois, $annee);
        $tabMonthsFull = [];
        $moisList = array_flip($this->global_s->getMois());
        foreach ($tabMonths as $value) {
            $tabMonthsFull[] = $moisList[(int)$value['mois']];
        }
        return ['mois'=>$tabMonths, 'fullMois'=>$tabMonthsFull];
    }

    public function calculUserTxMoy($tabMonths, $userId){
        $annee = (new \DateTime())->format('Y');
        $tabMonthSearch = [];
        foreach ($tabMonths as $value) {
            $tabMonthSearch[] = $value." ".$annee;
        }

        $sumTxMoy = $this->getDoctrine()->getRepository(Paie::class)->getTxMoyByTabMonth($tabMonthSearch, $userId);
        return ($sumTxMoy / count($tabMonthSearch));
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Paie $paie): Response
    {
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $datePaie = $form->get('date_paie')->getData();
            if($datePaie){
                if($paie->getHeureFictif() != 0){
                    $paie->setTxHoraire(((float)$form->get('cout_global')->getData())/$paie->getHeureFictif());
                }
                else{
                    $paie->setTxHoraire(0);
                }

                $datePaie2 = $this->buildDateByDateTextFr($paie->getDatePaie());
                $paie->setDatePaie2($datePaie2);
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('paie_list');
        }

        return $this->render('paie/edit.html.twig', [
            'paie' => $paie,
            'form' => $form->createView(),
            'fieldsIaZone'=>[]
        ]);
    }
    /**
     * @Route("/send-fiche-paie", name="send_fiche_paie")
     */
    public function sendFichePaie(Request $request, MailerInterface $mailer)
    {
        $error = null; $customMailer = null;
        try{
            $customMailer = $this->global_s->initEmailTransport();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if(!is_null($customMailer) && is_null($error)){
            
            $fichePaie = $this->getDoctrine()->getRepository(Paie::class)->findByTabId(explode('-', $request->request->get('list-fiche-id')));
            if ($request->isMethod('POST')) {
                $is_send = 0;
                foreach ($fichePaie as $value) {
                    if(filter_var($value['email'], FILTER_VALIDATE_EMAIL) && !is_null($value['document_file'])){
                        $is_send = 1;

                        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                        $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
                        $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();


                        $email = (new Email())
                            ->from(new Address($sender_email, $sender_name))
                            ->to($value['email'])
                            ->subject('Fiche de paie')
                            ->html('Bonjour '.$value['firstname'].' '.$value['lastname'].', Vous trouverez ci-joint votre fiche de paie.')
                            ->attachFromPath('uploads/paies/'.$value['document_file']);
                        
                            try {
                                $send = $customMailer->send($email);
                            } catch (\Exception $ex) { $error = $ex->getMessage(); }

                    }
                }
                if($is_send){
                    $this->get('session')->getFlashBag()->clear();
                    $this->addFlash('success', "Fiche envoyé avec success");
                }
            }

        }
        else{
            if(!is_null($error))
                $this->addFlash('error', $error);
            else
                $this->addFlash('error', "Veuillez configurer les informations d'envoie de mail de cette entreprise");
        }
        
        return $this->redirectToRoute('paie_list');
    }

    /**
     * @Route("/{id}", name="delete", methods={"DELETE"})
     */
    public function delete(Request $request, Paie $paie): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paie->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($paie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('paie_list');
    }




    /**
     * @Route("/import-document/{scan}", name="import_document", methods={"POST", "GET"})
     */
    public function loadDocumentToOcr(Request $request, Session $session, $scan= null){

        $document = $request->files->get('document');
        $dir = $this->params->get('kernel.project_dir') . "/public/uploads/paies/";
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

                $name_array = explode('.',$newFilename);
                $file_type=$name_array[sizeof($name_array)-1];
                $nameWithoutExt = str_replace(".".$file_type, "", $newFilename);

                $saveFile = $nameWithoutExt . '.jpg';
            }
            else{
                $this->addFlash("error", "Aucun document fournie");
                return $this->redirectToRoute("paie_add_manuel");
            }
            
        }
        else{
            $this->addFlash("error", "Aucun document fournie");
            return $this->redirectToRoute("paie_add_manuel");
        }


        //delete last ocr tmp data
        //$this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("paie", $session->get('tmp_ocr_paie', null));

        $isForm = false;
        $session->set('tmp_ocr_paie', $saveFile);
        if(($request->isMethod('post') && $document) || !is_null($scan)){
            $isForm = true;
            $this->global_s->convertPdfToImage2("uploads/paies/", $newFilename, $saveFile);
            $this->global_s->saveOcrScan("uploads/paies/", $saveFile, "paie", $isForm);
        }
        else{
            /*if(!$this->global_s->isDocumentConvert($saveFile)){
                $this->global_s->convertPdfToImage2("uploads/paies/", $newFilename, $saveFile);
            }

            if(!$this->global_s->isOcrSave($saveFile, "paie")){
                $this->global_s->saveOcrScan("uploads/paies/", $saveFile, "paie", $isForm);
            }*/
        }

        $paie = new Paie();

        $dirLandingImg = $this->params->get('kernel.project_dir') . "/public/uploads/paies/".$saveFile;

        $client = new GuzzleHttpClient([
            'base_uri' => $this->global_s->getBASEURL_OCRAPI(),
        ]);

        $response = $client->request('POST', 'ocrapi/launchia', [
            'form_params' => [
                    'dossier' => "paie",
                    'document_file' => $saveFile,
                    'dir_document_file' => "/public/uploads/paies/".$saveFile,
                    'entreprise' => $this->session->get('entreprise_session_id')
                ]
            ]);

        $datasResult = json_decode($response->getBody()->getContents(), true);

        $paie->setDocumentFile($datasResult['paie']['document_file']); 
        $paie->setDatePaie($datasResult['paie']['date_paie']); 
        $paie->setCongesPaye($datasResult['paie']['conges_paye']); 
        $paie->setHeureSup1($datasResult['paie']['heure_sup_1']); 
        $paie->setHeureSup2($datasResult['paie']['heure_sup_2']); 
        $paie->setHeureNormale($datasResult['paie']['heure_normale']); 
        $paie->setPanier($datasResult['paie']['panier']); 
        $paie->setTrajet($datasResult['paie']['trajet']); 
        $paie->setCoutGlobal($datasResult['paie']['cout_global']); 
        $paie->setSalaireNet($datasResult['paie']['salaire_net']); 
        $paie->setTxHoraire($datasResult['paie']['tx_horaire']); 

        if(array_key_exists('paie', $datasResult) && array_key_exists("utilisateur", $datasResult['paie']) && !is_null($datasResult['paie']['utilisateur']) ){
            $userPaie = $this->getDoctrine()->getRepository(Utilisateur::class)->find($datasResult['paie']['utilisateur']);
            $paie->setUtilisateur($userPaie); 
        }
       
        $datasResult['paie'] = $paie;

        $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"paie", "filename"=>$session->get('tmp_ocr_paie', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);
        $datasResult['tmpOcr'] = $tmpOcr;

        
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('paie_add_manuel');
        }

        $form = $this->createForm(PaieType::class, $datasResult['paie']);
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

        return $this->render('paie/add_manuel.html.twig', $datasResult);

    }


    /**
     * @Route("/launch-ia", name="launch_ia")
     */
    public function launchIa(Request $request){
        $lastOcrFile =  $this->session->get('tmp_ocr_paie', "");
        
        $paie = new Paie();
        
        $dirLandingImg = $this->params->get('kernel.project_dir') . "/public/uploads/paies/".$lastOcrFile;

        $datasResult = $this->global_s->lancerIa($lastOcrFile, $paie, "paie", $dirLandingImg);
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('paie_add_manuel');
        }

        $form = $this->createForm(PaieType::class, $datasResult['paie']);
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

        return $this->render('paie/add_manuel.html.twig', $datasResult);
    }

    /**
     * @Route("/add-manuel", name="add_manuel")
     */
    public function addPaieManuel(Request $request, Session $session){

        $paie = new Paie();
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            if(!is_null($entreprise))
                $paie->setEntreprise($entreprise);

            $datePaie = $form->get('date_paie')->getData();
            if($datePaie){
                $moisList = $this->global_s->getMoisFull();

                $dateTextFr = $this->global_s->dateFormatToString($datePaie);
                if(!is_null($dateTextFr)){
                    $paie->setDatePaie($dateTextFr);
                    $mois = $datePaie[1];
                    $annee = $datePaie[2];
                }
                elseif(count(explode(' ', $datePaie))){
                    if(!array_key_exists(explode(' ', $this->global_s->stripAccents($datePaie))[0], $moisList)){
                        $this->addFlash('error', "Le format de date ne correspond pas à celui attendu");
                        return $this->redirectToRoute('email_document_preview_list', ['dossier'=>'paie']);
                    }
                    $mois = $moisList[explode(' ', $this->global_s->stripAccents($datePaie))[0]];
                    $annee = explode(' ', $datePaie)[1];
                }
                else{
                    $this->addFlash('error', "Le format de date ne correspond pas à celui attendu");
                    return $this->redirectToRoute('email_document_preview_list', ['dossier'=>'paie']);
                }

                $heureFictif = $this->getDoctrine()->getRepository(Horaire::class)->getGeneraleHourFictif($form->get('utilisateur')->getData()->getUid(), $mois, $annee);  
                if($heureFictif)
                    $paie->setHeureFictif($heureFictif);

                if($heureFictif!= 0){
                    $paie->setTxHoraire(((float)$form->get('cout_global')->getData())/$heureFictif);
                }
                else{
                    $paie->setTxHoraire(0);
                }

                if($paie->getDatePaie()){

                    $txMoyenAvg = $this->paieRepository->getUserAVGTxMoyenYear($paie->getUtilisateur()->getUid());
                    $paie->setTxMoyen($txMoyenAvg['avg']);
                }

                $datePaie2 = $this->buildDateByDateTextFr($paie->getDatePaie());
                $paie->setDatePaie2($datePaie2);
            }


            /** @var UploadedFile $document_file */
            $uploadedFile = $request->files->get('document_file2');
            $dir = $this->params->get('kernel.project_dir') . "/public/uploads/paies/";
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
                $paie->setDocumentFile($newFilename);
            }  
            else if(!is_null($session->get('tmp_ocr_paie', null))){
                $f = $session->get('tmp_ocr_paie', null);
                $fileTab = explode('.', $f);
                $fileTab[count($fileTab)-1] = "pdf";
                $pdf = implode(".", $fileTab);

                if(!is_file($dir.$pdf)){
                    $pdf = str_replace("pdf", "PDF", $pdf);
                }
                
                $paie->setDocumentFile($pdf);

                // Telecharger le document PDF du projet api ocr vers le site
                $dirSave = "uploads/paies/";
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

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($paie);
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
                $this->global_s->saveOcrField($document, "paie", $session->get('tmp_ocr_paie', null));
            }


            //delete last ocr tmp data
            $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("paie", $session->get('tmp_ocr_paie', null));
            $session->set('tmp_ocr_paie', null);
            
            if(!is_null($session->get('email_facture_load_id', null))){
                $docPreview =  $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($session->get('email_facture_load_id', null));

                if(!is_null($docPreview)){ 
                    $entityManager->remove($docPreview);
                    $entityManager->flush();
                }
                $session->set('email_facture_load_id', null);
            } 

            return $this->redirectToRoute('paie_list');
        }


        $lastOcrFile = $session->get('tmp_ocr_paie', null);
        $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"paie", "filename"=>$lastOcrFile, 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

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

        return $this->render('paie/add_manuel.html.twig', [
            'form' => $form->createView(),
            'paie' => $paie,
            'nbrPage' => $nbrPage,
            'fieldPreference' => $fieldPreferenceArr,
            'tmpOcr'=>$tmpOcr,
            'lastOcrFile'=>$lastOcrFile,
            'display_lot'=>true,
            'add_mode'=>'manuel',
            'fieldsIaZone'=>[]
        ]);
    } 

    /**
     * @Route("/delete-tmp-ocr", name="delete_tmp_ocr")
     */
    public function deleteTmpOcr(Session $session){
        $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("paie", $session->get('tmp_ocr_paie', null));
        $session->set('tmp_ocr_paie', null);
        
        return $this->redirectToRoute('paie_add_manuel');
    }
    
    /**
     * @Route("/group-text-by-position", name="group_text_by_position",  methods={"POST"})
     */
    public function groupTextByPosition(Request $request, Session $session){
        $result = $this->global_s->groupTextByPosition($request, $session->get('tmp_ocr_paie', null), 'paie');
        return new Response(json_encode($result)); 
    }

    /**
     * @Route("/fiche-detail/{id}", name="show", methods={"GET"})
     */
    public function show(Paie $paie): Response
    {
        return $this->render('paie/show.html.twig', [
            'paie' => $paie,
        ]);
    }


}
