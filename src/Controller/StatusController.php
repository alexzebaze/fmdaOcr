<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\StatusRepository;
use App\Repository\EntrepriseRepository;
use App\Service\GlobalService;
use App\Entity\Status;
use App\Entity\Module;
use App\Entity\StatusModule;

class StatusController extends AbstractController
{
    private $global_s;
    private $statusRepository;
    private $entrepriseRepository;
    private $session;

    public function __construct(EntrepriseRepository $entrepriseRepository, GlobalService $global_s, StatusRepository $statusRepository, SessionInterface $session){
        $this->global_s = $global_s;
        $this->statusRepository = $statusRepository;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->session = $session;
    }

    /**
     * @Route("/status", name="status")
     */
    public function index()
    {
    	$status = $this->statusRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $modules = $this->getDoctrine()->getRepository(Module::class)->findAll();

        $statusMatrix = [];

        foreach ($status as $value) {
            $row = [];
            $row[] = $value;
            $modulesAssign = $this->toArray($value->getStatusModules());
            $modulesAssignArr = [];
            foreach ($modulesAssign as $value) {
                $modulesAssignArr[] = $value->getModule();
            }

            foreach ($modules as $val) {
                if(in_array($val, $modulesAssignArr))
                    $row[] = 'X';
                else
                    $row[] = "";
            }
            $statusMatrix[] = $row;
        }

        return $this->render('status/index.html.twig', [
            'modules' => $modules,
            'status' => $statusMatrix,
        ]);
    }

    public function toArray($objects){
        $arr = [];
        foreach ($objects as $value) {
            $arr[] = $value;
        }
        return $arr;
    }

    /**
     * @Route("/status/new", name="status_new")
     */
    public function new(Request $request)
    {
    	
    	if ($request->isMethod('POST')) {
    		$status = new Status();
	    	$em = $this->getDoctrine()->getManager();
	    	$status->setName($request->request->get('nom'));
	    	$status->setColor($request->request->get('couleur'));
	    	$status->setEntreprise($this->entrepriseRepository->find($this->session->get('entreprise_session_id')));

            if($request->request->get('modules')){
                $newModule = $request->request->get('modules');
                foreach ($newModule as $value) {
                    $statusModule = new StatusModule();
                    $statusModule->setModule($this->getDoctrine()->getRepository(Module::class)->find($value));
                    $statusModule->setStatus($status);
                    $status->addStatusModule($statusModule);
                }
            }

	    	$em->persist($status);
        	$em->flush(); 

        	return $this->redirectToRoute('status');
    	}

        $modules = $this->getDoctrine()->getRepository(Module::class)->findAll();

        return $this->render('status/new.html.twig', [
            "modules"=>$modules,
        ]);
    }

    /**
     * @Route("/status/edit/{id}", name="status_edit")
     */
    public function edit(Request $request, $id)
    {
        $status = $this->statusRepository->find($id);
        if ($request->isMethod('POST')) {
            $em = $this->getDoctrine()->getManager();
            $status->setName($request->request->get('nom'));
            $status->setColor($request->request->get('couleur'));

            foreach ($status->getStatusModules() as $value) {
                $em->remove($value);
            }
            $em->flush();

            if($request->request->get('modules')){
                $newModule = $request->request->get('modules');
                foreach ($newModule as $value) {
                    $statusModule = new StatusModule();
                    $statusModule->setModule($this->getDoctrine()->getRepository(Module::class)->find($value));
                    $statusModule->setStatus($status);
                    $status->addStatusModule($statusModule);
                }
            }
            $em->flush(); 
            return $this->redirectToRoute('status');
        }

        $modules = $this->getDoctrine()->getRepository(Module::class)->findAll();
        $modulesAssign = $this->getDoctrine()->getRepository(StatusModule::class)->findBy(['status'=>$status->getId()]);
        $modulesAssignArr = [];
        foreach ($modulesAssign as $value) {
            $modulesAssignArr[] = $value->getModule();
        }

        return $this->render('status/edit.html.twig', [
            'status'=>$status, 
            "modules"=>$modules,
            "modulesAssign"=>$modulesAssignArr,
        ]);
    }


    /**
     * @Route("/status/delete/{id}", name="status_delete")
     */
    public function delete(Request $request, $id): Response
    {
        $status = $this->statusRepository->find($id);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($status);
        $entityManager->flush();

        return $this->redirectToRoute('status');
    }
}