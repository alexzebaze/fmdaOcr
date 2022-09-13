<?php

namespace App\Controller\Rest;

use App\Kernel;
use Mimey\MimeTypes;
use App\Entity\Horaire;
use Pagerfanta\Pagerfanta;
use Swagger\Annotations as SWG;
use App\Repository\HoraireRepository;
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
 * @Route("/horaire")
 */
class ApiHoraireController extends AbstractController  {
    use CommunTrait;

    private $formErrorSerializer;
    private $horaireRepository;
    private $em;

    public function __construct(HoraireRepository $horaireRepository, EntityManagerInterface $em, FormErrorSerializer $formErrorSerializer)
    {
        $this->em = $em;
        $this->formErrorSerializer = $formErrorSerializer;
        $this->horaireRepository = $horaireRepository;
    }

    /**
     * @Route("/{id}/edit", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"passage_new"},
     *     description="Enregistrement d'une horaire",
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
    public function horaireEdit(Request $request, Horaire $horaire){
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $datestart = is_null($data['datestart']) ? new \DateTime() : new \DateTime($data['datestart']);
        $dateend = is_null($data['dateend']) ? new \DateTime() : new \DateTime($data['dateend']);
        $time = $data['time'];
        $pause = $data['pause'];
        $fonction = $data['fonction'];
        $chantierid = $data['chantierid'];
        $fonction = $data['fonction'];

        $time = $time - ((int)$pause)/60;
        $pause = round((int)$pause/60, 1);

        if(!is_null($user)){

            $horaire->setDatestart($datestart);
            $horaire->setDateend($dateend);
            $horaire->setTime($time);
            $horaire->setPause($pause);
            $horaire->setFonction($fonction);
            $horaire->setChantierid($chantierid);
            $horaire->setUserid($user->getUid());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            return new JsonResponse(array('status' => 200, 'response'=>"Enregistrement effectué avec succèss"), Response::HTTP_OK);
        }      

        return new JsonResponse(array('status' => 500, 'response'=>"Veuillez renseigner toutes les informations"), Response::HTTP_BAD_REQUEST);
          
    }


    /**
     * @Route("/new", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"passage_new"},
     *     description="Enregistrement d'une horaire",
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
    public function horaireNew(Request $request){
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $datestart = is_null($data['datestart']) ? new \DateTime() : new \DateTime($data['datestart']);
        $dateend = is_null($data['dateend']) ? new \DateTime() : new \DateTime($data['dateend']);
        $time = $data['time'];
        $pause = $data['pause'];
        $fonction = $data['fonction'];
        $chantierid = $data['chantierid'];
        $fonction = $data['fonction'];

        $time = $time - ((int)$pause)/60;
        $pause = round((int)$pause/60, 1);

        if(!is_null($user)){
            $horaire = new Horaire();
            $horaire->setDatestart($datestart);
            $horaire->setDateend($dateend);
            $horaire->setTime($time);
            $horaire->setPause($pause);
            $horaire->setFonction($fonction);
            $horaire->setChantierid($chantierid);
            $horaire->setUserid($user->getUid());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($horaire);
            $entityManager->flush();
            return new JsonResponse(array('status' => 200, 'response'=>"Enregistrement effectué avec succèss"), Response::HTTP_OK);
        }      

        return new JsonResponse(array('status' => 500, 'response'=>"Veuillez renseigner toutes les informations"), Response::HTTP_BAD_REQUEST);
          
    }


    /**
     * @Route("/", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"get_by_chanter"},
     *     description="liste d'horaires",
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
    public function getHoraires(Request $request){
        $user = $this->getUser();
        $horaires = $this->getDoctrine()->getRepository(Horaire::class)->findBy(['userid'=>$user->getUid()], ['idsession'=>"DESC"]);
        
        $horairesArr=[];
        foreach ($horaires as $value) {
            $horairesArr[] = [
                'idsession'=>$value->getIdsession(),
                'datestart'=>  (!is_null($value->getDatestart())) ? $value->getDatestart()->format('Y-m-d H:i:s') : '',
                'dateend'=> (!is_null($value->getDateend())) ? $value->getDateend()->format('Y-m-d H:i:s') : '',
                'time'=>$value->getTime(),
                'pause'=>$value->getPause(),
                'chantierid'=>$value->getChantierid(),
                'fonction'=>$value->getFonction(),
                'userid'=>$value->getUserid(),
            ];
        }

        return new JsonResponse(array('status' => 200, 'response'=>$horairesArr), Response::HTTP_OK);
          
    }
}