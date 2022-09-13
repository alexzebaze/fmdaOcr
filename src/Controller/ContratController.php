<?php

namespace App\Controller;

use App\Entity\Configuration;
use App\Entity\Horaire;
use App\Form\ContratType;
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

/**
 * @Route("/contrat", name="contrat_")
 */
class ContratController extends Controller
{
    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function index(Request $request, Session $session)
    {
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $contrats = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Configuration', 'c')
            ->andWhere('c.type = :type')
            ->setParameter('type', "contrat")
            ->getQuery()->getResult();


        return $this->render('contrat/index.html.twig', [
            'contrats' => $contrats,
        ]);
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"})
     */
    public function new(Request $request, $parent_id = null)
    {
        $user = $this->getUser();
        $contrat = new Configuration();
        $contrat->setType("contrat");
        $form = $this->createForm(ContratType::class, $contrat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($contrat);
            $entityManager->flush();
            $this->get('session')->getFlashBag()->add('success', 'Le contrat a bien été crée');

            return $this->redirectToRoute('contrat_list');
        }

        return $this->render('contrat/add.html.twig', [
            'contrat' => $contrat,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $id)
    {
        $contrat = $this->getDoctrine()->getRepository(Configuration::class)->findOneBy(["type"=>"contrat", "id" => $id]);
        $form = $this->createForm(ContratType::class, $contrat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($contrat);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', 'Le contrat a bien été modifié');

            return $this->redirectToRoute('contrat_list');
        }

        return $this->render('contrat/edit.html.twig', [
            'contrat' => $contrat,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/delete", name="delete")
     */
    public function delete(Request $request, $id)
    {
        $contrat = $this->getDoctrine()->getRepository(Configuration::class)->findOneBy(["type"=>"contrat", "id" => $id]);

        $entityManager = $this->getDoctrine()->getManager();

        try {
            $entityManager->remove($contrat);
            $entityManager->flush();
            $this->addFlash('success', "Suppression effectuée avec succès");
        } catch (\Exception $e) {
            $this->addFlash('error', "Vous ne pouvez supprimer cet element s'il est lié à d'autres elements");
        }

        return $this->redirectToRoute('contrat_list');
    }


}
