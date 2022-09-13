<?php

namespace App\Controller\Rest;

use App\Entity\Galerie;
use App\Entity\Chantier;
use App\Entity\ChantierOrder;
use Swagger\Annotations as SWG;
use App\Controller\Traits\CommunTrait;
use App\Repository\ChantierRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ChantierOrderRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/chantiers")
 */
class ApiChantierController extends AbstractController  {
    use CommunTrait;

    private $em;
    private $chantierRepository;
    private $chantierOrderRepository;

    public function __construct(EntityManagerInterface $em, ChantierRepository $chantierRepository, ChantierOrderRepository $chantierOrderRepository)
    {
        $this->em = $em;
        $this->chantierRepository = $chantierRepository;
        $this->chantierOrderRepository = $chantierOrderRepository;
    }

    /**
     * @Route("/all", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"default_galerie"},
     *     description="Afficher la liste de chantier",
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
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $chantiers = $this->em->getRepository(Chantier::class)->findBy(['entreprise'=>$user->getEntreprise(), 'status'=> 1]);
        //$chantiers = $this->chantierOrderRepository->getOrderIdsByUtilisateurEntity($user);


        $chantiersArr = []; $chantiersOrderNull = [];
        foreach ($chantiers as $value) {
            //$value = $val->getChantier();

            if($value->getStatus() == 1){
                $order = $this->chantierOrderRepository->findOneBy(['utilisateur'=>$user, 'chantier'=>$value]);
                $item = [
                        'id'=>$value->getChantierId(),
                        'name'=>$value->getNameentreprise(),
                        'adress'=>$value->getAddress(),
                        'photo_compress'=>(!is_null($value->getDefaultGalerie())) ? $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL).$value->getDefaultGalerie()->getCompressedUrl() : $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL).'/assets/images/logotransfmda.png',
                        'photo'=>(!is_null($value->getDefaultGalerie())) ? $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL).$value->getDefaultGalerie()->getUrl() : $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL).'/assets/images/logotransfmda.png'
                    ];

                if(!is_null($order)){
                    $item['order'] = $order->getOrderNum();
                    $chantiersArr[] = $item;
                }
                else{
                    $item['order'] = null;
                    $chantiersOrderNull[] = $item;
                }
            }
        }

        $column  = array_column($chantiersArr, 'order');
        array_multisort($column, SORT_ASC, $chantiersArr);   

        $datas = array_merge($chantiersArr, $chantiersOrderNull);


        return new JsonResponse(['status'=>'success','chantiers'=>$datas]);
    }


        /**
     * @Route("/{id}/default-galerie/", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"default_galerie"},
     *     description="Afficher la galerie par défaut",
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
    public function default_galerie($id, Request $request): JsonResponse
    {
        /**
         * @var Chantier
         */
        $chantier = $this->em->getRepository(Chantier::class)->find($id);
        if(!$chantier)
            return new JsonResponse(array("error" => "Ce chantier n'existe pas"), Response::HTTP_BAD_REQUEST);

        return new JsonResponse(['status'=>'success','default_galerie'=>$chantier->getdefaultGalerie() ? $chantier->getdefaultGalerie()->getCompressedUrl() : '/assets/images/logotransfmda.png']);
    }


    /**
     * @Route("/update/order", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"save_order"},
     *     description="Sauvegarder l'ordre des chantiers d'un utilisateur",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="chantiers", type="array", example="[2,1]"),
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
    public function save_order(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $chantiersIds = $data['chantiers'];

        $utilisateur = $this->getUser();

        $i = 0;
        foreach ($chantiersIds as $key => $id) {

            $chantier = $this->em->getRepository(Chantier::class)->find($id);
            if(!$chantier)
                continue;

            $i++;

            /**
             * @var ChantierOrder
             */
            $ordre = $this->em->getRepository(ChantierOrder::class)->findOneBy(['chantier'=>$chantier,'utilisateur'=>$utilisateur]) ?? new ChantierOrder;
            $ordre->setUtilisateur($utilisateur);
            $ordre->setChantier($chantier);
            $ordre->setOrderNum($i);

            $this->getDoctrine()->getManager()->persist($ordre);

        }

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([
            'status'=>'success',
            'message'=>'Ordre des chantiers enregistrés avec succès'
        ]);
    }

    /**
     * @Route("/get/order", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"get_order"},
     *     description="Sauvegarder l'ordre des chantiers d'un utilisateur",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="chantiers", type="array", example="[2,1]"),
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
    public function get_order(Request $request): JsonResponse
    {
        $utilisateur = $this->getUser();
        $order = $this->chantierOrderRepository->getOrderIdsByUtilisateur($utilisateur);
        $new_chantiers = $this->chantierRepository->getChantierIdsWhereNotInOrder($order);

        return new JsonResponse([
            'status'=>'success',
            'chantiers'=>array_merge($new_chantiers, $order)
        ]);
    }
}