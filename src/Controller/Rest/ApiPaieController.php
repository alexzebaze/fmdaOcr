<?php

namespace App\Controller\Rest;

use App\Entity\Paie;
use App\Entity\Utilisateur;
use App\Form\PaieType;
use App\Repository\PaieRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Entreprise;
use App\Entity\Horaire;
use App\Repository\EntrepriseRepository;
use App\Service\GlobalService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\Address;
use Swagger\Annotations as SWG;
/**
 * @Route("/paies", name="api_paie_")
 */
class ApiPaieController extends AbstractController
{
    private $global_s;
    private $paieRepository;
    private $utilisateurRepository;
    private $session;

    public function __construct(GlobalService $global_s, PaieRepository $paieRepository, UtilisateurRepository $utilisateurRepository, SessionInterface $session){
        $this->global_s = $global_s;
        $this->paieRepository = $paieRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->session = $session;
    }

    /**
     * @Route("/list", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, PaieRepository $paieRepository): Response
    {

        $moisList =array_flip($this->global_s->getMois());

        if ($request->get('mois') && $request->get('annee')){
            $mois = (!(int)$request->get('mois')) ? null : (int)$request->get('mois');
            $annee = (!(int)$request->get('annee')) ? null : (int)$request->get('annee');
        }
        else{
            $day = date('Y-m-d',strtotime('-1 month',strtotime((new \DateTime())->format('Y-m-d'))));
            $day = explode('-', $day);
            $mois = (int)$day[1];
            $annee = $day[0];
        }

        $monthText = !is_null($mois) ? $moisList[$mois] : "";
        $paies = $paieRepository->getByDateAndUser($monthText, $annee, $request->get('user_id') ?? $this->getUser()->getUid());

        $tabHoraire = [];
        $moisList2 = $this->global_s->getMois();
        foreach ($paies as $value) {
            $mois2 = $moisList2[explode(' ', $value->getDatePaie())[0]];
            $annee2 = explode(' ', $value->getDatePaie())[1];
            $tabHoraire[$value->getId()] = $this->getDoctrine()->getRepository(Horaire::class)->getGeneraleTime($value->getUtilisateur()->getUid(), $mois2, $annee2);
            
        }

        $paiesArray = [];
        foreach ($paies as $value) {
            $paiesArray[] = [
                "id"=>$value->getId(),
                "date_paie"=>$value->getDatePaie(),
                "document_file"=>"/uploads/paies/".$value->getDocumentFile(),
                "utilisateur"=>[
                    "id"=>$value->getUtilisateur()->getUid(),
                    "firstname"=>$value->getUtilisateur()->getFirstname(),
                    "lastname"=>$value->getUtilisateur()->getLastname(),
                ],
                "heure_sup_1"=>$value->getHeureSup1(),
                "heure_sup_2"=>$value->getHeureSup2(),
                "heure_normale"=>$value->getHeureNormale(),
                "trajet"=>$value->getTrajet(),
                "panier"=>$value->getPanier(),
                "cout_global"=>$value->getCoutGlobal(),
                "heures"=>isset($tabHoraire[$value->getId()]) ? $tabHoraire[$value->getId()] : "",
                "tx_horaire"=>$value->getTxHoraire(),
                "tx_moyen"=>$value->getTxMoyen(),
                "tx_charge"=>(100 - (($value->getSalaireNet() / $value->getCoutGlobal())*100)),
                "salaire_net"=>$value->getSalaireNet(),
                "conges_paye"=>$value->getCongesPaye(),
            ];
        }

        return new JsonResponse([
            'status'=>'success',
            'paies' => $paiesArray,
        ]);

    }

    /**
     * @Route("/checking-data-export", name="checking_data_export")
     */
    public function checkExport(Request $request)
    {
        
        if($request->get('document_id')){
            return JsonResponse([
                'status'=>'error',
                'message'=>'Veuillez préciser l\'id du document',
            ]);
        }

        $url = $this->global_s->constructUrl($this->global_s->getQueue('paie'), $request->get('document_id'));
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
                }
                if($children->schema_id == "sender_name"){
                    $sender_name = strtolower($children->value);
                    $sender_name = str_replace("monsieur ", "", $sender_name);
                    $sender_name = str_replace("madame ", "", $sender_name);

                    $utilisateur = $this->utilisateurRepository->getOneLikeName($sender_name);

                    if(!is_null($utilisateur))
                        $fichePaie->setUtilisateur($utilisateur);
                    else{
                        $datas = [
                            'status'=>'error',
                            'issue'=>'utilisateur',
                            'sender_name'=>$children->value,
                            "message"=>"L'utilisateur ".$children->value." n'existe pas"
                        ];
                        return new JsonResponse($datas);
                    }
                }
            }
        }

        if(is_null($fichePaie->getDatePaie())){
            $fichePaie->setDatePaie("");
        }

        if(!is_null($fichePaie->getUtilisateur())){
            $em = $this->getDoctrine()->getManager();
            $coutGlobal = (is_null($fichePaie->getCoutGlobal())) ? 0 : $fichePaie->getCoutGlobal();
            $totalHeureGenerale = $em->getRepository(Horaire::class)->getGeneraleHourFictif($fichePaie->getUtilisateur()->getUid());
            $tx_horaire = ($totalHeureGenerale != 0) ? $coutGlobal/$totalHeureGenerale : 0;
            $fichePaie->setTxHoraire($tx_horaire);
        }
        
        $datas = [
            'status'=>"success",
            "message"=>"",
            'fichePaie' => $fichePaie, 
            'annotations'=>$annotations,
        ];
        
        $response = new JsonResponse($datas);
    }

    /**
     * @Route("/send-fiche-paie", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"passage_new"},
     *     description="Envoie une fiche de paie precise",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="token for Authentification",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Invalid JSON",
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Erreur",
     *     ),
     * )
     */
    public function sendFichePaie(Request $request, MailerInterface $mailer)
    {
        $user = $this->getUser();

        $error = null; $customMailer = null;
        try{
            $customMailer = $this->global_s->initEmailTransport($user->getEntreprise()->getId());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if(!is_null($customMailer) && is_null($error)){

            $data = json_decode($request->getContent(), true);
            $fichePaie = $this->getDoctrine()->getRepository(Paie::class)->findByOnlyTabId($data['list-fiche-id']);
            
            $is_send = 0;
            
            $entreprise = $user->getEntreprise();
            $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
            $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();
                        
            foreach ($fichePaie as $value) {
                if(filter_var($value['email'], FILTER_VALIDATE_EMAIL) && !is_null($value['document_file'])){
                    $is_send = 1;
                    $email = (new Email())
                        ->from(new Address($sender_email, $sender_name))
                        ->to($value['email'])
                        ->subject('Sujet Fiche de Paie '.$value['datePaie'].' - Fmda')
                        ->html('Bonjour '.$value['firstname'].' '.$value['lastname'].', Vous trouverez ci-joint votre fiche de paie.')
                        ->attachFromPath('uploads/paies/'.$value['document_file']);
                    
                        try {
                            $send = $customMailer->send($email);
                        } catch (\Exception $ex) { $error = $ex->getMessage(); }

                }
            }

            if($is_send){
                return new JsonResponse(array('status' => 200, 'response'=>"Fiches de paies envoyées avec success"), Response::HTTP_OK);
            }
            else{
                return new JsonResponse(array('status' => 500, 'response'=>"Aucune fiche de paie n\'a été envoyée"), Response::HTTP_BAD_REQUEST);
            }

        }
        else{
            if(!is_null($error)){
                return new JsonResponse(array('status' => 500, 'response'=>"Un probleme a été rencontré lors de l envoie de ma fiche"), Response::HTTP_BAD_REQUEST);
            }
            else{
                return new JsonResponse(array('status' => 500, 'response'=>"Veuillez configurer les informations d'envoie de mail de cette entreprise"), Response::HTTP_BAD_REQUEST);
            }
        }

    }

}
