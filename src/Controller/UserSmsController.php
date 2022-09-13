<?php

namespace App\Controller;

use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Repository\UtilisateurRepository;
use App\Repository\MetaConfigRepository;
use App\Repository\PassageRepository;
use App\Entity\Utilisateur;
use App\Entity\Entreprise;
use App\Entity\Fournisseurs;
use App\Entity\UserSms;
use App\Service\GlobalService;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Carbon\Carbon;

/**
 * @Route("/sms/user", name="smsuser_")
 */
class UserSmsController extends AbstractController
{
    private $global_s;
    private $session;
    private $utilisateurRepository;
    private $passageRepository;
    private $metaConfigRepository;
    private $username; 
    private $password; 
    private $BASEURL; 
    private $MESSAGE_HIGH_QUALITY; 
    private $MESSAGE_MEDIUM_QUALITY; 
    private $sender; 

    public function __construct(UtilisateurRepository $utilisateurRepository, GlobalService $global_s, SessionInterface $session, MetaConfigRepository $metaConfigRepository, PassageRepository $passageRepository){
        $this->utilisateurRepository = $utilisateurRepository;
        $this->metaConfigRepository = $metaConfigRepository;
        $this->passageRepository = $passageRepository;
        $this->global_s = $global_s;
        $this->session = $session;
        $this->username = $this->metaConfigRepository->findOneBy(['mkey'=>'smsEnvoi_username', 'entreprise'=>$this->session->get('entreprise_session_id')]); 
        $this->password = $this->metaConfigRepository->findOneBy(['mkey'=>'smsEnvoi_password', 'entreprise'=>$this->session->get('entreprise_session_id')]); 
        $this->BASEURL="https://api.smsenvoi.com/API/v1.0/REST/"; 
        $this->MESSAGE_HIGH_QUALITY = "PRM"; 
        $this->MESSAGE_MEDIUM_QUALITY = "--"; 
        $this->sender = $this->metaConfigRepository->findOneBy(['mkey'=>'smsEnvoi_sender', 'entreprise'=>$this->session->get('entreprise_session_id')]);
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session)
    {
        $form = $request->request->get('form', null);

        $utilisateurId = $fournisseurId = null;
        if ($form) {
            $day = (!(int)$form['day']) ? null : (int)$form['day'];
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $utilisateurId = (!(int)$form['utilisateur']) ? null : (int)$form['utilisateur'];
            $fournisseurId = (!(int)$form['fournisseur']) ? null : (int)$form['fournisseur'];

            $passages = $this->passageRepository->findByParam($day, $mois, $annee, $utilisateurId, $fournisseurId);

            $session->set('day_pas', $day);
            $session->set('mois_pas', $mois);
            $session->set('annee_pas', $annee);
            $session->set('utilisateur_pas', $utilisateurId);
            $session->set('fournisseur_pas', $fournisseurId);
        }        
        else{
            $today = explode('-', (new \DateTime())->format('Y-m-d'));
            $day = null;
            $mois = $session->get('mois_pas', $today[1]);
            $annee = $session->get('annee_pas', $today[0]);
            $utilisateurId = $session->get('utilisateur_pas', null);
            $fournisseurId = $session->get('fournisseur_pas', null);

            $passages = $this->passageRepository->findByParam($day, $mois, $annee, $utilisateurId, $fournisseurId);
        }   


        $form = $this->createFormBuilder(array(
            'day' => !is_null($day) ? (int)$day : "",
            'mois' => (int)$mois,
            'annee' => $annee,
            'utilisateur' => (!is_null($utilisateurId)) ? $this->utilisateurRepository->find($utilisateurId) : "",
            'fournisseur' => (!is_null($fournisseurId)) ?  $this->fournisseursRepository->find($fournisseurId) : ""

        ))
        ->add('day', TextType::class, array(
            'label' => "Jour",
            'required' => false,
            'attr' => array(
                'class' => 'form-control'
            )
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


        $page = $request->query->get('page', 1);
        
        return $this->render('passage/passage.html.twig', [
            'passages' => $passages,
            'users'=>$this->utilisateurRepository->findBy(['etat'=>1]),
            'fournisseurs'=>[],
            'chantiers'=>[],
            'day'=>$day,
            'mois'=>$mois,
            'annee'=>$annee,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/send", name="send", methods={"POST"})
     */
    public function sendSmsRequest(Request $request, Session $session)
    {
        $userId = $request->request->get('user_id');
        $dateHoraire = $request->request->get('date_horaire');
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId);
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $dateFormat = Carbon::parseFromLocale($dateHoraire, 'fr', 'UTC');
        $date = new \DateTime($dateFormat->isoFormat('YYYY-MM-DD')); 

        $message = "Monsieur ".$user->getLastname()." ".$user->getFirstname()." vos horaires du ".$dateHoraire." ne sont pas renseignés dans l'application.";

        if(is_null($this->username) || is_null($this->password) || $this->username->getValue() == "" || $this->password->getValue() == "" ){
            return new JsonResponse(['status' => 300, "message"=>"Veuillez configurer les access de SMSEnvoi"]);
        }

        if($user->getPhone()){
            $auth = $this->login($this->username->getValue(), $this->password->getValue());
            if(is_null($auth)){
                return new JsonResponse(['status' => 300, "message"=>"Echec login à SMSEnvoi"]);
            }

            $smsSentData = array(
                "message" => $message,
                "message_type" => $this->MESSAGE_HIGH_QUALITY,
                "returnCredits" => true,
                "recipient" => array($user->getPhone()),
            );
            if(!is_null($this->sender)){
                $smsSentData['sender'] = $this->sender->getValue();
            }


            $smsSent = $this->sendSMS($auth, $smsSentData);

            if(is_null($smsSent)){
                return new JsonResponse(['status' => 300, "message"=>"Le message n'a pas pu etre envoyé"]);   
            }
            if ($smsSent->result == "OK") {
                $this->saveSms($request, $session);
                return new JsonResponse(['status' => 200]);
            }
            else
                return new JsonResponse(['status' => 300, "message"=>"le SMS n'a pas pu etre envoyé"]);
        }
        else{  
            return new JsonResponse(['status' => 300, "message"=>"l'utilisateur n'a pas une numero de telephone valide"]);
        }

    }


    public function saveSms(Request $request, Session $session)
    {
        $userId = $request->request->get('user_id');
        $dateHoraire = $request->request->get('date_horaire');
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId);
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $dateFormat = Carbon::parseFromLocale($dateHoraire, 'fr', 'UTC');
        $date = new \DateTime($dateFormat->isoFormat('YYYY-MM-DD')); 

        $sms = new UserSms();
        $sms->setDateHoraire($date);
        $sms->setUtilisateur($user);
        $sms->setEntreprise($entreprise);
        $sms->setMessage("Monsieur ".$user->getLastname()." ".$user->getFirstname()." vos horaires du ".$dateHoraire." ne sont pas renseignés dans l'application.");

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($sms);
        $entityManager->flush();

        return 1;
    }


    /**
     * Authenticates the user given it's username and password.
     * Returns the pair user_key, Session_key
     */
    public function login($username, $password) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->BASEURL .
                    'login?username=' . $username .
                    '&password=' . $password);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] != 200) {
            return null;
        }

        return explode(";", $response);
    }

    /**
     * Sends an SMS message
     */
    public function sendSMS($auth, $sendSMS) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->BASEURL . 'sms');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'user_key: ' . $auth[0],
            'Session_key: ' . $auth[1]
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sendSMS));
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        //dd([$sendSMS, $info]);
        if ($info['http_code'] != 201) {
            return null;
        }

        return json_decode($response);
    }

}   
