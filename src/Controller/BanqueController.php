<?php

namespace App\Controller;

use App\Entity\Banque;
use App\Entity\Entreprise;
use App\Form\BanqueType;
use App\Repository\BanqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/banque")
 */
class BanqueController extends AbstractController
{
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="banque_index", methods={"GET"})
     */
    public function index(BanqueRepository $banqueRepository): Response
    {
        return $this->render('banque/index.html.twig', [
            'banques' => $banqueRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]),
        ]);
    }

    /**
     * @Route("/new", name="banque_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $banque = new Banque();
        $form = $this->createForm(BanqueType::class, $banque);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->saveInfoBanque($form, $request, $banque);

            return $this->redirectToRoute('banque_index');
        }

        return $this->render('banque/new.html.twig', [
            'banque' => $banque,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="banque_show", methods={"GET"})
     */
    public function show(Banque $banque): Response
    {
        return $this->render('banque/show.html.twig', [
            'banque' => $banque,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="banque_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Banque $banque): Response
    {
        $form = $this->createForm(BanqueType::class, $banque);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->saveInfoBanque($form, $request, $banque);

            return $this->redirectToRoute('banque_index');
        }

        return $this->render('banque/edit.html.twig', [
            'banque' => $banque,
            'form' => $form->createView(),
        ]);
    }

    public function saveInfoBanque($form, $request, $banque){
        $entityManager = $this->getDoctrine()->getManager();
        /** @var UploadedFile $logo */
        $uploadedFile = $form['logo']->getData();
        if ($uploadedFile){
            $logo = $this->global_s->saveImage($uploadedFile, '/public/uploads/banque/logo/');
            $banque->setLogo($logo);
        }

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $banque->setEntreprise($entreprise);

        $entityManager->persist($banque);
        $entityManager->flush();
    }

    /**
     * @Route("/{id}", name="banque_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Banque $banque): Response
    {
        if ($this->isCsrfTokenValid('delete'.$banque->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($banque);
            $entityManager->flush();
        }

        return $this->redirectToRoute('banque_index');
    }
}
