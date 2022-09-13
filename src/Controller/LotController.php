<?php

namespace App\Controller;

use App\Entity\Lot;
use App\Entity\Utilisateur;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\PrevisionelCategorie;
use App\Entity\Horaire;
use App\Entity\Previsionel;
use App\Form\ChantierType;
use App\Form\LotType;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use App\Repository\EntrepriseRepository;
use App\Repository\LotRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/lot", name="lot_")
 */
class LotController extends Controller
{
    private $session;
    private $entrepriseRepository;
    private $lotRepository;

    public function __construct(SessionInterface $session, EntrepriseRepository $entrepriseRepository, LotRepository $lotRepository){
        $this->session = $session;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->lotRepository = $lotRepository;
    }

    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function index(Request $request, Session $session)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $lots = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Lot', 'c')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->orderBy('c.num', 'asc')
            ->getQuery()->getResult();


        $lotsWithoutCateg =  $this->lotRepository->getLotsWithoutCateg();
        $categories = $this->getDoctrine()->getRepository(PrevisionelCategorie::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);         
        

        return $this->render('lot/index.html.twig', [
            'categories'=>$categories,
            'lotsWithoutCateg'=>$lotsWithoutCateg,
            'lots' => $lots
        ]);
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"})
     */
    public function new(Request $request)
    {
        $user = $this->getUser();
        $lot = new Lot();
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        if(!is_null($entreprise))
            $lot->setEntreprise($entreprise);
        $form = $this->createForm(LotType::class, $lot, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($lot);
            $entityManager->flush();

            return $this->redirectToRoute('lot_list');
        }

        return $this->render('lot/add.html.twig', [
            'lot' => $lot,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $id)
    {
        $user = $this->getUser();
        $lot = $this->getDoctrine()->getRepository(Lot::class)->find($id);
        $form = $this->createForm(LotType::class, $lot, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($lot);
            $entityManager->flush();

            return $this->redirectToRoute('lot_list');
        }

        return $this->render('lot/edit.html.twig', [
            'chantier' => $lot,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/delete", name="delete")
     */
    public function delete(Request $request, $id)
    {
        $lot = $this->getDoctrine()->getRepository(Lot::class)->find($id);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($lot);
        $entityManager->flush();
        $this->get('session')->getFlashBag()->add('info', 'La tâche a bien été supprimée');


        return $this->redirectToRoute('lot_list');
    }


    /**
     * @Route("/order", name="order")
     */
    public function order(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $orders = $request->request->get('order', null);
        $this->orderRecursive(json_decode($orders));
        $entityManager->flush();
        return $this->redirectToRoute('lot_list');
    }

    private function orderRecursive($orders)
    {
        foreach ($orders as $key => $order) {
            if (isset($order->id)) {
                /** @var Lot $lot */
                $lot = $this->getDoctrine()->getRepository(Lot::class)->find($order->id);
                if ($lot) {
                    $lot->setNum($key);
                    if (count($order->children) > 0) {
                        $this->orderRecursive($order->children, $lot);
                    }
                }
            }
        }
    }

    /**
     * @Route("/set-categorie/{lotId}", name="set_categorie")
     */
    public function setCategorie(Request $request, $lotId){
        $categorieId =  $request->query->get('categorie_id');
        $categorie = $this->getDoctrine()->getRepository(PrevisionelCategorie::class)->find($categorieId);

        $entityManager = $this->getDoctrine()->getManager();
        $lot = $this->getDoctrine()->getRepository(Lot::class)->find($lotId);
        $lot->setPrevisionelCategorie($categorie);

        $entityManager->flush();
        return $this->redirectToRoute('lot_list');
    }

    /**
     * @Route("/add-lot-categorie", name="add_lot_categorie")
     */
    public function addLotCategorie(Request $request){
        $lotSelect =  $request->request->get('lotSelect');
        $categorieId =  $request->request->get('categorieId');

        $lots = $this->lotRepository->getByTabId($lotSelect);
        foreach ($lots as $value) {
            $categorie = $this->getDoctrine()->getRepository(PrevisionelCategorie::class)->find($categorieId);
            $value->setPrevisionelCategorie($categorie);
        }
        $entityManager = $this->getDoctrine()->getManager();
        
        $entityManager->flush();
        return $this->redirectToRoute('lot_list');
    }

}
