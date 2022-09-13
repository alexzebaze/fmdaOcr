<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Entity\Entreprise;
use App\Entity\VehiculeKilometrage;
use App\Form\VehiculeType;
use App\Repository\VehiculeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UtilisateurRepository;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/vehicule", name="vehicule_")
 */
class VehiculeController extends AbstractController
{
    private $global_s;
    private $utilisateurRepository;
    private $vehiculeRepository;
    private $session;

    public function __construct(GlobalService $global_s, VehiculeRepository $vehiculeRepository, UtilisateurRepository $utilisateurRepository, SessionInterface $session){
        $this->global_s = $global_s;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->vehiculeRepository = $vehiculeRepository;
        $this->session = $session;
    }

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(VehiculeRepository $vehiculeRepository): Response
    {
        $vehicules = $vehiculeRepository->findAll();

        $kilometragesArr = [];
        $kilometrages = $vehiculeRepository->getKilometrageVehicule();
        foreach ($kilometrages as $val) {
            $unite = "KM";
            $kilometragesArr[$val['vehicule_id']] = $val["quantite"]. " ".$unite;
        }
        

        return $this->render('vehicule/index.html.twig', [
            'vehicules' => $vehicules,
            'kilometrages' => $kilometragesArr,
            'financements'=>$this->global_s->getTabFinancement(),
            'status'=>$this->global_s->getVehiculeStatus()
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $vehicule = new Vehicule();
        $utilisateurs = $this->utilisateurRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'etat'=>1]);
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        $entityManager = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->saveInfoVehicule($form, $request, $vehicule);
            $this->get('session')->getFlashBag()->clear();
            $this->addFlash('success', "Ajout effectué avec succès");
        

            $kilometrages = $request->request->get('kilometrages');
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

            if(!is_null($kilometrages)){
                for ($i=0; $i < count($kilometrages['date']); $i++) { 
                    if(!empty($kilometrages['qte'][$i]) || !empty($kilometrages['date'][$i])){
                        $kilometrage = new VehiculeKilometrage();
                        $kilometrage->setQuantite((float)$kilometrages['qte'][$i]);
                        $kilometrage->setDateReleve(new \DateTime($kilometrages['date'][$i]));
                        $kilometrage->setEntreprise($entreprise);

                        $entityManager->persist($kilometrage);
                        $vehicule->addKilometrage($kilometrage);    
                    }
                }
            }
            /* FIN SAUVEGARDE KILOMETRAGE */  


            $entityManager->flush();
            return $this->redirectToRoute('vehicule_index');
        }

        return $this->render('vehicule/new.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form->createView(),
            'utilisateurs'=>$utilisateurs
        ]);
    }

    public function saveInfoVehicule($form, $request, $vehicule){
        $entityManager = $this->getDoctrine()->getManager();
        /** @var UploadedFile $logo_marque */
        $uploadedFile = $form['logo_marque']->getData();
        if ($uploadedFile){
            $logo = $this->global_s->saveImage($uploadedFile, '/public/uploads/vehicule/logo/');
            $vehicule->setLogoMarque($logo);
        }
        /** @var UploadedFile $carte_grise */
        $uploadedFile = $form['carte_grise']->getData();
        if ($uploadedFile){
            $carte_grise = $this->global_s->saveImage($uploadedFile, '/public/uploads/vehicule/document/');
            $vehicule->setCarteGrise($carte_grise);
        }
        
        /** @var UploadedFile $carte_totale */
        $uploadedFile = $form['carte_totale']->getData();
        if ($uploadedFile){
            $carte_totale = $this->global_s->saveImage($uploadedFile, '/public/uploads/vehicule/document/');
            $vehicule->setCarteTotale($carte_totale);
        }
        
        /** @var UploadedFile $assurance */
        $uploadedFile = $form['assurance']->getData();
        if ($uploadedFile){
            $assurance = $this->global_s->saveImage($uploadedFile, '/public/uploads/vehicule/document/');
            $vehicule->setAssurance($assurance);
        }

        $conducteursField = $request->request->get('conducteurs');
    
        if($conducteursField){
            foreach ($vehicule->getConducteurs() as $value) {
                if(!in_array($value->getUid(), $conducteursField) ){
                    $vehicule->removeConducteur($value);
                }
            }
            foreach ($conducteursField as $value) {
                $conducteur = $this->utilisateurRepository->find($value);
                $conducteur->setVehicule($vehicule);
            }
        }

        $entityManager->persist($vehicule);
        $entityManager->flush();
    }
    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(Vehicule $vehicule): Response
    {
        return $this->render('vehicule/show.html.twig', [
            'vehicule' => $vehicule,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Vehicule $vehicule): Response
    {
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $this->saveInfoVehicule($form, $request, $vehicule);

            /* SAUVEGARDE KILOMETRAGE */

            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $kilometrages = $vehicule->getKilometrages();
            $kilometrageEdit = $request->request->get('kilometragesEdit');
            
            if(!is_null($kilometrageEdit)){
                foreach ($kilometrageEdit as $key => $value) {
                    $kilometrage =  $this->getDoctrine()->getRepository(VehiculeKilometrage::class)->find($key);
                    if(!empty($value['kilometrage']) || !empty($value['qte']) || !empty($value['date'])){
                        $kilometrage->setQuantite((float)$value['qte']);
                        $kilometrage->setEntreprise($entreprise);
                        $kilometrage->setDateReleve(new \DateTime($value['date']));
                    }
                    else{
                        $vehicule->removeKilometrage($kilometrage);
                    }
                }
                foreach ($kilometrages as $value) {
                    if(!array_key_exists($value->getId(), $kilometrageEdit)){
                        $entityManager->remove($value);
                    }
                }
            }


            $kilometrages = $request->request->get('kilometrages');
            if(!is_null($kilometrages)){
                for ($i=0; $i < count($kilometrages['date']); $i++) { 
                    if(!empty($kilometrages['qte'][$i]) || !empty($kilometrages['date'][$i])){
                        $kilometrage = new VehiculeKilometrage();
                        $kilometrage->setQuantite((float)$kilometrages['qte'][$i]);
                        $kilometrage->setDateReleve(new \DateTime($kilometrages['date'][$i]));
                        $kilometrage->setEntreprise($entreprise);

                        $entityManager->persist($kilometrage);
                        $vehicule->addKilometrage($kilometrage);    
                    }
                }
            }
            /* FIN SAUVEGARDE KILOMETRAGE */    

            $entityManager->flush();

             $this->get('session')->getFlashBag()->clear();
            $this->addFlash('success', "edition effectuée avec succès");
            return $this->redirectToRoute('vehicule_index');

        }

        $utilisateurs = $this->utilisateurRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'etat'=>1]);

        return $this->render('vehicule/edit.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form->createView(),
            'utilisateurs'=>$utilisateurs,
            'conducteurs'=> $vehicule->getConducteurs()
        ]);
    }

    /**
     * @Route("/{id}/delete", name="delete")
     */
    public function delete(Request $request, Vehicule $vehicule): Response
    {
        //if ($this->isCsrfTokenValid('delete'.$vehicule->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($vehicule);
            $entityManager->flush();
        //}

        $this->get('session')->getFlashBag()->clear();
        $this->addFlash('success', "Suppression effectuée avec succès");

        return $this->redirectToRoute('vehicule_index');
    }

    /**
     * @Route("/{id}/delete-document/{type_document}", name="delete_document")
     */
    public function deleteDocument(Request $request, $id, $type_document): Response
    {
        $vehicule =$this->getDoctrine()
                ->getRepository(Vehicule::class)
                ->find($id);

        switch ($type_document) {
            case 'carte_totale':
                $vehicule->setCarteTotale(null);
                break;
            case 'carte_grise':
                $vehicule->setCarteGrise(null);
                break;
            case 'assurance':
                $vehicule->setAssurance(null);
                break;
            default:
                break;
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
        
        return $this->redirectToRoute('vehicule_edit', ['id'=>$id]);
    }
}
