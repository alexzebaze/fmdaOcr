<?php

namespace App\Controller;

use App\Entity\ConfigTotal;
use App\Form\ConfigTotalType;
use App\Repository\ConfigTotalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/config/total")
 */
class ConfigTotalController extends AbstractController
{
    /**
     * @Route("/", name="config_total_index", methods={"GET"})
     */
    public function index(ConfigTotalRepository $configTotalRepository): Response
    {
        return $this->render('config_total/index.html.twig', [
            'config_totals' => $configTotalRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="config_total_new", methods={"GET","POST"})
     */
    public function new(Request $request, ConfigTotalRepository $configTotalRepository): Response
    {
        $configTotal = new ConfigTotal();
        $form = $this->createForm(ConfigTotalType::class, $configTotal);
        $form->handleRequest($request);

        $configTotalAll = $configTotalRepository->findAll();
        if(count($configTotalAll) > 0){
            return $this->redirectToRoute('config_total_edit', ['id'=>$configTotalAll[0]->getId()]);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($configTotal);
            $entityManager->flush();

            return $this->redirectToRoute('config_total_edit', ['id'=>$configTotal->getId()]);
        }

        return $this->render('config_total/new.html.twig', [
            'config_total' => $configTotal,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="config_total_show", methods={"GET"})
     */
    public function show(ConfigTotal $configTotal): Response
    {
        return $this->render('config_total/show.html.twig', [
            'config_total' => $configTotal,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="config_total_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, ConfigTotal $configTotal): Response
    {
        $form = $this->createForm(ConfigTotalType::class, $configTotal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('config_total_edit', ['id'=>$configTotal->getId()]);
        }

        return $this->render('config_total/edit.html.twig', [
            'config_total' => $configTotal,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="config_total_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ConfigTotal $configTotal): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configTotal->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($configTotal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('config_total_index');
    }
}
