<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use App\Entity\Outillage;
use App\Entity\Utilisateur;
use App\Entity\OutillageAutorization;
use App\Form\OutillageType;
use App\Repository\OutillageRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\OutillageAutorizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use App\Service\GlobalService;
use App\Entity\Entreprise;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Iloveimg\Iloveimg;
use Carbon\Carbon;

/**
 * @Route("/outillage")
 */
class OutillageController extends Controller
{
    use CommunTrait;
    private $global_s;
    private $session;
    private $outillageRepository;
    private $outillageAutorizationRepository;
    private $utilisateurRepository;

    public function __construct(GlobalService $global_s, SessionInterface $session, OutillageRepository $outillageRepository, UtilisateurRepository $utilisateurRepository, OutillageAutorizationRepository $outillageAutorizationRepository){
        $this->global_s = $global_s;
        $this->session = $session;
        $this->outillageRepository = $outillageRepository;
        $this->outillageAutorizationRepository = $outillageAutorizationRepository;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    /**
     * @Route("/", name="outillage_index", methods={"GET"})
     */
    public function index(OutillageRepository $outillageRepository, Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $outillages = $outillageRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $adapter = new ArrayAdapter($outillages);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(500);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }  


        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true);
        $allUtilisateursEntrep = $this->utilisateurRepository->getUserByEntreprise();
        return $this->render('outillage/index.html.twig', [
            'pager' => $pager,
            'outillages' => $outillages,
            'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs),
            'allUtilisateursEntrep'=>$this->global_s->getUserByMiniature($allUtilisateursEntrep),
            'ouvriers' => $utilisateurs,
        ]);
    }

    /**
     * @Route("/maj/", name="maj", methods={"GET"})
     */
    public function maj(Request $request, Session $session)
    {
        $outillages = $this->getDoctrine()->getRepository(Outillage::class)->findAll();
        foreach ($outillages as $outil) {
            $galleries = unserialize($outil->getImage());
            if($galleries && count($galleries)){
                foreach ($galleries as $gallerie) {
                    $gallerieArr = explode('.', $gallerie);
                    $extension = $gallerieArr[count($gallerieArr)-1];
                    if(in_array($extension,array("png","jpeg","gif","jpg"))){
                        $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/image_outillage/";
                        $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/uploads/image_outillage/compressed/";
                        if(is_file($compressed_dir.$gallerie))
                            continue;

                        try {
                            if (!is_dir($compressed_dir)) {
                                mkdir($compressed_dir, 0777, true);
                            }
                            $this->global_s->make_thumb($dir.$gallerie, $compressed_dir.$gallerie,200, $extension);

                        } catch (FileException $e) {}
                    }                
                }
            }
            
        }

        return new Response('ok');
    }

    /**
     * @Route("/entree-sortie", name="outillage_entree_sortie", methods={"GET"})
     */
    public function entreeSortie(OutillageRepository $outillageRepository, Request $request): Response
    {
        $outilAuthRepo = $this->getDoctrine()->getRepository(OutillageAutorization::class);
        $outillagesAuth = $outilAuthRepo->getAuthGroupByUserAndDateDebut();

        $outillagesAuthArr = [];
        foreach ($outillagesAuth as $value) {
            $outillagesAuthArr[$value->getUtilisateur()->getUid()][] = $value;
        }

        $adapter = new ArrayAdapter($outillagesAuth);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(100);
        $page = $request->query->get('page', 1);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }  

        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true);
        $allUtilisateursEntrep = $this->utilisateurRepository->getUserByEntreprise();
        return $this->render('outillage/entree_sortie.html.twig', [
            'pager' => $pager,
            'outillagesAuthArr' => $outillagesAuthArr,
            'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs),
            'allUtilisateursEntrep'=>$this->global_s->getUserByMiniature($allUtilisateursEntrep),
            'ouvriers' => $utilisateurs,
        ]);
    }

    /**
     * @Route("/save-sign-doc", name="save_signe_doc")
     */
    public function saveSignDoc(Request $request){

        $uploadedFile = $request->files->get('doc_signe');
        $outilAuthId = $request->request->get('outillage-auth-id');

        if ($uploadedFile){ 
            $outilAuthRepo = $this->getDoctrine()->getRepository(OutillageAutorization::class);
            $outillageAuth = $outilAuthRepo->find($outilAuthId);

            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/doc_signe/";
            try {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            } catch (FileException $e) {}

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugify($originalFilename);
            $extension = $uploadedFile->guessExtension();
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
            $uploadedFile->move($dir, $newFilename);
            $outillageAuth->setDocumentSigne($newFilename);

            $em = $this->getDoctrine()->getManager();
            $em->flush();
        } 
        return $this->redirectToRoute('outillage_entree_sortie');
    }

    /**
     * @Route("/print-auth", name="print_auth", methods={"GET"})
     */
    public function printAuth(Request $request): Response
    {        
        $outil_auth_id = $request->query->get('outil_auth_id');
        $outilAuthRepo = $this->getDoctrine()->getRepository(OutillageAutorization::class);
        $oneOutilAuthRepo = $outilAuthRepo->find($outil_auth_id);

        $dateRetour = "";
        if(!is_null($oneOutilAuthRepo->getDateRetour())){
            $dateRetour = Carbon::parse($oneOutilAuthRepo->getDateRetour())->locale('fr')->isoFormat('D MMMM YYYY');
        }

        $dateRetourPrevu = "";
        if(!is_null($oneOutilAuthRepo->getPeriode())){
            $dateRetourPrevu = explode(" au ", $oneOutilAuthRepo->getPeriode())[1];
            $dateRetourPrevu = Carbon::parse($dateRetourPrevu)->locale('fr')->isoFormat('D MMMM YYYY');
        }

        return $this->render('outillage/print_auth.html.twig', [
            'outillagesAuth'=>[$oneOutilAuthRepo->getOutillage()],
            'outillagesAuthId'=>$oneOutilAuthRepo->getId(),
            'user'=>$oneOutilAuthRepo->getUtilisateur(),
            'date_sortie'=>Carbon::parse($oneOutilAuthRepo->getDateDepart())->locale('fr')->isoFormat('D MMMM YYYY'),
            'date_retour'=>$dateRetour,
            'periode'=> $dateRetourPrevu
        ]);
    }

    /**
     * @Route("/print-user-outillage/{user_id}", name="print_user_outillage", methods={"GET"})
     */
    public function printOutillageUser(Request $request, $user_id): Response
    {        
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($user_id);
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $outillages = $this->outillageRepository->findBy(['utilisateur'=>$user]);

        return $this->render('outillage/outillage_user_print.html.twig', [
            'outillages'=>$outillages,
            'user'=>$user,
            'entreprise'=>[
                'nom'=>$entreprise->getName(),
                'adresse'=>$entreprise->getAddress(),
                'ville'=>$entreprise->getCity(),
                'postalcode'=>$entreprise->getCp(),
                'phone'=>$entreprise->getPhone(),
                'email'=>$entreprise->getEmail(),
                'logo'=>$entreprise->getLogo(),
            ]
        ]);
    }

    /**
     * @Route("/retourn-materiel/{id}", name="retour_materiel")
     */
    public function retourMateriel($id){
        $outillage = $this->outillageRepository->find($id);
        $outilAuthRepo = $this->getDoctrine()->getRepository(OutillageAutorization::class);
        $outilAuth = $outilAuthRepo->findOneBy(['outillage'=>$id, 'date_retour'=>null], ['date_depart'=>'DESC']);
        $outilAuth->setDateRetour(new \DateTime());
        $outillage->setLibre(true);

        $em = $this->getDoctrine()->getManager();
        $em->flush();
        $this->addFlash('success', "Le materiel est de nouveau libre");
        return $this->redirectToRoute('outillage_index');
    }

    /**
     * @Route("/utilisateur/", name="outillage_utilisateur", methods={"GET"})
     * @Route("/utilisateur/{id}", name="outillage_utilisateur_select", methods={"GET"})
     */
    public function utilisateur(Request $request, Session $session, $id = 0)
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
        

        $page = $request->query->get('page', 1);
        
        $adapter = new DoctrineORMAdapter($query_user);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(500);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }


        $query_user = $query_user->andWhere('c.etat = :etat2')
                            ->setParameter('etat2', true);
        $adapter2 = new DoctrineORMAdapter($query_user);
        $pager2 = new PagerFanta($adapter2);
        $nb_ouvriers = $pager2->getNbResults();

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
            $outillages = $this->outillageRepository->findBy(['utilisateur'=>$user]);

        } else {
            $repositoryUser = $this->getDoctrine()->getRepository(Utilisateur::class);
            $user = $repositoryUser->find($id);
            $outillages = $this->outillageRepository->findBy(['utilisateur'=>$id]);
        }

        $session->set('user', $user);

        return $this->render('outillage/utilisateur.html.twig', [
            'pager' => $pager,
            'user' => $user,
            'outillages' => $outillages,
            'nb_ouvriers' => $nb_ouvriers,
            'mois' => $mois,
            'annee' => $annee,
        ]);
    }

    /**
     * @Route("/delete-outillage-image", name="delete_outillage_image")
     */
    public function deleteImageOutillage(Request $request)
    {
        $outillageId = $request->query->get('outillage_id');
        $imageName = $request->query->get('image_name');

        $outillage = $this->outillageRepository->find($outillageId);
        $images = unserialize($outillage->getImage());
        if(array_search($imageName, $images) !== FALSE){
            unset($images[array_search($imageName, $images)]);
        }
        $outillage->setImage(serialize($images));

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        $datas = ['status'=>200, "message"=>"bon trouvés"];
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/attribut-user", name="outillage_attribut_user")
     */
    public function attachUser(Request $request, Session $session)
    {
        $outillages = explode('-', $request->request->get('list-elt-id'));
        $userId = $request->request->get('user');

        if(!$userId || !count($outillages)){
            $this->addFlash('error', "Veuillez selectionner tout les elements requis");
            return $this->redirectToRoute('outillage_index');
        }

        $em = $this->getDoctrine()->getManager();
        $outilAuth = [];
        $dateSortie = new \DateTime();

        $isAttrib = false;
        foreach ($outillages as $value) {
            $value = $this->outillageRepository->find($value);
            if(!$value->getLibre()){
                $this->addFlash('warning', "Un materiel non libre a été trouvé");
                continue;
            }

            $value->setLibre(false);
            $autorisation = new OutillageAutorization();
            $currentUser =  $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId);
            $autorisation->setDateDepart($dateSortie);
            $autorisation->setUtilisateur($currentUser);
            $autorisation->setOutillage($value);
            $autorisation->setPeriode($request->request->get('periode'));
            $em->persist($autorisation);

            $outilAuth[] = $autorisation->getId();

            $isAttrib = true;
        }
        $em->flush();
        $this->getOutillageAuth($outillages,  $userId);

        if($isAttrib)
            $this->addFlash('success', "Attribution effectuée avec success");
        //return $this->redirectToRoute('print_auth', ['outil_to_print'=>$request->request->get('list-elt-id'), 'user_to_print'=>$userId]);
        return $this->redirectToRoute('outillage_index');
    }

    /**
     * @Route("/save-date-retour", name="save_date_retour")
     */
    public function saveDateRetour(Request $request){
        $id_outil_auth = $request->query->get('id_outil_auth');
        $dateRetour = $request->query->get('date_retour');
        $outilAuthRepo = $this->getDoctrine()->getRepository(OutillageAutorization::class);
        $outilAuth = $outilAuthRepo->find($id_outil_auth);
        $outilAuth->setDateRetour(new \DateTime($dateRetour));
        $outilAuth->getOutillage()->setLibre(true);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
        return $this->redirectToRoute('outillage_entree_sortie');
    }

    public function getOutillageAuth($tabOutil, $userId){
        $outillages = [];
        foreach ($tabOutil as $value) {
            $outillages[] = $this->outillageRepository->find($value);
        }
        return [
            'outillages' => $outillages,
            'user'=> $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId)
        ];
    }

    /**
     * @Route("/new/{user_id}", name="outillage_new", methods={"GET","POST"})
     */
    public function new(Request $request, $user_id = null): Response
    {
        $outillage = new Outillage();
        if(!is_null($user_id)){
            $user = $this->utilisateurRepository->find($user_id);
            $outillage->setUtilisateur($user);
        }
        $form = $this->createForm(OutillageType::class, $outillage);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager = $this->getDoctrine()->getManager();

            /** @var UploadedFile $image */
            $uploadedFile = $form->get('image')->getData();

            $badExtensionFound = false;
            if ($uploadedFile){
                $tabImage = [];
                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/image_outillage/";
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                } catch (FileException $e) {}

                foreach ($uploadedFile as $value) {
                    $originalFilename = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugify($originalFilename);
                    $extension = $value->guessExtension();
                    if(in_array($extension, array("png","jpeg","gif","jpg"))){
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $value->guessExtension();
                        $value->move($dir, $newFilename);

                        $tabImage[] = $newFilename;

                        /* compression image */
                        $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/uploads/image_outillage/compressed/";
                        if(is_file($compressed_dir.$newFilename))
                            continue;

                        try {
                            if (!is_dir($compressed_dir)) {
                                mkdir($compressed_dir, 0777, true);
                            }
                            $this->global_s->make_thumb($dir.$newFilename, $compressed_dir.$newFilename,200, $extension);

                        } catch (FileException $e) {}
                    }
                    else{
                        $badExtensionFound = true;
                    }
                }

                $outillage->setImage(serialize($tabImage));
            }
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $outillage->setEntreprise($entreprise);
            
            $entityManager->persist($outillage);
            $entityManager->flush();

            if($badExtensionFound){
                $this->addFlash('warning', "Une image a été fournit avec une extension non souhaitée");
            }
            return $this->redirectToRoute('outillage_index');
        }

        return $this->render('outillage/new.html.twig', [
            'outillage' => $outillage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="outillage_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Outillage $outillage): Response
    {
        $form = $this->createForm(OutillageType::class, $outillage);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var UploadedFile $image */
            $uploadedFile = $form['image']->getData();
            if ($uploadedFile){

                $tabImage = unserialize($outillage->getImage());
                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/image_outillage/";
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                } catch (FileException $e) {}

                foreach ($uploadedFile as $value) {
                    $originalFilename = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugify($originalFilename);
                    $extension = $value->guessExtension();
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $value->guessExtension();
                    $value->move($dir, $newFilename);

                    $tabImage[] = $newFilename;

                    /* compression image */
                    $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/uploads/image_outillage/compressed/";
                    if(is_file($compressed_dir.$newFilename))
                        continue;

                    try {
                        if (!is_dir($compressed_dir)) {
                            mkdir($compressed_dir, 0777, true);
                        }
                        $this->global_s->make_thumb($dir.$newFilename, $compressed_dir.$newFilename,200, $extension);

                    } catch (FileException $e) {}
                }
                $outillage->setImage(serialize($tabImage));
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('outillage_index');
        }

        return $this->render('outillage/edit.html.twig', [
            'outillage' => $outillage,
            'images'=>unserialize($outillage->getImage()),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="outillage_delete")
     */
    public function delete(Request $request, $id): Response
    {
        $outillage = $this->getDoctrine()->getRepository(Outillage::class)->find($id);

        try {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($outillage);
            $entityManager->flush();
        } catch (\Exception $e) {
            $this->addFlash('error', "Vous ne pouvez supprimer cet element s'il est lié à d'autres elements");
        }

        return $this->redirectToRoute('outillage_index');
    }
}
