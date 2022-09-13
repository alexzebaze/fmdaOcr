<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Serializer\FormErrorSerializer;
use App\Entity\Note;
use App\Entity\Utilisateur;
use App\Entity\PartageNote;
use App\Form\NoteType;
use App\Controller\Traits\CommunTrait;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @Route("/notes", name="note_")
 */
class NoteController extends Controller
{
    use CommunTrait;

    private $formErrorSerializer;
    private $session;
    private $image_vide = "data:image/jpeg;base64,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";

    public function __construct(SessionInterface $session, FormErrorSerializer $formErrorSerializer)
    {
        $this->formErrorSerializer = $formErrorSerializer;
        $this->session = $session;
    }

    /**
     * @Route("/", name="list", methods={"GET"})
     * @Route("/collaborator/{id}", name="list_collaborator", methods={"GET"})
     */
    public function index(Request $request, Session $session, $id = 0)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $mois = $session->get('mois', date('m'));
        $annee = $session->get('annee', date('Y'));

        $me = $this->getUser();

        $query_user = $entityManager->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('etat', false);
            
        $query = $query_user->getQuery();
        $query_user = $query->execute();

        $ids = [];
        foreach ($query_user as $key => $value) {
            if( ($value->getRawDateEntree() && ( (int)($annee.$mois) >= (int)($value->getRawDateEntree()->format('Ym')) )) && ($value->getRawDateSortie() && ( (int)($annee.$mois) <= (int)($value->getRawDateSortie()->format('Ym')) ))){
                $ids[]=$value->getUid();
            }
            elseif($value->getRawDateEntree() && ( (int)($annee.$mois) >= (int)($value->getRawDateEntree()->format('Ym')) ) && !$value->getRawDateSortie()){
                $ids[]=$value->getUid();
            }
        }

        $query_user = $entityManager->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.entreprise = :entreprise')
            ->andWhere("c.uid IN(:ids)")
            ->orderBy('c.num', 'asc')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('etat', false)
            ->setParameter('ids', array_values($ids));

        $users = $query_user;
        $users = $users->getQuery();
        $users = $users->execute();


        $page = $request->query->get('page', 1);
        
        $adapter = new DoctrineORMAdapter($query_user);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }

        $nb_ouvriers = $pager->getNbResults();

        $user = null;
        if ($id == 0) {
            $old_user = $session->get('user', null);
            if (!$old_user) {
                $users = $pager->getCurrentPageResults();
                if (isset($users[0]))
                    $user = $users[0];
            } else {
                $repositoryUser = $this->getDoctrine()->getRepository(Utilisateur::class);
                $user = $repositoryUser->find($old_user->getUid());
            }
            $repositoryNote = $this->getDoctrine()->getRepository(Note::class);
            $notes = $repositoryNote->findBy(['user'=>$user,'status'=>1]);

        } else {
            $repositoryUser = $this->getDoctrine()->getRepository(Utilisateur::class);
            $user = $repositoryUser->find($id);
            $repositoryNote = $this->getDoctrine()->getRepository(Note::class);
            $notes = $repositoryNote->findBy(['user'=>$user,'status'=>1]);
        }

        $session->set('user', $user);

        return $this->render('note/index.html.twig', [
            'pager' => $pager,
            'user' => $user,
            'notes' => $notes,
            'nb_ouvriers' => $nb_ouvriers,
            'image_vide' => $this->image_vide,
            'mois' => $mois,
            'annee' => $annee,
            'users' => $users,
        ]);
    }
    public function slugify($text)
    {
      // replace non letter or digits by -
      $text = preg_replace('~[^\pL\d]+~u', '-', $text);

      // transliterate
      $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

      // remove unwanted characters
      $text = preg_replace('~[^-\w]+~', '', $text);

      // trim
      $text = trim($text, '-');

      // remove duplicate -
      $text = preg_replace('~-+~', '-', $text);

      // lowercase
      $text = strtolower($text);

      if (empty($text)) {
        return 'n-a';
      }

      return $text;
    }
    public function saveImageNote($target_dir, $file){
        $imagePath = $file["name"];
        $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
        $name = $this->slugify(time().'-'.$imagePath).'.'.$ext;
        $target_file = $target_dir.$name ;
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        if(isset($_POST["submit"])) {
          $check = getimagesize($file["tmp_name"]);
          if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
          } else {

            return null;
            $uploadOk = 0;
          }
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            return null;
          $uploadOk = 0;
        }

        // Check file size
        if ($file["size"] > 500000) {
            return null;
          $uploadOk = 0;
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            return null;
          $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {

    
        // if everything is ok, try to upload file
        } else {
          if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $name;
          } else {
            echo "Sorry, there was an error uploading your file.";
          }
        }
        return null;
    }
    /**
     * @Route("/save_image", name="save_ck_image", methods={"POST","GET"})
     */
    public function saveCKEditor(Request $request){
         /** @var UploadedFile $fichier */
         
        if(isset($_FILES["file"])) {
            $check = getimagesize($_FILES["file"]["tmp_name"]);

            if($check !== false) {
                $dir = '';
                try {
                    $dir = $this->get('kernel')->getProjectDir() . "/public/notes/" . date('Y'). '/' . date('m') . "/";
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                } catch (FileException $e) {
                }
                $url = $this->saveImageNote($dir, $_FILES["file"]);
                if($url){

                    $datas = ['status'=>200,"message"=>"Image sauvegardee",
                    "default"=>"/notes/" . date('Y'). '/' . date('m') . "/".$url
                ];
                    $response = new Response(json_encode($datas));
                    return $response;
                }
            }
        }
        

            $datas = ['status'=>200,"message"=>"No image available","default"=>"https://developers.google.com/maps/documentation/streetview/images/error-image-generic.png"];
            $response = new Response(json_encode($datas));
            return $response;
        
    }


    /**
     * @Route("/save_image_token", name="save_ck_image_token", methods={"POST","GET"})
     */
    public function saveCKEditorToken(Request $request){
         /** @var UploadedFile $fichier */
         
        $datas = ['status'=>200,"message"=>"Token Valide"];
        $response = new Response(json_encode($datas));
        return $response;
        
    }

    /**
     * @Route("/{noteId}/frame", name="show_frame")
     */
    public function frame_show(Request $request, $noteId)
    {
        $note = $this->getDoctrine()->getRepository(Note::class)->find($noteId);
        if(!$note){
            if(!$request->isXmlHttpRequest()){
                $this->get('session')->getFlashBag()->add('error', 'Désolé ! Cette note n\'existe pas');
                return $this->redirectToRoute('note_list');
            }
            else{
                return new JsonResponse(array('error' => 'Désolé ! Cette note n\'existe pas'), Response::HTTP_BAD_REQUEST);
            }
        }
        $text = $note->getText();
    
        return new Response($text);
    }
    /**
     * @Route("/archive/", name="archive", methods={"GET"})
     * @Route("/archive/collaborator/{id}", name="archive_list_collaborator", methods={"GET"})
     */
    public function archive(Request $request, Session $session, $id = 0)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $mois = $session->get('mois', date('m'));
        $annee = $session->get('annee', date('Y'));

        $me = $this->getUser();

        $query_user = $entityManager->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('etat', false);
            
        $query = $query_user->getQuery();
        $query_user = $query->execute();

        $ids = [];
        foreach ($query_user as $key => $value) {
            if( ($value->getRawDateEntree() && ( (int)($annee.$mois) >= (int)($value->getRawDateEntree()->format('Ym')) )) && ($value->getRawDateSortie() && ( (int)($annee.$mois) <= (int)($value->getRawDateSortie()->format('Ym')) ))){
                $ids[]=$value->getUid();
            }
            elseif($value->getRawDateEntree() && ( (int)($annee.$mois) >= (int)($value->getRawDateEntree()->format('Ym')) ) && !$value->getRawDateSortie()){
                $ids[]=$value->getUid();
            }
        }

        $query_user = $entityManager->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.entreprise = :entreprise')
            ->andWhere("c.uid IN(:ids)")
            ->orderBy('c.num', 'asc')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('etat', false)
            ->setParameter('ids', array_values($ids));


        $page = $request->query->get('page', 1);
        
        $adapter = new DoctrineORMAdapter($query_user);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }

        $nb_ouvriers = $pager->getNbResults();

        $user = null;
        if ($id == 0) {
            $old_user = $session->get('user', null);
            if (!$old_user) {
                $users = $pager->getCurrentPageResults();
                if (isset($users[0]))
                    $user = $users[0];
            } else {
                $repositoryUser = $this->getDoctrine()->getRepository(Utilisateur::class);
                $user = $repositoryUser->find($old_user->getUid());
            }
            $repositoryNote = $this->getDoctrine()->getRepository(Note::class);
            $notes = $repositoryNote->findBy(['user'=>$user, 'status'=>0]);

        } else {
            $repositoryUser = $this->getDoctrine()->getRepository(Utilisateur::class);
            $user = $repositoryUser->find($id);
            $repositoryNote = $this->getDoctrine()->getRepository(Note::class);
            $notes = $repositoryNote->findBy(['user'=>$user, 'status'=>0]);
        }

        $session->set('user', $user);

        return $this->render('note/archive.html.twig', [
            'pager' => $pager,
            'user' => $user,
            'notes' => $notes,
            'nb_ouvriers' => $nb_ouvriers,
            'mois' => $mois,
            'annee' => $annee,
        ]);
    }

    /**
     * @Route("/add/{id}", name="add")
     */
    public function new(Request $request, $id)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->findOneBy(["uid"=>$id,'entreprise' => $this->session->get('entreprise_session_id')]);
        if(!$user){
            $this->get('session')->getFlashBag()->add('error', 'Désolé ! Cet utilisateur n\'existe pas');
            return $this->redirectToRoute('note_list');
        }

        $note = new Note();
        $form = $this->createForm(NoteType::class, $note, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);
        if($request->get('chantier')){
            $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($request->get('chantier'));
            if(!$chantier){
                $this->get('session')->getFlashBag()->add('error', 'Désolé ! Ce chantier n\'existe pas');
                return $this->redirectToRoute('note_list');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            
            $note->setUser($user);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($note);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', 'La note a bien été enregistrée');

            return $this->redirectToRoute('note_list_collaborator', ['id'=>$user->getUid()]);
        }

        return $this->render('note/add.html.twig', [
            'note' => $note,
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
    /**
     * @Route("/{noteId}/edit", name="edit")
     */
    public function edit(Request $request, $noteId)
    {
        $note = $this->getDoctrine()->getRepository(Note::class)->find($noteId);
        if(!$note){
            if(!$request->isXmlHttpRequest()){
                $this->get('session')->getFlashBag()->add('error', 'Désolé ! Cette note n\'existe pas');
                return $this->redirectToRoute('note_list');
            }
            else{
                return new JsonResponse(array('error' => 'Désolé ! Cette note n\'existe pas'), Response::HTTP_BAD_REQUEST);
            }
        }

        $user = $note->getUser();

        $form = $this->createForm(NoteType::class, $note, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {            

            if(!$request->isXmlHttpRequest()){
                $this->get('session')->getFlashBag()->add('success', 'La note a bien été modifiée');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            if($request->isXmlHttpRequest()){
                return new JsonResponse(array('success' => true, Response::HTTP_OK));
            }

            return $this->redirectToRoute('note_list');
        }
        else{
            if($request->isXmlHttpRequest()){
                return new JsonResponse(array('error' => $this->formErrorSerializer->convertFormToArray($form)), Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->render('note/edit.html.twig', [
            'form' => $form->createView(),
            'note' => $note,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{noteId}/delete", name="delete")
     */
    public function delete(Request $request, $noteId)
    {
        $note = $this->getDoctrine()->getRepository(Note::class)->find($noteId);

        if(!$note){
            $this->get('session')->getFlashBag()->add('error', 'Désolé ! Cette note n\'existe pas');
            return $this->redirectToRoute('note_list');
        }

        $user = $note->getUser();

        $entityManager = $this->getDoctrine()->getManager();

        foreach ($note->getPartageNotes() as $partageNote) {
            $entityManager->remove($partageNote);
            $entityManager->flush();
        }

        $entityManager->remove($note);
        $entityManager->flush();

        $this->get('session')->getFlashBag()->add('success', 'La note a bien été supprimée');

        return $this->redirectToRoute('note_list_collaborator', ['id'=>$user->getUid()]);
    } 

    /**
     * @Route("/{noteId}/archive", name="make_archive")
     */
    public function make_archive(Request $request, $noteId)
    {
        $note = $this->getDoctrine()->getRepository(Note::class)->find($noteId);

        if(!$note){
            $this->get('session')->getFlashBag()->add('error', 'Désolé ! Cette note n\'existe pas');
            return $this->redirectToRoute('note_list');
        }

        $user = $note->getUser();

        if($note->getStatus()==1){
            $note->setStatus(0);
        }
        else{
            $note->setStatus(1);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        if($note->getStatus()==1){
            $this->get('session')->getFlashBag()->add('success', 'La note a bien été désarchivée');
        }
        else{
            $this->get('session')->getFlashBag()->add('success', 'La note a bien été archivée');
        }

        return $this->redirectToRoute('note_list_collaborator', ['id'=>$user->getUid()]);
    }

    /**
     * @Route("/add_user_in_share", name="add_user_in_share")
     */
    public function add_user_in_share(Request $request)
    {
        $note = $this->getDoctrine()->getRepository(Note::class)->find($request->get('note_id'));
        if(!$note){
            if($request->isXmlHttpRequest()){
                return new JsonResponse(['status'=>'error','message'=>'Désolé ! Cette note n\'existe pas']);
            }
            $this->get('session')->getFlashBag()->add('error', 'Désolé ! Cette note n\'existe pas');
            return $this->redirectToRoute('note_list');
        }
        
        $entityManager = $this->getDoctrine()->getManager();

        $users_ids = $request->get('users_ids') ?? [];
        
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

        if($request->isXmlHttpRequest()){
            return new JsonResponse(['status'=>'success','message'=>'La note a bien été partagée']);
        }
        $this->get('session')->getFlashBag()->add('success', 'La note a bien été partagée');
        return $this->redirectToRoute('note_list_collaborator', ['id'=>$note->getUser()->getUid()]);
    }

}
