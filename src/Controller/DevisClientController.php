<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use App\Form\VenteType;
use App\Repository\VenteRepository;
use App\Repository\AchatRepository;
use App\Repository\HoraireRepository;
use Doctrine\ORM\EntityRepository;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Entity\Vente;
use App\Entity\Lot;
use App\Entity\PreferenceField;
use App\Entity\IAZone;
use App\Entity\StatusModule;
use App\Entity\OcrField;
use App\Entity\ModelDocument;
use App\Entity\TmpOcr;
use App\Entity\EmailDocumentPreview;
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
use App\Entity\Status;
use App\Entity\Client;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Repository\ChantierRepository;
use App\Repository\ClientRepository;
use App\Repository\EntrepriseRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\GlobalService;
use Carbon\Carbon;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use GuzzleHttp\Client as GuzzleHttpClient;


/**
 * @Route("/devis/client", name="devis_client_")
 */
class DevisClientController extends Controller
{
    use CommunTrait;
    private $username;
    private $password;
    private $queue_id ;
    private $chantierRepository;
    private $horaireRepository;
    private $clientRepository;
    private $venteRepository;
    private $achatRepository;
    private $global_s;
    private $session;
    private $params;

    public function __construct(ParameterBagInterface $params, GlobalService $global_s, ChantierRepository $chantierRepository, VenteRepository $venteRepository, ClientRepository $clientRepository, SessionInterface $session, AchatRepository $achatRepository, HoraireRepository $horaireRepository){
        $this->chantierRepository = $chantierRepository;
        $this->venteRepository = $venteRepository;
        $this->clientRepository = $clientRepository;
        $this->achatRepository = $achatRepository;
        $this->horaireRepository = $horaireRepository;
        $this->global_s = $global_s;
        $this->session = $session;
        $this->params = $params;
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session): Response
    {

        $page = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['cle'=>"DEVIS_CLIENT"]);

        $form = $request->request->get('form', null);
        $clientId = $chantierId = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $chantierId = (!(int)$form['chantier']) ? null : (int)$form['chantier'];
            $clientId = (!(int)$form['client']) ? null : (int)$form['client'];
            $ventes = $this->venteRepository->findByVenteDate($mois, $annee, 'devis_client', $chantierId, $clientId);

            $session->set('mois_dev_cl', $mois);
            $session->set('annee_dev_cl', $annee);
            $session->set('chantier_dev_cl', $chantierId);
            $session->set('client_dev_cl', $clientId);
        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_dev_cl', $day[1]);
            $annee = $session->get('annee_dev_cl', $day[0]);
            $chantierId = $session->get('chantier_dev_cl', $chantierId);
            $clientId = $session->get('client_dev_cl', $clientId);
            $ventes = $this->venteRepository->findByVenteDate($mois, $annee, 'devis_client', $chantierId, $clientId);
        }

        if(!is_null($chantierId)){
            $factures_client = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantierId, 'type'=>'facture']);
        }
        else{
            $factures_client = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>'facture']);
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

        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        

        $doublon = $entityManager->getRepository(Vente::class)->findDoublon('devis_client', $this->session->get('entreprise_session_id'));
        $tabDoublon = [];
        foreach ($ventes as $value) {
            if(array_search($value->getId(), array_column($doublon, 'id')) !== false) {
                $tabDoublon[] = $value->getId();
            }
        }

        $montant = $entityManager->getRepository(Vente::class)->countMontantByVenteDate($mois, $annee, 'devis_client', $chantierId, $clientId);
        $valChart = [0,0,0,0,0,0,0,0,0,0,0,0];
        $valChart[(int)$mois-1] = number_format($montant['prixttc'], 3, '.', '');

        $full_month = $annee;
        if($mois && !empty($mois))
            $full_month = Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY');

        $countDocAttentes = $entityManager->getRepository(EmailDocumentPreview::class)->countByDossier('devis_client');

        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("DEVIS_CLIENT");

        $page = $entityManager->getRepository(Page::class)->findOneBy(['cle'=>"DEVIS_CLIENT"]);
        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }
        
        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }

        return $this->render('devis_client/index.html.twig', [
            'pager' => $pager,
            'factures_client' => $factures_client,
            'status'=>$status,
            'ventes' => $ventes,
            'full_month'=> $full_month,
            'tabDoublon' => $tabDoublon,
            'valChart'=> $valChart,
            'countDocAttentes'=>$countDocAttentes,
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
     * @Route("/get-by-chantier", name="get_by_chantier")
     */
    public function getVenteByChantier(Request $request){

        $type = $request->request->get('type');
        $tabChantierId = explode('-', $request->request->get('list_chantier_id'));
        $eltSelect = $request->request->get('facture_select');

        $devis = $this->venteRepository->getVenteByChantier($tabChantierId, $type);
        $devisTarget = $this->venteRepository->find($eltSelect);

        $lot = $this->venteRepository->getLotVente(explode('-', $eltSelect));

        $priceTarget = (is_null($devisTarget)) ? 0 : $devisTarget->getPrixht();
        $devis = $this->global_s->sortVenteByLot($devis, $lot, $priceTarget);
        $datas = ['status'=>200, "message"=>""];
        

        $tagLink = $request->request->get('tagLink');
        if($type == 'devis_client'){
            $datas['content'] = $this->renderView('devis_client/modal_devis_client.html.twig', [
                    'devis' => $devis,
                    'page'=> $request->request->get('page'),
                    'facture_select'=> !empty($eltSelect) ? $eltSelect : "",
                    'tagLink'=>$tagLink,
                    'url'=> $this->generateUrl('devis_client_devis_attach', [], UrlGenerator::ABSOLUTE_URL)
                ]);
        }
        elseif($type == 'facture'){
            $datas['content'] = $this->renderView('facture_client/modal_facture_client.html.twig', [
                    'factures_client' => $devis,
                    'facture_select'=> !empty($eltSelect) ? $eltSelect : "",
                    'tagLink'=>$tagLink,
                    'page'=> $request->request->get('page'),
                    'url'=> $this->generateUrl('devis_client_devis_attach', [], UrlGenerator::ABSOLUTE_URL)
                ]);
        }
        $response = new Response(json_encode($datas));
        return $response;        
    }

    /**
     * @Route("/devis-attach", name="devis_attach")
     */
    public function attachDevis(Request $request, Session $session)
    {
        $listElt = explode('-', $request->request->get('list-elt-id'));
        $devisId = $request->request->get('devis');
        $factureId = $request->request->get('facture');
        if(!$devisId && !$factureId){
            $this->addFlash('error', "Vous devez selectionner un devis");
            return new Response('Vous devez selectionner un devis');
        }
        
        if($request->query->get('page') && $request->query->get('page') == "chantier"){
            $this->achatRepository->attachDevis($listElt, $devisId);
            return $this->redirectToRoute('chantier_show', ['chantierId'=>$request->query->get('chantierId'), 'tab'=>$request->query->get('tab')]);
        }
        if($request->query->get('page') && $request->query->get('page') == "facturation"){
            $this->achatRepository->attachDevisPro($listElt, $devisId);
            return $this->redirectToRoute('facturation_list');
        }
        if($request->query->get('page') && $request->query->get('page') == "bon_livraison"){
            $this->achatRepository->attachDevis($listElt, $devisId);
            return $this->redirectToRoute('bon_livraison_list');
        }
        if($request->query->get('page') && $request->query->get('page') == "facture_client"){
            $this->venteRepository->attachDevis($listElt, $devisId);
            return $this->redirectToRoute('facture_client_list');
        }
        if($request->query->get('page') && $request->query->get('page') == "horaire"){
            $this->horaireRepository->attachDevis($listElt, $devisId);
            return $this->redirectToRoute('horaire_horaire_devis');
        }
        if($request->query->get('page') && $request->query->get('page') == "devis_client"){
            $devis = $request->request->get('list-elt-id');
            $factureId = $request->request->get('facture');
            $factureIsAttach = $this->venteRepository->find($factureId);
            if(is_null($factureIsAttach->getDevis()))
                $this->venteRepository->attachDevis([$factureId], $devis);
            else{
                $this->addFlash('error', "Cette facture est deja associée à ce devis");
            }
            return $this->redirectToRoute('devis_client_list');
        }
        return $this->redirectToRoute('bon_livraison_list');
    }

    /**
     * @Route("/devis-dettach/{factureId}", name="dettach_facture")
     */
    public function dettachDevisFacture(Request $request, Session $session, $factureId)
    {
        $facture = $this->venteRepository->find($factureId);
        if(!is_null($facture)){
            $facture->setDevis(null);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();                                
        }
        return $this->redirectToRoute('devis_client_list');
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

            $ventes = $this->getDoctrine()->getRepository(Vente::class)->findByTabId(explode('-', $request->request->get('list-devis-id')), 'devis_client');
            if ($request->isMethod('POST')) {
                $is_send = 0;
                $this->get('session')->getFlashBag()->clear();
                if(filter_var($request->request->get('email_comptable'), FILTER_VALIDATE_EMAIL)){
                    foreach ($ventes as $value) {
                        if(!is_null($value['document_file'])){
                            $chantierName = $clientName = "";
                            if(!is_null($value['chantier_id'])){
                                $chantierName = $this->chantierRepository->find($value['chantier_id'])->getNameentreprise();
                            }
                            if(!is_null($value['client_id'])){
                                $clientName = $this->clientRepository->find($value['client_id'])->getNom();
                            }

                            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                            $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
                            $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();
                            $is_send = 1;
                            $email = (new Email())
                            ->from(new Address($sender_email, $sender_name))
                            ->to($request->request->get('email_comptable'))
                            ->subject('Facturation '.$clientName."-".$chantierName)
                            ->html('Bonjour, Vous trouverez ci-joint la facture.')
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

        return $this->redirectToRoute('devis_client_list');
    }

    /**
     * @Route("/post-attestation", name="post_attestation", methods={"POST"})
     */
    public function postAttestation(Request $request){
        $document = $request->files->get('attestation');
        $devisId = $request->request->get('devis_id');
        if ($document) {
            $dir = $this->params->get('kernel.project_dir') . "/public/uploads/devis/attestation/";
            try {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            } catch (FileException $e) {}

            $originalFilename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = $originalFilename . date('YmdHis') . '.' . $document->guessExtension();
            $document->move($dir, $newFilename);

            $devis = $this->venteRepository->find($devisId);
            $devis->setAttestation($newFilename);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
        } 

        return $this->redirectToRoute('devis_client_list');
    }

    /**
     * @Route("/add", name="add")
     */
    public function new(Request $request)
    {   
        $devis_client = new Vente();

        $form = $this->createForm(VenteType::class, $devis_client);

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                /** @var UploadedFile $document_file */
                $uploadedFile = $form['document_file']->getData();
                if ($uploadedFile){
                    $document_file = $this->global_s->saveImage($uploadedFile, '/uploads/devis/');
                    $devis_client->setDocumentFile($document_file);
                }    
                else{
                    $devis_client->setDocumentFile($request->request->get('document_file2'));
                }
                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $devis_client->setEntreprise($entreprise);

                $devis_client->setType('devis_client');
                if(!is_null($devis_client->getTva()))
                    $devis_client->setMtva($devis_client->getTva()->getValeur());
                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->persist($devis_client);
                $entityManager->flush();                

                $this->deleteDocument($devis_client->getRossumDocumentId());
                return $this->redirectToRoute('devis_client_list');
            }
        }

        $annotations = [];
        $rossumDossierId = $this->global_s->getQueue('devis_client');
        if(empty($rossumDossierId)){
            $this->addFlash('error', "Cette entreprise n'a pas d'identifiant pour ce dossier");
            return $this->render('devis_client/new.html.twig', [
                'form' => $form->createView(),
                'devis_client' => $devis_client,
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
            $rossum_documents = $this->global_s->listRossumDoc($annotations, $this->venteRepository->getAllBl('devis_client'));            
        }
        else
            $this->addFlash('error', "Aucune devis completed");

        return $this->render('devis_client/new.html.twig', [
            'form' => $form->createView(),
            'devis_client' => $devis_client,
            'rossum_documents'=>$rossum_documents,
            "fieldsIaZone"=>[]
        ]);
    }
    
    /**
     * @Route("/update-status", name="update_status")
     */
    public function updateStatus(Request $request){
        $devis = $this->venteRepository->find($request->query->get('devisId'));
        $status = $this->getDoctrine()->getRepository(Status::class)->find($request->query->get('statusId'));
        $devis->setStatus($status);
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush(); 
        return new Response(json_encode(['status'=>200]));
    }

    /**
     * @Route("/{rossum_document_id}/delete-doublon", name="delete_rossum_document_id")
     */
    public function deleteDocumentDuplique(Request $request, $rossum_document_id)
    {
        $this->deleteDocument($rossum_document_id);
        return $this->redirectToRoute('devis_client_add');
    }

    public function deleteDocument($rossum_document_id){
        $this->global_s->deleteDocument($rossum_document_id);
        return 1;
    }

    /**
     * @Route("/{devis_clientId}/edit", name="edit")
     */
    public function edit(Request $request, $devis_clientId)
    {
        $devis_client = $this->getDoctrine()->getRepository(Vente::class)->find($devis_clientId);

        $form = $this->createForm(VenteType::class, $devis_client);
        $form->handleRequest($request);

        $is_document_change = false;
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $document_file */
            $uploadedFile = $form['document_file']->getData();
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

                if($devis_client->getDocumentFile() != $newFilename){
                    $is_document_change = true;
                }

                $devis_client->setDocumentFile($newFilename);
            }           
            if(!is_null($devis_client->getTva()))
                $devis_client->setMtva($devis_client->getTva()->getValeur());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            if($is_document_change){
                return $this->render('devis_client/edit.html.twig', [
                    'form' => $form->createView(),
                    'devis_client' => $devis_client,
                    'is_document_change' => true,
                    'display_lot'=>true,
                    "fieldsIaZone"=>[]
                ]);
            }

            return $this->redirectToRoute('devis_client_list');
        }

        return $this->render('devis_client/edit.html.twig', [
            'form' => $form->createView(),
            'devis_client' => $devis_client,
            'display_lot'=>true,
            "fieldsIaZone"=>[],
            "fieldsIaZone"=>[]
        ]);
    }    

    /**
     * @Route("/{devis_clientId}/delete", name="delete")
     */
    public function delete($devis_clientId)
    {
        $devis_client = $this->getDoctrine()->getRepository(Vente::class)->find($devis_clientId);

        $entityManager = $this->getDoctrine()->getManager();

        try {
            $entityManager->remove($devis_client);
            $entityManager->flush();
            $this->addFlash('success', "Suppression effectuée avec succèss");
        } catch (\Exception $e) {
            $this->addFlash('error', "Vous ne pouvez supprimer cet element s'il est lié à d'autres elements");
        }

        return $this->redirectToRoute('devis_client_list');
    }    

    /**
     * @Route("/checking-data-export", name="checking_data_export")
     */
    public function checkExport(Request $request)
    {
        $url = $this->global_s->constructUrl($this->global_s->getQueue('devis_client'), $request->request->get('document_id'));
        $response = $this->global_s->makeRequest($url);
        $annotations = $response->results[0];
        $devis_client = new Vente();
        $url_document = explode('/', $annotations->url);
        $devis_client->setRossumDocumentId(end($url_document));
        $devis_client->setDocumentFile($this->global_s->retrieveDocFile($annotations->document->file, $annotations->document->file_name, 'uploads/devis/'));
        
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
                        $devis_client->setClient($client);
                        if(count($client->getChantiers()))
                            $devis_client->setChantier($client->getChantiers()[0]);
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
                        $devis_client->setChantier($chantier);
                    }
                }
                if( ($children->schema_id == "amount_total_tax") && !empty($children->value)){
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>(float)$children->value]);
                    if(!is_null($tva))
                        $devis_client->setTva($tva);
                }
                if($children->schema_id == "currency"){
                    $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>$children->value]);
                    if(!is_null($devise))
                        $devis_client->setDevise($devise);
                }
                if($children->schema_id == "lots"){
                    $lot = $this->getDoctrine()->getRepository(Lot::class)->findOneBy(["lot"=>$children->value], ['lot'=>'ASC']);
                    if(!is_null($lot))
                        $devis_client->setLot($lot);
                }
                if($children->schema_id == "document_id"){
                    $devis_client->setDocumentId($children->value);
                }
                if( ($children->schema_id == "amount_total_base") && !empty($children->value)){
                    $devis_client->setPrixht((float)$children->value);
                }
                if( ($children->schema_id == "amount_total") && !empty($children->value) ){
                    $devis_client->setPrixttc((float)$children->value);
                }
                if($children->schema_id == "date_issue"){
                    $devis_client->setFacturedAt(new \Datetime($children->value)); 
                }
            }
        }
        $devisExiste = "";
        if( !is_null($devis_client->getClient()) && !is_null($devis_client->getFacturedAt()) && !is_null($devis_client->getPrixht()) ){
            $blExist = $this->venteRepository->findByFFTH($devis_client->getClient()->getId(), $devis_client->getFacturedAt(), $devis_client->getPrixht(), 'devis_client');
            if($blExist){
                //$datas = ['status'=>500, 'issue'=>'doublon', "message"=>"Une devis similaire existe déjà."];
                //$response = new Response(json_encode($datas));
                //return $response;
                $devisExiste = $blExist['document_file'];
            }
        }

        if(is_null($devis_client->getTva())){
            $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
            $devis_client->setTva($tva);
        }
        if(is_null($devis_client->getDevise())){
            $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
            $devis_client->setDevise($devise);
        }
        if(is_null($devis_client->getFacturedAt())){
            $devis_client->setFacturedAt(new \Datetime());
        }
        if(is_null($devis_client->getPrixttc()) || $devis_client->getPrixht()){
            if(is_null($devis_client->getPrixttc()) && !is_null($devis_client->getPrixht())){
                $ttc = $devis_client->getPrixht() + ($devis_client->getPrixht()*($devis_client->getTva()->getValeur()/100));
                $devis_client->setPrixttc($ttc);
            }
            elseif(!is_null($devis_client->getPrixttc()) && is_null($devis_client->getPrixht())){
                $ht = $devis_client->getPrixttc() / (1+ ($devis_client->getTva()->getValeur()/100));
                $devis_client->setPrixttc($ht);
            }
        }

        $form = $this->createForm(VenteType::class, $devis_client);
        $form->handleRequest($request);

        /*
        $tvaVal = $this->global_s->calculTva($devis_client->getPrixttc(), $devis_client->getPrixht());
        $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>$tvaVal]);
        if(!is_null($tva))
            $devis_client->setTva($tva);*/
        $datas = ['status'=>200, "message"=>"", "count_client"=>count($clientfound)];
        $datas['preview'] = $this->renderView('devis_client/preview_export.html.twig', [
                'form' => $form->createView(),
                'devis_client' => $devis_client, 
                'annotations'=>$annotations,
                'clientfound'=>$clientfound,
                'devisExiste'=> $devisExiste,
                'display_lot'=>true,
                "fieldsIaZone"=>[]
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/client-add", name="client_add")
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
                
                    $entityManager->persist($client); 
                    $entityManager->flush();                

                    $datas = ['status'=>200, 'message'=>"Opération éffectuée avec succès",  "client"=>[
                        'id'=>$client->getId(),
                        'nom'=>$client->getNom(),
                    ]];
                }
                else
                    $datas = ['status'=>500, 'message'=>"Erreur creation client"];
            }
            else{
                $datas = ['status'=>200, "message"=>""];
                $datas['form'] = $this->renderView('devis_client/modal_client_add.html.twig', [
                        'form' => $form->createView(),
                        'client' => $client,
                        'url'=> $this->generateUrl('devis_client_client_add', [], UrlGenerator::ABSOLUTE_URL)
                    ]);
            }
            $response = new Response(json_encode($datas));
            return $response;
        }
        return  new Response("Attendez la fin du chargement de la page...");
    }

    /**
     * @Route("/get-by-chantier-import", name="get_by_chantier_import")
     */
    public function getByChantier(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $devis = $em->createQueryBuilder()
            ->select('d')
            ->from('App\Entity\Vente', 'd')
            ->andWhere('d.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->andWhere('d.type = :type')
            ->setParameter('type', 'devis_client');

        if($request->request->get('chantier_id')){
            $devis = $devis
                        ->andWhere('d.chantier = :chantier')
                        ->setParameter('chantier', $request->request->get('chantier_id'));
        }
        $devis = $devis->getQuery()->getResult();

        $devisArr = [];
        foreach ($devis as $value) {
            $devisArr[] = ['id'=>$value->getId(), 'label'=>$value->__toString()];
        }
        $responses = new Response(json_encode(['status'=>200, "devis"=>$devisArr]));
        return $responses; 
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
                    $session->set('tmp_ocr_devis_client', $newFilename);
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
                        $this->global_s->saveOcrScan("uploads/devis/", $saveFile, "devis_client", false);
                        $documentEmail->setExecute(true);
                    } catch (Exception $e) {}
                }

            }
            else{
                $this->addFlash("error", "Aucun document fournie");
                return $this->redirectToRoute("devis_client_add_manuel");
            }
            
        }
        else{
            $this->addFlash("error", "Aucun document fournie");
            return $this->redirectToRoute("devis_client_add_manuel");
        }

        //delete last ocr tmp data
        //$this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("devis_client", $session->get('tmp_ocr_devis_client', null));


        $isForm = false;
        $session->set('tmp_ocr_devis_client', $saveFile);
        if(($request->isMethod('post') && $document) || !is_null($scan)){
            $isForm = true;
            $this->global_s->convertPdfToImage2("uploads/devis/", $newFilename, $saveFile);
            $this->global_s->saveOcrScan("uploads/devis/", $saveFile, "devis_client", $isForm);
        }
        else{
            /*if(!$this->global_s->isDocumentConvert($saveFile)){
                $this->global_s->convertPdfToImage2("uploads/devis/", $newFilename, $saveFile);
            }
            if(!$this->global_s->isOcrSave($saveFile, "devis_client")){
                $this->global_s->saveOcrScan("uploads/devis/", $saveFile, "devis_client", $isForm);
            }*/
        }


        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/devis/".$saveFile;

        $facture = new Vente();
        $facture->setType('devis_client');

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

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"devis_client", "filename"=>$session->get('tmp_ocr_devis_client', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

            $datasResult = [
                "devis_client"=>$facture,
                "tmpOcr"=>$tmpOcr,
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
                "dossier" => "devis_client",
                "fieldsIaZone" => []
            ];
        }
        else{

            $client = new GuzzleHttpClient([
                'base_uri' => $this->global_s->getBASEURL_OCRAPI(),
            ]);

            $response = $client->request('POST', 'ocrapi/launchia', [
                'form_params' => [
                        'dossier' => "devis_client",
                        'document_file' => $saveFile,
                        'dir_document_file' => "/public/uploads/devis/".$saveFile,
                        'entreprise' => $this->session->get('entreprise_session_id')
                    ]
                ]);

            $datasResult = json_decode($response->getBody()->getContents(), true);

            $facture->setDocumentId($datasResult['devis_client']['documentId']); 
            $facture->setDocumentFile($datasResult['devis_client']['documentFile']); 
            $facture->setPrixht($datasResult['devis_client']['prixht']); 
            $facture->setPrixttc($datasResult['devis_client']['prixttc']); 

            if(array_key_exists('devis_client', $datasResult) && array_key_exists("client", $datasResult['devis_client']) && !is_null($datasResult['devis_client']['client']) ){
                $clientFac = $this->getDoctrine()->getRepository(Client::class)->find($datasResult['devis_client']['client']['id']);
                $facture->setClient($clientFac); 
            }
            if(array_key_exists('devis_client', $datasResult) && array_key_exists("facturedAt", $datasResult['devis_client']) && array_key_exists("date", $datasResult['devis_client']['facturedAt']) ){
                $facture->setFacturedAt(new \Datetime($datasResult['devis_client']['facturedAt']['date'])); 
            }
            if(array_key_exists('devis_client', $datasResult) && array_key_exists("chantier", $datasResult['devis_client']) && !is_null($datasResult['devis_client']['chantier']) ){
                $chantierBl = $this->getDoctrine()->getRepository(Chantier::class)->find($datasResult['devis_client']['chantier']['chantierId']);
                $facture->setChantier($chantierBl); 
            }
            if($datasResult["tvaVal"] != ""){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>str_replace("%", "", $datasResult["tvaVal"])]);
                $facture->setTva($tva);
            }
            $datasResult['devis_client'] = $facture;

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"devis_client", "filename"=>$session->get('tmp_ocr_devis_client', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);
            $datasResult['tmpOcr'] = $tmpOcr;
        }

        //$datasResult = $this->global_s->lancerIa($saveFile, $facture, "devis_client", $dirLandingImg);
        
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('devis_client_add_manuel');
        }

        $form = $this->createForm(VenteType::class, $datasResult['devis_client']);
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

        return $this->render('devis_client/add_manuel.html.twig', $datasResult);
        
    }


    /**
     * @Route("/launch-ia", name="launch_ia")
     */
    public function launchIa(Request $request){
        $lastOcrFile =  $this->session->get('tmp_ocr_devis_client', "");
        
        $devis_client = new Vente();
        $devis_client->setType('devis_client');
        
        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/devis/".$lastOcrFile;

        $datasResult = $this->global_s->lancerIa($lastOcrFile, $devis_client, "devis_client", $dirLandingImg);
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('devis_client_add_manuel');
        }

        $form = $this->createForm(VenteType::class, $datasResult['devis_client']);
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

        return $this->render('devis_client/add_manuel.html.twig', $datasResult);
    }

    /**
     * @Route("/add-manuel", name="add_manuel")
     */
    public function addDevisManuel(Request $request, Session $session){
        $devis_client = new Vente();
        $devis_client->setType('devis_client');
        $form = $this->createForm(VenteType::class, $devis_client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            if(!is_null($entreprise))
                $devis_client->setEntreprise($entreprise);

            if(is_null($devis_client->getTva())){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
                $devis_client->setTva($tva);
            }
            if(is_null($devis_client->getDevise())){
                $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
                $devis_client->setDevise($devise);
            }

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
                $devis_client->setDocumentFile($newFilename);
            }  
            else if(!is_null($session->get('tmp_ocr_devis_client', null))){
                $f = $session->get('tmp_ocr_devis_client', null);
                $fileTab = explode('.', $f);
                $fileTab[count($fileTab)-1] = "pdf";
                $pdf = implode(".", $fileTab);

                if(!is_file($dir.$pdf)){
                    $pdf = str_replace("pdf", "PDF", $pdf);
                }
                
                $devis_client->setDocumentFile($pdf);

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

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($devis_client);
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
                $this->global_s->saveOcrField($document, "devis_client", $session->get('tmp_ocr_devis_client', null));
            }


            //delete last ocr tmp data
            $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("devis_client", $session->get('tmp_ocr_devis_client', null));
            $session->set('tmp_ocr_devis_client', null);
            
            if(!is_null($session->get('email_facture_load_id', null))){
                $docPreview =  $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($session->get('email_facture_load_id', null));

                if(!is_null($docPreview)){ 
                    $entityManager->remove($docPreview);
                    $entityManager->flush();
                }
                $session->set('email_facture_load_id', null);
            } 

            return $this->redirectToRoute('devis_client_list');
        }


        $lastOcrFile = $session->get('tmp_ocr_devis_client', null);
        $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"devis_client", "filename"=>$lastOcrFile, 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

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

        return $this->render('devis_client/add_manuel.html.twig', [
            'form' => $form->createView(),
            'devis_client' => $devis_client,
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
        $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("devis_client", $session->get('tmp_ocr_devis_client', null));
        $session->set('tmp_ocr_devis_client', null);
        
        return $this->redirectToRoute('devis_client_add_manuel');
    }
    
    /**
     * @Route("/group-text-by-position", name="group_text_by_position",  methods={"POST"})
     */
    public function groupTextByPosition(Request $request, Session $session){
        $result = $this->global_s->groupTextByPosition($request, $session->get('tmp_ocr_devis_client', null), 'devis_client');
        return new Response(json_encode($result)); 
    }

}
