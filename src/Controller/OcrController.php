<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use Doctrine\ORM\EntityRepository;
use App\Entity\Achat;
use App\Entity\Passage;
use App\Entity\PreferenceField;
use App\Entity\Vente;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Entity\Fields;
use App\Entity\IAZone;
use App\Entity\OcrField;
use App\Entity\ModelDocument;
use App\Entity\Lot;
use App\Entity\TmpOcr;
use App\Entity\EmailDocumentPreview;
use App\Form\AchatType;
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
use App\Entity\Fournisseurs;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\Paie;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Repository\ChantierRepository;
use App\Repository\PassageRepository;
use App\Repository\FournisseursRepository;
use App\Repository\AchatRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\UtilisateurRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Service\GlobalService;
use App\Service\FactureService;
use Carbon\Carbon;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use \ConvertApi\ConvertApi;


use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class OcrController extends Controller
{
 
    use CommunTrait;
    private $global_s;
    private $passage_s;
    private $factureService;
    private $chantierRepository;
    private $achatRepository;
    private $entrepriseRepository;
    private $fournisseurRepository;
    private $utilisateurRepository;
    private $session;

    public function __construct(GlobalService $global_s, ChantierRepository $chantierRepository, AchatRepository $achatRepository, EntrepriseRepository $entrepriseRepository,FournisseursRepository $fournisseurRepository, SessionInterface $session, PassageRepository $passageRepository, FactureService $factureService, UtilisateurRepository $utilisateurRepository){
        $this->global_s = $global_s;
        $this->factureService = $factureService;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->chantierRepository = $chantierRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->achatRepository = $achatRepository;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->passageRepository = $passageRepository;
        $this->session = $session;
    }

    /**
     * @Route("/ocrapi/load-email-document", name="ocrapi_load_email_document", methods={"GET"})
     */
    public function loadEmailDocument(Request $request)
    {
        $dossier = $request->query->get('dossier');
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($request->query->get('entreprise'));

        $this->session->set('entreprise_session_id', $entreprise->getId());

        switch ($dossier) {
            case 'facturation':
                $path = "/uploads/achats/facturation/";
                break;
            case 'bon_livraison':
                $path =  "/uploads/factures/";
                break;
            case 'devis_pro':
                $path = "/uploads/devis/";
                break;
            case 'facture_client':
                $path = "/uploads/clients/factures/";
                break;
            case 'devis_client':
                $path = "/uploads/devis/";
                break;
            case 'paie':
                $path = "/uploads/paies/";
                break;

            default:
                return new Response(json_encode(array("status"=>500, "message"=>"Cette facture n'est rattaché à aucun dossier")));
                break;
        }

        $dir = $this->get('kernel')->getProjectDir() .'/public'. $path;
        if(is_null($this->global_s->loadEmailDocument($dossier, $dir, $entreprise))){
            return new Response(json_encode(array("status"=>500, "message"=>"Veuillez verifier la configuration IMAP pour ".$dossier)));
        }
        

        $documents = $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->findBy(['dossier'=>$dossier, "entreprise"=>$entreprise], ['id'=>'DESC']);


        $tabPassage = [];
        if($dossier == "bon_livraison"){
            foreach ($documents as $key => $value) {
                if(!is_null($value->getPassageId())){
                    $tabPassage[$value->getPassageId()] = $this->getDoctrine()->getRepository(Passage::class)->find($value->getPassageId());
                }
            }
        }

        $datas = ['status'=>200, "message"=>""];
        $datas['nb_document'] = count($documents);
        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true);
        $datas['content'] = $this->renderView('email_document_preview/content_list.html.twig', [
                'documents'=>$documents,
                "path"=>$path,
                "dossier"=>$dossier,
                "tabPassage"=>$tabPassage,
                'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs)
            ]);

        $response = new Response(json_encode($datas));
        return $response;
           
    }   

    /**
     * @Route("/ocrapi/launchia", name="ocrapi_lauchia", methods={"POST"})
     */
    public function launchIa(Request $request){

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($request->request->get('entreprise'));

        $this->session->set('entreprise_session_id', $entreprise->getId());

        $documentFile = $request->request->get('document_file');
        $dossier = $request->request->get('dossier');
        $dirDocumentFile = $this->get('kernel')->getProjectDir() . $request->request->get('dir_document_file'); // document avec chemin associé

        if($dossier == "bon_livraison" || $dossier == "facturation" || $dossier == "devis_pro"){
            $entity = new Achat();
            $entity->setType($dossier);
        }
        elseif($dossier == "facture_client" || $dossier == "devis_client"){
            $entity = new Vente();
            $entity->setType($dossier);
        }
        elseif($dossier == "paie"){
            $entity = new Paie();
        }

        $datasResult = $this->global_s->lancerIa($documentFile, $entity, $dossier, $dirDocumentFile);

        $datasResult[$dossier] = $datasResult[$dossier];
        $response = new Response(json_encode($datasResult));
        return $response;
    }
}



