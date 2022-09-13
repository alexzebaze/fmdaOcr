<?php

namespace App\Controller\Rest;

use App\Entity\Utilisateur;
use App\Entity\Galerie;
use App\Form\UtilisateurMobileType;
use App\Form\UtilisateurPasswordMobileType;
use App\Form\UtilisateurType;
use App\Serializer\FormErrorSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations\Post;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


/**
 * @Route("/utilisateur")
 */
class ApiUtilisateurController extends AbstractController
{
    private $formErrorSerializer;
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        FormErrorSerializer $formErrorSerializer
    )
    {
        $this->em = $em;
        $this->formErrorSerializer = $formErrorSerializer;
    }

    /*
     *
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *     ),
     */
    /**
     * @SWG\Post(
     *     tags={"utilisateur"},
     *     description="Inscription d'un utilisateur",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="code", type="string"),
     *              @SWG\Property(property="firstname", type="string"),
     *              @SWG\Property(property="lastname", type="string"),
     *              @SWG\Property(property="email", type="string"),
     *              @SWG\Property(property="phone", type="string"),
     *              @SWG\Property(property="password", type="string"),
     *              @SWG\Property(property="password_repeat", type="string")
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
     * @Route("/register", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = new Utilisateur();
        $form = $this->createForm(UtilisateurMobileType::class, $user);
        $form->submit($data);
        if ($form->isValid()) {
            $entreprise = $this->em->createQueryBuilder()
                ->select('e')
                ->from('App\Entity\Entreprise', 'e')
                ->andWhere('e.code = :entreprise')
                ->setParameter('entreprise', $data['code'])
                ->getQuery()
                ->getOneOrNullResult();

            if($entreprise) {
                $user->setEntreprise($entreprise);
                $this->em->persist($user);
                $this->em->flush();
                return new JsonResponse(array('success' => true), Response::HTTP_OK);
            } else {
                return new JsonResponse(array('error' => [
                    "children"=> [
                        "code"=> [
                            "errors"=> [
                                "Le code ne correspond à aucune entreprise"
                            ]
                        ],
                        "lastname"=> [],
                        "firstname"=> [],
                        "email"=> [],
                        "phone"=> [],
                        "password"=> [
                            "children"=> [
                                "first"=> [],
                                "second"=> []
                            ]
                        ]
                    ]
                ]), Response::HTTP_BAD_REQUEST);
            }
        } else {
            return new JsonResponse(array('error' => $this->formErrorSerializer->convertFormToArray($form)), Response::HTTP_BAD_REQUEST);
        }
    }

    /*
     *
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *     ),
     */
    /**
     * @SWG\Post(
     *     tags={"utilisateur"},
     *     description="Modification du mot de passe",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="last_password", type="string"),
     *              @SWG\Property(property="password_first", type="string"),
     *              @SWG\Property(property="password_second", type="string")
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
     * @Route("/change_password", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function change_password(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        
        if(isset($data['last_password'])){
            if(md5($data['last_password']) != $user->getPassword()){
                return new JsonResponse(array('error' => [
                    "children"=> [
                        "last_password"=> [
                            "errors"=> [
                                "Votre ancien mot de passe n'est pas correct"
                            ]
                        ],
                        "password"=> [
                            "children"=> [
                                "first"=> [],
                                "second"=> []
                            ]
                        ]
                    ]
                ]), Response::HTTP_BAD_REQUEST);
            }
        }
        else{
            return new JsonResponse(array('error' => [
                "children"=> [
                    "last_password"=> [
                        "errors"=> [
                            "Veuillez remplir votre ancien mot de passe"
                        ]
                    ],
                    "password"=> [
                        "children"=> [
                            "first"=> [],
                            "second"=> []
                        ]
                    ]
                ]
            ]), Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(UtilisateurPasswordMobileType::class, $user);
        $form->submit($data);
        if ($form->isValid()) {
            $this->em->persist($user);
            $this->em->flush();
            return new JsonResponse(array('success' => true), Response::HTTP_OK);
        } else {
            return new JsonResponse(array('error' => $this->formErrorSerializer->convertFormToArray($form)), Response::HTTP_BAD_REQUEST);
        }
    }

    /** 
     * @SWG\Post(
     *     tags={"utilisateur_history"},
     *     description="Récupération de l'historique de l'utilisateur",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="userid", type="string")
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
     * @Route("/history", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request){
        
        $data = json_decode($request->getContent(), true);

        if(!isset($data['userid'])){
            return new JsonResponse(array("error" => "Veuillez entrer l'identifiant de l'utilisateur"), Response::HTTP_BAD_REQUEST);
        }

        $query = $this->em->createQueryBuilder()
            ->select('s')
            ->from('App\Entity\Horaire', 's')
            ->where('s.userid = :userid')
            ->andWhere('YEAR(s.datestart) = :year')
            ->andWhere('MONTH(s.datestart) = :month')
            ->andWhere('s.chantierid IS NOT NULL')
            ->andWhere('s.dateend IS NOT NULL')
            ->setParameter('userid', $data['userid'])
            ->setParameter('year', isset($data['year']) ? $data['year'] : date('Y'))
            ->setParameter('month', isset($data['month']) ? $data['month'] : date('m'))
            ->orderBy('s.idsession','DESC');

        $horaires = $query
                ->getQuery()
                ->getResult();
        $horairesArray = [];
        foreach ($horaires as $horaire) {
            if(!$horaire->getChantierid())
                continue;
            $horairesArray[] = [
                "datestart"=>$horaire->getDatestart(),
                "dateend"=>$horaire->getDateend(),
                "time"=>$horaire->getTime(),
                'pause'=>$horaire->getPause(),
                "userid"=>$horaire->getUserid(),
                "fonction"=>$horaire->getFonction(),
                "chantierId"=>$horaire->getChantierid(),
                "createdAt"=>$horaire->getCreatedAt(),
            ];
        }
        return new JsonResponse(['data'=>$horairesArray]);
    }

    /**
     * @Route("/getby-entreprise", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"get_utilisateurs"},
     *     description="liste des utilisateurs de son entreprise",
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
    public function getUtilisateurs(Request $request){
        $user = $this->getUser();

        $utilisateurs = $this->getDoctrine()->getRepository(Utilisateur::class)->findBy(['entreprise'=>$user->getEntreprise()], ['firstname'=>"ASC"]);
        
        $utilisateursArr=[];
        $hote = $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        foreach ($utilisateurs as $value) {
            $utilisateursArr[] = [
                'id'=>$value->getUid(),
                'firstname'=>$value->getFirstname(),
                'lastname'=>$value->getLastname(),
                'email'=>$value->getEmail(),
                'phone'=>$value->getPhone(),
                'address'=>$value->getAddress(),
                'city'=>$value->getCity(),
                'code_postal'=>$value->getCp(),
                'etat'=>$value->getEtat(),
                'categoryuser'=>$value->getCategoryuser(),
                'poste'=>$value->getPoste(),
                'sous_traitant'=>$value->getSousTraitant(),
                'heure_hebdo'=>$value->getHeureHebdo(),
                'taux_horaire'=>$value->getTauxHoraire(),
                'type_contrat'=>$value->getTypeContrat(),
                'avatar'=>!is_null($value->getAvatar()) ? $hote.'uploads/user_avatars/'.$value->getEntreprise()->getId().'/compressed/'.$value->getAvatar() : ''
            ];
        }

        return new JsonResponse(array('status' => 200, 'response'=>$utilisateursArr), Response::HTTP_OK);
          
    }


    /*
     * @FOS\RestBundle\Controller\Annotations\Options("/login_check_custom")
     * @param Request $request
     * @return JsonResponse

    public function loginCheckCustom(Request $request, JWTTokenManagerInterface $jwt): JsonResponse
    {
        $postdata = file_get_contents("php://input");
        $json = json_decode($postdata);
        if(!isset($json->email) || empty($json->email))  {
            return new JsonResponse(array('error' => '_email not found'), Response::HTTP_OK);
        }
        if(!isset($json->password) || empty($json->password)) {
            return new JsonResponse(array('error' => '_password not found'), Response::HTTP_OK);
        }

        $user = $this->em->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\Utilisateur', 'u')
            ->andWhere('u.email = :email')
            ->andWhere('u.password = :password')
            ->setParameter('email', $json->email)
            ->setParameter('password', md5($json->password))
            ->getQuery()
            ->getOneOrNullResult();

        if($user) {
            $token = $jwt->create($user);
        } else {
            return new JsonResponse(array('error' => 'Invalid credentials'), Response::HTTP_OK);
        }
        return new JsonResponse(array('token' => $token), Response::HTTP_OK);
    }*/
}