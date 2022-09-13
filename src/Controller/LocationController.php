<?php

namespace App\Controller;

use Doctrine\ORM\EntityRepository;
use App\Controller\Traits\CommunTrait;
use App\Entity\ReleveLocation;
use App\Entity\Location;
use App\Entity\LocataireNotification;
use App\Entity\Client;
use App\Entity\Logement;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\LocationPaiement;
use App\Form\LocationType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Repository\LocationRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\MetaConfigRepository;
use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Service\GlobalService;
use Carbon\Carbon;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;


/**
 * @Route("/location")
 */
class LocationController extends Controller
{
    use CommunTrait;
    private $global_s;
    private $session;
    private $clientRepository;
    private $utilisateurRepository;
    private $locationRepository;

    private $metaConfigRepository;
    private $username; 
    private $password; 
    private $BASEURL; 
    private $MESSAGE_HIGH_QUALITY; 
    private $MESSAGE_MEDIUM_QUALITY; 
    private $sender; 
    

    public function __construct(UtilisateurRepository $utilisateurRepository, LocationRepository $locationRepository, MetaConfigRepository $metaConfigRepository, ClientRepository $clientRepository, SessionInterface $session, GlobalService $global_s){
        $this->session = $session;
        $this->global_s = $global_s;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->locationRepository = $locationRepository;
        $this->metaConfigRepository = $metaConfigRepository;
        $this->clientRepository = $clientRepository;

        $this->username = $this->metaConfigRepository->findOneBy(['mkey'=>'smsEnvoi_username', 'entreprise'=>$this->session->get('entreprise_session_id')]); 
        $this->password = $this->metaConfigRepository->findOneBy(['mkey'=>'smsEnvoi_password', 'entreprise'=>$this->session->get('entreprise_session_id')]); 
        $this->BASEURL="https://api.smsenvoi.com/API/v1.0/REST/"; 
        $this->MESSAGE_HIGH_QUALITY = "PRM"; 
        $this->MESSAGE_MEDIUM_QUALITY = "--"; 
        $this->sender = $this->metaConfigRepository->findOneBy(['mkey'=>'smsEnvoi_sender', 'entreprise'=>$this->session->get('entreprise_session_id')]);
    }

    /**
     * @Route("/", name="location_index", methods={"GET", "POST"})
     */
    public function index(LocationRepository $locationRepository, Session $session, Request $request): Response
    {

        $form = $request->request->get('form', null);

        $bienId = null;
        if ($form) {
            $bienId = (!(int)$form['bien']) ? null : (int)$form['bien'];
            $montant = $locationRepository->countMontantLocation($bienId);           
            $locations = $locationRepository->findByParams($bienId);

            $session->set('bien_loc', $bienId);
            
        } else {
            $bienId = $session->get('bien_loc', null);
            $montant = $locationRepository->countMontantLocation($bienId);  
            $locations = $locationRepository->findByParams($bienId);
        }

        $releves = [];
        foreach ($locations as $value) {
            $relevesArr = $locationRepository->getReleveLocation($value->getId());
            $releves[$value->getId()] = ["1"=>["quantite"=>""], "2"=>["quantite"=>""], "3"=>["quantite"=>""]];
            foreach ($relevesArr as $val) {
                $unite = ($val["releve"] == 1 || $val["releve"] == 3) ? "M3" : "KW";
                $releves[$value->getId()][$val['releve']] = ["quantite"=>$val["quantite"]. " ".$unite];
            }
        }

        $form = $this->createFormBuilder(array(
            'bien' => (!is_null($bienId)) ?  $this->getDoctrine()->getRepository(Chantier::class)->find($bienId) : "",

        ))
        ->add('bien', EntityType::class, array(
            'class' => Chantier::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('l')
                ->where('l.entreprise = :entreprise_id')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('l.nameentreprise', 'ASC');
            },
            'required' => false,
            'label' => "Bien",
            'choice_label' => 'nameentreprise',
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();


        return $this->render('location/index.html.twig', [
            'locations' => $locations,
            'releves' => $releves,
            'types'=>$this->global_s->getLocationType(),
            'typesIcones'=>$this->global_s->getLocationTypeIcone(),
            'utilisations' => $this->global_s->utilisations(),
            'releves_types' => $this->global_s->getLocationReleve(),
            'montant'=> $montant,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/message", name="location_message", methods={"GET", "POST"})
     */
    public function locationMessage(LocationRepository $locationRepository, Session $session, Request $request): Response
    {


        $form = $request->request->get('form', null);

        $clientId = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $clientId = (!(int)$form['client']) ? null : (int)$form['client'];
            
            $notifications = $this->getDoctrine()->getRepository(LocataireNotification::class)->findNotificationByParam($mois, $annee, $clientId);

            $session->set('mois_loca', $mois);
            $session->set('annee_loca', $annee);
            $session->set('client_loca', $clientId);
        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_loca', $day[1]);
            $annee = $session->get('annee_loca', $day[0]);
            $clientId = $session->get('client_loca', null);

            $notifications = $this->getDoctrine()->getRepository(LocataireNotification::class)->findNotificationByParam($mois, $annee, $clientId);
        }

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'client' => (!is_null($clientId)) ? $this->clientRepository->find($clientId) : ""
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
        ->add('client', EntityType::class, array(
            'class' => Client::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('c')
                ->andWhere('c.entreprise = :entreprise')
                ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                ->orderBy('c.nom', 'ASC');
            },
            'required' => false,
            'label' => "Client",
            'choice_label' => 'nom',
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        return $this->render('client/message.html.twig', [
            'notifications' => $notifications,
            'form'=>$form->createView()
        ]);
    }


    /**
     * @Route("/facture-eau/{locationId}", name="location_facture_eau_upload", methods={"POST"})
     */
    public function uploadsFactureEau(LocationRepository $locationRepository, Request $request, $locationId): Response
    {
        $location = $locationRepository->find($locationId);
        $uploadedFile = $request->files->get('facture_eau');
        if ($uploadedFile){
            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/location/";
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

            $location->setFactureEau($newFilename);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', "Facture enregistrée avec success");
        }

        return $this->redirectToRoute('location_index');

    }

    /**
     * @Route("/new", name="location_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $location = new Location();
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        $entityManager = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            $location->setType($request->request->get('type'));
            
            if($request->request->get('utilisation')){
                $location->setUtilisation($request->request->get('utilisation'));
            }
            if($request->request->get('charge_provision_forfait')){
                $location->setChargeProvisionForfait($request->request->get('charge_provision_forfait'));
            }
            $location->setRenouvellement($request->request->get('renouvellement'));
            $location->setRevisionAutomatique($request->request->get('revision_automatique'));
            $location->setIsLoyerReference($request->request->get('is_loyer_reference'));
            $location->setIsLoyerMajore($request->request->get('is_loyer_majore'));
            $location->setFinDernierBail($request->request->get('fin_dernier_bail'));
            $location->setIsLoyerReevalue($request->request->get('is_loyer_reevalue'));

            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $location->setEntreprise($entreprise);
            
      
            $charges = $request->request->get('charges');
            if(!is_null($charges)){
                for ($i=0; $i < count($charges['description']); $i++) { 
                    if(!empty($charges['description'][$i]) || !empty($charges['montant'][$i])){
                        $paiement = new LocationPaiement();
                        $paiement->setDescription($charges['description'][$i]);
                        $paiement->setMontant((float)$charges['montant'][$i]);

                        $entityManager->persist($paiement);
                        $location->addPaiement($paiement);    
                    }
                }
            }

            $releves = $request->request->get('releves');
            if(!is_null($releves)){
                for ($i=0; $i < count($releves['releve']); $i++) { 
                    if(!empty($releves['releve'][$i]) || !empty($releves['qte'][$i]) || !empty($releves['date'][$i])){
                        $releve = new ReleveLocation();
                        $releve->setReleve($releves['releve'][$i]);
                        $releve->setQuantite((float)$releves['qte'][$i]);
                        $releve->setEntreprise($entreprise);
                        $releve->setDateReleve(new \DateTime($releves['date'][$i]));

                        $entityManager->persist($releve);
                        $location->addReleve($releve);    
                    }
                }
            }

            $tabFile = ['mandat_sepa', 'etat_lieux', 'diagnostique', 'attestation_assurance', 'cheque_depot_garantie', 'offre_location_signe', 'facture_eau', 'plan', 'bail', 'diag_plomb', 'certificat_mesurage', 'diag_amiante'];

            foreach ($tabFile as $value) {
                $uploadedFile = $form[$value]->getData();
                if ($uploadedFile){
                    $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/location/";
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

                    switch ($value) {
                        case 'mandat_sepa':
                            $location->setMandatSepa($newFilename);
                            break;
                        case 'etat_lieux':
                            $location->setEtatLieux($newFilename);
                            break;
                        case 'diagnostique':
                            $location->setDiagnostique($newFilename);
                            break;
                        case 'attestation_assurance':
                            $location->setAttestationAssurance($newFilename);
                            break;
                        case 'cheque_depot_garantie':
                            $location->setChequeDepotGarantie($newFilename);
                            break;
                        case 'offre_location_signe':
                            $location->setOffreLocationSigne($newFilename);
                            break;
                        case 'facture_eau':
                            $location->setFactureEau($newFilename);
                            break;
                        case 'plan':
                            $location->setPlan($newFilename);
                            break;
                        case 'bail':
                            $location->setBail($newFilename);
                        case 'diag_plomb':
                            $location->setDiagPlomb($newFilename);
                        case 'certificat_mesurage':
                            $location->setCertificatMesurage($newFilename);
                        case 'diag_amiante':
                            $location->setDiagAmiante($newFilename);
                            break;
                        default:
                            break;
                    }
                }
            } 

            $entityManager->persist($location);
            $entityManager->flush();

            return $this->redirectToRoute('location_index');
        }

        return $this->render('location/new.html.twig', [
            'location' => $location,
            'types'=>$this->global_s->getLocationType(),
            'typesIcones'=>$this->global_s->getLocationTypeIcone(),
            'utilisations' => $this->global_s->utilisations(),
            'releves_types' => $this->global_s->getLocationReleve(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/send-sms", name="location_send_sms", methods={"POST"})
     */
    public function sendSmsEmail(Request $request, MailerInterface $mailer)
    {   

        $error = null; $customMailer = null;
        try{
            $customMailer = $this->global_s->initEmailTransport();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if(!is_null($customMailer) && is_null($error)){
            
            $typeEnvoi = $request->request->get('type_envoie');
            $listLocations = $request->request->get('list-location-id');
            $onlyClient = $request->request->get('only_client');
            $subject = $request->request->get('sujet');

            $client = null;
            if($onlyClient){
                $client = $this->clientRepository->find($onlyClient);
            }

            $locations = [];
            if($listLocations && $typeEnvoi){
                $locations = $this->locationRepository->getByTabLocation($typeEnvoi, $listLocations); 
            }

            $nbrSend = 0;
            if (count($locations) || !is_null($client)) {
                
                $tabEmail = []; $tabSms = [];
                if(!is_null($client)){
                    if($client->getEmail()){
                        $tabEmail[] = $client->getEmail();
                    }
                    if($client->getTelone()){
                        $tabSms[] = $client->getTelone();
                    }
                }


                foreach ($locations as $value) {
                    if((filter_var($value['email'], FILTER_VALIDATE_EMAIL))){
                        $tabEmail[] = $value['email'];
                    }
                    if($value['telone'])
                        $tabSms[] = $value['telone'];
                }

                if($typeEnvoi == "locataire_sms"){
                    $retourSend = $this->sendSmsRequest($request, $tabSms, $request->request->get('content'));
                    if(array_key_exists("status", $retourSend)){
                        if($retourSend['status'] != 200){
                            $this->addFlash('error', $retourSend['message']);
                        }
                        else if($retourSend['status'] == 200){
                            if(count($locations)){
                                $this->saveSms($request, $locations, []);
                            }
                            else if(!is_null($client)){
                                $this->saveSmsWithoutLocation($request, $client, []);
                            }

                            $nbrSend = count($tabSms);
                        }
                    }
                }
                else if($typeEnvoi == "locataire_email"){
                    $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                    $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
                    $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();


                    /** @var UploadedFile $uploadedFiles */
                    $uploadedFiles = $request->files->get('piece_jointe');
                    $tabPieceJointe = [];
                    if ($uploadedFiles){
                        $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/location/piecejointes/";
                        try {
                            if (!is_dir($dir)) {
                                mkdir($dir, 0777, true);
                            }
                        } catch (FileException $e) {}

                        foreach ($uploadedFiles as $uploadedFile) {
                            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $this->slugify($originalFilename);
                            $extension = $uploadedFile->guessExtension();
                            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
                            $uploadedFile->move($dir, $newFilename);
                            $tabPieceJointe[] = $newFilename;
                        }

                    } 

                    for ($i=0; $i < count($tabEmail) ; $i++) { 
                        $email = (new Email())
                        ->from(new Address($sender_email, $sender_name))
                            ->to($tabEmail[$i])
                            ->subject($subject)
                            ->html($request->request->get('content'));

                        if(count($tabPieceJointe)){
                            foreach ($tabPieceJointe as $f) {
                                $email = $email->attachFromPath('uploads/location/piecejointes/'.$f);
                            }
                        }

                        try {
                            $send = $customMailer->send($email);
                        } catch (\Exception $ex) { $error = $ex->getMessage(); }
                    }

                    $nbrSend = count($tabEmail);
                    
                    if(count($locations)){
                        $this->saveSms($request, $locations, $tabPieceJointe);
                    }
                    else if(!is_null($client)){
                        $this->saveSmsWithoutLocation($request, $client, $tabPieceJointe);
                    }
                }

                $this->addFlash('info', $nbrSend." Envoies");
                    
            }
        }
        else{
            if(!is_null($error))
                $this->addFlash('error', $error);
            else
                $this->addFlash('error', "Veuillez configurer les informations d'envoie de mail de cette entreprise");
        }

        return $this->redirectToRoute('location_index');
    }


    public function sendSmsRequest(Request $request, $tabSms, $message)
    {
        if(is_null($this->username) || is_null($this->password) || $this->username->getValue() == "" || $this->password->getValue() == "" ){
            return ['status' => 300, "message"=>"Veuillez configurer les access de SMSEnvoi"];
        }

        if(count($tabSms)){
            $auth = $this->global_s->login($this->username->getValue(), $this->password->getValue());
            if(is_null($auth)){
                return ['status' => 300, "message"=>"Echec login à SMSEnvoi"];
            }

            $smsSentData = array(
                "message" => $message,
                "message_type" => $this->MESSAGE_HIGH_QUALITY,
                "returnCredits" => true,
                "recipient" => $tabSms,
            );
            if(!is_null($this->sender)){
                $smsSentData['sender'] = $this->sender->getValue();
            }


            $smsSent = $this->global_s->sendSMS($auth, $smsSentData);

            if(is_null($smsSent)){
                return ['status' => 300, "message"=>"Le message n'a pas pu etre envoyé"];   
            }
            if ($smsSent->result == "OK") {
                return ['status' => 200];
            }
            else
                return ['status' => 300, "message"=>"le SMS n'a pas pu etre envoyé"];
        }
        else{  
            return ['status' => 300, "message"=>"Aucun numero de telephone fournit"];
        }

        return [];
    }

    public function saveSms(Request $request, $locations, $tabPieceJointe)
    {
        $entrepriseId = $this->session->get('entreprise_session_id');
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($entrepriseId);

        $typeEnvoi = $request->request->get('type_envoie');

        if($typeEnvoi){
            $typeEnvoi = ($typeEnvoi == "locataire_email") ? 'EMAIL' : 'SMS';
        }

        $message = $request->request->get('content');

        $piecejointes = null;
        if(count($tabPieceJointe))
            $piecejointes = serialize($tabPieceJointe);

        $entityManager = $this->getDoctrine()->getManager();
        foreach ($locations as $value) {
            $location = $this->locationRepository->find($value['location_id']);
            $client = $this->clientRepository->find($value['client_id']);

            $notification = new LocataireNotification();
            $notification->setLocation($location);
            $notification->setClient($client);
            $notification->setEntreprise($entreprise);
            $notification->setType($typeEnvoi);
            $notification->setMessage($message);
            $notification->setPieceJointe($piecejointes);

            if($request->request->get('sujet'))
                $notification->setSujet($request->request->get('sujet'));

            $entityManager->persist($notification);
        }

        $entityManager->flush();
        return 1;
    }

    public function saveSmsWithoutLocation(Request $request, $locataire, $tabPieceJointe)
    {
        $entrepriseId = $this->session->get('entreprise_session_id');
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($entrepriseId);

        $typeEnvoi = $request->request->get('type_envoie');
        if($typeEnvoi){
            $typeEnvoi = ($typeEnvoi == "locataire_email") ? 'EMAIL' : 'SMS';
        }

        $query = "";
        $message = $request->request->get('content');
        
        $piecejointes = null;
        if(count($tabPieceJointe))
            $piecejointes = serialize($tabPieceJointe);

        $entityManager = $this->getDoctrine()->getManager();
        foreach ($locataire as $value) {
            $client = $this->clientRepository->find($value['client_id']);

            $notification = new LocataireNotification();
            $notification->setLocation(null);
            $notification->setClient($client);
            $notification->setEntreprise($entreprise);
            $notification->setType($typeEnvoi);
            $notification->setMessage($message);
            $notification->setPieceJointe($piecejointes);

            if($request->request->get('sujet'))
                $notification->setSujet($request->request->get('sujet'));

            $entityManager->persist($notification);
        }

        $entityManager->flush();
        
        return 1;
    }

    /**
     * @Route("/{id}/edit", name="location_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Location $location): Response
    {
        //dd($request->request);
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        $entityManager = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            $location->setType($request->request->get('type'));

            if($request->request->get('utilisation') != "")
                $location->setUtilisation($request->request->get('utilisation'));
            

            if($request->request->get('charge_provision_forfait')){
                $location->setChargeProvisionForfait($request->request->get('charge_provision_forfait'));
            }
            $location->setRenouvellement($request->request->get('renouvellement'));
            $location->setRevisionAutomatique($request->request->get('revision_automatique'));
            $location->setIsLoyerReference($request->request->get('is_loyer_reference'));
            $location->setIsLoyerMajore($request->request->get('is_loyer_majore'));
            $location->setFinDernierBail($request->request->get('fin_dernier_bail'));
            $location->setIsLoyerReevalue($request->request->get('is_loyer_reevalue'));


            /* sauvegarde charge */
            $paiements = $location->getPaiements();
            $paiementsEdit = $request->request->get('chargesEdit');
            
            if(!is_null($paiementsEdit)){
                foreach ($paiementsEdit as $key => $value) {
                    $paiement =  $this->getDoctrine()->getRepository(LocationPaiement::class)->find($key);
                    if(!empty($value['description']) || !empty($value['montant'])){
                        $paiement->setDescription($value['description']);
                        $paiement->setMontant((float)$value['montant']);
                    }
                    else{
                        $location->removePaiement($paiement);
                    }
                }
                foreach ($paiements as $value) {
                    if(!array_key_exists($value->getId(), $paiementsEdit)){
                        $entityManager->remove($value);
                    }
                }
            }


            $charges = $request->request->get('charges');
            if(!is_null($charges)){
                for ($i=0; $i < count($charges['description']); $i++) { 
                    if(!empty($charges['description'][$i]) || !empty($charges['montant'][$i])){
                        $paiement = new LocationPaiement();
                        $paiement->setDescription($charges['description'][$i]);
                        $paiement->setMontant((float)$charges['montant'][$i]);

                        $entityManager->persist($paiement);
                        $location->addPaiement($paiement);    
                    }
                }
            }
            /* FIN SAUVEGARDE CHARGE */



            /* SAUVEGARDE RELEVE */

            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $releves = $location->getReleves();
            $releveEdit = $request->request->get('relevesEdit');
            
            if(!is_null($releveEdit)){
                foreach ($releveEdit as $key => $value) {
                    $releve =  $this->getDoctrine()->getRepository(ReleveLocation::class)->find($key);
                    if(!empty($value['releve']) || !empty($value['qte']) || !empty($value['date'])){
                        $releve->setReleve($value['releve']);
                        $releve->setQuantite((float)$value['qte']);
                        $releve->setEntreprise($entreprise);
                        $releve->setDateReleve(new \DateTime($value['date']));
                    }
                    else{
                        $location->removeReleve($releve);
                    }
                }
                foreach ($releves as $value) {
                    if(!array_key_exists($value->getId(), $releveEdit)){
                        $entityManager->remove($value);
                    }
                }
            }


            $releves = $request->request->get('releves');
            if(!is_null($releves)){
                for ($i=0; $i < count($releves['releve']); $i++) { 
                    if(!empty($releves['releve'][$i]) || !empty($releves['qte'][$i]) || !empty($releves['date'][$i])){
                        $releve = new ReleveLocation();
                        $releve->setReleve($releves['releve'][$i]);
                        $releve->setQuantite((float)$releves['qte'][$i]);
                        $releve->setDateReleve(new \DateTime($releves['date'][$i]));
                        $releve->setEntreprise($entreprise);

                        $entityManager->persist($releve);
                        $location->addReleve($releve);    
                    }
                }
            }
            /* FIN SAUVEGARDE RELEVE */



            if($request->request->get('locataire')){
                $locataire =  $this->getDoctrine()->getRepository(Client::class)->find($request->request->get('locataire'));
                $location->setLocataire($locataire);
            }   

            $tabFile = ['mandat_sepa', 'etat_lieux', 'diagnostique', 'attestation_assurance', 'cheque_depot_garantie', 'offre_location_signe', 'facture_eau', 'plan', 'bail', 'diag_plomb', 'certificat_mesurage', 'diag_amiante'];


            foreach ($tabFile as $value) {
                $uploadedFile = $form[$value]->getData();
                if ($uploadedFile){
                    $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/location/";
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

                    switch ($value) {
                        case 'mandat_sepa':
                            $location->setMandatSepa($newFilename);
                            break;
                        case 'etat_lieux':
                            $location->setEtatLieux($newFilename);
                            break;
                        case 'diagnostique':
                            $location->setDiagnostique($newFilename);
                            break;
                        case 'attestation_assurance':
                            $location->setAttestationAssurance($newFilename);
                            break;
                        case 'cheque_depot_garantie':
                            $location->setChequeDepotGarantie($newFilename);
                            break;
                        case 'offre_location_signe':
                            $location->setOffreLocationSigne($newFilename);
                            break;
                        case 'facture_eau': 
                            $location->setFactureEau($newFilename);
                            break;
                        case 'plan':
                            $location->setPlan($newFilename);
                            break;
                        case 'bail':
                            $location->setBail($newFilename);
                            break;
                        case 'diag_plomb':
                            $location->setDiagPlomb($newFilename);
                        case 'certificat_mesurage':
                            $location->setCertificatMesurage($newFilename);
                        case 'diag_amiante':
                            $location->setDiagAmiante($newFilename);
                            break;
                        default:
                            break;
                    }
                }
            }  


            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('location_index');
        }

        return $this->render('location/edit.html.twig', [
            'location' => $location,
            'types'=>$this->global_s->getLocationType(),
            'typesIcones'=>$this->global_s->getLocationTypeIcone(),
            'utilisations' => $this->global_s->utilisations(),
            'releves_types' => $this->global_s->getLocationReleve(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="location_delete")
     */
    public function delete(Request $request, $id): Response
    {
        $location =$this->getDoctrine()
                ->getRepository(Location::class)
                ->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($location);
        $entityManager->flush();
        
        return $this->redirectToRoute('location_index');
    }

    /**
     * @Route("/{id}/delete-document/{type_document}", name="location_delete_document")
     */
    public function deleteDocument(Request $request, $id, $type_document): Response
    {
        $location =$this->getDoctrine()
                ->getRepository(Location::class)
                ->find($id);

        switch ($type_document) {
            case 'mandat_sepa':
                $location->setMandatSepa(null);
                break;
            case 'etat_lieux':
                $location->setEtatLieux(null);
                break;
            case 'diagnostique':
                $location->setDiagnostique(null);
                break;
            case 'attestation_assurance':
                $location->setAttestationAssurance(null);
                break;
            case 'cheque_depot_garantie':
                $location->setChequeDepotGarantie(null);
                break;
            case 'offre_location_signe':
                $location->setOffreLocationSigne(null);
                break;
            case 'facture_eau':
                $location->setFactureEau(null);
                break;
            case 'plan':
                $location->setPlan(null);
                break;
            case 'bail':
                $location->setBail(null);
                break;          
            case 'diag_plomb':
                $location->setDiagPlomb(null);
            case 'certificat_mesurage':
                $location->setCertificatMesurage(null);
            case 'diag_amiante':
                $location->setDiagAmiante(null);
                break;   
            default:
                break;
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
        
        return $this->redirectToRoute('location_edit', ['id'=>$id]);
    }


    /**
     * @Route("/{id}", name="location_show", methods={"GET"})
     */
    public function show(Location $location): Response
    {
        return $this->render('location/show.html.twig', [
            'location' => $location,
        ]);
    }
}
