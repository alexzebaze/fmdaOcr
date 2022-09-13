<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use App\Entity\Logement;
use App\Entity\Location;
use App\Entity\Client;
use App\Entity\Entreprise;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use App\Repository\ChantierRepository;
use App\Repository\EntrepriseRepository;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

/**
 * @Route("/logement")
 */
class LogementController extends Controller
{   
    use CommunTrait;

    private $global_s;
    private $entrepriseRepository;
    private $logementRepository;
    private $chantierRepository;

    public function __construct(ChantierRepository $chantierRepository, EntrepriseRepository $entrepriseRepository, LogementRepository $logementRepository, SessionInterface $session, GlobalService $global_s){
        $this->entrepriseRepository = $entrepriseRepository;
        $this->chantierRepository = $chantierRepository;
        $this->logementRepository = $logementRepository;
        $this->session = $session;
        $this->global_s = $global_s;
    }

    /**
     * @Route("/", name="logement_index", methods={"GET"})
     * @Route("/print", name="logement_index_print", methods={"GET"})
     */
    public function index(LogementRepository $logementRepository, Request $request): Response
    {
        $logements = $logementRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        if($request->query->get('print'))
            $page = "logement/print_index.html.twig";
        else
            $page = "logement/index.html.twig";

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        return $this->render($page, [
            'logements' => $logements,
            'types' => $this->global_s->getLogementType(),
            'entreprise'=>[
                'nom'=>$entreprise->getName(),
                'adresse'=>$entreprise->getAddress(),
                'ville'=>$entreprise->getCity(),
                'postalcode'=>$entreprise->getCp(),
                'phone'=>$entreprise->getPhone(),
                'email'=>$entreprise->getEmail()
            ]
        ]);
    }

    /**
     * @Route("/new", name="logement_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $logement->setEntreprise($entreprise);


            $photos = $request->files->get('photos');
            if ($photos) {
                $tabPhotos = [];
                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/logement/" .$entreprise->getId() . "/";

                $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/uploads/logement/" . $entreprise->getId() . "/compressed/";

                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!is_dir($compressed_dir)) {
                        mkdir($compressed_dir, 0777, true);
                    }
                } catch (FileException $e) {
                }

                foreach ($photos as $value) {
                    $originalFilename = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugify($originalFilename);
                    $extension = $value->guessExtension();
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $value->guessExtension();
                   $value->move($dir, $newFilename);

                   $tabPhotos[] = $newFilename;
                   $this->global_s->make_thumb($dir.$newFilename, $compressed_dir.$newFilename,350, 350, $extension);
                }

                $logementPhotos = [];
                if(!is_null($logement->getPhotos())){
                    $logementPhotos = unserialize($logement->getPhotos());
                }

                $logement->setPhotos(serialize( array_merge($logementPhotos, $tabPhotos) ));
            }


            $entityManager->persist($logement);
            $entityManager->flush();

            $this->saveAcquereur(explode(',', $request->request->get('field-acquereur')), $logement);

            return $this->redirectToRoute('logement_index');
        }


        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $clientsArr = array_map(function($value){
            return  [
                'id'=>$value->getId(),
                'nom'=>$value->getNom(),
                'email'=>$value->getEmail(),
                'telephone'=>$value->getTelone(),
                'ville'=>$value->getVille(),
                'codePostal'=>$value->getCode(),
                'adresse'=>$value->getAdresse()
            ];
        }, $clients);

        return $this->render('logement/new.html.twig', [
            'logement' => $logement,
            'clients' => $clients,
            'types' => $this->global_s->getLogementType(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="logement_show", methods={"GET"})
     */
    public function show(Logement $logement): Response
    {
        return $this->render('logement/show.html.twig', [
            'logement' => $logement,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="logement_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Logement $logement): Response
    {
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $photos = $request->files->get('photos');
            if ($photos) {
                $tabPhotos = [];
                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/logement/" .$entreprise->getId() . "/";

                $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/uploads/logement/" . $entreprise->getId() . "/compressed/";

                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!is_dir($compressed_dir)) {
                        mkdir($compressed_dir, 0777, true);
                    }
                } catch (FileException $e) {
                }

                foreach ($photos as $value) {
                    $originalFilename = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugify($originalFilename);
                    $extension = $value->guessExtension();
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $value->guessExtension();
                   $value->move($dir, $newFilename);

                   $tabPhotos[] = $newFilename;
                   $this->global_s->make_thumb($dir.$newFilename, $compressed_dir.$newFilename,350, $extension);
                }

                $logementPhotos = [];
                if(!is_null($logement->getPhotos())){
                    $logementPhotos = unserialize($logement->getPhotos());
                }

                $logement->setPhotos(serialize( array_merge($logementPhotos, $tabPhotos) ));
            }

            $this->getDoctrine()->getManager()->flush();

            $this->saveAcquereur(explode(',', $request->request->get('field-acquereur')), $logement);

            return $this->redirectToRoute('logement_index');
        }

        $locations = $this->getDoctrine()->getRepository(Location::class)->findBy(['logement'=>$logement]);
        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $clientsArr = array_map(function($value){
            return  [
                'id'=>$value->getId(),
                'nom'=>$value->getNom(),
                'email'=>$value->getEmail(),
                'telephone'=>$value->getTelone(),
                'ville'=>$value->getVille(),
                'codePostal'=>$value->getCode(),
                'adresse'=>$value->getAdresse()
            ];
        }, $clients);


        return $this->render('logement/edit.html.twig', [
            'logement' => $logement,
            'clients' => $clientsArr,
            'locations' => $locations,
            'types' => $this->global_s->getLocationType(),
            'form' => $form->createView(),
        ]);
    }

    public function saveAcquereur($tabClient, $logement){
        $clients = $this->getDoctrine()->getRepository(Client::class)->getByTabClient2($tabClient);

        foreach ($clients as $client) {
            $logement->addAcquereur($client);
        }

        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * @Route("/{id}", name="logement_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Logement $logement): Response
    {
        if ($this->isCsrfTokenValid('delete'.$logement->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($logement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('logement_index');
    }
}
