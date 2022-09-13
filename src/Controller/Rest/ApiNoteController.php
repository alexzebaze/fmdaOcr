<?php

namespace App\Controller\Rest;

use App\Entity\Utilisateur;
use App\Entity\PartageNote;
use App\Entity\Note;
use App\Form\NoteMobileType;
use App\Utils\UploadedBase64File;
use Mimey\MimeTypes;
use App\Serializer\FormErrorSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Swagger\Annotations as SWG;


/**
 * @Route("/notes")
 */
class ApiNoteController extends AbstractController
{

    private $formErrorSerializer;

    public function __construct(FormErrorSerializer $formErrorSerializer)
    {
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
     *     tags={"add_user_in_share"},
     *     description="Ajouter/Supprimer des utilisateurs au partage",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="note_id", type="string"),
     *              @SWG\Property(property="users_ids", type="string"),
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
     * @Route("/add_user_in_share", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function add_user_in_share(Request $request): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        if(!isset($data['note_id']) || !isset($data['users_ids'])){
            return new JsonResponse(
                [
                    'status'=>'error',
                    'message'=>'Veuillez remplir tout les champs'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $note = $this->getDoctrine()->getRepository(Note::class)->find($data['note_id']);
        if(!$note){
            return new JsonResponse(
                [
                    'status'=>'error',
                    'message'=>'Désolé ! Cette note n\'existe pas'
                ], 
                Response::HTTP_BAD_REQUEST
            );
        }

        $entityManager = $this->getDoctrine()->getManager();

        $users_ids = $data['users_ids'];
        
        foreach ($note->getPartageNotes() as $partageNote) {
            if(!in_array($partageNote->getUser()->getUid(), $users_ids)){
                $entityManager->remove($partageNote);
                $entityManager->flush();
            }
        }

        foreach ($users_ids as $user_id) {
            $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($user_id);
            if(!$user){
                continue;
            }

            $partageNote = $this->getDoctrine()->getRepository(PartageNote::class)->findOneBy(['user'=>$user,'note'=>$note]) ?? new PartageNote;

            $partageNote->setNote($note);
            $partageNote->setUser($user);

            $entityManager->persist($partageNote);
        }

        $entityManager->flush();

        return new JsonResponse(['status'=>'success','message'=>'La note a bien été partagée']);

    }

    /**
     * @SWG\Post(
     *     tags={"new"},
     *     description="Ajouter une note",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="chantier", type="string"),
     *              @SWG\Property(property="text", type="string"),
     *         )
     *     ),
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
     * @Route("/new", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function new(Request $request)
    {
        
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $note = new Note();
        $form = $this->createForm(NoteMobileType::class, $note, array('entreprise' => $user->getEntreprise()));
        $form->submit($data);
        if ($form->isValid()) {
            
            $note->setUser($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($note);
            $entityManager->flush();

            return new JsonResponse(array('success' => true), Response::HTTP_OK);
        }

        return new JsonResponse(array('error' => $this->formErrorSerializer->convertFormToArray($form)), Response::HTTP_BAD_REQUEST);

    }

    /**
     * @Route("/add-image", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     * @SWG\Post(
     *     tags={"galerie"},
     *     description="Ajouter un fichier",
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
    public function add_image(Request $request, KernelInterface $kernel): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $fichier = isset($data['fichier']) ? $data['fichier']: null;
        if ($fichier) {
            $mimes = new MimeTypes;
            $namefiletemp = uniqid();
            $imageFile = new UploadedBase64File($fichier, $namefiletemp);
            $safeFilename = $namefiletemp;
            $extension = $mimes->getExtension($imageFile->getMimeType());
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;


            $second = "notes/" . date('Y-m-d') . "/";
            $dir = $kernel->getProjectDir() . "/public/" . $second;
            // Move the file to the directory where brochures are stored
            try {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $imageFile->move(
                    $dir,
                    $newFilename
                );
            } catch (FileException $e) {
                return new JsonResponse(array('error' => true), Response::HTTP_BAD_REQUEST);
            }
            return new JsonResponse(array('image' => $this->generateUrl('home', [], false).$second.$newFilename), Response::HTTP_OK);
        } else {
            return new JsonResponse(array('error' => true), Response::HTTP_BAD_REQUEST);
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
     *     tags={"delete_note"},
     *     description="Supprimer une note",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="note_id", type="string")
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
     * @Route("/delete", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        if(!isset($data['note_id'])){
            return new JsonResponse(
                [
                    'status'=>'error',
                    'message'=>'Veuillez remplir tout les champs'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $note = $this->getDoctrine()->getRepository(Note::class)->find($data['note_id']);
        if(!$note){
            return new JsonResponse(
                [
                    'status'=>'error',
                    'message'=>'Désolé ! Cette note n\'existe pas'
                ], 
                Response::HTTP_BAD_REQUEST
            );
        }

        $entityManager = $this->getDoctrine()->getManager();
        foreach ($note->getPartageNotes() as $partageNote) {
            $entityManager->remove($partageNote);
            $entityManager->flush();
        }

        $entityManager->remove($note);
        $entityManager->flush();

        return new JsonResponse(['status'=>'success','message'=>'La note a bien été supprimée']);

    }

}