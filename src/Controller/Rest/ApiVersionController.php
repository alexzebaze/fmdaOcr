<?php

namespace App\Controller\Rest;

use App\Controller\Traits\CommunTrait;
use App\Entity\Version;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @Route("/versions")
 */
class ApiVersionController extends AbstractController  {
    use CommunTrait;

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/last", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"default_galerie"},
     *     description="Retourne la dernière version en date",
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
     *         description="Token for Authentification",
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
    public function last(Request $request): JsonResponse
    {
        if($request->get('platform')){
            /**
             * @var Version
             */
            $version = $this->em->getRepository(Version::class)->findOneBy(['platform'=>$request->get('platform')],['name'=>'DESC']);
        }
        else{
            /**
             * @var Version
             */
            $version = $this->em->getRepository(Version::class)->findOneBy([],['name'=>'DESC']);
        }
      
        if(!$version)
            return new JsonResponse([
                "status" => "success",
                "name" => null,
                "platform" => null,
                "description"=>null,
            ]);

        return new JsonResponse([
            "status"=>"success",
            "name" => $version->getName(),
            "platform" => $version->getPlatform(),
            "description"=>$version->getDescription(), 
        ]);
    }
}