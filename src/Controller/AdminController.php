<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Entreprise;
use App\Entity\Chantier;
use App\Entity\Horaire;
use App\Entity\AdminActivity;
use App\Entity\Document;
use App\Form\ChantierType;
use App\Form\UtilisateurType;
use App\Form\AdminType;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Controller\Traits\CommunTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Repository\AdminRepository;
use App\Repository\DocumentRepository;
use App\Repository\EntrepriseRepository;
use App\Service\GlobalService;

/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends Controller
{
    use CommunTrait;

    private $session;
    private $entrepriseRepository;
    private $adminRepository;
    private $documentRepository;
    private $global_s;

    public function __construct(SessionInterface $session, AdminRepository $adminRepository,  DocumentRepository $documentRepository, GlobalService $global_s, EntrepriseRepository $entrepriseRepository){
        $this->session = $session;
        $this->adminRepository = $adminRepository;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->documentRepository = $documentRepository;
        $this->global_s = $global_s;
    }

    /**
     * @Route("/list", name="list", methods={"GET"})
     */
    public function list(){
        $admins = $this->adminRepository->findAll();

        return $this->render('admin/index.html.twig', [
            'admins' => $admins,
        ]);
    }

    /**
     * @Route("/control", name="control", methods={"GET"})
     */
    public function control(){

        return $this->render('admin/control_page.html.twig', [
        ]);
    }    

    /**
     * @Route("/list-entreprise", name="list_entreprise", methods={"GET"})
     */
    public function getEntrepriseAdmin(Request $request){
        $adminSelect = $this->adminRepository->find($request->query->get('admin_id'));
        $entreprisesAdmins = $adminSelect->getEntreprises();
        $tabAdminEntreprise = [];
        foreach ($entreprisesAdmins as $value) {
            $tabAdminEntreprise[] = $value->getId();
        }

        $datas = ['status'=>200, "message"=>""];
        $datas['datas'] = $this->renderView('admin/entreprise_content.html.twig', [
            'entreprise_admin' => $tabAdminEntreprise,
            'entreprises' => $this->entrepriseRepository->findAll()
        ]);
        
        $response = new Response(json_encode($datas));
        return $response;   
    }

        /**
     * @Route("/activite/{admin_id}", name="activite_list", methods={"GET"})
     */
    public function getActiviteAdmin(Request $request, $admin_id){
        $admin = $this->getDoctrine()->getRepository(Admin::class)->findOneBy(['uid'=>$admin_id]);
        $activites = $this->getDoctrine()->getRepository(AdminActivity::class)->findBy(['admin'=>$admin_id]);

        return $this->render('admin/activite.html.twig', [
            'admin' => $admin,
            'activites' => $activites,
        ]);  
    }
    
    /**
     * @Route("/new/{id}", name="new", methods={"GET", "POST"})
     */
    public function new(UserPasswordEncoderInterface $passwordEncoder, Request $request, $id = null){

        $entrepriseId = $request->request->get('entreprise_id');

        $admin = new Admin();
        if(!is_null($id)){
            $admin = $this->adminRepository->find($id);
        }

        $form = $this->createForm(AdminType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $admin->setRole("administrateur");
            if($entrepriseId){
                $entreprise = $this->entrepriseRepository->find($entrepriseId);
                $admin->setEntreprise($entreprise);
                $admin->addEntreprise($entreprise);
            }

            if($request->request->get('password') != "****"){
                $password = $passwordEncoder->encodePassword($admin, $request->request->get('password'));
                $admin->setPassword($password);
            }
            $admin->setSuperAdmin((int)$request->request->get('super_admin'));
            
            
            $entityManager->persist($admin);
            $entityManager->flush();

            if($request->query->get("page") && $request->query->get("page") ==  "control" )
                return $this->redirectToRoute('entreprise_infos', ['id'=>$entrepriseId]); 

            return $this->redirectToRoute('admin_list'); 
        }

        return $this->render('admin/edit.html.twig', [
            'form_admin' => $form->createView(),
            'admin' => $admin,

        ]);

    }

    /**
     * @Route("/attach-entreprise", name="attach_entreprise", methods={"POST"})
     */
    public function attachEntreprise(Request $request){
        $entreprisesSelect = $request->request->get("entreprise");
        $adminSelect = $this->adminRepository->find($request->request->get('admin_id'));

        $adminSelect->removeAllEntreprise();
        if($entreprisesSelect && count($entreprisesSelect)){
            foreach ($entreprisesSelect as $value) {
                $entrepriseExist = $this->entrepriseRepository->find($value);
                if(!is_null($entrepriseExist))
                    $adminSelect->addEntreprise($entrepriseExist);
            }
            if(is_null($adminSelect->getEntreprise())){
                $firstEntreprise = $this->entrepriseRepository->find($entreprisesSelect[0]);
                $adminSelect->setEntreprise($firstEntreprise);
            }
        }
        else{
            $adminSelect->setEntreprise(null);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();   
        return $this->redirectToRoute('admin_list'); 
    }

}