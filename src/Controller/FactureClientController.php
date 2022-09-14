<?php

namespace App\Controller;

use App\Form\VenteType;
use App\Repository\VenteRepository;
use Doctrine\ORM\EntityRepository;
use App\Entity\PreferenceField;
use App\Entity\Vente;
use App\Entity\IAZone;
use App\Entity\OcrField;
use App\Entity\ModelDocument;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Entity\EmailDocumentPreview;
use App\Entity\TmpOcr;
use App\Form\ClientType;
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
use App\Entity\Client;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Entity\Lot;
use App\Repository\ChantierRepository;
use App\Repository\ClientRepository;
use App\Repository\EntrepriseRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Service\GlobalService;
use Carbon\Carbon;
use Pagerfanta\Pagerfanta;
use App\Controller\Traits\CommunTrait;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use GuzzleHttp\Client as GuzzleHttpClient;

/**
 * @Route("/facture/client", name="facture_client_")
 */
class FactureClientController extends Controller
{
    use CommunTrait;
    private $username;
    private $password;
    private $queue_id;
    private $chantierRepository;
    private $clientRepository;
    private $venteRepository;
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, ChantierRepository $chantierRepository, VenteRepository $venteRepository, ClientRepository $clientRepository, SessionInterface $session){
        $this->chantierRepository = $chantierRepository;
        $this->venteRepository = $venteRepository;
        $this->clientRepository = $clientRepository;
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session): Response
    {
        $form = $request->request->get('form', null);
        $clientId = $chantierId = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $chantierId = (!(int)$form['chantier']) ? null : (int)$form['chantier'];
            $clientId = (!(int)$form['client']) ? null : (int)$form['client'];
            $ventes = $this->venteRepository->findByVenteDate($mois, $annee, 'facture', $chantierId, $clientId);

            $session->set('mois_fc_cli', $mois);
            $session->set('annee_fc_cli', $annee);
            $session->set('chantier_fc_cli', $chantierId);
            $session->set('client_fc_cli', $clientId);
        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_fc_cli', $day[1]);
            $annee = $session->get('annee_fc_cli', $day[0]);
            $chantierId = $session->get('chantier_fc_cli', null);
            $clientId = $session->get('client_fc_cli', null);

            $ventes = $this->venteRepository->findByVenteDate($mois, $annee, 'facture', $chantierId, $clientId);
        }
        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'chantier' => (!is_null($chantierId)) ? $this->chantierRepository->find($chantierId) : "",
            'client' => (!is_null($clientId)) ?  $this->clientRepository->find($clientId) : ""

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
        ->add('client', EntityType::class, array(
            'class' => Client::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('f')
                ->where('f.entreprise = :entreprise_id')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('f.nom', 'ASC');
            },
            'required' => false,
            'label' => "Client",
            'choice_label' => 'nom',
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $entityManager = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);

        $adapter = new ArrayAdapter($ventes);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(200);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        

        $doublon = $entityManager->getRepository(Vente::class)->findDoublon('facture', $this->session->get('entreprise_session_id'));
        $tabDoublon = [];
        foreach ($ventes as $value) {
            if(array_search($value->getId(), array_column($doublon, 'id')) !== false) {
                $tabDoublon[] = $value->getId();
            }
        }

        if(!is_null($chantierId)){
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantierId, 'type'=>'devis_client']);
        }
        else{
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>'devis_client']);
        }

        $montant = $entityManager->getRepository(Vente::class)->countMontantByVenteDate($mois, $annee, 'facture', $chantierId, $clientId);
        $valChart = [0,0,0,0,0,0,0,0,0,0,0,0];
        $valChart[(int)$mois-1] = number_format($montant['sum_ht'], 3, '.', '');

        $full_month = $annee;
        if($mois && !empty($mois))
            $full_month = Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY');

        $countDocAttentes = $entityManager->getRepository(EmailDocumentPreview::class)->countByDossier('facture_client');

        $page = $entityManager->getRepository(Page::class)->findOneBy(['cle'=>"FACTURE_CLIENT"]);
        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }
        
        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }

        return $this->render('facture_client/index.html.twig', [
            'pager' => $pager,
            'ventes' => $ventes,
            'devis'=>$devis,
            'countDocAttentes'=>$countDocAttentes,
            'full_month'=> $full_month,
            'tabDoublon' => $tabDoublon,
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
     * @Route("/ca", name="list_ca", methods={"GET", "POST"})
     */
    public function indexCa(Request $request, Session $session): Response
    {
        $form = $request->request->get('form', null);
        $clientId = $chantierId = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $chantierId = (!(int)$form['chantier']) ? null : (int)$form['chantier'];
            $clientId = (!(int)$form['client']) ? null : (int)$form['client'];
            $ventes = $this->venteRepository->findByVenteDate($mois, $annee, 'facture', $chantierId, $clientId);

            $session->set('mois_fc_cli', $mois);
            $session->set('annee_fc_cli', $annee);
            $session->set('chantier_fc_cli', $chantierId);
            $session->set('client_fc_cli', $clientId);
        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_fc_cli', $day[1]);
            $annee = $session->get('annee_fc_cli', $day[0]);
            $chantierId = $session->get('chantier_fc_cli', null);
            $clientId = $session->get('client_fc_cli', null);

            $ventes = $this->venteRepository->findByVenteDate($mois, $annee, 'facture', $chantierId, $clientId);
        }

        $ventesArr = $this->buildVenteGroupMois($ventes);

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'chantier' => (!is_null($chantierId)) ? $this->chantierRepository->find($chantierId) : "",
            'client' => (!is_null($clientId)) ?  $this->clientRepository->find($clientId) : ""

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
        ->add('client', EntityType::class, array(
            'class' => Client::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('f')
                ->where('f.entreprise = :entreprise_id')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('f.nom', 'ASC');
            },
            'required' => false,
            'label' => "Client",
            'choice_label' => 'nom',
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $entityManager = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);

        $adapter = new ArrayAdapter($ventes);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(200);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        

        $doublon = $entityManager->getRepository(Vente::class)->findDoublon('facture', $this->session->get('entreprise_session_id'));
        $tabDoublon = [];
        foreach ($ventes as $value) {
            if(array_search($value->getId(), array_column($doublon, 'id')) !== false) {
                $tabDoublon[] = $value->getId();
            }
        }

        if(!is_null($chantierId)){
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantierId, 'type'=>'devis_client']);
        }
        else{
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>'devis_client']);
        }

        $montant = $entityManager->getRepository(Vente::class)->countMontantByVenteDate($mois, $annee, 'facture', $chantierId, $clientId);
        $valChart = [0,0,0,0,0,0,0,0,0,0,0,0];
        $valChart[(int)$mois-1] = number_format($montant['sum_ht'], 3, '.', '');

        $full_month = $annee;
        if($mois && !empty($mois))
            $full_month = Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY');

        $lots = $this->getDoctrine()->getRepository(Lot::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        return $this->render('facture_client/index_ca.html.twig', [
            'lots'=>$lots,
            'pager' => $pager,
            'ventes' => $ventes,
            'ventesArr' => $ventesArr,
            'devis'=>$devis,
            'full_month'=> $full_month,
            'tabDoublon' => $tabDoublon,
            'valChart'=> $valChart,
            'mois'=>$mois,
            'annee'=>$annee,
            'form' => $form->createView(),
            'montant'=>$montant,
            "fieldsIaZone"=>[]
        ]);
    }

    /**
     * @Route("/zip", name="zip", methods={"POST"})
     */
    public function zip(Request $request)
    {   
        $facturations = $this->getDoctrine()->getRepository(Vente::class)->findByTabId(explode('-', $request->request->get('list-facture-id')), 'facture');

        $zip = new \ZipArchive();
        $zipName = 'factures_client.zip';

        $zip->open($zipName,  \ZipArchive::CREATE);

        foreach ($facturations as $value) {
            if (!is_null($value['document_file'])) {

                $cheminDocument = $this->get('kernel')->getProjectDir() . "/public/uploads/clients/factures/".$value['document_file'];
                if(file_exists($cheminDocument)) {
                    $zip->addFromString(basename($value['document_file']),  file_get_contents($cheminDocument));
                }
            }
        }

        $zip->close();

        $response = new Response(file_get_contents($zipName));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
        $response->headers->set('Content-length', filesize($zipName));

        @unlink($zipName);

        return $response;

    }

    public function buildVenteGroupMois($ventes){

        $ventesArr = [];
        if(count($ventes)){
            foreach ($ventes as $value) {
                $currentMonth = Carbon::parse($value->getFacturedAt()->format('Y-m-d'))->locale('fr')->isoFormat('MMMM YYYY');
                $ventesArr[$currentMonth][] = $value;
            }   
        }
        
        foreach ($ventesArr as $key => $value) {
            $ventesArr[$key] = $this->buildVenteGroupLot($value);   
        }
            
        return $ventesArr;
    }

    public function buildVenteGroupLot($ventes){

        $ventesArr = [];
        if(count($ventes)){
            foreach ($ventes as $value) {
                $lot = "sans_lot";
                if(!is_null($value->getLot())){
                    $lot = (String)$value->getLot()->getLot();
                }
                $ventesArr[$lot][] = $value;
            }   
        }
            
        return $ventesArr;
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

            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

            $sendTo = "";
            if($request->request->get('type_email') == 1){
                $sendTo = $request->request->get('email_comptable');
            }
            else if($request->request->get('type_email') == 2){
                $sendTo = $request->request->get('email_quittance');
            }

            if($request->request->get('submit') ==  "quittance"){
                $facturations = $this->getDoctrine()->getRepository(Vente::class)->findByTabIdNotExportCompta(explode('-', $request->request->get('list-facture-id')), 'facture', 'quittance');
            }
            elseif($request->request->get('submit') ==  "comptabilite"){
                $facturations = $this->getDoctrine()->getRepository(Vente::class)->findByTabIdNotExportCompta(explode('-', $request->request->get('list-facture-id')), 'facture', 'compact');
            }
            else{
                $countVenteExport = $this->getDoctrine()->getRepository(Vente::class)->countVenteExport(explode('-', $request->request->get('list-facture-id')), 'facture');
                $facturations = $this->getDoctrine()->getRepository(Vente::class)->findByTabId(explode('-', $request->request->get('list-facture-id')), 'facture');
            
                if ($request->isXmlHttpRequest()) {
                    if($countVenteExport > 0){
                        $datas = ['status'=>300, "message"=>"Document deja envoyé"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                }
            }

            if ($request->isMethod('POST')) {
                $is_send = 0;
                $this->get('session')->getFlashBag()->clear();
                if( (filter_var($sendTo, FILTER_VALIDATE_EMAIL)) || ($request->request->get('submit') ==  "quittance") ){
                    foreach ($facturations as $value) {
                        
                            $chantierName = $clientName = "";
                            if(!is_null($value['chantier_id'])){
                                $chantierName = $this->chantierRepository->find($value['chantier_id'])->getNameentreprise();
                            }
                            if(!is_null($value['client_id'])){
                                $clientName = $this->clientRepository->find($value['client_id'])->getNom();

                                if($request->request->get('submit') ==  "quittance"){
                                    if(!filter_var($sendTo, FILTER_VALIDATE_EMAIL)){
                                        $clientEmail = $this->clientRepository->find($value['client_id'])->getEmail();
                                        if($clientEmail)
                                            $sendTo = $clientEmail;
                                        else{
                                            $this->addFlash('error', "Le client n'a pas d'email");
                                            $datas = ['status'=>500, "message"=>"Le client n'a pas d'email"];
                                            $response = new Response(json_encode($datas));
                                            return $response;
                                        }
                                    }
                                   
                                }
                            }

                            
                            $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
                            $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();
                            $is_send = 1;
                            $textContent = "Bonjour, Vous trouverez ci-joint la facture.<br><br>Cordialement";
                            if($request->request->get('content'))                            
                                $textContent = $request->request->get('content');

                            $email = (new Email())
                            ->from(new Address($sender_email, $sender_name))
                            ->to($sendTo)
                            ->subject('Document '.$clientName."-".$chantierName)
                            ->html($textContent);

                            
                            if(!is_null($value['document_file'])){
                                $email = $email->attachFromPath('uploads/clients/factures/'.$value['document_file']);
                            }
                            
                            try {
                                $send = $customMailer->send($email);
                            } catch (\Exception $ex) { $error = $ex->getMessage(); }
                            
                            //if($sendTo == "alexngoumo.an@gmail.com"){
                            //    dd([$sendTo, $sender_name, $value['document_file']]);
                            //}
                    }
                    if($is_send){
                        if($request->request->get('submit') ==  "comptabilite"){
                            $this->venteRepository->updateVenteCompta(explode('-', $request->request->get('list-facture-id')));
                        }
                        elseif($request->request->get('submit') ==  "quittance"){
                            $this->venteRepository->updateVenteQuittance(explode('-', $request->request->get('list-facture-id')));
                        }
                        $this->addFlash('success', "Document envoyée avec success");

                        if ($request->isXmlHttpRequest()) {
                            $datas = ['status'=>200, "message"=>"Document envoyé avec succèss"];
                            $response = new Response(json_encode($datas));
                            return $response;
                        }
                    }
                    else{
                        $this->addFlash('error', "Aucune facture selectionnée ou facture déjà envoyée");
                        $datas = ['status'=>500, "message"=>"Aucune facture selectionnée ou facture déjà envoyée"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                }
                else{
                    $this->addFlash('error', "Veuillez verifier l'email fourni");
                    $datas = ['status'=>500, "message"=>"Veuillez verifier l'email fourni"];
                    $response = new Response(json_encode($datas));
                    return $response;
                }
            }
        }
        else{
            if(!is_null($error)){
                $datas = ['status'=>400, "message"=>$error];
            }
            else{
                $datas = ['status'=>400, "message"=>"Veuillez configurer les informations d'envoie de mail de cette entreprise"];
            }
        
            if ($request->isXmlHttpRequest()) {
                $response = new Response(json_encode($datas));
                return $response;
            }
        }
        return $this->redirectToRoute('facture_client_list');
    }


    /**
     * @Route("/add", name="add")
     */
    public function new(Request $request)
    {   
        $facture = new Vente();

        $form = $this->createForm(VenteType::class, $facture, array());

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var UploadedFile $document_file */
                $uploadedFile = $form['document_file']->getData();
                if ($uploadedFile){
                    $document_file = $this->global_s->saveImage($uploadedFile, '/uploads/clients/factures/');
                    $facture->setDocumentFile($document_file);
                }    
                else{
                    $facture->setDocumentFile($request->request->get('document_file2'));
                }

                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $facture->setEntreprise($entreprise);

                $facture->setType('facture');
                if(!is_null($facture->getTva()))
                    $facture->setMtva($facture->getTva()->getValeur());
                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->persist($facture);
                $entityManager->flush();                

                $this->deleteDocument($facture->getRossumDocumentId());
                return $this->redirectToRoute('facture_client_list');
            }
        }

        $annotations = [];
        $rossumDossierId = $this->global_s->getQueue('facture_client');
        if(empty($rossumDossierId)){
            $this->addFlash('error', "Cette entreprise n'a pas d'identifiant pour ce dossier");
            return $this->render('facture_client/new.html.twig', [
                'form' => $form->createView(),
                'facture' => $facture,
                'rossum_documents'=>[],
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
            $rossum_documents = $this->global_s->listRossumDoc($annotations, $this->venteRepository->getAllBl('facture'));            
        }
        else
            $this->addFlash('error', "Aucune facture completed");

        return $this->render('facture_client/new.html.twig', [
            'form' => $form->createView(),
            'facture' => $facture,
            'rossum_documents'=>$rossum_documents
        ]);
    }   

    /**
     * @Route("/devis-dettach/{factureId}", name="dettach_devis")
     */
    public function dettachDevisFacture($factureId)
    {
        $facture = $this->venteRepository->find($factureId);
        if(!is_null($facture)){
            $facture->setDevis(null);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();                                
        }
        return $this->redirectToRoute('facture_client_list');
    }
    
    /**
     * @Route("/{rossum_document_id}/delete-doublon", name="delete_rossum_document_id")
     */
    public function deleteDocumentDuplique(Request $request, $rossum_document_id)
    {
        $this->deleteDocument($rossum_document_id);
        return $this->redirectToRoute('facture_client_add');
    }

    public function deleteDocument($rossum_document_id){
        $this->global_s->deleteDocument($rossum_document_id);
        return 1;
    }

    /**
     * @Route("/{factureId}/edit", name="edit")
     */
    public function edit(Request $request, $factureId)
    {
        $facture = $this->getDoctrine()->getRepository(Vente::class)->find($factureId);

        $tabChantier = !is_null($facture->getChantier()) ? [$facture->getChantier()->getChantierId()] : [];

        $devis = $this->venteRepository->getVenteByChantier($tabChantier, 'devis_client');


        if(!is_null($facture->getClient())){
            $devis = $this->getDevisByClient($facture->getClient(), $devis);
        }

        $devisTarget = $this->venteRepository->find($facture->getId());
        $lot = $this->venteRepository->getLotVente(explode('-', $facture->getId()));

        $priceTarget = (is_null($devisTarget)) ? 0 : $devisTarget->getPrixht();
        $devis = $this->global_s->sortVenteByLot($devis, $lot, $priceTarget);
        
        $tabDevisId = [];
        foreach ($devis as $value) {
            $tabDevisId[] = $value->getId();
        }

        
        $form = $this->createForm(VenteType::class, $facture, array('params' => $tabDevisId));

        $form->handleRequest($request);

        $is_document_change = false;
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $document_file */
            $uploadedFile = $form['document_file']->getData();
            if ($uploadedFile){
                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/clients/factures/";
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

                if($facture->getDocumentFile() != $newFilename){
                    $is_document_change = true;
                }

                $facture->setDocumentFile($newFilename);
            }  

            if(!is_null($facture->getTva()))
                $facture->setMtva($facture->getTva()->getValeur());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            if($is_document_change){
                return $this->render('devis_client/edit.html.twig', [
                    'form' => $form->createView(),
                    'facture' => $facture,
                    'is_document_change' => true,
                    "fieldsIaZone"=>[]
                ]);
            }

            return $this->redirectToRoute('facture_client_list');
        }

        return $this->render('facture_client/edit.html.twig', [
            'form' => $form->createView(),
            'facture' => $facture,
            'fieldsIaZone'=>[]
        ]);
    }       

    public function getDevisByClient($client, $devis = null){

        $devisResponse = [];
        if(!is_null($devis)){
            $i = 0;
            foreach ($devis as $value) {
                if(!is_null($value->getClient()) && $value->getClient()->getId() != $client->getId()){
                    unset($devis[$i]);
                    $i++;
                }
            }
            $devisResponse = $devis;
        }
        elseif(!is_null($client)){
            $devis = $this->venteRepository->findBy(['client'=>$client, 'entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>'devis_client']);
            $devisResponse = $devis;
        }

        return $devisResponse;
    }

    /**
     * @Route("/devis-client", name="get_devis_client")
     */
    public function devisByClient(Request $request)
    {
        $clientId = $request->query->get('client_id');
        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $devis = $this->getDevisByClient($client);

        $devisArr = [];
        foreach ($devis as $value) {
            $devisArr[] = ['id'=>$value->getId(), 'label'=>$value->__toString()];
        }

        return new Response(json_encode(['status'=>200, 'devis'=>$devisArr]));
    }

    /**
     * @Route("/{factureId}/delete", name="delete")
     */
    public function delete($factureId)
    {
        $facture = $this->getDoctrine()->getRepository(Vente::class)->find($factureId);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($facture);
        $entityManager->flush();

        $this->get('session')->getFlashBag()->clear();
        $this->addFlash('success', "Suppression effectuée avec succès");
        return $this->redirectToRoute('facture_client_list');
    }    

    /**
     * @Route("/checking-data-export", name="checking_data_export")
     */
    public function checkExport(Request $request)
    {
        $url = $this->global_s->constructUrl($this->global_s->getQueue('facture_client'), $request->request->get('document_id'));
        $response = $this->global_s->makeRequest($url);
        $annotations = $response->results[0];
        $facture = new Vente();
        $url_document = explode('/', $annotations->url);
        $facture->setRossumDocumentId(end($url_document));
        $facture->setDocumentFile($this->global_s->retrieveDocFile($annotations->document->file, $annotations->document->file_name, 'uploads/clients/factures/'));
        
        $clientfound = [];
        foreach ($annotations->content as $content){
            foreach ($content->children as $children) {
                if($children->schema_id == "recipient_name"){
                    $clientfound = $this->global_s->findByNameAlpn($children->value, "client");
                    if(count($clientfound) == 0){
                        $datas = ['status'=>500, 'issue'=>'client', 'sender_name'=>$children->value, "message"=>"Le client ".$children->value." n'existe pas"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                    else{
                        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientfound[0]['id']);
                        $facture->setClient($client);
                        if(count($client->getChantiers()))
                            $facture->setChantier($client->getChantiers()[0]);
                    }
                }
                if( ($children->schema_id == "chantier") && !empty($children->value)) {
                    $chantierfound = $this->global_s->findByNameAlpn($children->value, "chantier");
                    if(count($chantierfound) == 0){
                        $datas = ['status'=>500, 'issue'=>'chantier', 'sender_name'=>$children->value, "message"=>"Le chantier ".$children->value." n'existe pas"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                    else{
                        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierfound[0]['id']);
                        $facture->setChantier($chantier);
                    }
                }
                if( ($children->schema_id == "amount_total_tax") && !empty($children->value)){
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>(float)$children->value]);
                    if(!is_null($tva))
                        $facture->setTva($tva);
                }
                if($children->schema_id == "currency"){
                    $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>$children->value]);
                    if(!is_null($devise))
                        $facture->setDevise($devise);
                }
                if($children->schema_id == "lots"){
                    $lot = $this->getDoctrine()->getRepository(Lot::class)->findOneBy(["lot"=>$children->value]);
                    if(!is_null($lot))
                        $facture->setLot($lot);
                }
                if($children->schema_id == "document_id"){
                    $facture->setDocumentId($children->value);
                }
                if( ($children->schema_id == "amount_total_base") && !empty($children->value)){
                    $facture->setPrixht((float)$children->value);
                }
                if( ($children->schema_id == "amount_total") && !empty($children->value) ){
                    $facture->setPrixttc((float)$children->value);
                }
                if($children->schema_id == "date_issue"){
                    $facture->setFacturedAt(new \Datetime($children->value)); 
                }
            }
        }
        $factureExiste = "";
        if( !is_null($facture->getClient()) && !is_null($facture->getFacturedAt()) && !is_null($facture->getPrixht()) ){
            $blExist = $this->venteRepository->findByFFTH($facture->getClient()->getId(), $facture->getFacturedAt(), $facture->getPrixht(), 'facture');
            if($blExist){
                //$datas = ['status'=>500, 'issue'=>'doublon', "message"=>"Une devis similaire existe déjà."];
                //$response = new Response(json_encode($datas));
                //return $response;
                $factureExiste = $blExist['document_file'];
            }
        }

        if(is_null($facture->getTva())){
            $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
            $facture->setTva($tva);
        }
        if(is_null($facture->getDevise())){
            $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
            $facture->setDevise($devise);
        }
        if(is_null($facture->getFacturedAt())){
            $facture->setFacturedAt(new \Datetime());
        }
        if(is_null($facture->getPrixttc()) || $facture->getPrixht()){
            if(is_null($facture->getPrixttc()) && !is_null($facture->getPrixht())){
                $ttc = $facture->getPrixht() + ($facture->getPrixht()*($facture->getTva()->getValeur()/100));
                $facture->setPrixttc($ttc);
            }
            elseif(!is_null($facture->getPrixttc()) && is_null($facture->getPrixht())){
                $ht = $facture->getPrixttc() / (1+ ($facture->getTva()->getValeur()/100));
                $facture->setPrixttc($ht);
            }
        }

        if(is_null($facture->getClient()))
            $devis = $this->venteRepository->findBy(['type'=> 'devis_client', 'entreprise' => $this->session->get('entreprise_session_id')]);
        else
            $devis = $this->venteRepository->findBy(['client'=>$facture->getClient(), 'type'=> 'devis_client', 'entreprise' => $this->session->get('entreprise_session_id')]);


        if(!is_null($facture->getClient())){
            $devis = $this->getDevisByClient($facture->getClient(), $devis);
        }

        $priceTarget = (is_null($facture->getPrixht())) ? 0 : $facture->getPrixht();
        $devis = $this->global_s->sortVenteByLot($devis,null, $priceTarget);
        
        $tabDevisId = [];
        foreach ($devis as $value) {
            $tabDevisId[] = $value->getId();
        }
        $form = $this->createForm(VenteType::class, $facture, array('params' => $tabDevisId));

        $form->handleRequest($request);

        /*
        $tvaVal = $this->global_s->calculTva($facture->getPrixttc(), $facture->getPrixht());
        $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>$tvaVal]);
        if(!is_null($tva))
            $facture->setTva($tva);*/
        $datas = ['status'=>200, "message"=>"", "count_client"=>count($clientfound)];
        $datas['preview'] = $this->renderView('facture_client/preview_export.html.twig', [
                'form' => $form->createView(),
                'facture' => $facture, 
                'annotations'=>$annotations,
                'clientfound'=>$clientfound,
                'factureExiste'=> $factureExiste,
                "fieldsIaZone"=>[]
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    public function findByNameAlpn($clientName){
        $clientArr = [];
        $clientfound = $this->clientRepository->findByNameAlpn($clientName);
        if(count($clientfound) > 0){
            foreach ($clientfound as $value) {
                $clientArr[] = $value;
            }
        }
        else{
            //$clientNameAlpn = str_replace("  ", " ", preg_replace("/[^a-zA-Z0-9]+/", " ", $clientName));
            $clientNameAlpn = $clientName;
            $clientTmpArr = explode(" ", $clientNameAlpn);
            $clientTmpArrCpy = $clientTmpArr;
            while (count($clientTmpArr) > 0) {
                $clientfound = $this->clientRepository->findByNameAlpn(implode(" ", $clientTmpArr));
                if(count($clientfound) > 0){
                    foreach ($clientfound as $value){
                        $clientArr[] = $value;
                    }
                    //break;
                }
                array_pop($clientTmpArr);
            }   
            if(count($clientArr) == 0){
                foreach ($clientTmpArrCpy as $value) {
                    $clientfound = $this->clientRepository->findByNameAlpn($value);
                    if(count($clientfound) > 0){
                        foreach ($clientfound as $value){
                            $clientArr[] = $value;
                        }
                        //break;
                    }
                }
            }        
        }
        return $clientArr;
    }

    /**
     * @Route("/client-add", name="new_client")
     */
    public function addClient(Request $request){
        $em = $this->getDoctrine()->getManager();
        $client = new Client();
        $client->setDatecrea(new \DateTime());
        $client->setDatemaj(new \DateTime());
        $form = $this->createForm(ClientType::class, $client);

        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('POST')) {
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager = $this->getDoctrine()->getManager();

                    $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                    if(!is_null($entreprise))
                        $client->setEntreprise($entreprise);
                    
                    $client->setType("client");
                    $entityManager->persist($client); 
                    $entityManager->flush();                

                    $datas = ['status'=>200, 'message'=>"Opération éffectuée avec succès", "client"=>[
                        'id'=>$client->getId(),
                        'nom'=>$client->getNom(),
                    ]];
                }
                else
                    $datas = ['status'=>500, 'message'=>"Erreur creation client"];
            }
            else{
                $datas = ['status'=>200, "message"=>""];
                $datas['form'] = $this->renderView('facture_client/modal_client_add.html.twig', [
                        'form' => $form->createView(),
                        'client' => $client,
                        'url'=> $this->generateUrl('facture_client_new_client', [], UrlGenerator::ABSOLUTE_URL)
                    ]);
            }
            $response = new Response(json_encode($datas));
            return $response;
        }
        return  new Response("Attendez la fin du chargement de la page...");
    }


    /**
     * @Route("/attach-lot", name="attach_lot", methods={"POST"})
     */
    public function attachLot(Request $request){
        $lotId =  $request->request->get('lot');
        $factureId =  $request->request->get('facture_id');

        $facture = $this->getDoctrine()->getRepository(Vente::class)->find($factureId);
        $lot = $this->getDoctrine()->getRepository(Lot::class)->find($lotId);
        
        $facture->setLot($lot);
        $entityManager = $this->getDoctrine()->getManager();
        
        $entityManager->flush();
        return $this->redirectToRoute('facture_client_list_ca');
    }



    /**
     * @Route("/import-document/{scan}", name="import_document", methods={"POST", "GET"})
     */
    public function loadDocumentToOcr(Request $request, Session $session, $scan = null){

        $document = $request->files->get('document');
        $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/clients/factures/";
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
                return $this->redirectToRoute("facture_client_add_manuel");
            }
            
        }
        else{
            $this->addFlash("error", "Aucun document fournie");
            return $this->redirectToRoute("facture_client_add_manuel");
        }

        //delete last ocr tmp data
        //$this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("facture_client", $session->get('tmp_ocr_fact_client', null));

        $isForm = false;
        $session->set('tmp_ocr_fact_client', $saveFile);
        if( ($request->isMethod('post') && $document) || !is_null($scan) ){
            $isForm = true;
            $this->global_s->convertPdfToImage2("uploads/clients/factures/", $newFilename, $saveFile);
            $this->global_s->saveOcrScan("uploads/clients/factures/", $saveFile, "facture_client", $isForm);
        }
        else{
            /*if(!$this->global_s->isDocumentConvert($saveFile)){
                $this->global_s->convertPdfToImage2("uploads/clients/factures/", $newFilename, $saveFile);
            }
            if(!$this->global_s->isOcrSave($saveFile, "facture_client")){
                $this->global_s->saveOcrScan("uploads/clients/factures/", $saveFile, "facture_client", $isForm);
            }*/
        }


        $facture = new Vente();
        $facture->setType('facture');

        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/clients/factures/".$saveFile;

        if(!is_null($documentEmail) && !is_null($documentEmail->getFacturedAt()) && !is_null($documentEmail->getClient()) && !is_null($documentEmail->getDocumentId()) && !is_null($documentEmail->getPrixht()) && !is_null($documentEmail->getPrixttc()) && !is_null($documentEmail->getChantier())){

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
            $facture->setTva($tva);

            $facture->setClient($documentEmail->getClient()); 
            $facture->setChantier($documentEmail->getChantier()); 
            $facture->setDocumentId($documentEmail->getDocumentId()); 
            $facture->setFacturedAt($documentEmail->getFacturedAt()); 
            $facture->setDocumentFile($documentEmail->getDocument()); 
            $facture->setPrixht($documentEmail->getPrixht()); 
            $facture->setPrixttc($documentEmail->getPrixttc()); 


            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"facture_client", "filename"=>$session->get('tmp_ocr_fact_client', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

            $datasResult = [
                "facture_client"=>$facture,
                "lastOcrFile" => $this->global_s->replaceExtenxionFilename($documentEmail->getDocument(), "jpg"),
                "display_lot" => false,
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
                "dossier" => "facture_client",
                "tmpOcr"=>$tmpOcr,
                "fieldsIaZone" => []
            ];
        }
        else{

            $client = new GuzzleHttpClient([
                'base_uri' => $this->global_s->getBASEURL_OCRAPI(),
            ]);

            $response = $client->request('POST', 'ocrapi/launchia', [
                'form_params' => [
                        'dossier' => "facture_client",
                        'document_file' => $saveFile,
                        'dir_document_file' => $dirLandingImg,
                        'entreprise' => $this->session->get('entreprise_session_id')
                    ]
                ]);

            $datasResult = json_decode($response->getBody()->getContents(), true);

            $facture->setDocumentId($datasResult['facture_client']['documentId']); 
            $facture->setDocumentFile($datasResult['facture_client']['documentFile']); 
            $facture->setPrixht($datasResult['facture_client']['prixht']); 
            $facture->setPrixttc($datasResult['facture_client']['prixttc']); 

            if(array_key_exists('facture_client', $datasResult) && array_key_exists("client", $datasResult['facture_client']) && !is_null($datasResult['facture_client']['client']) ){
                $clientFac = $this->getDoctrine()->getRepository(Client::class)->find($datasResult['facture_client']['client']['id']);
                $facture->setClient($clientFac); 
            }
            if(array_key_exists('facture_client', $datasResult) && array_key_exists("facturedAt", $datasResult['facture_client']) && array_key_exists("date", $datasResult['facture_client']['facturedAt']) ){
                $facture->setFacturedAt(new \Datetime($datasResult['facture_client']['facturedAt']['date'])); 
            }
            if(array_key_exists('facture_client', $datasResult) && array_key_exists("chantier", $datasResult['facture_client']) && !is_null($datasResult['facture_client']['chantier']) ){
                $chantierBl = $this->getDoctrine()->getRepository(Chantier::class)->find($datasResult['facture_client']['chantier']['chantierId']);
                $facture->setChantier($chantierBl); 
            }
            if($datasResult["tvaVal"] != ""){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>str_replace("%", "", $datasResult["tvaVal"])]);
                $facture->setTva($tva);
            }
            $datasResult['facture_client'] = $facture;

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"facture_client", "filename"=>$session->get('tmp_ocr_fact_client', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);
            $datasResult['tmpOcr'] = $tmpOcr;
        }

        //$datasResult = $this->global_s->lancerIa($saveFile, $facture, "facture_client", $dirLandingImg);

        
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('facture_client_add_manuel');
        }

        $clientfound = $datasResult['clientfound'];
        $tabDevisId = [];
        if(count($clientfound) > 0){
            $devis = $this->getDevisByClient($clientfound[0]);
            foreach ($devis as $value) {
                $tabDevisId[] = $value->getId();
            }
        }




        $form = $this->createForm(VenteType::class, $datasResult['facture_client'], array('params' => $tabDevisId));
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

        return $this->render('facture_client/add_manuel.html.twig', $datasResult);
    
    }


    /**
     * @Route("/launch-ia", name="launch_ia")
     */
    public function launchIa(Request $request){
        $lastOcrFile =  $this->session->get('tmp_ocr_fact_client', "");
        
        $facture = new Vente();
        $facture->setType('facture');
        
        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/clients/factures/".$lastOcrFile;

        $datasResult = $this->global_s->lancerIa($lastOcrFile, $facture, "facture_client", $dirLandingImg);
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('facture_client_add_manuel');
        }

        $form = $this->createForm(VenteType::class, $datasResult['facture_client']);
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

        return $this->render('facture_client/add_manuel.html.twig', $datasResult);
    }

    /**
     * @Route("/add-manuel", name="add_manuel")
     */
    public function addFactureManuel(Request $request, Session $session){

        $facture = new Vente();
        $facture->setType('facture');
        $form = $this->createForm(VenteType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            if(!is_null($entreprise))
                $facture->setEntreprise($entreprise);

            if(is_null($facture->getTva())){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
                $facture->setTva($tva);
            }
            if(is_null($facture->getDevise())){
                $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
                $facture->setDevise($devise);
            }

            /** @var UploadedFile $document_file */
            $uploadedFile = $request->files->get('document_file2');
            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/clients/factures/";
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
                $facture->setDocumentFile($newFilename);
            }  
            else if(!is_null($session->get('tmp_ocr_fact_client', null))){
                $f = $session->get('tmp_ocr_fact_client', null);
                $fileTab = explode('.', $f);
                $fileTab[count($fileTab)-1] = "pdf";
                $pdf = implode(".", $fileTab);

                if(!is_file($dir.$pdf)){
                    $pdf = str_replace("pdf", "PDF", $pdf);
                }

                $facture->setDocumentFile($pdf);

                // Telecharger le document PDF du projet api ocr vers le site
                $dirSave = "uploads/clients/factures/";
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
            $entityManager->persist($facture);
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
                $this->global_s->saveOcrField($document, "facture_client", $session->get('tmp_ocr_fact_client', null));
            }


            //delete last ocr tmp data
            $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("facture_client", $session->get('tmp_ocr_fact_client', null));
            $session->set('tmp_ocr_fact_client', null);

            if(!is_null($session->get('email_facture_load_id', null))){
                $docPreview =  $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($session->get('email_facture_load_id', null));

                if(!is_null($docPreview)){ 
                    $entityManager->remove($docPreview);
                    $entityManager->flush();
                }
                $session->set('email_facture_load_id', null);
            } 
               
            return $this->redirectToRoute('facture_client_list');
        }


        $lastOcrFile = $session->get('tmp_ocr_fact_client', null);
        $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"facture_client", "filename"=>$lastOcrFile, 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

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

        return $this->render('facture_client/add_manuel.html.twig', [
            'form' => $form->createView(),
            'facture' => $facture,
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
        $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("facture_client", $session->get('tmp_ocr_fact_client', null));
        $session->set('tmp_ocr_fact_client', null);
        
        return $this->redirectToRoute('facture_client_add_manuel');
    }
    
    /**
     * @Route("/group-text-by-position", name="group_text_by_position",  methods={"POST"})
     */
    public function groupTextByPosition(Request $request, Session $session){
        $result = $this->global_s->groupTextByPosition($request, $session->get('tmp_ocr_fact_client', null), 'facture_client');
        return new Response(json_encode($result)); 
    }
}
