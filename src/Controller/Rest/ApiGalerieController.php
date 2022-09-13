<?php

namespace App\Controller\Rest;

use App\Kernel;
use Mimey\MimeTypes;
use App\Entity\Galerie;
use App\Entity\Chantier;
use App\Form\GalerieType;
use Pagerfanta\Pagerfanta;
use App\Form\GalerieFusionType;
use App\Form\GalerieMobileType;
use Swagger\Annotations as SWG;
use App\Utils\UploadedBase64File;
use App\Repository\GalerieRepository;
use App\Repository\UtilisateurRepository;
use App\Controller\Traits\CommunTrait;
use App\Serializer\FormErrorSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\GlobalService;

/**
 * @Route("/galerie")
 */
class ApiGalerieController extends AbstractController  {
    use CommunTrait;

    private $formErrorSerializer;
    private $galerieRepository;
    private $utilisateurRepository;
    private $em;
    private $global_s;

    public function __construct(
        GalerieRepository $galerieRepository, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em,
        FormErrorSerializer $formErrorSerializer, GlobalService $global_s
    )
    {
        $this->em = $em;
        $this->formErrorSerializer = $formErrorSerializer;
        $this->galerieRepository = $galerieRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->global_s = $global_s;
    }

    /**
     * @Route("/add", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"galerie"},
     *     description="Ajouter un document",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="fichier", type="string", example="Base64"),
     *              @SWG\Property(property="chantier", type="int", example=1),
     *         )
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="token for Authentification",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Invalid JSON",
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Erreur",
     *     ),
     * )
     */
    public function add(Request $request, KernelInterface $kernel): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $galerie = new Galerie();
        $form = $this->createForm(GalerieMobileType::class, $galerie, array('entreprise' => $user->getEntreprise()));
        $form->submit($data);
        if ($form->isValid()) {
            $fichier = isset($data['fichier']) ? $data['fichier']: null;
            if ($fichier) {
                $mimes = new MimeTypes;
                $namefiletemp = $galerie->getChantier()->getNameentreprise()."-".uniqid();
                $imageFile = new UploadedBase64File($fichier, $namefiletemp);
                $safeFilename = $this->slugify($namefiletemp);
                $extension = $mimes->getExtension($imageFile->getMimeType());
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;


                $dir = $kernel->getProjectDir() . "/public/galerie/" . $user->getEntreprise()->getId() . "/" . date('Y-m-d') . "/";
                $compressed_dir = $kernel->getProjectDir() . "/public/galerie/" . $user->getEntreprise()->getId() . "/" . date('Y-m-d') . "/compressed/";
                // Move the file to the directory where brochures are stored
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!is_dir($compressed_dir)) {
                        mkdir($compressed_dir, 0777, true);
                    }
                    $imageFile->move(
                        $dir,
                        $newFilename
                    );
                    $this->make_thumb($dir.$newFilename, $compressed_dir.$newFilename,200, $extension);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($data['chantier']);
                if(isset($data['chantier'])){
                    $galerie->setChantier($chantier);
                }
                $galerie->setNom($newFilename);
                $galerie->setExtension($extension);
                if(in_array($extension,array("png","jpeg","gif","jpg"))){
                    $galerie->setType(1);
                }
                elseif(in_array($extension,array("flv","mp4","m3u8","ts","3gp","mov","avi","wmv"))){
                    $galerie->setType(2);
                }
                $galerie->setEntreprise($user->getEntreprise());
                $galerie->setUser($user);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($galerie);
                $entityManager->flush();
            } else {
                return new JsonResponse(array('error' => 'file_not_found'), Response::HTTP_BAD_REQUEST);
            }
            return new JsonResponse(array('success' => true), Response::HTTP_OK);
        } else {
            return new JsonResponse(array('error' => $this->formErrorSerializer->convertFormToArray($form)), Response::HTTP_BAD_REQUEST);
        }
    }


    /**
     * @Route("/merge/images", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"galerie_fusion"},
     *     description="Fusionner plusieurs images",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="image1", type="string", example="Base64"),
     *              @SWG\Property(property="image2", type="string", example="Base64"),
     *              @SWG\Property(property="chantier", type="int", example=1),
     *         )
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="token for Authentification",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Invalid JSON",
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Erreur",
     *     ),
     * )
     */
    public function mergeImages(Request $request, KernelInterface $kernel): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $galerie = new Galerie();
        $form = $this->createForm(GalerieFusionType::class, $galerie, array('entreprise' => $user->getEntreprise()));
        $form->submit($data);
        if ($form->isValid()) {
            $image1 = isset($data['image1']) ? $data['image1']: null;
            $image2 = isset($data['image2']) ? $data['image2']: null;

            if(!$image1 || !$image2){
                return new JsonResponse([
                    'status'=>'error',
                    'message'=>"Les informations n'ont pas bien été remplies",
                ]);
            }

            try {
                
                $src = imagecreatefromstring(base64_decode($data['image1']));
                $dest = imagecreatefromstring(base64_decode($data['image2']));

                $size_img1 = getimagesizefromstring(base64_decode($data['image1']));
                $size_img2  = getimagesizefromstring(base64_decode($data['image2']));
                
                $h1 = $size_img1[0];
                $w1 = $size_img1[1];

                $h2 = $size_img2[0];
                $w2 = $size_img2[1];

            } catch (\Exception $e) {
                return new JsonResponse([
                    'error'=>$e->getMessage(),
                ]);
            }

            $index = imagecolorat($dest, 0, 0);
            $rgb = imagecolorsforindex($dest, $index);
            imagecolortransparent($dest, $index);

            $dest = imagescale($dest, $w1, $h1);

            $index = imagecolorat($dest, 0, 0);
            $rgb = imagecolorsforindex($dest, $index);
            imagecolortransparent($dest, $index);

            imagecopymerge($src, $dest, 0, 0, 0, 0, $w1, $h1, 100);

            ob_start(); // Let's start output buffering.
            imagepng($src); //This will normally output the image, but because of ob_start(), it won't.
            $contents = ob_get_contents(); //Instead, output above is saved to $contents
            ob_end_clean(); //End the output buffer.
            
            $mimes = new MimeTypes;
            $namefiletemp = $galerie->getChantier()->getNameentreprise()."-".uniqid();
            $imageFile = new UploadedBase64File(base64_encode($contents), $namefiletemp);
            $safeFilename = $this->slugify($namefiletemp);
            $extension = $mimes->getExtension($imageFile->getMimeType());
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            $dir = $kernel->getProjectDir() . "/public/galerie/" . $user->getEntreprise()->getId() . "/" . date('Y-m-d') . "/";
            $compressed_dir = $kernel->getProjectDir() . "/public/galerie/" . $user->getEntreprise()->getId() . "/" . date('Y-m-d') . "/compressed/";
            // Move the file to the directory where brochures are stored
            try {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                if (!is_dir($compressed_dir)) {
                    mkdir($compressed_dir, 0777, true);
                }
                $imageFile->move(
                    $dir,
                    $newFilename
                );
                $this->make_thumb($dir.$newFilename, $compressed_dir.$newFilename,200, $extension);
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }

            // updates the 'brochureFilename' property to store the PDF file name
            // instead of its contents
            $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($data['chantier']);
            if(isset($data['chantier'])){
                $galerie->setChantier($chantier);
            }
            $galerie->setNom($newFilename);
            $galerie->setExtension($extension);
            if(in_array($extension,array("png","jpeg","gif","jpg"))){
                $galerie->setType(1);
            }
            elseif(in_array($extension,array("flv","mp4","m3u8","ts","3gp","mov","avi","wmv"))){
                $galerie->setType(2);
            }
            $galerie->setEntreprise($user->getEntreprise());
            $galerie->setUser($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($galerie);
            $entityManager->flush();

            return new JsonResponse(array('success' => true), Response::HTTP_OK);
            
        } else {
            return new JsonResponse(array('error' => $this->formErrorSerializer->convertFormToArray($form)), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/user-recap-gallery", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"default_galerie"},
     *     description="Affiche le compteur d'image gallery pour chaque utilisateur",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="token for Authentification",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Invalid JSON",
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Erreur",
     *     ),
     * )
     */
    public function recapUserGallery(){
        $user = $this->getUser();
        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true, $user->getEntreprise()->getId());
        
        $galleryUser = $this->galerieRepository->getGalleryGroupByUser($user->getEntreprise()->getId());
        usort($galleryUser, function($a, $b) {
            return $b['nbr_gallery'] - $a['nbr_gallery'];
        });

        $galleryUserArr = [];
        foreach ($galleryUser as $key => $value) {
            $galleryItem = [
                "firstname" => $value['firstname'],
                "lastname" => $value['lastname'],
                "nbr_photo" => $value['nbr_gallery'],
                'user_avatar' => ''
            ];

            $searchIndexUser = array_search($value['uid'], array_column($utilisateurs, 'uid'));
            if($searchIndexUser !== false){
                $galleryItem['user_avatar'] = $utilisateurs[$searchIndexUser]['image'] ;
            }

            $galleryUserArr[] = $galleryItem;
        }


        return new JsonResponse(array('success' => true, 'data'=>$galleryUserArr), Response::HTTP_OK);
          
    }



    /**
     * @Route("/all", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"default_galerie"},
     *     description="Affiche toutes la galerie d'un chantier",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="token for Authentification",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Invalid JSON",
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Erreur",
     *     ),
     * )
     */
    public function getGalerie(Request $request){
        $user = $this->getUser();
        $chantierId = $request->query->get('chantier_id');

        $entityManager = $this->getDoctrine()->getManager();
        $galeries = $entityManager->createQueryBuilder()
            ->select('g')
            ->from('App\Entity\Galerie', 'g')
            ->andWhere('g.chantier = :chantierId')
            ->setParameter('chantierId', $chantierId)
            ->orderBy('g.id', 'DESC')
            ->getQuery()
            ->getResult();


        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise($user->getEntreprise()->getId());

        $galerieArr = [];
        foreach ($galeries as $value) {

            $compresseImage = "";
            if($value->getUser() && $value->getUser()->getImage()){
                $compresseImage = $this->global_s->miniatureImage($value->getUser()->getImage());
            }

            $hote = $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL);
            $galerieArr[] = [
                'id'=>$value->getId(),
                'image'=>$hote.$value->getUrl(),
                'image_compress'=> $hote.$value->getCompressedUrl(),
                'user'=>[
                    'id'=> !is_null($value->getUser()) ? $value->getUser()->getUid() : '',
                    'name'=> !is_null($value->getUser()) ? $value->getUser()->getFirstname().' '.$value->getUser()->getLastname() : '',
                    'photo_compress'=> !is_null($value->getUser()) ? $hote.'uploads/user_avatars/'.$value->getUser()->getEntreprise()->getId().'/compressed/'.$value->getUser()->getAvatar() : '',
                ]
            ];
        }    

        return new JsonResponse(array('success' => true, 'galeries'=>$galerieArr), Response::HTTP_OK);
          
    }


    public function make_thumb($src, $dest, $desired_width, $extension) {

        /* read the source image */
        if(is_file($src)){
            if(in_array($extension,["mp4","pdf","mov"]))
                return;
            elseif($extension == "png")
                $source_image = imagecreatefrompng($src);
            elseif($extension == "gif")
                $source_image = imagecreatefromgif($src);
            else
                $source_image = imagecreatefromjpeg($src);
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

    public function save_temp_image($contents, $kernel){
        $mimes = new MimeTypes;
        $namefiletemp = date('YmdHis')."-".uniqid();
        $imageFile = new UploadedBase64File(base64_encode($contents), $namefiletemp);
        $safeFilename = $this->slugify($namefiletemp);
        $extension = $mimes->getExtension($imageFile->getMimeType());
        $newFilename = $safeFilename . '.' . $extension;

        $dir = $kernel->getProjectDir() . "/public/temp/";
        // Move the file to the directory where brochures are stored
        try {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $imageFile->move(
                $dir,
                $newFilename
            );

            return $newFilename;
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }
    }
}