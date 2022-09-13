<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\Fabricant;
use App\Form\FabricantType;
use App\Repository\FabricantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * @Route("/fabricant")
 */
class FabricantController extends AbstractController
{

    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="fabricant_index", methods={"GET"})
     */
    public function index(FabricantRepository $fabricantRepository): Response
    {
        $fabricants = $this->getDoctrine()->getRepository(Fabricant::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        return $this->render('fabricant/index.html.twig', [
            'fabricants' => $fabricants,
        ]);
    }

    /**
     * @Route("/new", name="fabricant_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $fabricant = new Fabricant();
        $form = $this->createForm(FabricantType::class, $fabricant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($fabricant);

            /** @var UploadedFile $logo */
            $uploadedFile = $form['logo']->getData();
            if ($uploadedFile){
                $logo = $this->global_s->saveImage($uploadedFile, '/public/uploads/fabricant/');
                $fabricant->setLogo($logo);
            }

            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $fabricant->setEntreprise($entreprise);

            $entityManager->flush();

            return $this->redirectToRoute('fabricant_index');
        }

        return $this->render('fabricant/new.html.twig', [
            'fabricant' => $fabricant,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/fabricant-xhr/form", name="fabricant_load_form_xhr", methods={"GET"})
     */
    public function loadFormXhr(Request $request): Response
    {
        $fabricant_id = $request->query->get('fabricant_id');
        if((int)$fabricant_id > 0){
            $fabricant = $this->getDoctrine()->getRepository(Fabricant::class)->find($fabricant_id);   
        }
        else{
            $fabricant = new Fabricant(); 
        }
        $form = $this->createForm(FabricantType::class, $fabricant);

        $datas = ['status'=>200, "message"=>""];
        $datas['content'] = $this->renderView('fabricant/_form.html.twig', [
            'form' => $form->createView(),
            'fabricant'=>$fabricant
        ]);
        
        $response = new Response(json_encode($datas));

        return $response;
    }

    /**
     * @Route("/fabricant-xhr", name="fabricant_load_xhr", methods={"GET"})
     */
    public function loadFabricanXhr(Request $request): Response
    {
        $fabricants = $this->getDoctrine()->getRepository(Fabricant::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $datas = ['status'=>200, "message"=>""];
        $datas['content'] = $this->renderView('fabricant/list_xhr.html.twig', [
            'fabricants'=>$fabricants
        ]);
        
        $response = new Response(json_encode($datas));

        return $response;
    }

    /**
     * @Route("/fabricant-xhr/edit", name="fabricant_edit_xhr", methods={"POST"})
     */
    public function editXhr(Request $request): Response
    {
        if($request->request->get('fabricant_id') == ""){
            $fabricant = new Fabricant();
        }
        else{
            $fabricant = $this->getDoctrine()->getRepository(Fabricant::class)->find($request->request->get('fabricant_id'));
        }
        
        $form = $this->createForm(FabricantType::class, $fabricant);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();

        if($request->request->get('fabricant_id') == ""){
            $entityManager->persist($fabricant);
        }

        /** @var UploadedFile $logo */
        $uploadedFile = $form['logo']->getData();
        if ($uploadedFile){
            $logo = $this->global_s->saveImage($uploadedFile, '/public/uploads/fabricant/');
            $fabricant->setLogo($logo);
        }

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $fabricant->setEntreprise($entreprise);

        $entityManager->flush();

        $fabricants = $this->getDoctrine()->getRepository(Fabricant::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $fabricantsArr = [];
        foreach ($fabricants as $value) {
            $fabricantsArr [] = ['id'=>$value->getId(), 'nom'=>$value->getNom()];
        }

        $datas = [
            'status'=>200, 
            "message"=>"", 
            'fabricants'=> $fabricantsArr,
            'fabricant_id'=>$fabricant->getId()
        ];
        
        $response = new Response(json_encode($datas));

        return $response;
    }

    /**
     * @Route("/{id}", name="fabricant_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Fabricant $fabricant): Response
    {
        if ($this->isCsrfTokenValid('delete'.$fabricant->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($fabricant);
            $entityManager->flush();
        }

        return $this->redirectToRoute('fabricant_index');
    }

    /**
     * @Route("/edit/{id}", name="fabricant_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $id): Response
    {
        $fabricant = $this->getDoctrine()->getRepository(Fabricant::class)->find($id);
        $form = $this->createForm(FabricantType::class, $fabricant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $logo */
            $uploadedFile = $form['logo']->getData();
            if ($uploadedFile){
                $logo = $this->global_s->saveImage($uploadedFile, '/public/uploads/fabricant/');
                $fabricant->setLogo($logo);
            }
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $fabricant->setEntreprise($entreprise);

            $this->getDoctrine()->getManager()->flush();

            // if($request->query->get('page') && $request->query->get('page') == "article"){
            //     return $this->redirectToRoute('fabricant_index');
            // }
            
            return $this->redirectToRoute('fabricant_index');
        }

        return $this->render('fabricant/edit.html.twig', [
            'fabricant' => $fabricant,
            'form' => $form->createView(),
        ]);
    }
}
