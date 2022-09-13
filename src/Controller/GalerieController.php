<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use App\Entity\Utilisateur;
use App\Entity\Galerie;
use App\Entity\Chantier;
use App\Entity\Horaire;
use App\Entity\Entreprise;
use App\Form\GalerieType;
use App\Form\ChantierType;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use ffmpeg_movie;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\component\HttpFoundation\File\UploadedFile;
use App\Repository\GalerieRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/galerie", name="galerie_")
 */
class GalerieController extends Controller
{
    use CommunTrait;

    private $galerieRepository;
    private $session;

    public function __construct(GalerieRepository $galerieRepository, SessionInterface $session){
        $this->galerieRepository = $galerieRepository;
        $this->session = $session;
    }

    /**
     * @Route("/zip", name="zip")
     */
    /**
     * @Route("/zip-images/{chantierId}", name="zip_image")
     */
    public function zipImage(Request $request, $chantierId)
    {

        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);
        $galeries = $chantier->getGaleries();

        $zip = new \ZipArchive(); // Load zip library
        $zip_name = $chantier->getNameentreprise().".zip"; // Zip name
        if($zip->open($zip_name, \ZIPARCHIVE::CREATE)!==TRUE){
            $this->get('session')->getFlashBag()->add('error', 'goSorry ZIP creation failed at this timeod');
        }
        else{
            foreach($galeries as $file){
                if($file->getNom()){
                    $zip->addFile('galerie/'.$chantier->getEntreprise()->getId().'/'.$file->getCreatedAt()->format('Y-m-d').'/'.$file->getNom(), $file->getCreatedAt()->format('Y-m-d').'/'.$file->getNom());
                }
            }
            $zip->close();
            if(file_exists($zip_name))
            {
                header('Content-type: application/zip');
                header('Content-Disposition: attachment; filename="'.$zip_name.'"');
                readfile($zip_name);
                // remove zip file is exists in temp path
                unlink($zip_name);
            }            
        }

        return $this->redirectToRoute('galerie_image_list',['id'=>$chantierId]);
    }

    /**
     * @Route("/", name="list", methods={"GET"})
     */
    public function index(Request $request, Session $session)
    {

        // $user = $this->getUser();
        // $em = $this->getDoctrine()->getManager();
        // $page = $request->query->get('page', 1);
        // $chantiers = $em->createQueryBuilder()
        //     ->select('g')
        //     ->from('App\Entity\Galerie', 'g')
        //     ->andWhere('g.entreprise = :entreprise')
        //     ->setParameter('entreprise', $user->getEntreprise())
        //     ->orderBy('g.created_at', 'desc');

        // $adapter = new DoctrineORMAdapter($chantiers);
        // $pager = new PagerFanta($adapter);
        // $pager->setMaxPerPage(75);
        // if ($pager->getNbPages() >= $page) {
        //     $pager->setCurrentPage($page);
        // } else {
        //     $pager->setCurrentPage($pager->getNbPages());
        // }
        // /** @var Galerie $file */
        // foreach ($pager->getCurrentPageResults() as $file) {
        //     if ($file->getExtension() == "mp4") {
        //         /* $path = '/galerie/'.$user->getEntreprise()->getId()."/".$file->getCreatedAt()->format('Y-m-d')."/".$file->getNom();
        //          $ffmpeg = FFMpeg::create();
        //          $video = $ffmpeg->open($path);
        //          $frame = $video->frame(TimeCode::fromSeconds(42));
        //          dump($frame);die();*/
        //         $file->setThumbnail('');
        //     } else {
        //         $file->setThumbnail('/galerie/' . $file->getEntreprise()->getId() . "/" . $file->getCreatedAt()->format('Y-m-d') . "/" . $file->getNom());
        //     }
        // }

        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $chantiers = $em->createQueryBuilder()
            ->select('l')
            ->from('App\Entity\Chantier', 'l')
            ->andWhere('l.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'));

        if($request->get('q')){
            $chantiers = $chantiers->andWhere('LOWER(l.nameentreprise) LIKE :nameentreprise')
                ->setParameter('nameentreprise', strtolower($request->get('q'))."%");
        }
        $chantiers = $chantiers->orderBy('l.chantierId', 'desc')
            ->getQuery()
            ->getResult();

        $chantierArr = [];
        foreach ($chantiers as $value) {
            if($value->getStatus() || (!$value->getStatus() && count($value->getGaleries()))){
                $chantierArr[] = $value;
            }
        }
        /*$adapter = new DoctrineORMAdapter($chantierArr);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }*/

        return $this->render('galerie/index.html.twig', [
            'chantiers' => $chantierArr,
            'q' => $request->get('q'),
        ]);
    }


    /**
     * @Route("/datas-filter", name="datas_filter")
    */
    public function filterChantier(Request $request){

        $valFilter = $request->query->get('val');
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $chantiers = $em->createQueryBuilder()
            ->select('l')
            ->from('App\Entity\Chantier', 'l')
            ->andWhere('l.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'));

        if($valFilter){
            $chantiers = $chantiers->andWhere('LOWER(l.nameentreprise) LIKE :nameentreprise')
                ->setParameter('nameentreprise', strtolower($valFilter)."%");
        }
        $chantiers = $chantiers->orderBy('l.chantierId', 'desc')
            ->getQuery()
            ->getResult();

        $chantierArr = [];
        foreach ($chantiers as $value) {
            if($value->getStatus() || (!$value->getStatus() && count($value->getGaleries()))){
                $chantierArr[] = $value;
            }
        }
        
        $datas = ['status'=>200, "message"=>""];
        $datas['content'] = $this->renderView('galerie/galerie_content.html.twig', ['chantiers'=>$chantierArr]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }


    /**
     * @Route("/maj/", name="maj", methods={"GET"})
     */
    public function maj(Request $request, Session $session)
    {
        $entreprises = $this->getDoctrine()->getRepository(Entreprise::class)->findAll();
        foreach ($entreprises as $entreprise) {

            $galeries = $this->getDoctrine()->getRepository(Galerie::class)->findBy(['entreprise'=>$entreprise]);

            foreach ($galeries as $galerie) {

                if(in_array($galerie->getExtension(),array("png","jpeg","gif","jpg"))){
                    $dir = $this->get('kernel')->getProjectDir() . "/public/galerie/" . $entreprise->getId() . "/" . $galerie->getCreatedAt()->format('Y-m-d') . "/";
                    $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/galerie/" . $entreprise->getId() . "/" . $galerie->getCreatedAt()->format('Y-m-d') . "/compressed/";
                    if(is_file($compressed_dir.$galerie->getNom()))
                        continue;

                    try {
                        if (!is_dir($compressed_dir)) {
                            mkdir($compressed_dir, 0777, true);
                        }
                        $this->make_thumb($dir.$galerie->getNom(), $compressed_dir.$galerie->getNom(),200, $galerie->getExtension());

                    } catch (FileException $e) {
                    }
                }
                
            }

        }
        return new JsonResponse(['success'=>true]);
    }


    /**
     * @Route("/chantier/{id}/", name="image_list", methods={"GET"})
     */
    public function image_list($id, Request $request, Session $session)
    {
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($id);
        if(!$chantier){
            $this->get('session')->getFlashBag()->add('error', 'Désolé ! Ce chantier n\'existe pas');
            return $this->redirectToRoute('galerie_list');
        }

        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $chantiers = $em->createQueryBuilder()
            ->select('g')
            ->from('App\Entity\Galerie', 'g')
            ->andWhere('g.entreprise = :entreprise')
            ->andWhere('g.chantier = :chantier')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('chantier', $id)
            ->orderBy('g.created_at', 'desc');

        $adapter = new DoctrineORMAdapter($chantiers);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }
        /** @var Galerie $file */
        foreach ($pager->getCurrentPageResults() as $file) {
            if ($file->getExtension() == "mp4") {
                /* $path = '/galerie/'.$user->getEntreprise()->getId()."/".$file->getCreatedAt()->format('Y-m-d')."/".$file->getNom();
                 $ffmpeg = FFMpeg::create();
                 $video = $ffmpeg->open($path);
                 $frame = $video->frame(TimeCode::fromSeconds(42));
                 dump($frame);die();*/
                $file->setThumbnail('');
            } else {
                $file->setThumbnail('/galerie/' . $file->getEntreprise()->getId() . "/" . $file->getCreatedAt()->format('Y-m-d') . "/" . $file->getNom());
            }

            try {
                list($width, $height) = getimagesize($this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL).$file->getUrl());
                $file->width = $width;
                $file->height = $height;
            } catch (\Exception $e) {
                $file->width = 0;
                $file->height = 0;
            }

        }

        return $this->render('galerie/index_chantier.html.twig', [
            'pager' => $pager,
            'chantier' => $chantier,
        ]);
    }

    public function base64_to_jpeg($base64_string, $output_file) {
        // open the output file for writing
        $ifp = fopen( $output_file, 'wb' ); 

        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode( ',', $base64_string );

        // we could add validation here with ensuring count( $data ) > 1
        fwrite( $ifp, base64_decode( $data[ 1 ] ) );

        // clean up the file resource
        fclose( $ifp ); 

        return $output_file; 
    }

    public function baseToJpg(){

        $users = $this->getDoctrine()->getRepository(Utilisateur::class)->findAll();
        $entityManager = $this->getDoctrine()->getManager();
        foreach ($users as $user) {
            $ph = $user->getImage();

            if($ph) {

                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/user_avatars/" . $user->getEntreprise()->getId() . "/";
                $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/uploads/user_avatars/" . $user->getEntreprise()->getId() . "/compressed/";

                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!is_dir($compressed_dir)) {
                        mkdir($compressed_dir, 0777, true);
                    }
                } catch (FileException $e) {}


                $extension =  "jpeg";                  
                $filename = md5(time().uniqid()).".".$extension;

                $ph = "data:image/jpeg;base64,".$ph;
                $file = $this->base64_to_jpeg($ph, $dir.$filename);
                $file2 = $this->base64_to_jpeg($ph, $compressed_dir.$filename);

                $user->setAvatar($filename);

                
                //$this->make_thumb($dir.$filename, $compressed_dir.$filename,200,$extension);
            }
        }
        $entityManager->flush();
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"})
     */
    public function new(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $user = $this->getUser();
        $galerie = new Galerie();
        $form = $this->createForm(GalerieType::class, $galerie, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);
        if($request->get('chantier')){
            $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($request->get('chantier'));
            if(!$chantier){
                $this->get('session')->getFlashBag()->add('error', 'Désolé ! Ce chantier n\'existe pas');
                return $this->redirectToRoute('galerie_list');
            }
        }

        if ($form->isSubmitted()) {

            /** @var UploadedFile $fichier */
            $fichiers = $form->get('fichier')->getData();
            if ($fichiers) {
                $dir = $this->get('kernel')->getProjectDir() . "/public/galerie/" . $this->session->get('entreprise_session_id') . "/" . date('Y-m-d') . "/";

                $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/galerie/" . $this->session->get('entreprise_session_id') . "/" . date('Y-m-d') . "/compressed/";
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!is_dir($compressed_dir)) {
                        mkdir($compressed_dir, 0777, true);
                    }
                } catch (FileException $e) {
                }


                $tabPhoto = '';
                $userExist =  $this->getDoctrine()->getRepository(Utilisateur::class)->find($this->getUser()->getUsername());
                $userExistId = !is_null($userExist) ? $userExist->getId() : 1420;
                $chantierId = $chantier->getChantierId();
                $entrepriseId = $this->session->get('entreprise_session_id');
                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($entrepriseId);


                $tabPhotoHeic = "";
                $photos = explode("##", $request->request->get("fileBase64"));
                $photos = array_filter($photos, function($value){
                    return !is_null($value) && $value !== '';
                });

                foreach ($photos as $ph) {  
                    $extension =  explode(";", explode("/", $ph)[1])[0];                  
                    $filename = md5(time().uniqid()).".".$extension;
                    $file = $this->base64_to_jpeg($ph, $dir.$filename);

                    if(in_array($extension,array("png","jpeg","gif","jpg", "bin", "heic"))){
                        $type = 1;
                    }
                    elseif(in_array($extension,array("flv","mp4","m3u8","ts","3gp","mov","avi","wmv"))){
                        $type = 2;
                    }

                    $tabPhotoHeic .= '('.$chantierId.',"'.$filename.'","'.$extension.'",'.$type.','.$entrepriseId.','.$userExistId.',"'.date('Y-m-d H:i:s').'"),';


                    $this->make_thumb($dir.$filename, $compressed_dir.$filename,200,$extension);
                }
                $this->galerieRepository->insertPhoto($tabPhotoHeic);
                    
                
                /*else{
                    foreach ($fichiers as $value) {
                        $originalFilename = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $this->slugify($originalFilename);
                        $extension = strtolower($value->guessExtension());
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $value->guessExtension();
                       $value->move($dir, $newFilename);

                        if(in_array($extension,array("png","jpeg","gif","jpg", "bin", "heic"))){
                            $type = 1;
                        }
                        elseif(in_array($extension,array("flv","mp4","m3u8","ts","3gp","mov","avi","wmv"))){
                            $type = 2;
                        }
                       
                       $tabPhoto .= '('.$chantierId.',"'.$newFilename.'","'.$extension.'",'.$type.','.$entrepriseId.','.$userExistId.',"'.date('Y-m-d H:i:s').'"),';

                        $this->make_thumb($dir.$newFilename, $compressed_dir.$newFilename,200,$extension);
                    }
                    $this->galerieRepository->insertPhoto($tabPhoto);
                }*/
            }

            if($request->get('chantier')){
                return $this->redirectToRoute('galerie_image_list',['id'=>$request->get('chantier')]);
            }

            return $this->redirectToRoute('galerie_list');
        }

        return $this->render('galerie/add.html.twig', [
            'galerie' => $galerie,
            'form' => $form->createView(),
            'chantier' => $request->get('chantier'),
        ]);
    }


    /**
     * @Route("/{fileid}/download", name="download")
     */
    public function download(Request $request, $fileid)
    {
        $file = $this->getDoctrine()->getRepository(Galerie::class)->find($fileid);
        $path = $this->get('kernel')->getProjectDir().'/public/galerie/'.$file->getEntreprise()->getId()."/".$file->getCreatedAt()->format('Y-m-d')."/".$file->getNom();
        header('Content-Type: application/octet-stream');
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename='.$file->getNom());
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        ob_clean();
        flush();
        readfile($path);
        exit;
    }


    /**
     * @Route("/{fileid}/choose", name="choose", methods={"GET","POST"})
     */
    public function changeDisplay(Request $request, $fileid)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var Chantier $galerie */
        $galerie = $this->getDoctrine()->getRepository(Galerie::class)->find($fileid);
        if(!$galerie){
            $this->get('session')->getFlashBag()->add('error', 'Désolé ! Cette galerie n\'existe pas');
            return $this->redirectToRoute('galerie_list');
        }

        if(!$galerie->getChantier()){
            $this->get('session')->getFlashBag()->add('error', 'Désolé ! Ce chantier n\'existe pas');
            return $this->redirectToRoute('galerie_list');
        }

        $galerie->getChantier()->setDefaultGalerie($galerie);

        $entityManager->flush();

        $this->get('session')->getFlashBag()->add('success', 'L\'image principale a été définie avec succès');
        return $this->redirectToRoute('galerie_image_list',['id'=>$galerie->getChantier()->getChantierId()]);
    }

    /**
     * @Route("/{fileid}/delete", name="delete")
     */
    public function delete(Request $request, $fileid)
    {
        $file = $this->getDoctrine()->getRepository(Galerie::class)->find($fileid);
        $path = $this->get('kernel')->getProjectDir().'/public/galerie/'.$file->getEntreprise()->getId()."/".$file->getCreatedAt()->format('Y-m-d')."/".$file->getNom();

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($file);
        $entityManager->flush();
        if(is_file($path)) {
            unlink($path);
        }
        $this->get('session')->getFlashBag()->add('info', 'Le fichier a bien été supprimé');

        if($request->query->get('redirect') == "home")
            return $this->redirectToRoute('home');
        else
            return $this->redirectToRoute('galerie_list');
    }

    public function make_thumb($src, $dest, $desired_width, $extension) {

        /* read the source image */
        if(is_file($src)){
            if(strtolower($extension) == "png")
                $source_image = imagecreatefrompng($src);
            elseif(strtolower($extension) == "jpeg")
                $source_image = imagecreatefromjpeg($src);
            elseif(strtolower($extension) == "jpg")
                $source_image = imagecreatefromjpg($src);
            else
                return 1;

            $width = imagesx($source_image);
            $height = imagesy($source_image);

            /* find the "desired height" of this thumbnail, relative to the desired width  */
            $desired_height = floor($height * ($desired_width / $width));

            /* create a new, "virtual" image */
            $virtual_image = imagecreatetruecolor($desired_width, $desired_height);

            /* copy source image at a resized size */
            imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

            /* create the physical thumbnail image to its destination */
            imagejpeg($virtual_image, $dest);
        }
        
    }

}
