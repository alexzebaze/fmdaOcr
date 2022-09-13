<?php

namespace App\Controller\Rest;

use App\Entity\Fournisseurs;
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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


/**
 * @Route("/fournisseur")
 */
class ApiFournisseurController extends AbstractController
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



    /**
     * @Route("/list", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"get_order"},
     *     description="Liste des fournisseurs",
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
    public function getList(Request $request): JsonResponse
    {
        $utilisateur = $this->getUser();
        $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->findFournisseurWithFavorite($utilisateur->getUid(), $utilisateur->getEntreprise()->getId());

        $column  = array_column($fournisseurs, "favoris");
        array_multisort($column, SORT_DESC, $fournisseurs); 
        
        /*$fournisseursArr = [];
        foreach ($fournisseurs as $value) {
            $fournisseursArr[] = [
                'id'=>$value['id'],
                'nom'=>$value['nom'],
                'email'=>$value['email'],
                'favoris'=>$value['is_favoris']
            ];
        }*/

        $fournisseurs = array_map(function($fournisseur){
            $fournisseur['favoris'] = ($fournisseur['favoris'] >= 1) ? true : false;
            return $fournisseur;
        }, $fournisseurs);


        return new JsonResponse([
            'status'=>'success',
            'fournisseurs'=> $fournisseurs
        ]);
    }


    /**
     * @Route("/add-favoris", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"passage_new"},
     *     description="Ajouter un utilisateur en favoris",
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
    public function addFavoris(Request $request){
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $fournisseurId = $data['fournisseur_id'];
        $action = $data['action'];

        if(!is_null($user) && $fournisseurId){
            $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurId);
            
            if($action)
                $user->addFournisseur($fournisseur);
            else
                $user->removeFournisseur($fournisseur);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($fournisseur);
            $entityManager->flush();
            return new JsonResponse(array('status' => 200, 'response'=>"Operation effectuée avec succèss"), Response::HTTP_OK);
        }      

        return new JsonResponse(array('status' => 500, 'response'=>"Veuillez renseigner toutes les informations"), Response::HTTP_BAD_REQUEST);
          
    }

}