<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Client;
use App\Entity\Chantier;
use App\Entity\RapportProspect;
use App\Entity\Entreprise;
use App\Form\ClientType;
use App\Service\GlobalService;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @Route("/prospect", name="prospect_")
 */
class ProspectController extends AbstractController
{
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
        $prospects = $entityManager->getRepository(Client::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>"prospect"]); 
        
        $adapter = new ArrayAdapter($prospects);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(100000);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        

        return $this->render('prospect/index.html.twig', [
            'pager' => $pager,
            'prospects' => $prospects
        ]);
    }

    /**
     * @Route("/add", name="add")
     */
    public function new(Request $request)
    {
        $prospect = new Client();
        $prospect->setDatecrea(new \DateTime());
        $prospect->setDatemaj(new \DateTime());

        $form = $this->createForm(ClientType::class, $prospect);

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                
                $entityManager = $this->getDoctrine()->getManager();

                /** @var UploadedFile $logo */
                $uploadedFile = $form['logo']->getData();

                if ($uploadedFile){
                    $dir = $this->params->get('kernel.project_dir') . "/public/uploads/logo_prospect/";
                    try {
                        if (!is_dir($dir)) {
                            mkdir($dir, 0777, true);
                        }
                    } catch (FileException $e) {}

                    $newFilename = $this->global_s->saveImage($uploadedFile, "/public/uploads/logo_prospect/");
                    $prospect->setLogo($newFilename);
                }

                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $prospect->setEntreprise($entreprise);
                
                $prospect->setNom(strtoupper($prospect->getNom()));
                $prospect->setType('prospect');
                $entityManager->persist($prospect);

                if($request->request->get('rapport')){
                    $rapport = new RapportProspect();
                    $rapport->setMessage($request->request->get('rapport'));
                    $rapport->setProspect($prospect);
                    $entityManager->persist($rapport);
                }
                
                $entityManager->flush();                

                return $this->redirectToRoute('prospect_list');
            }
        }

        return $this->render('prospect/add.html.twig', [
            'form' => $form->createView(),
            'prospect' => $prospect
        ]);
    }

    /**
     * @Route("/{prospectId}/edit", name="edit")
     */
    public function edit(Request $request, $prospectId)
    {
        $prospect = $this->getDoctrine()->getRepository(Client::class)->find($prospectId);
        $prospect->setDatemaj(new \DateTime());
        $form = $this->createForm(ClientType::class, $prospect);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logo */
            $uploadedFile = $form['logo']->getData();
            if ($uploadedFile){
                $dir = $this->params->get('kernel.project_dir') . "/public/uploads/logo_prospect/";
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                } catch (FileException $e) {}

                $newFilename = $this->global_s->saveImage($uploadedFile, "/public/uploads/logo_prospect/");
                $prospect->setLogo($newFilename);
            }
            $prospect->setNom(strtoupper($prospect->getNom()));
            
            $entityManager = $this->getDoctrine()->getManager();

            if($request->request->get('rapport')){
                $rapport = new RapportProspect();
                $rapport->setMessage($request->request->get('rapport'));
                $rapport->setProspect($prospect);
                $entityManager->persist($rapport);
                $entityManager->flush();

                $this->get('session')->getFlashBag()->clear();
                $this->addFlash('success', "Rapport enregistré");
                return $this->redirectToRoute('prospect_edit', ['prospectId'=>$prospectId]);
            }
            
            $entityManager->flush();

            return $this->redirectToRoute('prospect_list');
        }

        return $this->render('prospect/edit.html.twig', [
            'form' => $form->createView(),
            'prospect' => $prospect
        ]);
    }


    /**
     * @Route("/{prospectId}/delete", name="delete")
     */
    public function delete(Request $request, $prospectId)
    {
        $prospect = $this->getDoctrine()->getRepository(Client::class)->find($prospectId);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($prospect);
        $entityManager->flush();

        return $this->redirectToRoute('prospect_list');
    }

    /**
     * @Route("/save-rapport", name="save_rapport")
     */
    public function saveRapport(Request $request)
    {
        $prospectId = $request->request->get('prospect_id');
        $message = $request->request->get('rapport');
        $prospect = $this->getDoctrine()->getRepository(Client::class)->find($prospectId);
        
        $entityManager = $this->getDoctrine()->getManager();
        $rapport = new RapportProspect();
        $rapport->setMessage($message);
        $rapport->setProspect($prospect);
        $entityManager->persist($rapport);
        $entityManager->flush();

        $datas = [
            'status'=>200, 
            "message"=>"",
            'datas'=> $this->renderView('prospect/rapport.html.twig', [
                'rapports' => $prospect->getRapport()
            ])
        ];
        
        return new Response(json_encode($datas));
    }
    
    /**
     * @Route("/get-by-id", name="get_by_chantier")
     */
    public function getByChantier(Request $request)
    {
        $chantier_id = $request->query->get('chantier_id');
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantier_id);
        $prospects = $this->getDoctrine()->getRepository(Chantier::class)->findBy(['chantier'=>$chantier->getChantierId(), 'type'=>'prospect']);
        $prospectsArr = [];
        foreach ($prospects as $value) {
            $prospectsArr[] = ['id'=>$value->getId(), 'nom'=>$value->getNom()];
        }

        return new Response(json_encode(['status'=>200, 'prospects'=> $prospectsArr]));
    }    

}
