<?php

namespace App\Controller;

use App\Entity\RossumConfig;
use App\Entity\Entreprise;
use App\Form\RossumConfigType;
use App\Repository\RossumConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GlobalService;

/**
 * @Route("/rossum/config")
 */
class RossumConfigController extends AbstractController
{
    private $global_s;
    private $session;
    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="rossum_config_index", methods={"GET"})
     */
    public function index(RossumConfigRepository $rossumConfigRepository): Response
    {
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        return $this->render('rossum_config/index.html.twig', [
            'rossum_configs' => $rossumConfigRepository->findBy(['entreprise'=>$entreprise]),
            'dossiers'=>$this->global_s->getRossumFolderReverse()
        ]);
    }

    /**
     * @Route("/new", name="rossum_config_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {      
        $rossumConfig = new RossumConfig();
        $form = $this->createForm(RossumConfigType::class, $rossumConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $rossumConfig->setEntreprise($entreprise);
            
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($rossumConfig);
            $entityManager->flush();

            return $this->redirectToRoute('rossum_config_index');
        }

        return $this->render('rossum_config/new.html.twig', [
            'rossum_config' => $rossumConfig,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="rossum_config_show", methods={"GET"})
     */
    public function show(RossumConfig $rossumConfig): Response
    {
        return $this->render('rossum_config/show.html.twig', [
            'rossum_config' => $rossumConfig,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="rossum_config_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, RossumConfig $rossumConfig): Response
    {
        $form = $this->createForm(RossumConfigType::class, $rossumConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('rossum_config_index');
        }

        return $this->render('rossum_config/edit.html.twig', [
            'rossum_config' => $rossumConfig,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="rossum_config_delete")
     */
    public function delete(Request $request, RossumConfig $rossumConfig): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($rossumConfig);
        $entityManager->flush();

        return $this->redirectToRoute('rossum_config_index');
    }
}
