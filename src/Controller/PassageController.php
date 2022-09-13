<?php

namespace App\Controller;

use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Repository\PassageRepository;
use App\Repository\AchatRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\ChantierRepository;
use App\Repository\FournisseursRepository;
use App\Entity\LocataireNotification;
use App\Entity\Passage;
use App\Entity\Fournisseurs;
use App\Entity\Entreprise;
use App\Entity\Chantier;
use App\Entity\Utilisateur;
use App\Service\GlobalService;
use App\Service\PassageService;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Carbon\Carbon;

/**
 * @Route("/passage", name="passage_")
 */
class PassageController extends AbstractController
{
    private $global_s;
    private $passage_s;
    private $session;
    private $passageRepository;
    private $utilisateurRepository;
    private $chantierRepository;
    private $achatRepository;
    private $fournisseursRepository;

    public function __construct(AchatRepository $achatRepository, UtilisateurRepository $utilisateurRepository, FournisseursRepository $fournisseursRepository, PassageRepository $passageRepository, GlobalService $global_s, SessionInterface $session, PassageService $passage_s, ChantierRepository $chantierRepository){
        $this->passageRepository = $passageRepository;
        $this->chantierRepository = $chantierRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->fournisseursRepository = $fournisseursRepository;
        $this->achatRepository = $achatRepository;
        $this->global_s = $global_s;
        $this->passage_s = $passage_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session)
    {
        $form = $request->request->get('form', null);

        $utilisateurId = $fournisseurId = $is_bl = null;
        if ($form) {
            $day = (!(int)$form['day']) ? null : (int)$form['day'];
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $utilisateurId = (!(int)$form['utilisateur']) ? null : (int)$form['utilisateur'];
            $fournisseurId = (!(int)$form['fournisseur']) ? null : (int)$form['fournisseur'];
            $is_bl = (!(int)$form['is_bl']) ? null : (int)$form['is_bl'];

            if($request->query->get('control') && $request->query->get('control') == "nb_passage_ouvrier"){
                $nbPassageGroupByOuvrier = $this->passageRepository->countGroupByOuvrier($day, $mois, $annee, $fournisseurId, $is_bl);
                if($request->query->get('passage_ouvrier')){
                    $passages = $this->passageRepository->findByParam($day, $mois, $annee, $request->query->get('passage_ouvrier'), $fournisseurId, $is_bl);

                    $messageSended = $this->getDoctrine()->getRepository(LocataireNotification::class)->findNotificationByParam($mois, $annee, null, $fournisseurId, "PRO");
                    $messageSendedArr = [];
                    foreach ($messageSended as $value) {
                        if(!is_null($value->getPassage())){
                            $messageSendedArr[] = $value->getPassage()->getId();
                        }
                    }
                }
            }
            else{
                $passages = $this->passageRepository->findByParam($day, $mois, $annee, $utilisateurId, $fournisseurId, $is_bl);
                $messageSended = $this->getDoctrine()->getRepository(LocataireNotification::class)->findNotificationByParam($mois, $annee, null, $fournisseurId, "PRO");

                $messageSendedArr = [];
                foreach ($messageSended as $value) {
                    if(!is_null($value->getPassage())){
                        $messageSendedArr[] = $value->getPassage()->getId();
                    }
                }
            }


            $session->set('day_pas', $day);
            $session->set('mois_pas', $mois);
            $session->set('annee_pas', $annee);
            $session->set('utilisateur_pas', $utilisateurId);
            $session->set('fournisseur_pas', $fournisseurId);
            $session->set('is_bl_pas', $is_bl);
        }        
        else{

            $today = explode('-', (new \DateTime())->format('Y-m-d'));
            $day = null;
            $mois = $session->get('mois_pas', $today[1]);
            $annee = $session->get('annee_pas', $today[0]);
            $utilisateurId = $session->get('utilisateur_pas', null);
            $fournisseurId = $session->get('fournisseur_pas', null);
            $is_bl = $session->get('is_bl_pas', null);

            if($request->query->get('control') && $request->query->get('control') == "nb_passage_ouvrier"){
                $nbPassageGroupByOuvrier = $this->passageRepository->countGroupByOuvrier($day, $mois, $annee, $fournisseurId, $is_bl);
                if($request->query->get('passage_ouvrier')){
                    $passages = $this->passageRepository->findByParam($day, $mois, $annee, $request->query->get('passage_ouvrier'), $fournisseurId, $is_bl);

                    $messageSended = $this->getDoctrine()->getRepository(LocataireNotification::class)->findNotificationByParam($mois, $annee, null, $fournisseurId, "PRO");
                    $messageSendedArr = [];
                    foreach ($messageSended as $value) {
                        if(!is_null($value->getPassage())){
                            $messageSendedArr[] = $value->getPassage()->getId();
                        }
                    }
                }
            }
            else{
                $passages = $this->passageRepository->findByParam($day, $mois, $annee, $utilisateurId, $fournisseurId, $is_bl);
                $messageSended = $this->getDoctrine()->getRepository(LocataireNotification::class)->findNotificationByParam($mois, $annee, null, $fournisseurId, "PRO");

                $messageSendedArr = [];
                foreach ($messageSended as $value) {
                    if(!is_null($value->getPassage())){
                        $messageSendedArr[] = $value->getPassage()->getId();
                    }
                }
            }
        }   


        $form = $this->createFormBuilder(array(
            'day' => !is_null($day) ? (int)$day : "",
            'mois' => (int)$mois,
            'annee' => $annee,
            'utilisateur' => (!is_null($utilisateurId)) ? $this->utilisateurRepository->find($utilisateurId) : "",
            'fournisseur' => (!is_null($fournisseurId)) ?  $this->fournisseursRepository->find($fournisseurId) : "",
            'is_bl'=>$is_bl

        ))
        ->add('day', TextType::class, array(
            'label' => "Jour",
            'required' => false,
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('mois', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $this->global_s->getAnnee(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('fournisseur', EntityType::class, array(
            'class' => Fournisseurs::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('f')
                ->where('f.entreprise = :entreprise_id')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('f.nom', 'ASC');
            },
            'required' => false,
            'label' => "Fournisseur",
            'choice_label' => 'nom',
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('utilisateur', EntityType::class, array(
            'class' => Utilisateur::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('u')
                ->where('u.entreprise = :entreprise_id')
                ->andWhere('u.etat = 1')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('u.lastname', 'ASC');
            },
            'required' => false,
            'label' => "Utilisateur",
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('is_bl', ChoiceType::class, [
            'label' => "Bon Lv",
            'required' => false,
            'choices' => ["Avec bl"=>1, "Sans bl"=>2],
            'attr' => array(
                'class' => 'form-control'
            )
        ])->getForm();


        $page = $request->query->get('page', 1);
        

        $fournisseurs = $this->fournisseursRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['nom'=>'ASC']);
        $users = $this->utilisateurRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'etat'=>1], ['firstname'=>'ASC']);

        $datas = [
            'chantiers'=> $this->chantierRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'status'=>1], ['nameentreprise'=>'ASC']),
            'fournisseurs'=>$fournisseurs,
            'users'=>$users,
            'day'=>$day,
            'mois'=>$mois,
            'annee'=>$annee,
            'is_bl'=>$is_bl,
            'form' => $form->createView(),
        ];

        if($request->query->get('control') && $request->query->get('control') == "nb_passage_ouvrier"){
            $datas['nbPassageGroupByOuvrier'] = $nbPassageGroupByOuvrier;
            if($request->query->get('passage_ouvrier')){
                $datas['passages'] = $passages;
                $datas['messagesSended'] = $messageSendedArr;
            }
        }
        else{
            $datas['passages'] = $passages;
            $datas['messagesSended'] = $messageSendedArr;
        }


        return $this->render('passage/passage.html.twig', $datas);
    }

    /**
     * @Route("/new", name="new")
     */
    public function new(Request $request)
    {
        if($request->request->get('utilisateur') == ""){
            $this->addFlash('error', "Veuillez selectionner un ouvrier");
            return $this->redirectToRoute('passage_list');
        }
        $fournisseur = $this->fournisseursRepository->find($request->request->get('fournisseur'));
        $utilisateur = $this->utilisateurRepository->find($request->request->get('utilisateur'));
        $chantier = $this->chantierRepository->find($request->request->get('chantier'));
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        
        $passage = new Passage();
        $passage->setEntreprise($entreprise);
        $passage->setFournisseur($fournisseur);
        $passage->setUtilisateur($utilisateur);
        $passage->setChantier($chantier);

        if($request->request->get('date_create')){
            $dateCreate = $request->request->get('date_create');
            if($request->request->get('heure_create'))
                $dateCreate .= ' '.$request->request->get('heure_create');


            $passage->setCreateAt(new \DateTime($dateCreate));
        }

        $entityManager = $this->getDoctrine()->getManager();

        if(!is_null($chantier->getChantierId())){
            $bl = $this->achatRepository->getBlByCHantierFournDate($chantier->getChantierId(), $fournisseur->getId(), $passage->getDateDetection()->format('Y-m-d'));

            if($bl){
                $bl = $this->achatRepository->find($bl['id']); 
                if(is_null($bl->getPassage()))
                    $passage->setBonLivraison($bl);
            }
        }

        $entityManager->persist($passage);
        $entityManager->flush();

        return $this->redirectToRoute('passage_list');
    }

    /**
     * @Route("/edit-chantier", name="edit_chantier")
     */
    public function editChantier(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $passageId = $request->request->get('passage_id');
        $chantier = $this->chantierRepository->find($request->request->get('chantier'));
        $passage = $this->passageRepository->find($passageId);


        if(is_null($passage->getBonLivraison())){
            $bl = $this->achatRepository->getBlByCHantierFournDate($chantier->getChantierId(), $passage->getFournisseur()->getId(), $passage->getDateDetection()->format('Y-m-d'));

            if($bl){
                $bl = $this->achatRepository->find($bl['id']); 
                if(is_null($bl->getPassage()))
                    $passage->setBonLivraison($bl);
            }
        }

        $passage->setChantier($chantier);

        $entityManager->flush();
        return $this->redirectToRoute('passage_list');
    }

    /**
     * @Route("/delete-many", name="delete_many", methods={"POST"})
     */
    public function deletePassage(Request $request){
        $tabPassage = explode('-', $request->request->get('list-passage-check'));

        try {
            $this->passageRepository->deletePassage($tabPassage);
            $this->addFlash('success', "Passages supprimés avec success");
        } catch (\Exception $e) {
            $this->addFlash('error', "Vous ne pouvez supprimer cet element s'il est associé à d'autres elements");
        }
        
        return $this->redirectToRoute('passage_list');
    }

    /**
     * @Route("/valide-by-bl/{bl}/{passage_id}", name="valide_by_bl", methods={"GET"})
     */
    public function validerPassage($bl, $passage_id){

        $bl = $this->achatRepository->find($bl);
        $passage = $this->passageRepository->find($passage_id);

        if(is_null($bl->getPassage())){
            $passage->setBonLivraison($bl);
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', "Passage validé avec succès");
        }
        else
            $this->addFlash('error', "Le bon de livraison est deja couplé à un passage");

        return $this->redirectToRoute('bon_livraison_list');
    }  

    /**
     * @Route("/send-email/{passage_id}", name="send_email", methods={"GET"})
     */
    public function sendEmailFournisseur(MailerInterface $mailer, $passage_id){

        $error = null; $customMailer = null;
        try{
            $customMailer = $this->global_s->initEmailTransport();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if(!is_null($customMailer) && is_null($error)){

            $passage = $this->passageRepository->find($passage_id);

            $infosEnvoie = $this->global_s->getDefaultEntrepriseMail();
            $sender_email = $infosEnvoie['email'];
            $sender_name = $infosEnvoie['name'];

            $datePassage = Carbon::parse($passage->getDateDetection()->format("Y-m-d"))->locale('fr')->isoFormat('D MMMM YYYY');

            $userInfo = "";
            if(!is_null($passage->getUtilisateur())){
                $userP = $passage->getUtilisateur();
                $userInfo = $userP->getFirstname()." ".$userP->getLastname();
            }
            
            $message = "Bonjour,<br><br> Nous avons remarqué qu’un de nos ouvriers, ". $userInfo ." est passé chez vous le <b>".$datePassage." à ".$passage->getDateDetection()->format("H:i")."</b> prendre du matériel mais nous n’avons pas reçu le bon de livraison. <br><br>Merci de bien vouloir nous l’envoyer sur l’adresse email : bl@fmda.fr <br><br>Cordialement <br><br>
                FMDA Construction<br>
                65, rue de l’Abbé Lemire<br>
                62000 ARRAS<br>
                03.59.25.08.48<br>
                contact@fmda.fr";

            if($passage->getFournisseur()->getEmail()){

                $hote = $this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL);
                $logo = !is_null($passage->getEntreprise()->getLogo()) ? "logo/".$passage->getEntreprise()->getLogo() : "assets/images/logotransfmda.png";

                $html = $this->renderView('mail/mail_template.html.twig', [
                    'message'=>$message,
                    'logo'=> $hote.$logo
                ]);

                $email = (new Email())
                ->from(new Address($sender_email, $sender_name))
                    ->to($passage->getFournisseur()->getEmail())
                    ->subject("Rappel d'envoi de bon de livraison.")
                    ->html($html);

                try {
                    $send = $customMailer->send($email);
                } catch (\Exception $ex) { $error = $ex->getMessage(); }

                $this->saveSms($passage, $message);
                $this->addFlash('success', "Email envoyé avec success");
            }
            else{
                $this->addFlash('error', "Le fournisseur n'a pas d'email");
            }
        }
        else{
            if(!is_null($error))
                $this->addFlash('error', $error);
            else
                $this->addFlash('error', "Veuillez configurer les informations d'envoie de mail de cette entreprise");
        }
        
        return $this->redirectToRoute('passage_list');
    }  

    public function saveSms($passage, $message)
    {
        $entrepriseId = $this->session->get('entreprise_session_id');
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($entrepriseId);

        $notification = new LocataireNotification();
        $notification->setPassage($passage);
        $notification->setEntreprise($entreprise);
        $notification->setType("EMAIL");
        $notification->setReceiver("PRO");
        $notification->setMessage(strip_tags($message));
        $notification->setSujet("Rappel d'envoie bon de livraison");

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($notification);
        $entityManager->flush();

        return 1;
    }


    /**
     * @Route("/get-passage-to-match", name="get_passage_to_match", methods={"GET"})
     */
    public function getPassagesToAttach(Request $request){

        $blId = $request->query->get('bl_id');
        $bl = $this->achatRepository->find($blId);
        $passageExist = null;

        $pasChantierId = !is_null($bl->getChantier()) ? $bl->getChantier()->getChantierId() : null;
        $pasFournisseurId = !is_null($bl->getFournisseur()) ? $bl->getFournisseur()->getId() : null;
        
        $passages = $this->passageRepository->getPassagesToAttach($pasChantierId, $pasFournisseurId, $bl->getFacturedAt()->format('Y-m-d'));
    
        $passagesSort = $this->passage_s->sortToDateProch(strtotime($bl->getFacturedAt()->format('Y-m-d')), $passages);

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('bon_livraison/passages_list.html.twig', [
                'passages' => $passagesSort,
                'bon_livraison'=>$bl
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

}
