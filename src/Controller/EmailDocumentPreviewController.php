<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\EmailDocumentPreview;
use App\Entity\Entreprise;
use App\Entity\Passage;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UtilisateurRepository;
use GuzzleHttp\Client;

/**
 * @Route("/document/preview")
 */
class EmailDocumentPreviewController extends Controller
{
    private $global_s;
    private $session;
    private $utilisateurRepository;

    public function __construct(GlobalService $global_s, SessionInterface $session, UtilisateurRepository $utilisateurRepository){
        $this->global_s = $global_s;
        $this->session = $session;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    /**
     * @Route("/list/{dossier}", name="email_document_preview_list", methods={"GET"})
     */
    public function index($dossier)
    {
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
                return new Response("Cette facture n'est rattaché à aucun dossier");
                break;
        }


        $dir = $this->get('kernel')->getProjectDir() .'/public'. $path;
        try{
            $entreprise =  $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            // if(is_null($this->global_s->loadEmailDocument($dossier, $dir, $entreprise))){
            //     $this->addFlash('warning', "Veuillez verifier la configuration IMPA pour ".$dossier);
            // }
        } catch (\Exception $e) {
            $this->addFlash("error", $e->getMessage());
            $error = $e->getMessage();
            //return new Response($error);
        }

    	$documents = $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->findBy(['dossier'=>$dossier, 'entreprise'=>$this->session->get('entreprise_session_id')], ['id'=>'DESC']);

        $tabPassage = [];
        if($dossier == "bon_livraison"){
            foreach ($documents as $key => $value) {
                if(!is_null($value->getPassageId())){
                    $tabPassage[$value->getPassageId()] = $this->getDoctrine()->getRepository(Passage::class)->find($value->getPassageId());
                }
            }
        }

        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true);
        return $this->render('email_document_preview/index.html.twig', [
        	'documents'=>$documents,
            "path"=>$path,
            "dossier"=>$dossier,
            "tabPassage"=>$tabPassage,
            'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs),
        ]);
    }

    /**
     * @Route("/list-xhr", name="email_document_preview_list_xhr", methods={"GET"})
     */
    public function getDocumentEmailXhr(Request $request)
    {

        $client = new Client([
            'base_uri' => $this->global_s->getBASEURL_OCRAPI(),
        ]);

        $response = $client->request('GET', 'ocrapi/load-email-document', [
            'query' => [
                    'dossier' => $request->query->get('dossier'),
                    'entreprise' => $this->session->get('entreprise_session_id')
                ]
            ]);

        $body = $response->getBody()->getContents();

        return new Response($body);





        // $dossier = $request->query->get('dossier');
        // switch ($dossier) {
        //     case 'facturation':
        //         $path = "/uploads/achats/facturation/";
        //         break;
        //     case 'bon_livraison':
        //         $path =  "/uploads/factures/";
        //         break;
        //     case 'devis_pro':
        //         $path = "/uploads/devis/";
        //         break;
        //     case 'facture_client':
        //         $path = "/uploads/clients/factures/";
        //         break;
        //     case 'devis_client':
        //         $path = "/uploads/devis/";
        //         break;
        //     case 'paie':
        //         $path = "/uploads/paies/";
        //         break;

        //     default:
        //         return new Response(json_encode(array("status"=>500, "message"=>"Cette facture n'est rattaché à aucun dossier")));
        //         break;
        // }


        // $dir = $this->get('kernel')->getProjectDir() .'/public'. $path;
        // try{
        //     $entreprise =  $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        //     if(is_null($this->global_s->loadEmailDocument($dossier, $dir, $entreprise))){
        //         return new Response(json_encode(array("status"=>500, "message"=>"Veuillez verifier la configuration IMAP pour ".$dossier)));
        //     }
        // } catch (\Exception $e) {
        //     return new Response(json_encode(array("status"=>500, "message"=>$e->getMessage())));
        // }

        // $documents = $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->findBy(['dossier'=>$dossier, 'entreprise'=>$this->session->get('entreprise_session_id')], ['id'=>'DESC']);


        // $tabPassage = [];
        // if($dossier == "bon_livraison"){
        //     foreach ($documents as $key => $value) {
        //         if(!is_null($value->getPassageId())){
        //             $tabPassage[$value->getPassageId()] = $this->getDoctrine()->getRepository(Passage::class)->find($value->getPassageId());
        //         }
        //     }
        // }

        // $datas = ['status'=>200, "message"=>""];
        // $datas['nb_document'] = count($documents);
        // $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true);
        // $datas['content'] = $this->renderView('email_document_preview/content_list.html.twig', [
        //         'documents'=>$documents,
        //         "path"=>$path,
        //         "dossier"=>$dossier,
        //         "tabPassage"=>$tabPassage,
        //         'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs)
        //     ]);
        
        // $response = new Response(json_encode($datas));
        // return $response;
    }

    /**
     * @Route("/detail/{id}", name="email_document_preview_detail")
     */
    public function detail($id)
    {
    	$document = $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($id);

        if(is_null($document)){
            return new Response("Ce document n'existe plus");
        }

    	$this->session->set('email_facture_load_id', $id);

    	switch ($document->getDossier()) {
    		case 'facturation':
    			return $this->redirectToRoute('facturation_import_document');
    			break;
    		case 'bon_livraison':
    			return $this->redirectToRoute('bon_livraison_import_document');
    			break;
            case 'devis_pro':
                return $this->redirectToRoute('devis_pro_import_document');
                break;
            case 'facture_client':
                return $this->redirectToRoute('facture_client_import_document');
                break;
            case 'devis_client':
                return $this->redirectToRoute('devis_client_import_document');
                break;
            case 'paie':
                return $this->redirectToRoute('paie_import_document');
                break;

    		default:
    			return new Response("Cette facture n'est rattaché à aucun dossier");
    			break;
    	}

    }


    /**
     * @Route("/delete/{id}", name="email_document_preview_delete", methods={"DELETE"})
     */
    public function delete(Request $request, EmailDocumentPreview $document): Response
    {   
        $dossier = $document->getDossier();
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($document);
            $entityManager->flush();
        }

        $this->addFlash("success", "Suppression effectué avec success");

        return $this->redirectToRoute('email_document_preview_list', ['dossier'=>$dossier]);
    }


    
    /**
     * @Route("/deletedocument/{id}", name="email_document_preview_delete2", methods={"GET"})
     */
    public function delete2(Request $request, EmailDocumentPreview $document): Response
    {   
        $dossier = $document->getDossier();
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($document);
        $entityManager->flush();
        $this->addFlash("success", "Suppression effectué avec success");

        return $this->redirectToRoute('email_document_preview_list', ['dossier'=>$dossier]);
    }
}

	




