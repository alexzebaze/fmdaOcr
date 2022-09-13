<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Utilisateur;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\Horaire;
use App\Form\ChantierType;
use App\Form\TacheType;
use App\Repository\EntrepriseRepository;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
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
 * @Route("/tache", name="tache_")
 */
class TacheController extends Controller
{
    private $session;
    private $entrepriseRepository;

    public function __construct(SessionInterface $session, EntrepriseRepository $entrepriseRepository){
        $this->session = $session;
        $this->entrepriseRepository = $entrepriseRepository;
    }

    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function index(Request $request, Session $session)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $taches = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Category', 'c')
            ->andWhere('c.parent is null')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->orderBy('c.num', 'asc')
            ->getQuery()->getResult();


        return $this->render('tache/index.html.twig', [
            'taches' => $taches,
        ]);
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"})
     * @Route("/add/{parent_id}", name="add", methods={"GET","POST"})
     */
    public function new(Request $request, $parent_id = null)
    {
        $user = $this->getUser();
        $tache = new Category();
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        if(!is_null($entreprise))
            $tache->setEntreprise($entreprise);
        $parent = null;
        if ($parent_id) {
            $parent = $this->getDoctrine()->getRepository(Category::class)->find($parent_id);
            $tache->setParent($parent);
        }
        $form = $this->createForm(TacheType::class, $tache, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);
        if ($tache->getParent())
            $tache->setNum((int)$tache->getParent()->getNum() + 1);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($tache);
            $entityManager->flush();

            return $this->redirectToRoute('tache_list');
        }

        return $this->render('tache/add.html.twig', [
            'tache' => $tache,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{idcat}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $idcat)
    {
        $user = $this->getUser();
        $tache = $this->getDoctrine()->getRepository(Category::class)->find($idcat);
        $form = $this->createForm(TacheType::class, $tache, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);

        if ($tache->getParent()) {
            $tache->setNum($tache->getParent()->getNum() + 1);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($tache);
            $entityManager->flush();

            return $this->redirectToRoute('tache_list');
        }

        return $this->render('tache/edit.html.twig', [
            'chantier' => $tache,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{idcat}/delete", name="delete")
     */
    public function delete(Request $request, $idcat)
    {
        $tache = $this->getDoctrine()->getRepository(Category::class)->find($idcat);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($tache);
        $entityManager->flush();
        $this->get('session')->getFlashBag()->add('info', 'La tâche a bien été supprimée');


        return $this->redirectToRoute('tache_list');
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
        return $this->redirectToRoute('tache_list');
    }

    private function orderRecursive($orders, $parent = null)
    {
        foreach ($orders as $key => $order) {
            if (isset($order->id)) {
                /** @var Category $tache */
                $tache = $this->getDoctrine()->getRepository(Category::class)->find($order->id);
                if ($tache) {
                    $tache->setParent($parent);
                    $tache->setNum($key);
                    if (count($order->children) > 0) {
                        $this->orderRecursive($order->children, $tache);
                    }
                }
            }
        }
    }

}
