<?php

namespace App\Controller;

use App\Entity\Version;
use App\Form\VersionType;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/version", name="version_")
 */
class VersionController extends Controller
{
    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function index(Request $request, Session $session)
    {
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $versions = $em->createQueryBuilder()
            ->select('v')
            ->from('App\Entity\Version', 'v')
            ->orderBy('v.name','DESC')
            ->getQuery();

        $adapter = new DoctrineORMAdapter($versions);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(10);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }

        return $this->render('version/index.html.twig', [
            'pager' => $pager,
        ]);
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"})
     */
    public function new(Request $request, $parent_id = null)
    {
        $version = new Version();
        $form = $this->createForm(VersionType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($version);
            $entityManager->flush();
            $this->get('session')->getFlashBag()->add('success', 'La version a bien été créée');

            return $this->redirectToRoute('version_list');
        }

        return $this->render('version/add.html.twig', [
            'version' => $version,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $id)
    {
        $version = $this->getDoctrine()->getRepository(Version::class)->find($id);
        if(!$version){
            $this->get('session')->getFlashBag()->add('error', 'Cette version n\'existe pas ou a été supprimée');
            return $this->redirectToRoute('version_list');
        }
        
        $form = $this->createForm(VersionType::class, $version);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($version);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', 'La version a bien été modifiée');

            return $this->redirectToRoute('version_list');
        }

        return $this->render('version/edit.html.twig', [
            'version' => $version,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/delete", name="delete")
     */
    public function delete(Request $request, $id)
    {
        $version = $this->getDoctrine()->getRepository(Version::class)->find($id);
        if(!$version){
            $this->get('session')->getFlashBag()->add('error', 'Cette version n\'existe pas ou a déjà été supprimée');
            return $this->redirectToRoute('version_list');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($version);
        $entityManager->flush();
        $this->get('session')->getFlashBag()->add('info', 'Le version a bien été supprimé');


        return $this->redirectToRoute('version_list');
    }


}
