<?php

namespace App\Controller\Rest;

use App\Kernel;
use Mimey\MimeTypes;
use App\Entity\Garrage;
use App\Entity\Chantier;
use App\Entity\Utilisateur;
use App\Entity\Fournisseurs;
use App\Form\GalerieType;
use Pagerfanta\Pagerfanta;
use App\Form\GalerieFusionType;
use App\Form\GalerieMobileType;
use Swagger\Annotations as SWG;
use App\Utils\UploadedBase64File;
use App\Repository\UtilisateurRepository;
use App\Repository\GarrageRepository;
use App\Controller\Traits\CommunTrait;
use App\Serializer\FormErrorSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * @Route("/garrage")
 */
class GarrageController extends AbstractController  {
    use CommunTrait;

    private $formErrorSerializer;
    private $utilisateurRepository;
    private $garrageRepository;
    private $em;

    public function __construct(UtilisateurRepository $utilisateurRepository, GarrageRepository $garrageRepository, EntityManagerInterface $em, FormErrorSerializer $formErrorSerializer)
    {
        $this->em = $em;
        $this->formErrorSerializer = $formErrorSerializer;
        $this->garrageRepository = $garrageRepository;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    /**
     * @Route("/new", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"garrage_new"},
     *     description="Enregistrement d'un garrage",
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
    public function garrageNew(Request $request){
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $nom = $data['nom'];
        $numero = $data['numero'];

        if(!is_null($user)){

            $garrage = new Garrage();
            $garrage->setUtilisateur($user);
            $garrage->setNom($nom);
            $garrage->setNumero($numero);
            $garrage->setEntreprise($user->getEntreprise());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($garrage);
            $entityManager->flush();
            return new JsonResponse(array('status' => 200, 'response'=>"Enregistrement effectué avec succèss"), Response::HTTP_OK);
        }      

        return new JsonResponse(array('status' => 500, 'response'=>"Veuillez renseigner toutes les informations"), Response::HTTP_BAD_REQUEST);
          
    }


    /**
     * @Route("/list", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"get_by_chanter"},
     *     description="liste les garrage d'un utilisateur",
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
    public function getByUser(Request $request){
        $user = $this->getUser();
        $garrages = $this->getDoctrine()->getRepository(Garrage::class)->findBy(['entreprise'=>$user->getEntreprise()]);
        
        $garragesArr=[];
        $hote = $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        foreach ($garrages as $value) {
            $garragesArr[] = [
                'id'=>$value->getId(),
                'numero'=>$value->getNumero(),
                'nom'=>$value->getNom(),
                'user'=>[
                    'id'=>$value->getUtilisateur()->getUid(),
                    'firstname'=>$value->getUtilisateur()->getFirstname(),
                    'lastname'=>$value->getUtilisateur()->getLastname(),
                    'avatar'=>!is_null($value->getUtilisateur()) ? $hote.'uploads/user_avatars/'.$value->getUtilisateur()->getEntreprise()->getId().'/compressed/'.$value->getUtilisateur()->getAvatar() : '',
                ]
            ];
        }

        return new JsonResponse(array('status' => 200, 'response'=>$garragesArr), Response::HTTP_OK);
          
    }
}