<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Entreprise;
use App\Entity\Chantier;
use App\Entity\Horaire;
use App\Entity\Document;
use App\Form\ChantierType;
use App\Form\UtilisateurType;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Controller\Traits\CommunTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\UtilisateurRepository;
use App\Repository\DocumentRepository;
use App\Service\GlobalService;

/**
 * @Route("/utilisateur", name="utilisateur_")
 */
class UtilisateurController extends Controller
{
    use CommunTrait;

    private $session;
    private $utilisateurRepository;
    private $documentRepository;
    private $global_s;

    public function __construct(SessionInterface $session, UtilisateurRepository $utilisateurRepository,  DocumentRepository $documentRepository, GlobalService $global_s){
        $this->session = $session;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->documentRepository = $documentRepository;
        $this->global_s = $global_s;
    }

    private $image_vide = "data:image/jpeg;base64,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";
    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function index(Request $request, Session $session)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $page2 = $request->query->get('page', 1);

        $verif = $request->query->get('verif', 1);
        $embauches = $em->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\Utilisateur', 'u')
            ->andWhere('u.entreprise = :entreprise')
            ->andWhere('u.etat = :verif')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('verif', $verif)
            ->orderBy('u.lastname', 'asc')
            ->andWhere('u.sous_traitant = :sous_traitant')
            ->setParameter('sous_traitant', 0);

        $sous_traitant = $em->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\Utilisateur', 'u')
            ->andWhere('u.entreprise = :entreprise')
            ->andWhere('u.etat = :verif')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('verif', $verif)
            ->orderBy('u.lastname', 'asc')
            ->andWhere('u.sous_traitant = :sous_traitant')
            ->setParameter('sous_traitant', 1);

        $adapterEmb = new DoctrineORMAdapter($embauches);
        $pagerEmb = new PagerFanta($adapterEmb);
        $pagerEmb->setMaxPerPage(750);
        if ($pagerEmb->getNbPages() >= $page) {
            $pagerEmb->setCurrentPage($page);
        } else {
            $pagerEmb->setCurrentPage($pagerEmb->getNbPages());
        }

        $adapterST = new DoctrineORMAdapter($sous_traitant);
        $pagerST = new PagerFanta($adapterST);
        $pagerST->setMaxPerPage(750);
        if ($pagerST->getNbPages() >= $page2) {
            $pagerST->setCurrentPage($page2);
        } else {
            $pagerST->setCurrentPage($pagerST->getNbPages());
        }


        return $this->render('utilisateur/index.html.twig', [
            'pager' => $pagerEmb,
            'pagerST' => $pagerST,
            'image_vide' => $this->image_vide,
            'verification' => $verif
        ]);
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"})
     */
    public function new(Request $request)
    {
        $typeDocuments = $this->global_s->getTypeDocument();
        $me = $this->getUser();
        $user = new Utilisateur();
        $user->setEntreprise($this->utilisateurRepository->find($this->session->get('entreprise_session_id')));
        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            /** @var UploadedFile $image */
            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL

                // Move the file to the directory where brochures are stored
                try {
                    $dir = $this->get('kernel')->getProjectDir()."/public/uploads/";
                    if(!is_dir($dir)) {
                        mkdir($dir);
                    }
                    $ext = $image->guessExtension();
                    $image->move(
                        $dir,
                        $originalFilename.'.'.$ext
                    );
                    # recup base64
                    $content = $this->resizeImage($dir.$originalFilename.'.'.$ext, 400, 400);
                    //$content = file_get_contents($dir.$originalFilename.'.'.$ext);

                    ob_start(); // Let's start output buffering.
                    $photo = imagejpeg($content);
                    $photo = ob_get_contents(); //Instead, output above is saved to $contents
                    ob_end_clean(); //End the output buffer.

                    $base = base64_encode($photo);
                    # delete file
                    unlink($dir . $originalFilename.'.'.$ext);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                $user->setImage($base);
            }

            $password = $this->randomPassword();
            $user->setPassword($password);
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $user->setEntreprise($entreprise);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $dir_storage = $this->get('kernel')->getProjectDir() . "/public/uploads/entreprise_" . $this->session->get('entreprise_session_id') . "/user_" . $user->getUid();

            try {
                if (!is_dir($dir_storage)) {
                    mkdir($dir_storage, 0777, true);
                }

            } catch (FileException $e) {}

            foreach ($typeDocuments as $value) {
                $dir = $dir_storage;
                $doc = $request->files->get($value);
                if($doc){
                    $originalFilename = pathinfo($doc->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $this->slugify($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$doc->guessExtension();

                    $dir = $dir."/".$value."/".(new \DateTime())->format('Y-m-d') . "/";
                    try {
                        if (!is_dir($dir)) {
                            mkdir($dir, 0777, true);
                        }
                         $doc->move(
                            $dir,
                            $newFilename
                        );
                    } catch (FileException $e) {}

                    $docUser = new Document();
                    $docUser->setUtilisateur($user);
                    $docUser->setType($value);
                    $docUser->setNomFichier($newFilename);
                    $entityManager->persist($docUser);
                }
            }
            $entityManager->flush();

            return $this->redirectToRoute('utilisateur_list');
        }

        $documents = $this->documentRepository->findBy(['utilisateur'=>$user]);
        return $this->render('utilisateur/add.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'documents'=>$documents,
            'typeDocuments'=>$typeDocuments

        ]);
    }

    public function resizeImage($filename, $max_width, $max_height)
    {
        list($orig_width, $orig_height) = getimagesize($filename);

        $width = $orig_width;
        $height = $orig_height;

        # taller
        if ($height > $max_height) {
            $width = ($max_height / $height) * $width;
            $height = $max_height;
        }

        # wider
        if ($width > $max_width) {
            $height = ($max_width / $width) * $height;
            $width = $max_width;
        }

        $image_p = imagecreatetruecolor($width, $height);

        $image = imagecreatefromjpeg($filename);

        imagecopyresampled($image_p, $image, 0, 0, 0, 0,
            $width, $height, $orig_width, $orig_height);

        return $image_p;
    }

    public function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

        /**
     * @Route("/get-all-xhr", name="get_all_xhr")
    */
    public function getUsersXhr(Request $request){
        $utilisateurs = $this->utilisateurRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'etat'=>1]);
        $utilisateursArr = [];
        foreach ($utilisateurs as $user) {
            $currentUser = [];
            $currentUser['id'] = $user->getUid();
            $currentUser['lastname'] = $user->getLastName();
            $currentUser['firstname'] = $user->getFirstName();
            $currentUser['full_name'] = $user->getLastName().' '.$user->getFirstName();
            $currentUser['email'] = $user->getEmail();
            $currentUser['avatar'] = $user->getImage();
            $utilisateursArr[] = $currentUser;    
        }
        $responses['status'] = 200;
        $responses['users'] = $utilisateursArr;
        $responses = new Response(json_encode($responses));
        return $responses; 
    }

    /**
     * @Route("/{userId}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $userId)
    {
        $typeDocuments = $this->global_s->getTypeDocument();
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId);
        $old_image = $user->getImage();
        $user->setImage(null);
        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            /** @var UploadedFile $image */
            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL

                // Move the file to the directory where brochures are stored
                try {
                    $dir = $this->get('kernel')->getProjectDir()."/public/uploads/";
                    if(!is_dir($dir)) {
                        mkdir($dir);
                    }
                    $ext = $image->guessExtension();
                    $image->move(
                        $dir,
                        $originalFilename.'.'.$ext
                    );
                    # recup base64
                    $content = $this->resizeImage($dir.$originalFilename.'.'.$ext, 400, 400);
                    //$content = file_get_contents($dir.$originalFilename.'.'.$ext);

                    ob_start(); // Let's start output buffering.
                    $photo = imagejpeg($content);
                    $photo = ob_get_contents(); //Instead, output above is saved to $contents
                    ob_end_clean(); //End the output buffer.

                    $base = base64_encode($photo);
                    # delete file
                    unlink($dir . $originalFilename.'.'.$ext);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                $user->setImage($base);

            } else {
                $user->setImage($old_image);
            }

            $dir_storage = $this->get('kernel')->getProjectDir() . "/public/uploads/entreprise_" . $this->session->get('entreprise_session_id') . "/user_" . $user->getUid();

            try {
                if (!is_dir($dir_storage)) {
                    mkdir($dir_storage, 0777, true);
                }

            } catch (FileException $e) {}


            foreach ($typeDocuments as $value) {
                $dir = $dir_storage;
                $doc = $request->files->get($value);
                if($doc){
                    $originalFilename = pathinfo($doc->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $this->slugify($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$doc->guessExtension();

                    $dir = $dir."/".$value."/".(new \DateTime())->format('Y-m-d') . "/";
                    try {
                        if (!is_dir($dir)) {
                            mkdir($dir, 0777, true);
                        }
                         $doc->move(
                            $dir,
                            $newFilename
                        );
                    } catch (FileException $e) {}

                    $docUser = $this->documentRepository->findOneBy(['utilisateur'=>$user, 'type'=>$value]);
                    if(is_null($docUser)){
                        $docUser = new Document();
                        $docUser->setUtilisateur($user);
                        $docUser->setType($value);
                        $entityManager->persist($docUser);
                    }
                    $docUser->setNomFichier($newFilename);
                }
            }


            //$password = $this->randomPassword();
            //$user->setPassword($password);
            $entityManager->flush();

            return $this->redirectToRoute('utilisateur_list');
        }

        $documents = $this->documentRepository->findBy(['utilisateur'=>$user]);
        return $this->render('utilisateur/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'old_image' => $old_image,
            'documents'=>$documents,
            'typeDocuments'=>$typeDocuments
        ]);
    }

    /**
     * @Route("/{userId}/status", name="status", methods={"GET","POST"})
     */
    public function changeStatus(Request $request, $userId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var Utilisateur $user */
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId);
        if ($user->getEtat() == 1) {
            $user->setEtat(0);
        } else {
            $user->setEtat(1);
        }
        $entityManager->flush();


        return $this->redirectToRoute('utilisateur_list');
    }

    /**
     * @Route("/{userId}/delete", name="delete")
     */
    public function delete(Request $request, $userId)
    {
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId);
        $horaires = $this->getDoctrine()->getRepository(Horaire::class)->findBy(array('userid' => $user->getUid()));
        if($horaires) {
            $this->get('session')->getFlashBag()->add('error', 'Vous ne pouvez pas supprimer l\'utilisateur si vous avez des horaires associés');
            return $this->redirectToRoute('utilisateur_list');
        }
        $entityManager = $this->getDoctrine()->getManager();
        
        try {
            $entityManager->remove($user);
            $entityManager->flush();
        } catch (\Exception $e) {
            $this->addFlash('error', "Vous ne pouvez supprimer cet element s'il est lié à d'autres elements");
        }

        return $this->redirectToRoute('utilisateur_list');
    }


    /**
     * @Route("/map/{id}", name="map", methods={"GET"})
     */
    public function map(Request $request, $id)
    {
        $apikey = $this->getParameter('google_api');
        /** @var Utilisateur $user */
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($id);
        //$json = file_get_contents("https://maps.google.com/maps/api/geocode/json?address=".$user->getFullAddressUrlEncoded()."&sensor=false&key=".$apikey);
        //$output = json_decode($json);
        $adresse = $user->getFullAddressUrlEncoded();
        if(!empty($adresse)) {
            //$latitude = $output->results[0]->geometry->location->lat;
            //$longitude = $output->results[0]->geometry->location->lng;
            return $this->redirect("https://www.google.fr/maps/place/".$adresse."/");
        } else {
            return $this->redirectToRoute('chantier_list');
        }
    }

    /**
     * @Route("/{userId}", name="show", methods={"GET"})
     */
    public function show(Chantier $chantier): Response
    {
        return $this->render('chantier/show.html.twig', [
            'chantier' => $chantier,
        ]);
    }
}
