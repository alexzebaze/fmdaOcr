<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use Doctrine\ORM\EntityRepository;
use App\Entity\Achat;
use App\Entity\Passage;
use App\Entity\PreferenceField;
use App\Entity\Vente;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Entity\Fields;
use App\Entity\IAZone;
use App\Entity\OcrField;
use App\Entity\ModelDocument;
use App\Entity\Lot;
use App\Entity\TmpOcr;
use App\Entity\EmailDocumentPreview;
use App\Form\AchatType;
use App\Form\FournisseursType;
use App\Form\ChantierType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Entity\Fournisseurs;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Repository\ChantierRepository;
use App\Repository\PassageRepository;
use App\Repository\FournisseursRepository;
use App\Repository\AchatRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\UtilisateurRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Service\GlobalService;
use App\Service\PassageService;
use Carbon\Carbon;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use \ConvertApi\ConvertApi;
use GuzzleHttp\Client;

/**
 * @Route("/bon_livraison", name="bon_livraison_")
 */
class BonLivraisonController extends Controller
{
    use CommunTrait;
    private $global_s;
    private $passage_s;
    private $chantierRepository;
    private $passageRepository;
    private $achatRepository;
    private $entrepriseRepository;
    private $fournisseurRepository;
    private $utilisateurRepository;
    private $session;

    public function __construct(GlobalService $global_s, ChantierRepository $chantierRepository, AchatRepository $achatRepository, EntrepriseRepository $entrepriseRepository,FournisseursRepository $fournisseurRepository, SessionInterface $session, PassageRepository $passageRepository, PassageService $passage_s, UtilisateurRepository $utilisateurRepository){
        $this->global_s = $global_s;
        $this->passage_s = $passage_s;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->chantierRepository = $chantierRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->achatRepository = $achatRepository;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->passageRepository = $passageRepository;
        $this->session = $session;
    }

    
    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session)
    {

        $form = $request->request->get('form', null);

        $fournisseurId = $chantierId = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $chantierId = (!(int)$form['chantier']) ? null : (int)$form['chantier'];
            $fournisseurId = (!(int)$form['fournisseur']) ? null : (int)$form['fournisseur'];
            $lotId = (!(int)$form['lot']) ? null : (int)$form['lot'];
            $is_devis_rattach = (!(int)$form['is_devis_rattach']) ? null : (int)$form['is_devis_rattach'];
            
            $bon_livraisons = $this->achatRepository->findByfacturedDate($mois, $annee, 'bon_livraison', $chantierId, $fournisseurId, null, $lotId, null, null, $is_devis_rattach);

            $session->set('mois_bl', $mois);
            $session->set('annee_bl', $annee);
            $session->set('chantier_bl', $chantierId);
            $session->set('fournisseur_bl', $fournisseurId);
            $session->set('lot_bl', $lotId);
            $session->set('is_devis_rattach_bl', $is_devis_rattach);

        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_bl', $day[1]);
            $annee = $session->get('annee_bl', $day[0]);
            $chantierId = $session->get('chantier_bl', null);
            $fournisseurId = $session->get('fournisseur_bl', null);
            $lotId = $session->get('lot_bl', null);
            $is_devis_rattach = $session->get('is_devis_rattach_bl', null);
            $bon_livraisons = $this->achatRepository->findByfacturedDate($mois, $annee, 'bon_livraison', $chantierId, $fournisseurId, null, $lotId, null, null, $is_devis_rattach);
        }

        if(!is_null($chantierId)){
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantierId, 'type'=>'devis_client']);
        }
        else{
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>'devis_client']);
        }

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'chantier' => (!is_null($chantierId)) ? $this->chantierRepository->find($chantierId) : "",
            'fournisseur' => (!is_null($fournisseurId)) ?  $this->fournisseurRepository->find($fournisseurId) : "",
            'lot' => (!is_null($lotId)) ?  $this->getDoctrine()->getRepository(Lot::class)->find($lotId) : "",
            'is_devis_rattach'=>$is_devis_rattach

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
        ->add('chantier', EntityType::class, array(
            'class' => Chantier::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('c')
                ->andWhere('c.entreprise = :entreprise')
                ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                ->orderBy('c.nameentreprise', 'ASC');
            },
            'required' => false,
            'label' => "Chantier",
            'choice_label' => 'nameentreprise',
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
        ->add('is_devis_rattach', ChoiceType::class, [
            'label' => "Devis rattachés",
            'required' => false,
            'choices' => ["Avec devis"=>1, "Sans devis"=>2],
            'attr' => array(
                'class' => 'form-control'
            )
        ])
        ->add('lot', EntityType::class, array(
            'class' => Lot::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('l')
                ->where('l.entreprise = :entreprise_id')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('l.lot', 'ASC');
            },
            'required' => false,
            'label' => "Lot",
            'choice_label' => 'lot',
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $entityManager = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);

        $adapter = new ArrayAdapter($bon_livraisons);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(100000);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        

        $doublon = $entityManager->getRepository(Achat::class)->findDoublon('bon_livraison', $this->session->get('entreprise_session_id'));
        $tabDoublon = [];
        foreach ($bon_livraisons as $value) {
            if(array_search($value->getId(), array_column($doublon, 'id')) !== false) {
                $tabDoublon[] = $value->getId();
            }
        }

        $montant = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDate($mois, $annee, 'bon_livraison', $chantierId, $fournisseurId, null, null, $lotId , $is_devis_rattach);

        $montantPaye = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDateList($mois, $annee, 'bon_livraison', $chantierId, $fournisseurId, null, true, $lotId , $is_devis_rattach);

        $montantNonRelie = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDate($mois, $annee, 'bon_livraison', $chantierId, $fournisseurId, null, false, $lotId , $is_devis_rattach);

        $valChart = [0,0,0,0,0,0,0,0,0,0,0,0];
        $sumMontant = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($montant as $value) {
            $valChart[(int)$value['mois']-1] = number_format($value['prixttc'], 3, '.', '');
            $sumMontant['prixttc'] =  $sumMontant['prixttc'] + $value['prixttc'];
            $sumMontant['sum_ht'] =  $sumMontant['sum_ht'] + $value['sum_ht'];
        }

        $sumMontantNonRelie = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($montantNonRelie as $value) {
            $sumMontantNonRelie['prixttc'] =  $sumMontantNonRelie['prixttc'] + $value['prixttc'];
            $sumMontantNonRelie['sum_ht'] =  $sumMontantNonRelie['sum_ht'] + $value['sum_ht'];
        }

        $sumMontantPaye = ['prixttc' => 0, 'sum_ht'=>0];
        $sumMontantPaye['prixttc'] =  $sumMontantPaye['prixttc'] + $montantPaye['prixttc'];
        $sumMontantPaye['sum_ht'] =  $sumMontantPaye['sum_ht'] + $montantPaye['sum_ht'];
        


        $full_month = $annee;
        if($mois && !empty($mois) && $annee)
            $full_month = Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY');


        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true);
        $countDocAttentes = $entityManager->getRepository(EmailDocumentPreview::class)->countByDossier('bon_livraison');
            
        $page = $entityManager->getRepository(Page::class)->findOneBy(['cle'=>"BONLIVRAISON"]);
        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }
        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }

        return $this->render('bon_livraison/index.html.twig', [
            'pager' => $pager,
            'devis'=>$devis,
            'chantiers'=> $this->chantierRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['nameentreprise'=>'ASC']),
            'bon_livraisons' => $bon_livraisons,
            'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs),
            'full_month'=> $full_month,
            'tabDoublon' => $tabDoublon,
            'valChart'=> $valChart,
            'mois'=>$mois,
            'annee'=>$annee,
            'form' => $form->createView(),
            'montant'=>$sumMontant,
            'countDocAttentes'=>$countDocAttentes,
            'montant_paye'=>$sumMontantPaye,
            'montant_non_relie'=>$sumMontantNonRelie,
            'columns'=>$columns,
            'columnsVisibileId'=>$columnsVisibileIdArr,
            'tabColumns'=>$this->global_s->getTypeFields()
        ]);
    }

    /**
     * @Route("/get-by-listid", name="list_by_list_id", methods={"GET"})
     */
    public function getByListId(Request $request)
    {
        $tabBlId = explode(',', $request->query->get('bl_list'));
        $bon_livraisons = $this->achatRepository->getBlByListId($tabBlId);

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('comparateur/modal_bl.html.twig', [
                'bon_livraisons' => $bon_livraisons,
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/get-by-documentID", name="list_by_documentID", methods={"GET"})
     */
    public function getByDocumentID(Request $request)
    {
        $documentId = explode(',', $request->query->get('documentId'));
        $bon_livraisons = $this->achatRepository->findBy(['type'=>'bon_livraison', 'document_id'=>$documentId]);

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('comparateur/modal_bl.html.twig', [
                'bon_livraisons' => $bon_livraisons,
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/edit-chantier", name="edit_chantier")
     */
    public function editChantier(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $blId = $request->request->get('bl_id');
        $chantier = $this->chantierRepository->find($request->request->get('chantier'));
        $bl = $this->achatRepository->find($blId);
        $bl->setChantier($chantier);

        $entityManager->flush();
        return $this->redirectToRoute('bon_livraison_list');
    }

    /**
     * @Route("/dettach-facture/{id}", name="dettach_facture", methods={"GET"})
     */
    public function dettachFacture(Request $request, $id)
    {
        $bon_livraison = $this->achatRepository->find($id);
        $facturation = $this->achatRepository->find($bon_livraison->getBlValidation());
        if(!is_null($facturation)){
            $listBl = explode(',', $facturation->getBlValidation());
            $newBlList = [];
            foreach ($listBl as $value) {
                if($value != $bon_livraison->getId())
                    $newBlList[] = $value;

            }   

            $listBl = implode(',', $newBlList);
            $facturation->setBlValidation($listBl);
        }
        $bon_livraison->setBlValidation(null);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();   
        return $this->redirectToRoute('bon_livraison_list');
    }

    /**
     * @Route("/dettach-devis/{id}", name="dettach_devis", methods={"GET"})
     */
    public function dettachDevis(Request $request, $id)
    {
        $bon_livraison = $this->achatRepository->find($id);
        $bon_livraison->setDevis(null);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();   

        if($request->query->get('page') && $request->query->get('page') == "bl_associe" && $request->query->get('devis_id') )
            return $this->redirectToRoute('chantier_list_bl_associe_devis', ['devisId'=> $request->query->get('devis_id')]);

        return $this->redirectToRoute('bon_livraison_list');
    }

    /**
     * @Route("/get-by-passage", name="get_by_passage")
     */
    public function getBlByPassage(Request $request)
    {   
        $entityManager = $this->getDoctrine()->getManager();
        $passageId = $request->query->get('passageId');

        $passage = $this->passageRepository->find($passageId);

        $bonLivraisons = $this->achatRepository->getBlByCHantierFournDateMonth('bon_livraison', $passage->getFournisseur()->getId(), $passage->getChantier()->getChantierId(), $passage->getDateDetection()->format('Y-m'));

        $bonLivraisonsArr = [];
        foreach ($bonLivraisons as $value) {
            $bonLivraisonsArr[] = [
                'prixht'=>$value->getPrixht() ? $value->getPrixht() : '',
                'lot'=>$value->getLot() ? $value->getLot()->getLot() : '',
                'documentId'=>$value->getDocumentId(),
                'id'=>$value->getId(),
            ];
        }
        return new Response(json_encode([
            'bonLivraisons'=>$bonLivraisonsArr
        ]));
    }

    /**
     * @Route("/bl-devis-attach", name="devis_attach")
     */
    public function attachHoraireDevis(Request $request, Session $session)
    {
        $listBl = explode('-', $request->request->get('list-elt-id'));
        $devisId = $request->request->get('devis');
        if($devisId){
            $this->achatRepository->attachDevis($listBl, $devisId);
        }
        else
            $this->addFlash('error', "Vous devez selectionner un devis");
        
        if($request->query->get('page') && $request->query->get('page') == "chantier"){
            return $this->redirectToRoute('chantier_show', ['chantierId'=>$request->query->get('chantierId'), 'tab'=>$request->query->get('tab')]);
        }
        if($request->query->get('page') && $request->query->get('page') == "bon_livraison"){
            return $this->redirectToRoute('facturation_list');
        }
        return $this->redirectToRoute('bon_livraison_list');
    }

    /**
     * @Route("/send-bl", name="send_bl")
     */
    public function sendBl(Request $request, MailerInterface $mailer)
    {
        $error = null; $customMailer = null;
        try{
            $customMailer = $this->global_s->initEmailTransport();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if(!is_null($customMailer) && is_null($error)){
               
            $bon_livraisons = $this->getDoctrine()->getRepository(Achat::class)->findByTabId(explode('-', $request->request->get('list-bl-id')), 'bon_livraison');
            if ($request->isMethod('POST')) {
                $is_send = 0;
                $this->get('session')->getFlashBag()->clear();
                if(filter_var($request->request->get('email_comptable'), FILTER_VALIDATE_EMAIL)){
                    foreach ($bon_livraisons as $value) {
                        if(!is_null($value['document_file'])){

                            $infosEnvoie = $this->global_s->getDefaultEntrepriseMail();
                            $sender_email = $infosEnvoie['email'];
                            $sender_name = $infosEnvoie['name'];
                        
                            $is_send = 1;
                            $email = (new Email())
                            ->from(new Address($sender_email, $sender_name))
                            ->to($request->request->get('email_comptable'))
                            ->subject('Bons de livraison')
                            ->html($request->request->get('content'))
                            ->attachFromPath('uploads/factures/'.$value['document_file']);
                            
                            try {
                                $send = $customMailer->send($email);
                            } catch (\Exception $ex) { $error = $ex->getMessage(); }
                        }
                    }
                    if($is_send){
                        $this->addFlash('success', "Bon de livraison envoyé avec success");
                    }
                    else{
                        $this->addFlash('error', "Aucun bon trouvé");
                    }
                    
                }
                else
                    $this->addFlash('error', "Veuillez verifier l'email fourni");
            }
        }
        else{
            if(!is_null($error))
                $this->addFlash('error', $error);
            else
                $this->addFlash('error', "Veuillez configurer les informations d'envoie de mail de cette entreprise");
        }
        return $this->redirectToRoute('bon_livraison_list');
    }

    /**
     * @Route("/import-document/{scan}", name="import_document", methods={"POST", "GET"})
     */
    public function loadDocumentToOcr(Request $request, Session $session, $scan = null){

        $document = $request->files->get('document');
        $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/factures/";
        $documentEmail = null;
        if ($document) {
            
            try {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            } catch (FileException $e) {}

            if ($request->isMethod('post') && $document) {
                $originalFilename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename . date('YmdHis') . '.' . $document->guessExtension();
                $saveFile = $originalFilename . date('YmdHis') . '.jpg';
                $document->move($dir, $newFilename);
            }
        }
        else if($request->isMethod('GET')){
            $documentEmailId = $session->get('email_facture_load_id', null);

            if(!is_null($documentEmailId)){
                $documentEmail = $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($documentEmailId);
                $newFilename = $documentEmail->getDocument();

                if(!$documentEmail->getIsConvert() && !$documentEmail->getExecute()){
                    $filenameRename = uniqid().$newFilename;
                    $documentEmail->setDocument($filenameRename);
                    rename("uploads/factures/".$newFilename, "uploads/factures/".$filenameRename);
                    $newFilename = $filenameRename;

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->flush();
                    $session->set('tmp_acr_bl', $newFilename);
                }
                
                $name_array = explode('.',$newFilename);
                $file_type=$name_array[sizeof($name_array)-1];
                $nameWithoutExt = str_replace(".".$file_type, "", $newFilename);

                $saveFile = $nameWithoutExt . '.jpg';

                if(!$documentEmail->getIsConvert()){
                    
                    try {
                        $this->global_s->convertPdfToImage2("uploads/factures/", $newFilename, $saveFile);
                        $documentEmail->setIsConvert(true);
                    } catch (Exception $e) {}
                }

                if(!$documentEmail->getExecute()){
                    try {
                        $this->global_s->saveOcrScan("uploads/factures/", $saveFile, "bon_livraison", false);
                        $documentEmail->setExecute(true);
                    } catch (Exception $e) {}
                }
            }
            else{
                $this->addFlash("error", "Aucun document fournie");
                return $this->redirectToRoute("bon_livraison_add_manuel");
            }
            
        }
        else{
            $this->addFlash("error", "Aucun document fournie");
            return $this->redirectToRoute("bon_livraison_add_manuel");
        }

        //delete last ocr tmp data
        $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("bon_livraison", $session->get('tmp_acr_bl', null));

        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/factures/".$saveFile;

        $isForm = false;
        $session->set('tmp_acr_bl', $saveFile);
        if(($request->isMethod('post') && $document) || !is_null($scan)){
            $isForm = true;
            $this->global_s->convertPdfToImage2("uploads/factures/", $newFilename, $saveFile);
            $this->global_s->saveOcrScan("uploads/factures/", $saveFile, "bon_livraison", $isForm);
        }
        else{
            if(!$this->global_s->isDocumentConvert($saveFile)){
                $this->global_s->convertPdfToImage2("uploads/factures/", $newFilename, $saveFile);
            }

            if(!$this->global_s->isOcrSave($saveFile, "bon_livraison")){
                $this->global_s->saveOcrScan("uploads/factures/", $saveFile, "bon_livraison", $isForm);
            }
        }

        $bl = new Achat();
        $bl->setType('bon_livraison');

        if(!is_null($documentEmail) && !is_null($documentEmail->getFacturedAt()) && !is_null($documentEmail->getFournisseur()) && !is_null($documentEmail->getDocumentId()) && !is_null($documentEmail->getPrixht()) && !is_null($documentEmail->getPrixttc()) && !is_null($documentEmail->getChantier())){

            $oldDate = 0;
            if(!is_null($documentEmail->getFacturedAt()) && $documentEmail->getFacturedAt()->format('Y') < (new \DateTime())->format('Y')){
                $oldDate = 1;
            }
            
            $tvaVal = 0;$color = "orange";
            $dividende = ((float)$documentEmail->getPrixht() != 0) ? $documentEmail->getPrixht() : 1 ;
            $tva = ( ((float)$documentEmail->getPrixttc() - (float)$documentEmail->getPrixht()) / $dividende )*100;
            $partE = (int)$tva;
            $partD = $tva - $partE;

            if($partE != 0){
                if($partE == 5  and ($partD <= 0.8 and $partD >= 0.2)){
                    $tvaVal = 5.5;
                    $color = "green";
                }
                elseif($partE == 20  and ($partD <= 0.3)){
                    $tvaVal = 20;
                    $color = "green";
                }
                elseif($partE == 19  and ($partD >= 0.7)){
                    $tvaVal = 20;
                    $color = "green";
                }
                elseif($partE == 10  and ($partD <= 0.3)){
                    $tvaVal = 10;
                    $color = "green";
                }
                elseif($partE == 9  and ($partD >= 0.3)){
                    $tvaVal = 10;
                    $color = "green";
                }
                else{
                    $tvaVal = $tva;
                    $color = "red";
                }
            }

            $tva = $this->getDoctrine()->getRepository(Tva::class)->find($tvaVal);
            $bl->setTva($tva);

            $bl->setFournisseur($documentEmail->getFournisseur()); 
            $bl->setChantier($documentEmail->getChantier()); 
            $bl->setDocumentId($documentEmail->getDocumentId()); 
            $bl->setFacturedAt($documentEmail->getFacturedAt()); 
            $bl->setDocumentFile($documentEmail->getDocument()); 
            $bl->setPrixht($documentEmail->getPrixht()); 
            $bl->setPrixttc($documentEmail->getPrixttc()); 
            $bl->setCodeCompta($documentEmail->getFournisseur()->getCodeCompta()); 
            $bl->setLot($documentEmail->getFournisseur()->getLot()); 

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"bon_livraison", "filename"=>$session->get('tmp_acr_bl', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

            $datasResult = [
                "bon_livraison"=>$bl,
                "tmpOcr"=>$tmpOcr,
                "lastOcrFile" => $this->global_s->replaceExtenxionFilename($documentEmail->getDocument(), "jpg"),
                "display_lot" => true,
                "ia_launch" => true,
                "add_mode" => "manuel",
                "score" => $documentEmail->getScore(),
                "tvaVal" => $tvaVal."%",
                "oldDate" => $oldDate,
                "color" => $color,
                "nbrPage" => 0,
                "fournisseurfound" => [$documentEmail->getFournisseur()],
                "chantierfound" => [$documentEmail->getChantier()],
                "clientfound" => [$documentEmail->getClient()],
                "dossier" => "bon_livraison",
                "fieldsIaZone" => []
            ];
        }
        else{
            $client = new Client([
                'base_uri' => $this->global_s->getBASEURL_OCRAPI(),
            ]);

            $response = $client->request('POST', 'ocrapi/launchia', [
                'form_params' => [
                        'dossier' => "bon_livraison",
                        'document_file' => $saveFile,
                        'dir_document_file' => $dirLandingImg,
                        'entreprise' => $this->session->get('entreprise_session_id')
                    ]
                ]);

            $datasResult = json_decode($response->getBody()->getContents(), true);

            $bl->setDocumentId($datasResult['bon_livraison']['documentId']); 
            $bl->setDocumentFile($datasResult['bon_livraison']['documentFile']); 
            $bl->setPrixht($datasResult['bon_livraison']['prixht']); 
            $bl->setPrixttc($datasResult['bon_livraison']['prixttc']); 

            if(array_key_exists('bon_livraison', $datasResult) && array_key_exists("fournisseur", $datasResult['bon_livraison']) && !is_null($datasResult['bon_livraison']['fournisseur']) ){
                $fournisseurBl = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($datasResult['bon_livraison']['fournisseur']['id']);
                $bl->setFournisseur($fournisseurBl); 
                $bl->setCodeCompta($fournisseurBl->getCodeCompta()); 
                $bl->setLot($fournisseurBl->getLot()); 
            }
            if(array_key_exists('bon_livraison', $datasResult) && array_key_exists("facturedAt", $datasResult['bon_livraison']) && array_key_exists("date", $datasResult['bon_livraison']['facturedAt']) ){
                $bl->setFacturedAt(new \Datetime($datasResult['bon_livraison']['facturedAt']['date'])); 
            }
            if(array_key_exists('bon_livraison', $datasResult) && array_key_exists("chantier", $datasResult['bon_livraison']) && !is_null($datasResult['bon_livraison']['chantier']) ){
                $chantierBl = $this->getDoctrine()->getRepository(Chantier::class)->find($datasResult['bon_livraison']['chantier']['chantierId']);
                $bl->setChantier($chantierBl); 
            }
            if($datasResult["tvaVal"] != ""){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>str_replace("%", "", $datasResult["tvaVal"])]);
                $bl->setTva($tva);
            }
            $datasResult['bon_livraison'] = $bl;

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"bon_livraison", "filename"=>$session->get('tmp_acr_bl', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);
            $datasResult['tmpOcr'] = $tmpOcr;
        }

        //$datasResult = $this->global_s->lancerIa($saveFile, $bl, "bon_livraison", $dirLandingImg);


        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('bon_livraison_add_manuel');
        }

        $bl = $datasResult['bon_livraison'];

        $chantier = $bl->getChantier() ?? null;

        $form = $this->createForm(AchatType::class, $datasResult['bon_livraison'], array('chantier' =>$chantier));
        $form->handleRequest($request);

        $datasResult['form'] = $form->createView();
        $datasResult['document_pdf'] = $newFilename;


        $score = $datasResult['score'];
        if($score < 50)
            $status = "error";
        else if($score < 80)
            $status = "warning";
        else if($score >= 80)
            $status = "success";

        $this->addFlash($status, "La reconnaissance du document est de ".$score."%");

        if(!is_null($documentEmail)){
            $documentEmail->setScore($score);
            $documentEmail->setLu(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($documentEmail);
            $entityManager->flush();     
        }

        return $this->render('bon_livraison/add_manuel.html.twig', $datasResult);
            
    }


    /**
     * @Route("/launch-ia", name="launch_ia")
     */
    public function launchIa(Request $request){
        $lastOcrFile =  $this->session->get('tmp_acr_bl', "");
        
        $bl = new Achat();
        $bl->setType('bon_livraison');
        

        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/factures/".$lastOcrFile;

        $datasResult = $this->global_s->lancerIa($lastOcrFile, $bl, "bon_livraison", $dirLandingImg);
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('bon_livraison_add_manuel');
        }

        $form = $this->createForm(AchatType::class, $datasResult['bon_livraison']);
        $form->handleRequest($request);

        $datasResult['form'] = $form->createView();
        

        $score = $datasResult['score'];
        if($score < 50)
            $status = "error";
        else if($score < 80)
            $status = "warning";
        else if($score >= 80)
            $status = "success";

        $this->addFlash($status, "La reconnaissance du document est de ".$score."%");

        return $this->render('bon_livraison/add_manuel.html.twig', $datasResult);
    }

    /**
     * @Route("/attach-passage", name="attach_passage", methods={"POST"})
     */
    public function attachPassage(Request $request){
        $passage = $this->getDoctrine()->getRepository(Passage::class)->find($request->request->get('passageId'));
        $bl = $this->getDoctrine()->getRepository(Achat::class)->find($request->request->get('bonLivraisonId'));
    
        $entityManager = $this->getDoctrine()->getManager();        

        if(!is_null($bl->getPassage())){
            $passage->setBonLivraison($bl);
            $entityManager->flush();
        }

        return $this->redirectToRoute('passage_list');
    }

    /**
     * @Route("/add-manuel", name="add_manuel")
     */
    public function addFactureManuel(Request $request, Session $session){

        $bl = new Achat();
        $bl->setType('bon_livraison');
        $form = $this->createForm(AchatType::class, $bl);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            if(!is_null($entreprise))
                $bl->setEntreprise($entreprise);

            if(is_null($bl->getTva())){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
                $bl->setTva($tva);
            }
            if(is_null($bl->getDevise())){
                $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
                $bl->setDevise($devise);
            }

            /** @var UploadedFile $document_file */
            $uploadedFile = $request->files->get('document_file2');
            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/factures/";
            if ($uploadedFile){
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
                $bl->setDocumentFile($newFilename);
            }  
            else if(!is_null($session->get('tmp_acr_bl', null))){
                $f = $session->get('tmp_acr_bl', null);
                $fileTab = explode('.', $f);
                $fileTab[count($fileTab)-1] = "pdf";
                $pdf = implode(".", $fileTab);

                if(!is_file($dir.$pdf)){
                    $pdf = str_replace("pdf", "PDF", $pdf);
                }

                $bl->setDocumentFile($pdf);

                // Telecharger le document PDF du projet api ocr vers le site
                $dirSave = "uploads/factures/";
                $filenamedownloaded = $pdf;
                $urlFile = $this->global_s->getBASEURL_OCRAPI().$dirSave.$filenamedownloaded;
                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if(!is_file($dir.$filenamedownloaded)){
                        $this->global_s->downloadExternalFile($urlFile, $dirSave, $filenamedownloaded, "pdf");
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', "Le document n'a pas pu etre téléchargé");
                }

            }     

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($bl);
            $entityManager->flush();

            if($request->request->get('submit') == "validate_ia" && $request->request->get('model_document_id')){
                $document = $this->getDoctrine()->getRepository(ModelDocument::class)->find($request->request->get('model_document_id'));
            }
            else if($request->request->get('submit') == "ajuste_ia" && $request->request->get('model_document_id')){
                $document = $this->getDoctrine()->getRepository(ModelDocument::class)->find($request->request->get('model_document_id'));
                //$document->setNbrPage($request->request->get('nbr_page'));

                //suppression des positions defini precedement par l'user
                $this->getDoctrine()->getRepository(IAZone::class)->deleteIAZoneByDocument($document->getId());

                //suppression des positions de reconnaissance de facture
                $this->getDoctrine()->getRepository(OcrField::class)->deleteOcrFieldByDocument($document->getId());
            }
            else{
                $document = new ModelDocument();
                //$document->setNbrPage($request->request->get('nbr_page'));
                $entityManager->persist($document);
                $entityManager->flush();
            }
            if($request->request->get('submit') != "validate_ia"){
                $this->global_s->saveIAField($request, $form, $document);
                $this->global_s->saveOcrField($document, "bon_livraison", $session->get('tmp_acr_bl', null));
            }


            //delete last ocr tmp data
            $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("bon_livraison", $session->get('tmp_acr_bl', null));
            $session->set('tmp_acr_bl', null);
            
            if(!is_null($session->get('email_facture_load_id', null))){
                $docPreview =  $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($session->get('email_facture_load_id', null));

                if(!is_null($docPreview)){ 
                    $entityManager->remove($docPreview);
                    $entityManager->flush();
                }
                $session->set('email_facture_load_id', null);
            } 

            /* couplage passage */
            $passageExist = null;

            $pasChantierId = !is_null($bl->getChantier()) ? $bl->getChantier()->getChantierId() : null;
            $pasFournisseurId = !is_null($bl->getFournisseur()) ? $bl->getFournisseur()->getId() : null;
            
            $passageExist = $this->passageRepository->isPassage($pasChantierId, $pasFournisseurId, $bl->getFacturedAt()->format('Y-m-d'));
            
            if($passageExist && is_null($bl->getPassage())){
                $passageExist = $this->passageRepository->find($passageExist['id']);
                $passageExist->setBonLivraison($bl);
                $this->addFlash('success', "Bon crée avec success et couplé à un passage");
            }
            $entityManager->flush();

            return $this->redirectToRoute('bon_livraison_list');
        }


        $lastOcrFile = $session->get('tmp_acr_bl', null);
        $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"bon_livraison", "filename"=>$lastOcrFile, 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

        $nbrPage = 0;
        if(is_null($lastOcrFile) || count($tmpOcr) == 0){
            $lastOcrFile = null;
        }

        $fieldPreference = $this->getDoctrine()->getRepository(PreferenceField::class)->findAll();

        if(count($fieldPreference) == 0){
            $this->addFlash('warning', "Veuillez configurer les champs à extraire pour cette entreprise");
        }

        $fieldPreferenceArr = [];
        foreach ($fieldPreference as $value) {
            $fieldPreferenceArr[$value->getIdentifiant()] = [
                'type'=>$value->getType(),
                'identifiant'=>$value->getIdentifiant(),
            ];
        }

        return $this->render('bon_livraison/add_manuel.html.twig', [
            'form' => $form->createView(),
            'achat' => $bl,
            'nbrPage' => $nbrPage,
            'fieldPreference' => $fieldPreferenceArr,
            'tmpOcr'=>$tmpOcr,
            'lastOcrFile'=>$lastOcrFile,
            'display_lot'=>true,
            'add_mode'=>'manuel',
            'fieldsIaZone'=>[]
        ]);
    } 

    /**
     * @Route("/delete-tmp-ocr", name="delete_tmp_ocr")
     */
    public function deleteTmpOcr(Session $session){
        $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("bon_livraison", $session->get('tmp_acr_bl', null));
        $session->set('tmp_acr_bl', null);
        
        return $this->redirectToRoute('bon_livraison_add_manuel');
    }
    
    /**
     * @Route("/group-text-by-position", name="group_text_by_position",  methods={"POST"})
     */
    public function groupTextByPosition(Request $request, Session $session){
        $result = $this->global_s->groupTextByPosition($request, $session->get('tmp_acr_bl', null), 'bon_livraison');
        return new Response(json_encode($result)); 
    }

    /**
     * @Route("/find", name="find", methods={"GET"})
     */
    public function findDate(Request $request)
    {
        $dossier = $request->query->get('dossier');
        $filename = $request->query->get('filename');

        $found = 0;
        $dateFound = "";
        if($dossier != "" && $filename != ""){
            
            $firstEltDocument = $this->getDoctrine()->getRepository(OcrField::class)->getFirstEltDocument($dossier, $this->session->get('entreprise_session_id'), $filename);

            $firstTmpOcrText = $this->getDoctrine()->getRepository(OcrField::class)->findFirstTmpOcrText($dossier, $this->session->get('entreprise_session_id'), $filename, $firstEltDocument['id'], 300);
            foreach ($firstTmpOcrText as $value) {
                $dateSearch = str_replace('du ', "", strtolower($value['name']));
                $dateSearch = str_replace('du', "", strtolower($dateSearch));
                $formattedDate = $this->global_s->rebuildDate($dateSearch);
                if(!is_null($formattedDate)){
                    if(strtotime($formattedDate)){
                        $dateFound = (new \DateTime($formattedDate))->format("Y-m-d");
                        break;
                    }
                }
            }

            if($dateFound != "")
                $found = 1;
            
        }

        $response = new Response(json_encode([
            'count'=>$found,
            'date'=>$dateFound
        ]));
        return $response;
    }

    /**
     * @Route("/add", name="add")
     */
    public function new(Request $request)
    {
        $bon_livraison = new Achat();

        $form = $this->createForm(AchatType::class, $bon_livraison, array());

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $bon_livraison->setEntreprise($entreprise);

                $bon_livraison->setType('bon_livraison');

                if(is_null($bon_livraison->getDevise())){
                    $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
                    $bon_livraison->setDevise($devise);
                }
                
                if(is_null($bon_livraison->getTva())){
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
                    $bon_livraison->setTva($tva);
                }  
            
                if(!is_null($bon_livraison->getTva()))
                    $bon_livraison->setMtva($bon_livraison->getTva()->getValeur());
                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->persist($bon_livraison);

                /* couplage passage */
                $passageExist = null;

                $pasChantierId = !is_null($bon_livraison->getChantier()) ? $bon_livraison->getChantier()->getChantierId() : null;
                $pasFournisseurId = !is_null($bon_livraison->getFournisseur()) ? $bon_livraison->getFournisseur()->getId() : null;
                
                $passageExist = $this->passageRepository->isPassage($pasChantierId, $pasFournisseurId, $bon_livraison->getFacturedAt()->format('Y-m-d'));
                
                if($passageExist){
                    $passageExist = $this->passageRepository->find($passageExist['id']);
                    $passageExist->setBonLivraison($bon_livraison);
                    $this->addFlash('success', "Bon crée avec success et couplé à un passage");
                }
                else{
                    //$this->passage_s->createPassage($bon_livraison);
                    //$this->addFlash('success', "bon de livraison et Passage crées avec success");
                }
                /* fin couplage passage */


                $entityManager->flush();                

                $this->deleteDocument($bon_livraison->getRossumDocumentId());
                return $this->redirectToRoute('bon_livraison_list');
            }
        }


        $annotations = [];
        $rossumDossierId = $this->global_s->getQueue('bonLv');
        if(empty($rossumDossierId)){
            $this->addFlash('error', "Cette entreprise n'a pas d'identifiant pour ce dossier");
            return $this->render('bon_livraison/add.html.twig', [
                'form' => $form->createView(),
                'achat' => $bon_livraison,
                'rossum_documents'=>[],
                "fieldsIaZone"=>[]
            ]);
        }
        $url = $this->global_s->constructUrl($this->global_s->getQueue('bonLv'));
        while ($url) {
            $response = $this->global_s->makeRequest($url);
            $annotations = array_merge($annotations, $response->results);
            $url = $response->pagination->next;
        }

        $rossum_documents = [];
        if(!empty($annotations)){
            $rossum_documents = $this->global_s->listRossumDoc($annotations, $this->achatRepository->getAllBl('bonLv'));            
        }
        else
            $this->addFlash('error', "Aucune facture completed");

        return $this->render('bon_livraison/add.html.twig', [
            'form' => $form->createView(),
            'achat' => $bon_livraison,
            'rossum_documents'=>$rossum_documents,
            'display_lot'=>true,
            "fieldsIaZone"=>[]
        ]);
    }
    

    /**
     * @Route("/{rossum_document_id}/delete-doublon", name="delete_rossum_document_id")
     */
    public function deleteDocumentDuplique(Request $request, $rossum_document_id)
    {
        $this->deleteDocument($rossum_document_id);
        return $this->redirectToRoute('bon_livraison_add');
    }

    public function deleteDocument($rossum_document_id){
        $this->global_s->deleteDocument($rossum_document_id);
        return 1;
    }

    /**
     * @Route("/{bon_livraisonId}/edit", name="edit")
     */
    public function edit(Request $request, $bon_livraisonId)
    {
        $bon_livraison = $this->getDoctrine()->getRepository(Achat::class)->find($bon_livraisonId);

        $form = $this->createForm(AchatType::class, $bon_livraison, array('chantier' => $bon_livraison->getChantier()));
        $form->handleRequest($request);

        $is_document_change = false;
        if ($form->isSubmitted() && $form->isValid()) {          

            /** @var UploadedFile $document_file */
            $uploadedFile = $request->files->get('document_file2');
            if ($uploadedFile){
                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/factures/";
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

                if($bon_livraison->getDocumentFile() != $newFilename){
                    $is_document_change = true;
                }

                $bon_livraison->setDocumentFile($newFilename);
            }

            if(is_null($bon_livraison->getDevise())){
                $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
                $bon_livraison->setDevise($devise);
            }
            
            if(is_null($bon_livraison->getTva())){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
                $bon_livraison->setTva($tva);
            }   

            if(!is_null($bon_livraison->getTva()))
                $bon_livraison->setMtva($bon_livraison->getTva()->getValeur());

            /* couplage passage */
            $passageExist = null;

            $pasChantierId = !is_null($bon_livraison->getChantier()) ? $bon_livraison->getChantier()->getChantierId() : null;
            $pasFournisseurId = !is_null($bon_livraison->getFournisseur()) ? $bon_livraison->getFournisseur()->getId() : null;
            
            $passageExist = $this->passageRepository->isPassage($pasChantierId, $pasFournisseurId, $bon_livraison->getFacturedAt()->format('Y-m-d'));
            
            if($passageExist && is_null($bon_livraison->getPassage())){
                $passageExist = $this->passageRepository->find($passageExist['id']);
                $passageExist->setBonLivraison($bon_livraison);
                $this->addFlash('success', "Bon de livraison couplé à un passage");
            }
            /* fin couplage passage */

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            if($is_document_change){
                  return $this->render('bon_livraison/edit.html.twig', [
                    'form' => $form->createView(),
                    'achat' => $bon_livraison,
                    'is_document_change' => true,
                    'display_lot'=>true,
                    'fieldsIaZone'=>[],
                ]);
            }
            return $this->redirectToRoute('bon_livraison_list');
        }

        return $this->render('bon_livraison/edit.html.twig', [
            'form' => $form->createView(),
            'achat' => $bon_livraison,
            'display_lot'=>true,
            'fieldsIaZone'=>[],
        ]);
    }    

    /**
     * @Route("/{bon_livraisonId}/delete", name="delete")
     */
    public function delete(Request $request, $bon_livraisonId)
    {
        $bon_livraison = $this->getDoctrine()->getRepository(Achat::class)->find($bon_livraisonId);

        $entityManager = $this->getDoctrine()->getManager();

        try {
            $entityManager->remove($bon_livraison);
            $entityManager->flush();
            $this->addFlash('success', "Suppression effectuée avec succès");
        } catch (\Exception $e) {
            $this->addFlash('error', "Vous ne pouvez supprimer cet element s'il est lié à d'autres elements");
        }

        if($request->query->get('page') == "comparateur")
            return $this->redirectToRoute('comparateur_bl_fc_index');

        return $this->redirectToRoute('bon_livraison_list');
    }    

    /**
     * @Route("/checking-data-export", name="checking_data_export")
     */
    public function checkExport(Request $request)
    {
        $url = $this->global_s->constructUrl($this->global_s->getQueue('bonLv'), $request->request->get('document_id'));
        $response = $this->global_s->makeRequest($url);
        $annotations = $response->results[0];
        $bon_livraison = new Achat();
        $url_document = explode('/', $annotations->url);
        $bon_livraison->setRossumDocumentId(end($url_document));

        $bon_livraison->setDocumentFile($this->global_s->retrieveDocFile($annotations->document->file, $annotations->document->file_name, 'uploads/factures/'));
        
        $fournisseurfound = [];
        foreach ($annotations->content as $content){
            foreach ($content->children as $children) {
                if( ($children->schema_id == "chantier") && !empty($children->value)) {
                    $chantierfound = $this->global_s->findByNameAlpn($children->value, "chantier");
                    if(count($chantierfound) == 0){
                        $datas = ['status'=>500, 'issue'=>'chantier', 'sender_name'=>$children->value, "message"=>"Le chantier ".$children->value." n'existe pas"];
                        $response = new Response(json_encode($datas));
        
                        return $response;
                    }
                    else{
                        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierfound[0]['id']);
                        $bon_livraison->setChantier($chantier);
                    }
                }
                if($children->schema_id == "lots"){
                    $lot = $this->getDoctrine()->getRepository(Lot::class)->findOneBy(["lot"=>$children->value], ['lot'=>"ASC"]);
                    if(!is_null($lot))
                        $bon_livraison->setLot($lot);
                }
                if($children->schema_id == "sender_name"){

                    $fournisseurfound = $this->global_s->findByNameAlpn($children->value, "fournisseur");
                    if(count($fournisseurfound) == 0){
                        $datas = ['status'=>500, 'issue'=>'fournisseur', 'sender_name'=>$children->value, "message"=>"Le fournisseur ".$children->value." n'existe pas"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                    else{
                        $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurfound[0]['id']);
                        $bon_livraison->setFournisseur($fournisseur);
                        if(!is_null($fournisseur)){
                            $lot = $fournisseur->getLot();
                            if(!is_null($lot))
                                $bon_livraison->setLot($lot);

                            $bon_livraison->setCodeCompta($fournisseur->getCodeCompta());
                        }
                    }
                }
                if( ($children->schema_id == "amount_total_tax") && !empty($children->value)){
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>(float)$children->value]);
                    if(!is_null($tva))
                        $bon_livraison->setTva($tva);
                }
                if($children->schema_id == "currency"){
                    $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>$children->value]);
                    if(!is_null($devise))
                        $bon_livraison->setDevise($devise);
                }
                if($children->schema_id == "document_id"){
                    $bon_livraison->setDocumentId($children->value);
                }
                if( ($children->schema_id == "amount_total_base") && !empty($children->value)){
                    $bon_livraison->setPrixht((float)$children->value);
                }
                if( ($children->schema_id == "amount_total") && !empty($children->value) ){
                    $bon_livraison->setPrixttc((float)$children->value);
                }
                if($children->schema_id == "date_issue"){
                    $bon_livraison->setFacturedAt(new \Datetime($children->value)); 
                }
                if( ($children->schema_id == "date_due") && !empty($children->value)){
                    $bon_livraison->setDueAt(new \Datetime($children->value)); 
                }
            }
        }

        $blExiste = "";
        if( !is_null($bon_livraison->getFournisseur()) && !is_null($bon_livraison->getFacturedAt()) && !is_null($bon_livraison->getPrixht()) ){
            $blExist = $this->achatRepository->findByFFTH($bon_livraison->getFournisseur()->getId(), $bon_livraison->getFacturedAt(), $bon_livraison->getPrixht(), 'bon_livraison');
            if($blExist){
                //$datas = ['status'=>500, 'issue'=>'doublon', "message"=>"Un bon similaire existe déjà."];
                //$response = new Response(json_encode($datas));
                //return $response;
                $blExiste = $blExist['document_file'];
            }
        }

        if(is_null($bon_livraison->getTva())){
            $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
            $bon_livraison->setTva($tva);
        }
        if(is_null($bon_livraison->getDevise())){
            $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
            $bon_livraison->setDevise($devise);
        }
        if(is_null($bon_livraison->getFacturedAt())){
            $bon_livraison->setFacturedAt(new \Datetime());
        }
        if(is_null($bon_livraison->getPrixttc()) || $bon_livraison->getPrixht()){
            if(is_null($bon_livraison->getPrixttc()) && !is_null($bon_livraison->getPrixht())){
                $ttc = $bon_livraison->getPrixht() + ($bon_livraison->getPrixht()*($bon_livraison->getTva()->getValeur()/100));
                $bon_livraison->setPrixttc($ttc);
            }
            elseif(!is_null($bon_livraison->getPrixttc()) && is_null($bon_livraison->getPrixht())){
                $ht = $bon_livraison->getPrixttc() / (1+ ($bon_livraison->getTva()->getValeur()/100));
                $bon_livraison->setPrixttc($ht);
            }
        }

        $form = $this->createForm(AchatType::class, $bon_livraison, array('chantier' => $bon_livraison->getChantier()));
        $form->handleRequest($request);


        /*
        $tvaVal = $this->global_s->calculTva($bon_livraison->getPrixttc(), $bon_livraison->getPrixht());
        $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>$tvaVal]);
        if(!is_null($tva))
            $bon_livraison->setTva($tva);*/
        $datas = ['status'=>200, "message"=>"", "count_fournisseur"=>count($fournisseurfound)];
        $datas['preview'] = $this->renderView('bon_livraison/preview_export.html.twig', [
                'form' => $form->createView(),
                'bon_livraison' => $bon_livraison,
                'annotations'=>$annotations,
                'fournisseurfound'=>$fournisseurfound,
                'blExiste'=>$blExiste,
                'display_lot'=>true,
                "fieldsIaZone"=>[]
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/fournisseur-add", name="fournisseur_add")
     */
    public function addFournisseur(Request $request){
        $em = $this->getDoctrine()->getManager();
        $fournisseur = new Fournisseurs();
        $fournisseur->setDatecrea(new \DateTime());
        $fournisseur->setDatemaj(new \DateTime());
        $form = $this->createForm(FournisseursType::class, $fournisseur);

        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('POST')) {
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager = $this->getDoctrine()->getManager();

                    $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                    if(!is_null($entreprise))
                        $fournisseur->setEntreprise($entreprise);
                    
                    $entityManager->persist($fournisseur);
                    $entityManager->flush();                

                    $datas = ['status'=>200, 'message'=>"Opération éffectuée avec succès", "fournisseur"=>[
                        'id'=>$fournisseur->getId(),
                        'nom'=>$fournisseur->getNom(),
                    ]];
                }
                else
                    $datas = ['status'=>500, 'message'=>"Erreur creation fournisseur"];
            }
            else{
                $datas = ['status'=>200, "message"=>""];
                $datas['form'] = $this->renderView('bon_livraison/modal_fournisseur_add.html.twig', [
                        'form' => $form->createView(),
                        'fournisseur' => $fournisseur,
                        'url'=> $this->generateUrl('bon_livraison_fournisseur_add', [], UrlGenerator::ABSOLUTE_URL)
                    ]);
            }
            $response = new Response(json_encode($datas));
            return $response;
        }
        return  new Response("Attendez la fin du chargement de la page...");
    }

}



