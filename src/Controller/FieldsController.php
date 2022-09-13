<?php

namespace App\Controller;

use App\Entity\Fields;
use App\Entity\FieldsEntreprise;
use App\Entity\Page;
use App\Entity\Entreprise;
use App\Form\FieldsType;
use App\Repository\FieldsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/fields")
 */
class FieldsController extends AbstractController
{

    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="fields_index", methods={"GET"})
     */
    public function index(FieldsRepository $fieldsRepository): Response
    {
        return $this->render('fields/index.html.twig', [
            'fields' => $fieldsRepository->findAll(),
            'colonnes'=>$this->global_s->getTypeFields()
        ]);
    }


    /**
     * @Route("/update-toggle-column", name="update_toggle_column", methods={"POST"})
     */
    public function updateToggleColumn(Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $configs = array_keys($request->request->get('configs'));
        $pageCle = $request->request->get('page');

        $page = $this->getDoctrine()->getRepository(Page::class)->findOneBy(['cle'=>$pageCle]);

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        
        $fieldsVIsibleOld = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$entreprise]);
        $fieldsVIsibleNew = $this->getDoctrine()->getRepository(Fields::class)->findByTabId($configs);

        foreach ($fieldsVIsibleOld as $value) {
            if(!in_array($value, $fieldsVIsibleNew)){
                $entityManager->remove($value);
            }
        }
        foreach ($fieldsVIsibleNew as $value) {
            if(!in_array($value, $fieldsVIsibleOld)){
                $newVisible = new FieldsEntreprise();
                $newVisible->setColonne($value);
                $newVisible->setPage($page);
                $newVisible->setEntreprise($entreprise);


                $entityManager->persist($newVisible);
            }
        }

        $entityManager->flush();


        return $this->redirectToRoute($request->request->get('redirect'));
    }

    /**
     * @Route("/new", name="fields_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $field = new Fields();
        $form = $this->createForm(FieldsType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($field);

            $tabPage = $request->request->get('pages');
            $tabPage = $tabPage ?? [];

            $pages = $this->getDoctrine()->getRepository(Page::class)->getByTabPageEntity($tabPage);  
            foreach ($pages as $value) {
                $field->addPage($value);
            }
     
            $entityManager->flush();

            return $this->redirectToRoute('fields_index');
        }

        $pages = $this->getDoctrine()->getRepository(Page::class)->findAll(); 
        return $this->render('fields/new.html.twig', [
            'field' => $field,
            'form' => $form->createView(),
            'pages'=>$pages,
        ]);
    }

    /**
     * @Route("/{id}", name="fields_show", methods={"GET"})
     */
    public function show(Fields $field): Response
    {
        return $this->render('fields/show.html.twig', [
            'field' => $field,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="fields_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Fields $field): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(FieldsType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $tabPage = $request->request->get('pages');
            $tabPage = $tabPage ?? [];

            $pages = $this->getDoctrine()->getRepository(Page::class)->getByTabPageEntity($tabPage); 
            $field->pageToRemove($tabPage);       

            foreach ($pages as $value) {
                $field->addPage($value);
            }

            $entityManager->persist($field);
            $entityManager->flush();

            return $this->redirectToRoute('fields_index');
        }

        $pages = $this->getDoctrine()->getRepository(Page::class)->findAll(); 
        return $this->render('fields/edit.html.twig', [
            'field' => $field,
            'form' => $form->createView(),
            'pages'=>$pages,
            'pagesField'=>$field->getPages(),
        ]);
    }

    /**
     * @Route("/{id}", name="fields_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Fields $field): Response
    {
        if ($this->isCsrfTokenValid('delete'.$field->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($field);
            $entityManager->flush();
        }

        return $this->redirectToRoute('fields_index');
    }
}
