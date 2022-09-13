<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Entreprise;
use App\Entity\Chantier;
use App\Entity\Admin;
use App\Entity\Garrage;
use App\Entity\Prime;
use App\Entity\Horaire;
use App\Form\EntrepriseType;
use App\Form\AdminType;
use App\Form\HoraireEditType;
use App\Form\HoraireType;
use App\Form\ChantierType;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Repository\MenuRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\AdminRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Carbon\Carbon;
use App\Controller\Traits\CommunTrait;
use App\Repository\EntrepriseRepository;
use App\Repository\MetaConfigRepository;
use App\Entity\MetaConfig;
use App\Service\GlobalService;
use App\Service\MenuService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;

/**
 * @Route("/entreprise", name="entreprise_")
 */
class EntrepriseController extends Controller
{
    use CommunTrait;
    private $entrepriseRepository;
    private $metaConfigRepository;
    private $global_s;
    private $menu_s;
    private $adminRepository;
    private $session;

    public function __construct(GlobalService $global_s, AdminRepository $adminRepository, EntrepriseRepository $entrepriseRepository, SessionInterface $session, MenuService $menu_s, MetaConfigRepository $metaConfigRepository){
        $this->entrepriseRepository = $entrepriseRepository;
        $this->metaConfigRepository = $metaConfigRepository;
        $this->adminRepository = $adminRepository;
        $this->global_s = $global_s;
        $this->menu_s = $menu_s;
        $this->session = $session;
    }


    /**
     * @Route("/", name="index")
     */
    public function index(){
        $entreprises = $this->entrepriseRepository->findAll();
        return $this->render('entreprise/list.html.twig', [
            'entreprises' => $entreprises
        ]);
    }

    /**
     * @Route("/load-xhr", name="load_xhr")
     */
    public function loadXhr(Request $request){
        $userConnect = $this->getUser();
        if($this->getUser()->getRole() != "administrateur"){
            $datas = ['status'=>500, "message"=>"Vous n'etes pas autorisé à effectuer cette operation"];
        }
        else{
            // $entreprises = [];
            // if( ($this->getUser()->getEmail() == "mdistri@gmail.com") || ($this->getUser()->getEmail() == "holdingamd@gmail.com") )
            //     $entreprises = $this->entrepriseRepository->findAll();
            // elseif(!is_null($this->getUser()->getEntreprise()))
            //     $entreprises = $this->entrepriseRepository->findBy(['id'=>$this->getUser()->getEntreprise()]);

            $entreprises = $userConnect->getEntreprises();
            $datas = ['status'=>200, "message"=>""];
            $datas['datas'] = $this->renderView('entreprise/entreprise_content.html.twig', [
                'entreprises' => $entreprises
            ]);
        }
        $response = new Response(json_encode($datas));
        return $response;   
    }

    /**
     * @Route("/infos/{id}", name="infos")
     */
    public function infos(Request $request, MenuRepository $menuRepository, $id){
        $entreprise = $this->entrepriseRepository->find($id);
        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        $admins = $entreprise->getAdmins();

        $menu = $menuRepository->getMenuFirstNiveau();
        $menusArr = $this->menu_s->orderMenu($menu);

        $admin = new Admin();
        $formAdmin = $this->createForm(AdminType::class, $admin);
        $formAdmin->handleRequest($request);


        $emailServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'email_serveur_mail', 'entreprise'=>$entreprise->getId()]);
        $passwordServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'password_serveur_mail', 'entreprise'=>$entreprise->getId()]);
        $hoteServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'hote_serveur_mail', 'entreprise'=>$entreprise->getId()]);
        $portServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'port_serveur_mail', 'entreprise'=>$entreprise->getId()]);
    

        $entityManager = $this->getDoctrine()->getManager();
        $utilisateurs = $entityManager->getRepository(Utilisateur::class)->findBy(['entreprise'=>$entreprise->getId()]);
        $garrages = $entityManager->getRepository(Garrage::class)->findBy(['entreprise'=>$entreprise->getId()]);

        return $this->render('entreprise/entreprise_control.html.twig', [
            'form_admin' => $formAdmin->createView(),
            'entreprise' => $entreprise,
            'utilisateurs' => $utilisateurs,
            'garrages' => $garrages,
            'menus' => $menusArr,
            'menusEntrepise'=>$entreprise->getMenus(),
            'form' => $form->createView(),
            'admins' => $admins,
            'emailServeurMail'=>$emailServeurMail,
            'passwordServeurMail'=>$passwordServeurMail,
            'hoteServeurMail'=>$hoteServeurMail,
            'portServeurMail'=>$portServeurMail,
        ]);
    }


    /**
     * @Route("/new", name="new", methods={"GET"})
     */
    public function new(Request $request, MenuRepository $menuRepository)
    {
        $entreprise = new Entreprise();
        $form = $this->createForm(EntrepriseType::class, $entreprise);

        $form->handleRequest($request);

        return $this->render('entreprise/new.html.twig', [
            'entreprise' => $entreprise,
            'form' => $form->createView(),
        ]);
    }

    public function saveGarrage($request, $entreprise){
        
        $entityManager = $this->getDoctrine()->getManager();
        $garrages = $this->getDoctrine()->getRepository(Garrage::class)->findBy(["entreprise"=>$entreprise]);
        $garragesEdit = $request->request->get('garragesEdit');
            
        if(!is_null($garragesEdit)){
            foreach ($garragesEdit as $key => $value) {
                $garrage =  $this->getDoctrine()->getRepository(Garrage::class)->find($key);
                if(!empty($value['nom']) || !empty($value['numero'])){
                    $garrage->setNom($value['nom']);
                    $garrage->setNumero($value['numero']);
                }
                else{
                    $garrage->setEntreprise(null);
                }
            }
            foreach ($garrages as $value) {
                if(!array_key_exists($value->getId(), $garragesEdit)){
                    $entityManager->remove($value);
                }
            }
        }

        $garrages = $request->request->get('garrages');
        if(!is_null($garrages)){
            for ($i=0; $i < count($garrages['numero']); $i++) { 
                if(!empty($garrages['nom'][$i]) || !empty($garrages['numero'][$i])){
                    $garrage = new Garrage();
                    $garrage->setNom($garrages['nom'][$i]);
                    $garrage->setNumero($garrages['numero'][$i]);

                    $entityManager->persist($garrage);// persist in caller function
                    $garrage->setEntreprise($entreprise);
                }
            }
        }

        return $entreprise;
    }

    /**
     * @Route("/edit", name="edit_first", methods={"GET","POST"})
     * @Route("/edit/{entreprise_id}", name="edit", methods={"GET","POST"})
     */
    public function edit(UserPasswordEncoderInterface $passwordEncoder, Request $request, $entreprise_id = null, MenuRepository $menuRepository)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $entreprise = new Entreprise();

        if($request->query->get("page") &&  $request->query->get("page") == "entreprise_new")
            $entreprise = new Entreprise();
        else{
            if(!is_null($entreprise_id))
                $entreprise = $this->entrepriseRepository->find($entreprise_id);
            else if($this->session->get('entreprise_session_id')){
                $entreprise = $this->entrepriseRepository->find($this->session->get('entreprise_session_id'));
            }

            if(!$entreprise) {
                $entreprise = new Entreprise();
            }
        }

        $form = $this->createForm(EntrepriseType::class, $entreprise);

        $form->handleRequest($request);

        $emailServeurMail = null; $passwordServeurMail = null; $hoteServeurMail = null; $portServeurMail = null;

        if ($form->isSubmitted() && $form->isValid()) { 

            if($request->request->get('tester_connexion') && $request->request->get('tester_connexion') == "Tester" && $request->request->get('username') != "" && $request->request->get('password') != "" && $request->request->get('hote') != "" && $request->request->get('port') != ""){

                $customMailer = null;

                try{
                    $dsn = "smtp://".$request->request->get('username').":".$request->request->get('password')."@".$request->request->get('hote').":".$request->request->get('port')."";
                    $transport = Transport::fromDsn($dsn);
                    $customMailer = new Mailer($transport);

                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    $this->addFlash('error', $error);
                }

                if(!is_null($customMailer)){
                    $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                    $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
                    $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();

                    $email = (new Email())
                        ->from(new Address($sender_email, $sender_name))
                        ->to($request->request->get('username'))
                        ->subject('Test Connexion serveur mail')
                        ->html('Connexion au serveur de mail reussite');
                    
                        try {
                            $send = $customMailer->send($email);
                        } catch (\Exception $ex) { 
                            $error = $ex->getMessage(); 
                            $this->addFlash('error', $error);
                        }
                }
            }
            

            $ribFile = $form->get('rib')->getData();
            $logoFile = $form->get('logo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($ribFile) {
                $originalFilename = pathinfo($ribFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $this->slugify($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$ribFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $dir = $this->get('kernel')->getProjectDir()."/public/rib/";
                    if(!is_dir($dir)) {
                        mkdir($dir);
                    }
                    $ribFile->move(
                        $dir,
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $entreprise->setRib($newFilename);
            }

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $this->slugify($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $dir = $this->get('kernel')->getProjectDir()."/public/logo/";
                    if(!is_dir($dir)) {
                        mkdir($dir);
                    }
                    $logoFile->move(
                        $dir,
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $entreprise->setLogo($newFilename);
            }

            $logoFacture = $form->get('logo_facture')->getData();
            if ($logoFacture) {
                $originalFilename = pathinfo($logoFacture->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugify($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFacture->guessExtension();

                try {
                    $dir = $this->get('kernel')->getProjectDir()."/public/logo/";
                    if(!is_dir($dir)) {
                        mkdir($dir);
                    }
                    $logoFacture->move(
                        $dir,
                        $newFilename
                    );
                } catch (FileException $e) {}
                $entreprise->setLogoFacture($newFilename);
            }

            
            /*foreach ($entreprise->getMenus() as $value) {
                $entreprise->removeMenu($value);
            }

            $menus = $request->request->get('menus');
            if($menus){
                foreach ($menus as $value) {
                    $menuItem = $menuRepository->find($value);
                    $entreprise->addMenu($menuItem);
                }
            }*/

            $entreprise = $this->saveGarrage($request, $entreprise);
            $entityManager->persist($entreprise);
            $entityManager->flush();


            $emailServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'email_serveur_mail', 'entreprise'=>$entreprise->getId()]);
            $passwordServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'password_serveur_mail', 'entreprise'=>$entreprise->getId()]);

            $hoteServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'hote_serveur_mail', 'entreprise'=>$entreprise->getId()]);
            $portServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'port_serveur_mail', 'entreprise'=>$entreprise->getId()]);


            if(is_null($emailServeurMail)){
                $emailServeurMail = new MetaConfig();
            }
            if(is_null($passwordServeurMail)){
                $passwordServeurMail = new MetaConfig();
            }
            if(is_null($hoteServeurMail)){
                $hoteServeurMail = new MetaConfig();
            }
            if(is_null($portServeurMail)){
                $portServeurMail = new MetaConfig();
            }



            $emailServeurMail->setMkey('email_serveur_mail');
            $emailServeurMail->setValue($request->request->get('username'));
            $emailServeurMail->setEntreprise($entreprise);

            $passwordServeurMail->setMkey('password_serveur_mail');
            $passwordServeurMail->setValue($request->request->get('password'));
            $passwordServeurMail->setEntreprise($entreprise);

            $hoteServeurMail->setMkey('hote_serveur_mail');
            $hoteServeurMail->setValue($request->request->get('hote'));
            $hoteServeurMail->setEntreprise($entreprise);

            $portServeurMail->setMkey('port_serveur_mail');
            $portServeurMail->setValue($request->request->get('port'));
            $portServeurMail->setEntreprise($entreprise);

            $entityManager->persist($emailServeurMail);
            $entityManager->persist($passwordServeurMail);
            $entityManager->persist($hoteServeurMail);
            $entityManager->persist($portServeurMail);
            $entityManager->flush();


            if($request->query->get('page') && $request->query->get('page') == "control"){
                $page = $request->query->get('page');
                return $this->redirectToRoute('entreprise_infos', ['id'=>$entreprise_id]);
            }
            if($request->query->get("page") &&  $request->query->get("page") == "entreprise_new"){

                $this->global_s->addColumnToEntreprise($entreprise);
                return $this->redirectToRoute('entreprise_index');
            }

            if(is_null($entreprise_id) && $this->session->get('entreprise_session_id'))
                return $this->redirectToRoute('entreprise_edit_first');
        }

        if(!is_null($entreprise)){
            $emailServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'email_serveur_mail', 'entreprise'=>$entreprise->getId()]);
            $passwordServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'password_serveur_mail', 'entreprise'=>$entreprise->getId()]);
            $hoteServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'hote_serveur_mail', 'entreprise'=>$entreprise->getId()]);
            $portServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'port_serveur_mail', 'entreprise'=>$entreprise->getId()]);
        }

        $utilisateurs = $entityManager->getRepository(Utilisateur::class)->findBy(['entreprise'=>$entreprise->getId()]);
        $garrages = $entityManager->getRepository(Garrage::class)->findBy(['entreprise'=>$entreprise->getId()]);
        
        return $this->render('entreprise/edit.html.twig', [
            'entreprise' => $entreprise,
            'menus' => $menuRepository->getMenuFirstNiveau(),
            'menusEntrepise'=>$entreprise->getMenus(),
            'utilisateurs'=>$utilisateurs,
            'garrages'=>$garrages,
            'emailServeurMail'=>$emailServeurMail,
            'passwordServeurMail'=>$passwordServeurMail,
            'hoteServeurMail'=>$hoteServeurMail,
            'portServeurMail'=>$portServeurMail,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/select", name="select")
     */
    public function select(Request $request, Session $session, $id){
        $entreprise = $this->entrepriseRepository->find($id);
        $session->set('entreprise_session_name', $entreprise->getName());
        $session->set('entreprise_session_logo', $entreprise->getLogo());
        $session->set('entreprise_session_id', $id);

        $datas = ['status'=>200, "message"=>""];
        $response = new Response(json_encode($datas));
        return $response;   
    }
}
