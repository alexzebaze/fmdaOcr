<?php

namespace App\Controller;

use App\Entity\ConfigImapEmail;
use App\Entity\Entreprise;
use App\Form\ConfigImapEmailType;
use App\Repository\ConfigImapEmailRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/config/imap-email")
 */
class ConfigImapEmailController extends AbstractController
{
    
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="config_imap_email_index", methods={"GET"})
     */
    public function index(ConfigImapEmailRepository $configImapEmailRepository): Response
    {
        $configurations = $configImapEmailRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        return $this->render('config_imap_email/index.html.twig', [
            'configurations' => $configurations,
            'dossiers'=>array_flip($this->global_s->getRossumFolder2())
        ]);
    }

    /**
     * @Route("/new", name="config_imap_email_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $configImapEmail = new ConfigImapEmail();
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $configImapEmail->setEntreprise($entreprise);
        
        $form = $this->createForm(ConfigImapEmailType::class, $configImapEmail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($configImapEmail);
            $entityManager->flush();

            return $this->redirectToRoute('config_imap_email_index');
        }

        return $this->render('config_imap_email/new.html.twig', [
            'config_imap_email' => $configImapEmail,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="config_imap_email_show", methods={"GET"})
     */
    public function show(ConfigImapEmail $configImapEmail): Response
    {
        return $this->render('config_imap_email/show.html.twig', [
            'config_imap_email' => $configImapEmail,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="config_imap_email_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, ConfigImapEmail $configImapEmail): Response
    {
        $form = $this->createForm(ConfigImapEmailType::class, $configImapEmail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('config_imap_email_index');
        }

        return $this->render('config_imap_email/edit.html.twig', [
            'config_imap_email' => $configImapEmail,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="config_imap_email_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ConfigImapEmail $configImapEmail): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configImapEmail->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($configImapEmail);
            $entityManager->flush();
        }

        return $this->redirectToRoute('config_imap_email_index');
    }
}
