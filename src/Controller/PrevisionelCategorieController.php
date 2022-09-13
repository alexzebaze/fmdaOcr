<?php

namespace App\Controller;

use App\Entity\PrevisionelCategorie;
use App\Form\PrevisionelCategorieType;
use App\Repository\PrevisionelCategorieRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Entreprise;
use App\Entity\Chantier;
use App\Entity\Previsionel;
use App\Entity\Lot;
use App\Service\GlobalService;

/**
 * @Route("/previsionel/categorie")
 */
class PrevisionelCategorieController extends AbstractController
{
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="previsionel_categorie_index", methods={"GET"})
     */
    public function index(PrevisionelCategorieRepository $previsionelCategorieRepository): Response
    {
        return $this->render('previsionel_categorie/index.html.twig', [
            'previsionel_categories' => $previsionelCategorieRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')])
        ]);
    }

    /**
     * @Route("/remove-date-previsionel", name="remove_date_previsionel")
     */
    public function removeDatePrevisionel(Request $request): Response
    {
        $lotId = $request->query->get('lot_id');
        $chantierId = $request->query->get('chantier_id');

        $previsionel = $this->getDoctrine()->getRepository(Previsionel::class)->findOneBy(['lot'=> $lotId, 'chantier'=>$chantierId]);

        $entityManager = $this->getDoctrine()->getManager();        
        $previsionel->setDateDebut(null);
        $previsionel->setDateFin(null);
        $entityManager->persist($previsionel);
        $entityManager->flush();
       
        return new Response(json_encode(array('status'=>200, 'message'=>"mise à jour reussite")));
    }

    /**
     * @Route("/update-date-previsionel", name="update_date_previsionel")
     */
    public function updateDatePrevisionel(Request $request): Response
    {
        $lotId = $request->query->get('lot_id');
        $chantierId = $request->query->get('chantier_id');

        $previsionel = $this->getDoctrine()->getRepository(Previsionel::class)->findOneBy(['lot'=> $lotId, 'chantier'=>$chantierId]);

        if(is_null($previsionel))
            $previsionel = new Previsionel();

        $dateStrInput = explode(' au ', $request->query->get('dateStrInput'));

        $entityManager = $this->getDoctrine()->getManager();
        $lot =  $this->getDoctrine()->getRepository(Lot::class)->find($lotId);
        $previsionel->setLot($lot);

        $chantier =  $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);
        $previsionel->setChantier($chantier);
        
        $previsionel->setDateDebut(new \Datetime($dateStrInput[0]));
        $previsionel->setDateFin(new \Datetime($dateStrInput[1]));
        $entityManager->persist($previsionel);
        $entityManager->flush();
       
        return new Response(json_encode(array('status'=>200, 'message'=>"mise à jour reussite")));
    }


    /**
     * @Route("/new", name="previsionel_categorie_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $previsionelCategorie = new PrevisionelCategorie();
        $form = $this->createForm(PrevisionelCategorieType::class, $previsionelCategorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            if(!is_null($entreprise))
                $previsionelCategorie->setEntreprise($entreprise);

            $entityManager->persist($previsionelCategorie);
            $entityManager->flush();

            return $this->redirectToRoute('previsionel_categorie_index');
        }

        return $this->render('previsionel_categorie/new.html.twig', [
            'previsionel_categorie' => $previsionelCategorie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="previsionel_categorie_show", methods={"GET"})
     */
    public function show(PrevisionelCategorie $previsionelCategorie): Response
    {
        return $this->render('previsionel_categorie/show.html.twig', [
            'previsionel_categorie' => $previsionelCategorie,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="previsionel_categorie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, PrevisionelCategorie $previsionelCategorie): Response
    {
        $form = $this->createForm(PrevisionelCategorieType::class, $previsionelCategorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('previsionel_categorie_index');
        }

        return $this->render('previsionel_categorie/edit.html.twig', [
            'previsionel_categorie' => $previsionelCategorie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="previsionel_categorie_delete", methods={"DELETE"})
     */
    public function delete(Request $request, PrevisionelCategorie $previsionelCategorie): Response
    {
        if ($this->isCsrfTokenValid('delete'.$previsionelCategorie->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($previsionelCategorie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('previsionel_categorie_index');
    }

}
