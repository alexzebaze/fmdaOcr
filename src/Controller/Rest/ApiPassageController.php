<?php

namespace App\Controller\Rest;

use App\Kernel;
use Mimey\MimeTypes;
use App\Entity\Passage;
use App\Entity\Chantier;
use App\Entity\Utilisateur;
use App\Entity\Fournisseurs;
use App\Form\GalerieType;
use Pagerfanta\Pagerfanta;
use App\Form\GalerieFusionType;
use App\Form\GalerieMobileType;
use Swagger\Annotations as SWG;
use App\Utils\UploadedBase64File;
use App\Repository\AchatRepository;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\PassageService;

/**
 * @Route("/passage")
 */
class ApiPassageController extends AbstractController  {
    use CommunTrait;

    private $passage_s;
    private $formErrorSerializer;
    private $achatRepository;
    private $utilisateurRepository;
    private $em;

    public function __construct(
        AchatRepository $achatRepository, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em, FormErrorSerializer $formErrorSerializer, PassageService $passage_s)
    {
        $this->em = $em;
        $this->passage_s = $passage_s;
        $this->formErrorSerializer = $formErrorSerializer;
        $this->achatRepository = $achatRepository;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    /**
     * @Route("/new", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"passage_new"},
     *     description="Enregistrement d'un passage",
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
    public function passageNew(Request $request){
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $fournisseurId = $data['fournisseur_id'];
        $chantierId = $data['chantier_id'];
        $dateDetection = is_null($data['date']) ? new \DateTime() : new \DateTime($data['date']);

        if(!is_null($user) && $fournisseurId && $chantierId){
            $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurId);
            $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);

            $passage = new Passage();
            $passage->setUtilisateur($user);
            $passage->setChantier($chantier);
            $passage->setFournisseur($fournisseur);
            $passage->setDateDetection($dateDetection);
            $passage->setEntreprise($user->getEntreprise());


            $bl = $this->achatRepository->getBlByCHantierFournDate($chantier->getChantierId(), $fournisseur->getId(), $dateDetection->format('Y-m-d'));

            if($bl){
                $bl = $this->achatRepository->find($bl['id']); 
                if(is_null($bl->getPassage()))
                    $passage->setBonLivraison($bl);
            }
            //$passageExist = $this->passageRepository->isPassage($passage->getChantier()->getChantierId(), $passage->getFournisseur()->getId(), $dateDetection->format('Y-m-d H:i:s'));


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($passage);
            $entityManager->flush();
            return new JsonResponse(array('status' => 200, 'response'=>"Enregistrement effectué avec succèss"), Response::HTTP_OK);
        }      

        return new JsonResponse(array('status' => 500, 'response'=>"Veuillez renseigner toutes les informations"), Response::HTTP_BAD_REQUEST);
          
    }


    /**
     * @Route("/getby-chantier", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"get_by_chanter"},
     *     description="liste de passage d'un chantier",
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
    public function getByChantier(Request $request){
        $user = $this->getUser();
        $chantierId = $request->query->get('chantier_id');
        $passages = $this->getDoctrine()->getRepository(Passage::class)->findBy(['chantier'=>$chantierId], ['date_detection'=>"DESC"]);
        
        $passagesArr=[];
        $hote = $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        foreach ($passages as $value) {
            $passagesArr[] = [
                'id'=>$value->getId(),
                'date_detection'=>$value->getDateDetection()->format('Y-m-d H:i:s'),
                'user'=>[
                    'id'=>$value->getUtilisateur()->getUid(),
                    'firstname'=>$value->getUtilisateur()->getFirstname(),
                    'lastname'=>$value->getUtilisateur()->getLastname(),
                    'avatar'=>!is_null($value->getUtilisateur()) ? $hote.'uploads/user_avatars/'.$value->getUtilisateur()->getEntreprise()->getId().'/compressed/'.$value->getUtilisateur()->getAvatar() : '',
                ],
                'fournisseur'=>[
                    'id'=>$value->getFournisseur()->getId(),
                    'nom'=>$value->getFournisseur()->getNom(),
                ],
                'chantier'=>[
                    'id'=>$value->getChantier()->getChantierId(),
                    'nom'=>$value->getChantier()->getNameentreprise(),
                ]
            ];
        }

        return new JsonResponse(array('status' => 200, 'response'=>$passagesArr), Response::HTTP_OK);
          
    }
}