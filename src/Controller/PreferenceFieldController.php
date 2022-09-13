<?php

namespace App\Controller;

use App\Entity\PreferenceField;
use App\Entity\Entreprise;
use App\Form\PreferenceFieldType;
use App\Repository\PreferenceFieldRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

/**
 * @Route("/preference-field")
 */
class PreferenceFieldController extends AbstractController
{

    private $session;
    private $global_s;

    public function __construct(SessionInterface $session, GlobalService $global_s){
        $this->session = $session;
        $this->global_s = $global_s;
    }

    /**
     * @Route("/", name="preference_field_index", methods={"GET"})
     */
    public function index(PreferenceFieldRepository $preferenceFieldRepository): Response
    {
        return $this->render('preference_field/index.html.twig', [
            'preference_fields' => $preferenceFieldRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="preference_field_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $preferenceField = new PreferenceField();
        $form = $this->createForm(PreferenceFieldType::class, $preferenceField);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

            $preferenceField->setEntreprise($entreprise);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($preferenceField);
            $entityManager->flush();

            return $this->redirectToRoute('preference_field_index');
        }

        return $this->render('preference_field/new.html.twig', [
            'preference_field' => $preferenceField,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="preference_field_show", methods={"GET"})
     */
    public function show(PreferenceField $preferenceField): Response
    {
        return $this->render('preference_field/show.html.twig', [
            'preference_field' => $preferenceField,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="preference_field_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, PreferenceField $preferenceField): Response
    {
        $form = $this->createForm(PreferenceFieldType::class, $preferenceField);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('preference_field_index');
        }

        return $this->render('preference_field/edit.html.twig', [
            'preference_field' => $preferenceField,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="preference_field_delete", methods={"DELETE"})
     */
    public function delete(Request $request, PreferenceField $preferenceField): Response
    {
        if ($this->isCsrfTokenValid('delete'.$preferenceField->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($preferenceField);
            $entityManager->flush();
        }

        return $this->redirectToRoute('preference_field_index');
    }
}
