<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Entreprise;
use App\Repository\EntrepriseRepository;
use App\Repository\MetaConfigRepository;
use App\Entity\MetaConfig;
use App\Service\GlobalService;

class MetaConfigController extends AbstractController
{
	private $metaConfigRepository;
    private $entrepriseRepository;
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, EntrepriseRepository $entrepriseRepository, MetaConfigRepository $metaConfigRepository, SessionInterface $session){
        $this->metaConfigRepository = $metaConfigRepository;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/config/email", name="config_email")
     */
    public function index()
    {
    	$cabinetComptable = $this->metaConfigRepository->findOneBy(['mkey'=>'cabinet_comptable', 'entreprise'=>$this->session->get('entreprise_session_id')]);
        $gestionFinancement = $this->metaConfigRepository->findOneBy(['mkey'=>'gestion_financement', 'entreprise'=>$this->session->get('entreprise_session_id')]);
        $gestionTresorie = $this->metaConfigRepository->findOneBy(['mkey'=>'gestion_tresorie', 'entreprise'=>$this->session->get('entreprise_session_id')]);
        
        return $this->render('config_email/index.html.twig', [
            'cabinetComptable' => $cabinetComptable,
            'gestionFinancement' => $gestionFinancement,
            'gestionTresorie' => $gestionTresorie
        ]);
    }

    /**
     * @Route("/config/email/add", name="config_email_add")
     */
    public function addEmailComptable(Request $request)
    {
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        if(is_null($entreprise)){
            $this->addFlash('error', "Vous devez selectionner une entreprise");
            return $this->redirectToRoute('config_email');
        }

        $cabinetComptable = $this->metaConfigRepository->findOneBy(['mkey'=>'cabinet_comptable', 'entreprise'=>$entreprise->getId()]);
    	if(is_null($cabinetComptable))
    		$cabinetComptable = new MetaConfig();

    	$cabinetComptable->setMkey('cabinet_comptable');
    	$cabinetComptable->setValue($request->request->get('cabinet_comptable'));
        $cabinetComptable->setEntreprise($entreprise);


        $gestionFinancements = $this->metaConfigRepository->findOneBy(['mkey'=>'gestion_financement', 'entreprise'=>$entreprise->getId()]);
        if(is_null($gestionFinancements))
            $gestionFinancements = new MetaConfig();    

        $gestionFinancements->setMkey('gestion_financement');
        $gestionFinancements->setValue($request->request->get('gestionFinancement'));
        $gestionFinancements->setEntreprise($entreprise);


        $gestionTresorie = $this->metaConfigRepository->findOneBy(['mkey'=>'gestion_tresorie', 'entreprise'=>$entreprise->getId()]);
        if(is_null($gestionTresorie))
            $gestionTresorie = new MetaConfig();

        $gestionTresorie->setMkey('gestion_tresorie');
        $gestionTresorie->setValue($request->request->get('gestionTresorie'));
        $gestionTresorie->setEntreprise($entreprise);


    	$entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($cabinetComptable);
        $entityManager->persist($gestionFinancements);
        $entityManager->persist($gestionTresorie);
        $entityManager->flush(); 

        return $this->redirectToRoute('config_email');
    }

    /**
     * @Route("/getConfigMeta", name="get_meta_config")
     */
    public function getConfigMeta(Request $request, $key)
    {
    	$cabinetComptable = $this->metaConfigRepository->findOneBy(['mkey'=>$key, 'entreprise'=>$this->session->get('entreprise_session_id')]);
    	$val = "";
    	if(!is_null($cabinetComptable))
    		$val = $cabinetComptable->getValue();

    	return new Response($val);
    }

    /**
     * @Route("/config/smsSend", name="config_acces_sms_send")
     */
    public function smsEnvoie(Request $request)
    {
        $metaUsername = $this->metaConfigRepository->findOneBy(['mkey'=>"smsEnvoi_username", 'entreprise'=>$this->session->get('entreprise_session_id')]);
        $metaPassword = $this->metaConfigRepository->findOneBy(['mkey'=>"smsEnvoi_password", 'entreprise'=>$this->session->get('entreprise_session_id')]);
        $metaSender = $this->metaConfigRepository->findOneBy(['mkey'=>"smsEnvoi_sender", 'entreprise'=>$this->session->get('entreprise_session_id')]);

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $sender = $request->request->get('sender');

            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

            if(is_null($metaUsername))
                $metaUsername = new MetaConfig();
            
            if(is_null($metaPassword))
                $metaPassword = new MetaConfig();

            if(is_null($metaSender))
                $metaSender = new MetaConfig();

            $metaUsername->setMkey("smsEnvoi_username");
            $metaUsername->setValue($username);
            $metaUsername->setEntreprise($entreprise);

            $metaPassword->setMkey("smsEnvoi_password");
            $metaPassword->setValue($password);
            $metaPassword->setEntreprise($entreprise);

            $metaSender->setMkey("smsEnvoi_sender");
            $metaSender->setValue($sender);
            $metaSender->setEntreprise($entreprise);


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($metaUsername);
            $entityManager->persist($metaPassword);
            $entityManager->persist($metaSender);
            $entityManager->flush(); 
            $this->addFlash('success', "Enregistrement effectué");
        }
        

        return $this->render('config_email/config_smsenvoie.html.twig', [
            'username'=>$metaUsername,
            'password'=>$metaPassword,
            'sender'=>$metaSender
        ]);
    }

}
