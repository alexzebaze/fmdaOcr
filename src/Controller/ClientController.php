<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Client;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Entity\Vente;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Form\ClientType;
use App\Service\GlobalService;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @Route("/client", name="client_")
 */
class ClientController extends AbstractController
{
    use CommunTrait;
    private $global_s;
    private $session;
    private $params;

    public function __construct(ParameterBagInterface $params, GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
        $this->params = $params;
    }

    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function index(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $clients = $entityManager->getRepository(Client::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>'client', 'display'=>true]); 
        
        $adapter = new ArrayAdapter($clients);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(100000);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        


        $page = $entityManager->getRepository(Page::class)->findOneBy(['cle'=>"CLIENT"]);
        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }
        
        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }

        return $this->render('client/index.html.twig', [
            'pager' => $pager,
            'clients' => $clients,
            'columns'=>$columns,
            'columnsVisibileId'=>$columnsVisibileIdArr,
            'tabColumns'=>$this->global_s->getTypeFields()
        ]);
    }

    /**
     * @Route("/masquer", name="masquer", methods={"POST"})
     */
    public function masquerClient(Request $request){
        $tabClient = explode('-', $request->request->get('list-client-check'));

        $entityManager = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);

        $entityManager->getRepository(Client::class)->hideClient($tabClient);
        $this->addFlash('success', "Client masqués");

        return $this->redirectToRoute('client_list');
    }

    /**
     * @Route("/add", name="add")
     */
    public function new(Request $request)
    {
        $client = new Client();
        $client->setDatecrea(new \DateTime());
        $client->setDatemaj(new \DateTime());

        $form = $this->createForm(ClientType::class, $client);

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                
                $entityManager = $this->getDoctrine()->getManager();

                /** @var UploadedFile $logo */
                $uploadedFile = $form['logo']->getData();
                if ($uploadedFile){
                    $dir = $this->params->get('kernel.project_dir') . "/public/uploads/logo_client/";
                    try {
                        if (!is_dir($dir)) {
                            mkdir($dir, 0777, true);
                        }
                    } catch (FileException $e) {}

                    $newFilename = $this->global_s->saveImage($uploadedFile, "/public/uploads/logo_client/");
                    $client->setLogo($newFilename);
                }

                if ($uploadedFile){
                    $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/client/cni/";
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

                    $client->setCni($newFilename);
                }

                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $client->setEntreprise($entreprise);
                
                $client->setNom(strtoupper($client->getNom()));
                $client->setType('client');
                $entityManager->persist($client);
                $entityManager->flush();                

                return $this->redirectToRoute('client_list');
            }
        }

        return $this->render('client/add.html.twig', [
            'form' => $form->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Route("/get-client-byListId", name="get_by_listId")
     */
    public function getClientByListId(Request $request){
        $tabClient = explode(',' , $request->request->get('list_client'));

        $clients = $this->getDoctrine()->getRepository(Client::class)->getByTabClient2($tabClient);

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('logement/list_acquereur_tmp.html.twig', [
                'clients' => $clients,
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/{clientId}/edit", name="edit")
     */
    public function edit(Request $request, $clientId)
    {
        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $client->setDatemaj(new \DateTime());
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logo */
            $uploadedFile = $form['logo']->getData();
            if ($uploadedFile){
                $dir = $this->params->get('kernel.project_dir') . "/public/uploads/logo_client/";
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                } catch (FileException $e) {}

                $newFilename = $this->global_s->saveImage($uploadedFile, "/public/uploads/logo_client/");
                $client->setLogo($newFilename);
            }

            $uploadedFile = $form["cni"]->getData();
            if ($uploadedFile){
                $dir = $this->params->get('kernel.project_dir') . "/public/uploads/client/cni/";
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

                $client->setCni($newFilename);
            }

            $client->setNom(strtoupper($client->getNom()));
            
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('client_list');
        }

        return $this->render('client/edit.html.twig', [
            'form' => $form->createView(),
            'client' => $client
        ]);
    }


    /**
     * @Route("/{clientId}/delete", name="delete")
     */
    public function delete(Request $request, $clientId)
    {
        $counVente = $this->getDoctrine()->getRepository(Vente::class)->count(['client'=>$clientId]);

        $this->get('session')->getFlashBag()->clear();
        if($counVente > 0){
            $this->addFlash('error', "Ce client est lié à (".$counVente.") facture et(ou) devis...");
            return $this->redirectToRoute('client_list');
        }

        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $entityManager = $this->getDoctrine()->getManager();

        try {
            $entityManager->remove($client);
            $entityManager->flush();
            $this->addFlash('success', "Suppression effectuée avec succès");
        } catch (\Exception $e) {
            $this->addFlash('error', "Vous ne pouvez supprimer cet element s'il est lié à d'autres elements");
        }
        
        return $this->redirectToRoute('client_list');
    }

    /**
     * @Route("/get-by-id", name="get_by_chantier")
     */
    public function getByChantier(Request $request)
    {
        $chantier_id = $request->query->get('chantier_id');
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantier_id);
        $clients = $this->getDoctrine()->getRepository(Chantier::class)->findBy(['chantier'=>$chantier->getChantierId(), 'type'=>'client']);
        $clientsArr = [];
        foreach ($clients as $value) {
            $clientsArr[] = ['id'=>$value->getId(), 'nom'=>$value->getNom()];
        }

        return new Response(json_encode(['status'=>200, 'clients'=> $clientsArr]));
    }    

    /**
     * @Route("/{id}/delete-document/{type_document}", name="delete_document")
     */
    public function deleteDocument(Request $request, $id, $type_document): Response
    {
        $client =$this->getDoctrine()
                ->getRepository(Client::class)
                ->find($id);

        switch ($type_document) {
            case 'cni':
                $client->setCni(null);
                break;
            default:
                break;
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
        
        return $this->redirectToRoute('client_edit', ['clientId'=>$id]);
    }

}
