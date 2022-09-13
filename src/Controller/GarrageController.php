<?php

namespace App\Controller;

use App\Entity\Garrage;
use App\Entity\Entreprise;
use App\Form\GarrageType;
use App\Repository\GarrageRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

/**
 * @Route("/garrage")
 */
class GarrageController extends AbstractController
{

    private $utilisateurRepository;
    private $garrageRepository;
    private $session;
    private $global_s;

    public function __construct(SessionInterface $session, GarrageRepository $garrageRepository, UtilisateurRepository $utilisateurRepository, GlobalService $global_s){
        $this->global_s = $global_s;
        $this->session = $session;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->garrageRepository = $garrageRepository;
    }

    /**
     * @Route("/", name="garrage_index", methods={"GET"})
     */
    public function index(GarrageRepository $garrageRepository): Response
    {
        $allUtilisateursEntrep = $this->utilisateurRepository->getUserByEntreprise();
        return $this->render('garrage/index.html.twig', [
            'garrages' => $garrageRepository->findAll(),
            'allUtilisateursEntrep'=>$this->global_s->getUserByMiniature($allUtilisateursEntrep)
        ]);
    }

    /**
     * @Route("/new", name="garrage_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $garrage = new Garrage();
        $form = $this->createForm(GarrageType::class, $garrage);
        $form->handleRequest($request);

        $entityManager = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {

            if(!is_null($this->session->get('entreprise_session_id'), null)){
                $entreprise = $entityManager->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                $garrage->setEntreprise($entreprise);
            }
            
            $entityManager->persist($garrage);
            $entityManager->flush();

            return $this->redirectToRoute('garrage_index');
        }

        return $this->render('garrage/new.html.twig', [
            'garrage' => $garrage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="garrage_show", methods={"GET"})
     */
    public function show(Garrage $garrage): Response
    {
        return $this->render('garrage/show.html.twig', [
            'garrage' => $garrage,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="garrage_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Garrage $garrage): Response
    {
        $form = $this->createForm(GarrageType::class, $garrage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('garrage_index');
        }

        return $this->render('garrage/edit.html.twig', [
            'garrage' => $garrage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="garrage_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Garrage $garrage): Response
    {
        if ($this->isCsrfTokenValid('delete'.$garrage->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($garrage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('garrage_index');
    }
}
