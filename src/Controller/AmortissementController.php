<?php

namespace App\Controller;

use App\Entity\Pret;
use App\Entity\Entreprise;
use App\Entity\Amortissement;
use App\Form\PretType;
use App\Repository\AmortissementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

/**
 * @Route("/amortissement")
 */
class AmortissementController extends AbstractController
{
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="amortissement_index", methods={"GET"})
     */
    public function index(AmortissementRepository $amortissementRepository): Response
    {
        return $this->render('pret/amortissement.html.twig', [
            'amortissements' => $amortissementRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{id}", name="amortissement_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Amortissement $amortissement): Response
    {
        if ($this->isCsrfTokenValid('delete'.$amortissement->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($amortissement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('amortissement_index');
    }

}
