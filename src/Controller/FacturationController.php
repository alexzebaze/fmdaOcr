<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use Doctrine\ORM\EntityRepository;
use App\Entity\Achat;
use App\Entity\IAZone;
use App\Entity\TmpOcr;
use App\Entity\OcrField;
use App\Entity\ModelDocument;
use App\Entity\PreferenceField;
use App\Entity\Utilisateur;
use App\Entity\Lot;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Entity\Fields;
use App\Entity\EmailDocumentPreview;
use App\Entity\PrevisionelCategorie;
use App\Entity\Vente;
use App\Form\AchatType;
use App\Form\FournisseursType;
use App\Form\ReglementType;
use App\Entity\Reglement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Fournisseurs;
use App\Entity\Entreprise;
use App\Entity\Chantier;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Repository\ChantierRepository;
use App\Repository\PassageRepository;
use App\Repository\FournisseursRepository;
use App\Repository\AchatRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ReglementRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Service\GlobalService;
use App\Service\PassageService;
use App\Service\FactureService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mime\Address;
use setasign\Fpdi\Fpdi;
use \ConvertApi\ConvertApi;
use GuzzleHttp\Client;

/**
 * @Route("/facturation", name="facturation_")
 */
class FacturationController extends Controller{

    use CommunTrait;
    private $username;
    private $password;
    private $queue_id ;
    private $chantierRepository;
    private $fournisseurRepository;
    private $passageRepository;
    private $achatRepository;
    private $global_s;
    private $passage_s;
    private $facture_s;
    private $session;

    public function __construct(GlobalService $global_s, ChantierRepository $chantierRepository, AchatRepository $achatRepository, FournisseursRepository $fournisseurRepository, PassageRepository $passageRepository, SessionInterface $session, FactureService $facture_s, PassageService $passage_s){
        $this->chantierRepository = $chantierRepository;
        $this->achatRepository = $achatRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->passageRepository = $passageRepository;
        $this->global_s = $global_s;
        $this->facture_s = $facture_s;
        $this->passage_s = $passage_s;
        $this->session = $session;
    }

    /**
     * @Route("/lot-status/{lotId}/{chantierId}", name="list_by_lot_status", methods={"GET", "POST"})
    */
    public function indexByLot(Request $request, Session $session, $lotId, $chantierId): Response
    {
        $session->set('mois_facturation', null);
        $session->set('annee_facturation', null);
        if($chantierId)
            $session->set('chantier_facturation', $chantierId);

        $session->set('status_facturation', 15);
        $session->set('lot_facturation', $lotId);

        return $this->redirectToRoute('facturation_list');
    }
    
    /**
     * @Route("/zip", name="zip", methods={"POST"})
     */
    public function zip(Request $request)
    {   
        $facturations = $this->getDoctrine()->getRepository(Achat::class)->findByTabId(explode('-', $request->request->get('list-facture-id')), 'facturation');

        $zip = new \ZipArchive();
        $zipName = 'factures_zip.zip';

        $zip->open($zipName,  \ZipArchive::CREATE);

        foreach ($facturations as $value) {
            if (!is_null($value['document_file'])) {

                $cheminDocument = $this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/".$value['document_file'];
                if(file_exists($cheminDocument)) {
                    $zip->addFromString(basename($value['document_file']),  file_get_contents($cheminDocument));
                }
            }
        }

        $zip->close();

        $response = new Response(file_get_contents($zipName));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
        $response->headers->set('Content-length', filesize($zipName));

        @unlink($zipName);

        return $response;

    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session)
    {   
        $form = $request->request->get('form', null);

        $reglement = new Reglement();
        $formR = $this->createForm(ReglementType::class, $reglement);

        $fournisseurId = $chantierId = $is_paid = $lotId = $categorieLotId = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $chantierId = (!(int)$form['chantier']) ? null : (int)$form['chantier'];
            $fournisseurId = (!(int)$form['fournisseur']) ? null : (int)$form['fournisseur'];
            $is_paid = (!(int)$form['is_paid']) ? null : (int)$form['is_paid'];
            $lotId = (!(int)$form['lot']) ? null : (int)$form['lot'];
            $categorieLotId = (!(int)$form['categorie_lot']) ? null : (int)$form['categorie_lot'];

            $achats = $this->achatRepository->findByfacturedDate($mois, $annee, 'facturation', $chantierId, $fournisseurId,null, $lotId, null, $is_paid);

            $session->set('mois_facturation', $mois);
            $session->set('annee_facturation', $annee);
            $session->set('chantier_facturation', $chantierId);
            $session->set('fournisseur_facturation', $fournisseurId);
            $session->set('is_paid_facturation', $is_paid);
            $session->set('lot_facturation', $lotId);
            $session->set('categorie_lot_facturation', $categorieLotId);
        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_facturation', $day[1]);
            $annee = $session->get('annee_facturation', $day[0]);
            $chantierId = $session->get('chantier_facturation', null);
            $statusId = $session->get('status_facturation', null);
            $fournisseurId = $session->get('fournisseur_facturation', null);
            $lotId = $session->get('lot_facturation', null);
            $is_paid = $session->get('is_paid_facturation', null);
            $categorieLotId = $session->get('categorie_lot_facturation', null);

            $achats = $this->achatRepository->findByfacturedDate($mois, $annee, 'facturation', $chantierId, $fournisseurId, null, $lotId, $statusId, $is_paid);

            $session->set('status_facturation', null);

            if($request->query->get('tt')){
                dd([$mois, $annee, $chantierId, $fournisseurId, $lotId, $statusId]);
            }
        }
        if(!is_null($chantierId)){
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantierId, 'type'=>'devis_client']);
        }
        else{
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>'devis_client']);
        }
        
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'chantier' => (!is_null($chantierId)) ? $this->chantierRepository->find($chantierId) : "",
            'fournisseur' => (!is_null($fournisseurId)) ?  $this->fournisseurRepository->find($fournisseurId) : "",
            'lot' => (!is_null($lotId)) ?  $entityManager->getRepository(Lot::class)->find($lotId) : "",
            'categorie_lot' => (!is_null($categorieLotId)) ?  $entityManager->getRepository(PrevisionelCategorie::class)->find($categorieLotId) : "",
            'is_paid'=>$is_paid
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
        ->add('is_paid', ChoiceType::class, [
            'label' => "Paiement",
            'required' => false,
            'choices' => ["Payé"=>1, "Non Payé"=>2],
            'attr' => array(
                'class' => 'form-control'
            )
        ])
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
        ->add('lot', EntityType::class, array(
            'class' => Lot::class,
            'query_builder' => function(EntityRepository $repository) use($categorieLotId) { 
                $req = $repository->createQueryBuilder('l')
                ->andWhere('l.entreprise = :entreprise')
                ->setParameter('entreprise', $this->session->get('entreprise_session_id'));

                if(!is_null($categorieLotId)){
                    $req = $req->andWhere('l.previsionel_categorie = :previsionel_categorie')
                    ->setParameter('previsionel_categorie', $categorieLotId);
                }
                
                $req = $req->orderBy('l.lot', 'ASC');
                return $req;
            },
            'required' => false,
            'label' => "Lot",
            'choice_label' => 'lot',
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('categorie_lot', EntityType::class, array(
            'class' => PrevisionelCategorie::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('l')
                ->andWhere('l.entreprise = :entreprise')
                ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                ->orderBy('l.nom', 'ASC');
            },
            'required' => false,
            'label' => "Categorie",
            'choice_label' => 'nom',
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
        ))->getForm();

        $type = 'facturation';
        $entityManager = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);

        $adapter = new ArrayAdapter($achats);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(100000);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }        

        $montantPaye = $entityManager->getRepository(Achat::class)->countMontantByfacturedPayeDate($mois, $annee, 'facturation', $chantierId, $fournisseurId, $lotId, $is_paid);
        $montant = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDate($mois, $annee, 'facturation', $chantierId, $fournisseurId, null, null, $lotId, null, $is_paid);
        $montantCodeComptaAchat = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDate($mois, $annee, 'facturation', $chantierId, $fournisseurId, 1, null, $lotId, null, $is_paid); // code compte Achat
        $montantCodeComptaCharge = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDate($mois, $annee, 'facturation', $chantierId, $fournisseurId, 2, null, $lotId, null, $is_paid); // code compte Charge
        $montantDue = $entityManager->getRepository(Achat::class)->countMontantDue($mois, $annee, 'facturation', $chantierId, $fournisseurId, $lotId, $is_paid); // code compte Charge

        $valChart = [0,0,0,0,0,0,0,0,0,0,0,0];
        $sumMontant = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($montant as $value) {
            $valChart[(int)$value['mois']-1] = number_format($value['prixttc'], 3, '.', '');
            $sumMontant['prixttc'] =  $sumMontant['prixttc'] + $value['prixttc'];
            $sumMontant['sum_ht'] =  $sumMontant['sum_ht'] + $value['sum_ht'];
        }

        $sumMontantCodeComptaAchat = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($montantCodeComptaAchat as $value) {
            $sumMontantCodeComptaAchat['sum_ht'] =  $sumMontantCodeComptaAchat['sum_ht'] + $value['sum_ht'];
        }

        $sumMontantCodeComptaCharge = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($montantCodeComptaCharge as $value) {
            $sumMontantCodeComptaCharge['prixttc'] =  $sumMontantCodeComptaCharge['prixttc'] + $value['prixttc'];
            $sumMontantCodeComptaCharge['sum_ht'] =  $sumMontantCodeComptaCharge['sum_ht'] + $value['sum_ht'];
        }
        $full_month = $annee;
        if($mois && !empty($mois) && $annee)
            $full_month = Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY');
        

        $countDocAttentes = $entityManager->getRepository(EmailDocumentPreview::class)->countByDossier('facturation');
        $page = $entityManager->getRepository(Page::class)->findOneBy(['cle'=>"FACTURE_FOURNISSEUR"]);
        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }
        
        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }


        $chantiers = $this->chantierRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['nameentreprise'=>"ASC"]);
        return $this->render($type.'/index.html.twig', [
            'pager' => $pager,
            'devis'=>$devis,
            'is_paid'=>$is_paid,
            'achats' => $achats,
            'type'=>$type,
            'full_month'=> $full_month,
            'valChart'=> $valChart,
            'mois'=>$mois,
            'annee'=>$annee,
            'formR' => $formR->createView(),
            'form' => $form->createView(),
            'montant'=>$sumMontant,
            'countDocAttentes'=>$countDocAttentes,
            'montantCodeComptaAchat'=>$sumMontantCodeComptaAchat,
            'montantCodeComptaCharge'=>$sumMontantCodeComptaCharge,
            'montantPaye'=>$montantPaye,
            'montantDue'=>$montantDue,
            'countDocAttentes'=>$countDocAttentes,
            'tab_code_compta'=>$this->global_s->getTabCodeCompta(),
            'url'=> $this->generateUrl('comparateur_bl_fc_reglement_post', ['facturation'=>1], UrlGenerator::ABSOLUTE_URL),
            'columns'=>$columns,
            'columnsVisibileId'=>$columnsVisibileIdArr,
            'tabColumns'=>$this->global_s->getTypeFields(),
            'chantiers'=>$chantiers
        ]);
    }


    /**
     * @Route("/attribut-chantier", name="attribut_chantier")
     */
    public function attribuChantier(Request $request){
        $factureId = $request->request->get('factureId');
        $chantierId = $request->request->get('chantier');

        $facture = $this->achatRepository->find($factureId);
        $chantier = $this->chantierRepository->find($chantierId);

        $facture->setChantier($chantier);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        return $this->redirectToRoute('facturation_list');
    }

    /**
     * @Route("/add-manuel", name="add_manuel")
     */
    public function addFactureManuel(Request $request, Session $session){

        $facturation = new Achat();
        $facturation->setType('facturation');
        $form = $this->createForm(AchatType::class, $facturation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            if(!is_null($entreprise))
                $facturation->setEntreprise($entreprise);

            if(is_null($facturation->getTva())){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
                $facturation->setTva($tva);
            }
            if(is_null($facturation->getDevise())){
                $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
                $facturation->setDevise($devise);
            }

            /** @var UploadedFile $document_file */
            $uploadedFile = $request->files->get('document_file2');
            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/";
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
                $facturation->setDocumentFile($newFilename);
            } 
            else if(!is_null($session->get('tmp_acr_facture', null))){
                $f = $session->get('tmp_acr_facture', null);
                $fileTab = explode('.', $f);
                $fileTab[count($fileTab)-1] = "pdf";
                $pdf = implode(".", $fileTab);

                if(!is_file($dir.$pdf)){
                    $pdf = str_replace("pdf", "PDF", $pdf);
                }

                $facturation->setDocumentFile($pdf);


                // Telecharger le document PDF du projet api ocr vers le site
                $dirSave = "uploads/achats/facturation/";
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

            if($request->request->get('automatique')){
                $echeance = (int)$request->request->get('periode');
                if($echeance > 0){
                    $jourGenerate = ((int)$request->request->get('jour_generate') >0 ) ? $request->request->get('jour_generate') : 1; 
                    $facturation->setAutomatique(true);
                    $facturation->setEcheance($echeance);
                    $facturation->setJourGenerate($jourGenerate);

                    $date = new \DateTime();
                    $date->add(new \DateInterval('P'.$echeance.'M'));

                    $jour = 1;
                    if($request->request->get('jour_generate'))
                        $jour = $request->request->get('jour_generate');

                    $date = $date->format('Y-m').'-'.$jour;

                    $facturation->setDateGenerate(new \DateTime($date));

                    $dueDateGenerate = $request->request->get('due_generate');
                    if($dueDateGenerate){
                        $facturation->setDueDateGenerate(new \Datetime($dueDateGenerate));
                    }
                }
                else{
                    $this->get('session')->getFlashBag()->clear();
                    $this->addFlash('warning', "pour une generation automatique vous devez renseigner la periode");
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($facturation);
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
                $this->global_s->saveOcrField($document, "facturation", $session->get('tmp_acr_facture', null));
            }
            
            //delete last ocr tmp data
            $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("facturation", $session->get('tmp_acr_facture', null));
            $session->set('tmp_acr_facture', null);

            if(!is_null($session->get('email_facture_load_id', null))){
                $docPreview =  $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($session->get('email_facture_load_id', null));

                if(!is_null($docPreview)){ 
                    $entityManager->remove($docPreview);
                    $entityManager->flush();
                }
                $session->set('email_facture_load_id', null);
            }


            unlink($dir.'/'.$this->global_s->replaceExtenxionFilename($facturation->getDocumentFile(), 'jpg'));
            
            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/".$this->global_s->replaceExtenxionFilename($facturation->getDocumentFile(), 'jpg')."/";
            if (is_dir($dir)) {
                try {
                    $this->global_s->deleteDirectory($dir);
                } catch (Exception $e) {
                    
                }
            } 

            return $this->redirectToRoute('facturation_list');
        }

        $lastOcrFile = $session->get('tmp_acr_facture', null);
        $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"facturation", "filename"=>$lastOcrFile, 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

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
        return $this->render('facturation/add_facture.html.twig', [
            'form' => $form->createView(),
            'facturation' => $facturation,
            'nbrPage' => $nbrPage,
            'fieldPreference' => $fieldPreferenceArr,
            'tmpOcr'=>$tmpOcr,
            'lastOcrFile'=>$lastOcrFile,
            'display_lot'=>true,
            'add_mode'=>'manuel',
            'fieldsIaZone'=>[],
        ]);
    } 

    /**
     * @Route("/launch-ia", name="launch_ia")
     */
    public function launchIa(Request $request){
        $lastOcrFile =  $this->session->get('tmp_acr_facture', "");
        
        $facturation = new Achat();
        $facturation->setType('facturation');
        

        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/".$lastOcrFile;

        $datasResult = $this->global_s->lancerIa($lastOcrFile, $facturation, "facturation", $dirLandingImg);
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('facturation_add_manuel');
        }

        $form = $this->createForm(AchatType::class, $datasResult['facturation']);
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

        return $this->render('facturation/add_facture.html.twig', $datasResult);
    }

    /**
     * @Route("/group-text-by-position", name="group_text_by_position",  methods={"POST"})
     */
    public function groupTextByPosition(Request $request, Session $session){

        $result = $this->global_s->groupTextByPosition($request, $session->get('tmp_acr_facture', null), 'facturation');
        return new Response(json_encode($result)); 
    }

    /**
     * @Route("/import-document/{scan}", name="import_document", methods={"POST", "GET"})
     */
    public function loadDocumentToOcr(Request $request, Session $session, $scan = null){

        $document = $request->files->get('document');

        $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/";
        $documentEmail = null;
        try {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        } catch (FileException $e) {}

        if ( ($request->isMethod('post') && $document)) {
            $originalFilename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = $originalFilename . date('YmdHis') . '.' . $document->guessExtension();
            $imagenameSaved = $originalFilename . date('YmdHis') . '.jpg'; // document als image filename save
            $document->move($dir, $newFilename);
        }
        else if($request->isMethod('GET')){
            $documentEmailId = $session->get('email_facture_load_id', null);

            if(!is_null($documentEmailId)){
                $documentEmail = $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->find($documentEmailId);
                $newFilename = $documentEmail->getDocument();

                if(!$documentEmail->getIsConvert() && !$documentEmail->getExecute()){
                    $filenameRename = uniqid().$newFilename;
                    $documentEmail->setDocument($filenameRename);
                    rename("uploads/achats/facturation/".$newFilename, "uploads/achats/facturation/".$filenameRename);
                    $newFilename = $filenameRename;

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->flush();
                    $session->set('tmp_acr_facture', $newFilename);
                }

                $name_array = explode('.',$newFilename);
                $file_type=$name_array[sizeof($name_array)-1];
                $nameWithoutExt = str_replace(".".$file_type, "", $newFilename);

                $imagenameSaved = $nameWithoutExt . '.jpg';

                if(!$documentEmail->getIsConvert()){
                    
                    try {
                        $this->global_s->convertPdfToImage2("uploads/achats/facturation/", $newFilename, $imagenameSaved);
                        $documentEmail->setIsConvert(true);
                    } catch (Exception $e) {}
                }
                if(!$documentEmail->getExecute()){
                    try {
                        $this->global_s->saveOcrScan("uploads/achats/facturation/", $imagenameSaved, "facturation", false);
                        $documentEmail->setExecute(true);
                    } catch (Exception $e) {}
                }

            }
            else{
                $this->addFlash("error", "Aucun document fournie");
                return $this->redirectToRoute("facturation_add_manuel");
            }
            
        }
        else{
            $this->addFlash("error", "Aucun document fournie");
            return $this->redirectToRoute("facturation_add_manuel");
        } 

        //delete last ocr tmp data
        $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("facturation", $session->get('tmp_acr_facture', null));
        
        $isForm = false;
        $session->set('tmp_acr_facture', $imagenameSaved);
        if( ($request->isMethod('post') && $document) || !is_null($scan) ){
            $isForm = true;
            try {
                $this->global_s->convertPdfToImage2("uploads/achats/facturation/", $newFilename, $imagenameSaved);
            } catch (\Exception $e) {
                $this->addFlash("error", $e->getMessage());
            }
            $this->global_s->saveOcrScan("uploads/achats/facturation/", $imagenameSaved, "facturation", $isForm);
        }
        else{
            if(!$this->global_s->isDocumentConvert($imagenameSaved)){
                $this->global_s->convertPdfToImage2("uploads/achats/facturation/", $newFilename, $imagenameSaved);
            }
            if(!$this->global_s->isOcrSave($imagenameSaved, "facturation")){
                $this->global_s->saveOcrScan("uploads/achats/facturation/", $imagenameSaved, "facturation", $isForm);
            }
        }


        $facturation = new Achat();
        $facturation->setType('facturation');

        $dirLandingImg = $this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/".$imagenameSaved;
                
        
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
            $facturation->setTva($tva);

            $facturation->setFournisseur($documentEmail->getFournisseur()); 
            $facturation->setChantier($documentEmail->getChantier()); 
            $facturation->setDocumentId($documentEmail->getDocumentId()); 
            $facturation->setFacturedAt($documentEmail->getFacturedAt()); 
            $facturation->setDocumentFile($documentEmail->getDocument()); 
            $facturation->setPrixht($documentEmail->getPrixht()); 
            $facturation->setPrixttc($documentEmail->getPrixttc()); 
            $facturation->setCodeCompta($documentEmail->getFournisseur()->getCodeCompta()); 
            $facturation->setLot($documentEmail->getFournisseur()->getLot()); 

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"facturation", "filename"=>$session->get('tmp_acr_facture', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

            $datasResult = [
                "facturation"=>$facturation,
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
                "dossier" => "facturation",
                "tmpOcr"=>$tmpOcr,
                "fieldsIaZone" => []
            ];
        }
        else{

            $client = new Client([
                'base_uri' => $this->global_s->getBASEURL_OCRAPI(),
            ]);

            $response = $client->request('POST', 'ocrapi/launchia', [
                'form_params' => [
                        'dossier' => "facturation",
                        'document_file' => $imagenameSaved,
                        'dir_document_file' => $dirLandingImg,
                        'entreprise' => $this->session->get('entreprise_session_id')
                    ]
                ]);

            $datasResult = json_decode($response->getBody()->getContents(), true);

            $facturation->setDocumentId($datasResult['facturation']['documentId']); 
            $facturation->setDocumentFile($datasResult['facturation']['documentFile']); 
            $facturation->setPrixht($datasResult['facturation']['prixht']); 
            $facturation->setPrixttc($datasResult['facturation']['prixttc']); 

            if(array_key_exists('facturation', $datasResult) && array_key_exists("fournisseur", $datasResult['facturation']) && !is_null($datasResult['facturation']['fournisseur']) ){
                $fournisseurBl = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($datasResult['facturation']['fournisseur']['id']);
                $facturation->setFournisseur($fournisseurBl); 
                $facturation->setCodeCompta($fournisseurBl->getCodeCompta()); 
                $facturation->setLot($fournisseurBl->getLot()); 
            }
            if(array_key_exists('facturation', $datasResult) && array_key_exists("facturedAt", $datasResult['facturation']) && array_key_exists("date", $datasResult['facturation']['facturedAt']) ){
                $facturation->setFacturedAt(new \Datetime($datasResult['facturation']['facturedAt']['date'])); 
            }
            if(array_key_exists('facturation', $datasResult) && array_key_exists("chantier", $datasResult['facturation']) && !is_null($datasResult['facturation']['chantier']) ){
                $chantierBl = $this->getDoctrine()->getRepository(Chantier::class)->find($datasResult['facturation']['chantier']['chantierId']);
                $facturation->setChantier($chantierBl); 
            }
            if($datasResult["tvaVal"] != ""){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>str_replace("%", "", $datasResult["tvaVal"])]);
                $facturation->setTva($tva);
            }
            $datasResult['facturation'] = $facturation;

            $tmpOcr = $this->getDoctrine()->getRepository(TmpOcr::class)->findBy(['dossier'=>"facturation", "filename"=>$session->get('tmp_acr_facture', null), 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);
            $datasResult['tmpOcr'] = $tmpOcr;
        }

        //$datasResult = $this->global_s->lancerIa($imagenameSaved, $facturation, "facturation", $dirLandingImg);

        
        if(is_null($datasResult)){
            $this->addFlash('warning', "Aucun text detecté pour le document");
            return $this->redirectToRoute('facturation_add_manuel');
        }

        $facturation = $datasResult['facturation'];
        $chantier = $facturation->getChantier() ?? null;

        $form = $this->createForm(AchatType::class, $datasResult['facturation'], array('chantier' =>$chantier));
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

        return $this->render('facturation/add_facture.html.twig', $datasResult);
            
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

            if($request->request->get('submit') ==  "comptabilite"){
                $facturations = $this->getDoctrine()->getRepository(Achat::class)->findByTabIdNotExportCompta(explode('-', $request->request->get('list-fc-id')), 'facturation');
            }
            else{
                $countFactureExport = $this->getDoctrine()->getRepository(Achat::class)->countFactureExport(explode('-', $request->request->get('list-fc-id')), 'facturation');
                $facturations = $this->getDoctrine()->getRepository(Achat::class)->findByTabId(explode('-', $request->request->get('list-fc-id')), 'facturation');
            
                if ($request->isXmlHttpRequest()) {
                    if($countFactureExport > 0){
                        $datas = ['status'=>500, "message"=>"Document deja envoyé"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                }
            }
            
            if ($request->isMethod('POST')) {
                $is_send = 0;
                $this->get('session')->getFlashBag()->clear();
                if(filter_var($request->request->get('email_comptable'), FILTER_VALIDATE_EMAIL)){
                    foreach ($facturations as $value) {
                        
                        $chantierName = $fournisseurName = "";
                        if(!is_null($value['chantier_id'])){
                            $chantierName = $this->chantierRepository->find($value['chantier_id'])->getNameentreprise();
                        }
                        if(!is_null($value['fournisseur_id'])){
                            $fournisseurName = $this->fournisseurRepository->find($value['fournisseur_id'])->getNom();
                        }

                        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                        $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
                        $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();
                        $is_send = 1;
                        $email = (new Email())
                        ->from(new Address($sender_email, $sender_name))
                        ->to($request->request->get('email_comptable'))
                        ->subject('Facturation '.$fournisseurName."-".$chantierName)
                        ->html('Bonjour, Vous trouverez ci-joint la facture.');

                        if(!is_null($value['document_file'])){
                            $email = $email->attachFromPath('uploads/achats/facturation/'.$value['document_file']);
                        }
                        
                        try {
                            $send = $customMailer->send($email);
                        } catch (\Exception $ex) { $error = $ex->getMessage(); }
                            
                    }
                    if($is_send){
                        if($request->request->get('submit') ==  "comptabilite"){
                            $this->achatRepository->updateFactureCompta(explode('-', $request->request->get('list-fc-id')));
                        }
                        $this->addFlash('success', "Facturation envoyée avec success");
                    }
                    else{
                        $this->addFlash('error', "Aucune facture à envoyer");
                    }
                }
                else
                    $this->addFlash('error', "Veuillez verifier l'email fourni");
            }

            if ($request->isXmlHttpRequest()) {
                $datas = ['status'=>200, "message"=>"Document envoyé avec succèss"];
                $response = new Response(json_encode($datas));
                return $response;
            }
        }
        else{
            if(!is_null($error)){
                $datas = ['status'=>300, "message"=>$error];
            }
            else{
                $datas = ['status'=>300, "message"=>"Veuillez configurer les informations d'envoie de mail de cette entreprise"];
            }
        
            if ($request->isXmlHttpRequest()) {
                $response = new Response(json_encode($datas));
                return $response;
            }
        }
        return $this->redirectToRoute('facturation_list');
    }

    /**
     * @Route("/add", name="add")
     */
    public function new(Request $request)
    {   
        $facturation = new Achat();

        $form = $this->createForm(AchatType::class, $facturation, array());

        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $facturation->setEntreprise($entreprise);

                $facturation->setType('facturation');

                if(is_null($facturation->getDevise())){
                    $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
                    $facturation->setDevise($devise);
                }
                if(is_null($facturation->getTva())){
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
                    $facturation->setTva($tva);
                } 


                if(!is_null($facturation->getTva()))
                    $facturation->setMtva($facturation->getTva()->getValeur());
                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->persist($facturation);
                $entityManager->flush();                

                $this->deleteDocument($facturation->getRossumDocumentId());
                return $this->redirectToRoute('facturation_list');
            }
        }

        $annotations = [];
        $rossumDossierId = $this->global_s->getQueue('facturation');
        if(empty($rossumDossierId)){
            $this->addFlash('error', "Cette entreprise n'a pas d'identifiant pour ce dossier");
            return $this->render('facturation/add.html.twig', [
                'form' => $form->createView(),
                'facturation' => $facturation,
                    'rossum_documents'=>[],
                'display_lot'=>true,
                "fieldsIaZone"=>[]
            ]);
        }
        
        $url = $this->global_s->constructUrl($rossumDossierId);
        while ($url) {
            $response = $this->global_s->makeRequest($url);
            $annotations = array_merge($annotations, $response->results);
            $url = $response->pagination->next;
        }

        $rossum_documents = [];
        if(!empty($annotations)){
            $rossum_documents = $this->global_s->listRossumDoc($annotations, $this->achatRepository->getAllBl('facturation'));            
        }
        else
            $this->addFlash('error', "Aucune facture completed");

        return $this->render('facturation/add.html.twig', [
            'form' => $form->createView(),
            'facturation' => $facturation,
            'rossum_documents'=>$rossum_documents,
            'display_lot'=>true,
            "fieldsIaZone"=>[]
        ]);
    }
    

    /**
     * @Route("/delete-tmp-ocr", name="delete_tmp_ocr")
     */
    public function deleteTmpOcr(Session $session){
        $this->getDoctrine()->getRepository(TmpOcr::class)->removesAll("facturation", $session->get('tmp_acr_facture', null));
        $session->set('tmp_acr_facture', null);
           
        return $this->redirectToRoute('facturation_add_manuel');
    }

    /**
     * @Route("/get-document-by-id", name="get_document_by_id", methods={"GET"})
     */
    public function getDocumentById(Request $request)
    {
        $factureId = $request->query->get('facture_id');
        $facture = $this->achatRepository->find($factureId);

        if(is_null($facture)){
            $datas["status"] = 500;
            $datas["message"] = "Facture non trouvée";
            $datas = [
                'status'=>500, 
                "message"=>"Facture non trouvée"
            ];
        }
        else{
            $datas = [
                'status'=>200, 
                "message"=>"",
                "facture"=>[
                    "id"=>$facture->getId(),
                    "document"=>$facture->getDocumentFile(),
                    "prixttc"=>$facture->getPrixttc(),
                    "prixht"=>$facture->getPrixht()
                ]
            ];
        }


        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/duplique-facture", name="duplique")
     */
    public function duplique(Request $request)
    {      
        $em = $this->getDoctrine()->getManager();
        $facture = $this->achatRepository->find($request->query->get('facture_id'));
        $isBlExist = $this->achatRepository->findOneBy(['document_id'=>$facture->getDocumentId(), 'fournisseur'=>$facture->getFournisseur()->getId(), 'type'=>'bon_livraison']);
        $this->get('session')->getFlashBag()->clear();
        if(is_null($isBlExist)){
            $bl = new Achat();
            $bl = clone $facture;
            $bl->setType('bon_livraison');
            $bl->setBlValidation($facture->getId());

            $em->persist($bl);

            $em->flush();
            $facture->setBlValidation($bl->getId());



            $file = $this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/".$facture->getDocumentFile();
            $text_image = $this->get('kernel')->getProjectDir() . "/public/assets/images/filigrane_image.png"; 
             
            // Set source PDF file 
            $pdf = new Fpdi(); 
            try {
                if (file_exists($file)) {
                    $pagecount = $pdf->setSourceFile($file);

                    // Add watermark image to PDF pages 
                    for($i=1;$i<=1;$i++){ 
                        $tpl = $pdf->importPage($i); 
                        $size = $pdf->getTemplateSize($tpl); 
                        $pdf->addPage(); 
                        $pdf->useTemplate($tpl, 1, 1, $size['width'], $size['height'], TRUE); 
                        //Put the watermark 
                        $xxx_final = ($size['width']/4 - 15); 
                        $yyy_final = ($size['height']/4 +20); 
                        $pdf->Image($text_image, $xxx_final, $yyy_final, 0, 0, 'png'); 
                    } 
                     
                    // Output PDF with watermark 
                    $pdf->Output('F', $this->get('kernel')->getProjectDir() . "/public/uploads/factures/".$bl->getDocumentFile());
                }
                else{
                    $bl->setDocumentFile($facture->getDocumentFile());
                }
            } catch (FileException $e) {
                $bl->setDocumentFile($facture->getDocumentFile());
            }

            $em->flush();

            /* couplage passage */
            $passageExist = null;
            $pasChantierId = !is_null($bl->getChantier()) ? $bl->getChantier()->getChantierId() : null;
            $pasFournisseurId = !is_null($bl->getFournisseur()) ? $bl->getFournisseur()->getId() : null;
            
            $passageExist = $this->passageRepository->isPassage($pasChantierId, $pasFournisseurId, $bl->getFacturedAt()->format('Y-m-d'));
            

            $this->addFlash('success', "Operation effectuée avec success");
            if($passageExist){
                $passageExist = $this->passageRepository->find($passageExist['id']);
                $passageExist->setBonLivraison($bl);
                $this->addFlash('success', "Bon couplé à un passage");
            }
            else{
                //$this->passage_s->createPassage($bl);
                //$this->addFlash('success', "bon de livraison et Passage crées avec success");
            }
            /* fin couplage passage */

            return $this->redirectToRoute('bon_livraison_list');
        }
        else{
            $this->addFlash('warning', "un bon de meme fournisseur et documentID identique existe déjà");
        }

        if($request->query->get('page') && $request->query->get('page') == "facturation")
            return $this->redirectToRoute('facturation_list');

        return $this->redirectToRoute('comparateur_bl_fc_index');
    }

    /**
     * @Route("/{rossum_document_id}/delete-doublon", name="delete_rossum_document_id")
     */
    public function deleteDocumentDuplique(Request $request, $rossum_document_id)
    {
        $this->deleteDocument($rossum_document_id);
        return $this->redirectToRoute('facturation_add');
    }

    public function deleteDocument($rossum_document_id){
        $this->global_s->deleteDocument($rossum_document_id);
        return 1;
    }

    /**
     * @Route("/{facturationId}/edit", name="edit")
     */
    public function edit(Request $request, $facturationId)
    {
        $facturation = $this->getDoctrine()->getRepository(Achat::class)->find($facturationId);

        $form = $this->createForm(AchatType::class, $facturation, array('chantier' => $facturation->getChantier()));
        $form->handleRequest($request);

        $is_document_change = false;
        if ($form->isSubmitted() && $form->isValid()) {

            //$valttc = $facturation->getPrixht() + ( ($facturation->getTva()->getValeur() * $facturation->getPrixht()) / 100);
            //$facturation->setPrixttc($valttc);

            if(is_null($facturation->getDevise())){
                $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
                $facturation->setDevise($devise);
            }
            
            if(is_null($facturation->getTva())){
                $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
                $facturation->setTva($tva);
            }            
            if(!is_null($facturation->getTva()))
                $facturation->setMtva($facturation->getTva()->getValeur());

            if($request->request->get('automatique')){
                $echeance = (int)$request->request->get('periode');
                if($echeance > 0){
                    $facturation->setAutomatique(true);
                    
                    //$date = $facturation->getFacturedAt();
                    //$date->add(new \DateInterval('P'.$echeance.'M'));

                    $jourGenerate = ((int)$request->request->get('jour_generate') >0 ) ? $request->request->get('jour_generate') : 1; 

                    if($request->request->get('periode') != $facturation->getEcheance() || $jourGenerate != $facturation->getJourGenerate()){

                        $date = new \DateTime();
                        $date->add(new \DateInterval('P'.$echeance.'M'));

                        $jour = 1;
                        if($request->request->get('jour_generate'))
                            $jour = $request->request->get('jour_generate');

                        $date = $date->format('Y-m').'-'.$jour;

                        $facturation->setDateGenerate(new \DateTime($date));
                    }

                    $facturation->setEcheance($echeance);
                    $facturation->setJourGenerate($jourGenerate);

                    if($request->request->get('due_generate')){
                        $dueDateGenerate = $request->request->get('due_generate');
                        $facturation->setDueDateGenerate(new \Datetime($dueDateGenerate));
                    }
                    else{
                        $facturation->setDueDateGenerate(null);
                    }
                }
                else{
                    $this->get('session')->getFlashBag()->clear();
                    $this->addFlash('warning', "pour une generation automatique vous devez renseigner la periode");
                }
            }
            else{
                $facturation->setAutomatique(false);
            }

            /** @var UploadedFile $document_file */
            $uploadedFile = $request->files->get('document_file2');
            if ($uploadedFile){
                $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/";
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

                if($facturation->getDocumentFile() != $newFilename){
                    $is_document_change = true;
                }

                $facturation->setDocumentFile($newFilename);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();


            if($request->query->get('page') == "comparateur")
                return $this->redirectToRoute('comparateur_bl_fc_index');

            if($is_document_change){
                return $this->render('facturation/edit.html.twig', [
                    'form' => $form->createView(),
                    'facturation' => $facturation,
                    'achat' => $facturation,
                    'is_document_change' => true,
                    'display_lot'=>true,
                    'fieldsIaZone'=>[],
                ]);
            }

            return $this->redirectToRoute('facturation_list');
        }

        return $this->render('facturation/edit.html.twig', [
            'form' => $form->createView(),
            'facturation' => $facturation,
            'achat' => $facturation,
            'display_lot'=>true,
            'fieldsIaZone'=>[],
        ]);
    }    

    /**
     * @Route("/{facturationId}/delete", name="delete")
     */
    public function delete($facturationId)
    {
        $facturation = $this->getDoctrine()->getRepository(Achat::class)->find($facturationId);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($facturation);
        $entityManager->flush();

        $this->get('session')->getFlashBag()->clear();
        $this->addFlash('success', "Suppression effectuée avec succès");
        return $this->redirectToRoute('facturation_list');
    }    

    /**
     * @Route("/checking-data-export", name="checking_data_export")
     */
    public function checkExport(Request $request)
    {
        $url = $this->global_s->constructUrl($this->global_s->getQueue('facturation'), $request->request->get('document_id'));
        $response = $this->global_s->makeRequest($url);
        $annotations = $response->results[0];
        $facturation = new Achat();
        $url_document = explode('/', $annotations->url);
        $facturation->setRossumDocumentId(end($url_document));
        $facturation->setDocumentFile($this->global_s->retrieveDocFile($annotations->document->file, $annotations->document->file_name, 'uploads/achats/facturation/'));
        
        $fournisseurfound = [];
        foreach ($annotations->content as $content){
            foreach ($content->children as $children) {

                if( ($children->schema_id == "chantier") && !empty($children->value)) {
                    $chantierfound = $this->findByNameAlpn($children->value, "chantier");
                    if(count($chantierfound) == 0){
                        $datas = ['status'=>500, 'issue'=>'chantier', 'sender_name'=>$children->value, "message"=>"Le chantier ".$children->value." n'existe pas"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                    else{
                        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierfound[0]['id']);
                        $facturation->setChantier($chantier);
                    }
                }
                if($children->schema_id == "lots"){
                    $lot = $this->getDoctrine()->getRepository(Lot::class)->findOneBy(["lot"=>$children->value]);
                    if(!is_null($lot))
                        $facturation->setLot($lot);
                }
                if($children->schema_id == "sender_name"){
                    if(!empty($children->value)){
                        $fournisseurfound = $this->findByNameAlpn($children->value, "fournisseur");
                        if(count($fournisseurfound) == 0){
                            $datas = ['status'=>500, 'issue'=>'fournisseur', 'sender_name'=>$children->value, "message"=>"Le fournisseur ".$children->value." n'existe pas"];
                            $response = new Response(json_encode($datas));
                            return $response;
                        }
                        else{
                            $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurfound[0]['id']);
                            $facturation->setFournisseur($fournisseur);
                            if(!is_null($fournisseur)){
                                $lot = $fournisseur->getLot();
                                if(!is_null($lot))
                                    $facturation->setLot($lot);

                                $facturation->setCodeCompta($fournisseur->getCodeCompta());
                            }
                        }

                    }
                    else{
                        $datas = ['status'=>500, 'issue'=>'fournisseur', 'sender_name'=>"", "message"=>"Vous devez fournir un nom de fournisseur"];
                        $response = new Response(json_encode($datas));
                        return $response;
                    }
                }
                if( ($children->schema_id == "amount_total_tax") && !empty($children->value)){
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>(float)$children->value]);
                    if(!is_null($tva))
                        $facturation->setTva($tva);
                }
                if($children->schema_id == "currency"){
                    $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>$children->value]);
                    if(!is_null($devise))
                        $facturation->setDevise($devise);
                }
                if($children->schema_id == "document_id"){
                    $facturation->setDocumentId($children->value);
                }
                if( ($children->schema_id == "amount_total_base") && !empty($children->value)){
                    $facturation->setPrixht((float)$children->value);
                }
                if( ($children->schema_id == "amount_total") && !empty($children->value) ){
                    $facturation->setPrixttc((float)$children->value);
                }
                if($children->schema_id == "date_issue"){
                    $facturation->setFacturedAt(new \Datetime($children->value)); 
                }
            }
        }
        $factureExiste = "";
        if( !is_null($facturation->getFournisseur()) && !is_null($facturation->getFacturedAt()) && !is_null($facturation->getPrixht()) ){
            $blExist = $this->achatRepository->findByFFTH($facturation->getFournisseur()->getId(), $facturation->getFacturedAt(), $facturation->getPrixht(), 'facturation');
            if($blExist){
                //$datas = ['status'=>500, 'issue'=>'doublon', "message"=>"Une facture similaire existe déjà."];
                //$response = new Response(json_encode($datas));
                //return $response;
                $factureExiste = $blExist['document_file'];
            }
        }

        if(is_null($facturation->getTva())){
            $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>"20"]);
            $facturation->setTva($tva);
        }
        if(is_null($facturation->getDevise())){
            $devise = $this->getDoctrine()->getRepository(Devise::class)->findOneBy(["nom"=>"Euro"]);
            $facturation->setDevise($devise);
        }
        if(is_null($facturation->getFacturedAt())){
            $facturation->setFacturedAt(new \Datetime());
        }
        if(is_null($facturation->getPrixttc()) || $facturation->getPrixht()){
            if(is_null($facturation->getPrixttc()) && !is_null($facturation->getPrixht())){
                $ttc = $facturation->getPrixht() + ($facturation->getPrixht()*($facturation->getTva()->getValeur()/100));
                $facturation->setPrixttc($ttc);
            }
            elseif(!is_null($facturation->getPrixttc()) && is_null($facturation->getPrixht())){
                $ht = $facturation->getPrixttc() / (1+ ($facturation->getTva()->getValeur()/100));
                $facturation->setPrixttc($ht);
            }
        }

        $form = $this->createForm(AchatType::class, $facturation, array('chantier' => $facturation->getChantier()));
        $form->handleRequest($request);

        /*
        $tvaVal = $this->global_s->calculTva($facturation->getPrixttc(), $facturation->getPrixht());
        $tva = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(["valeur"=>$tvaVal]);
        if(!is_null($tva))
            $facturation->setTva($tva);*/
        $datas = ['status'=>200, "message"=>"", "count_fournisseur"=>count($fournisseurfound)];
        $datas['preview'] = $this->renderView('facturation/preview_export.html.twig', [
                'form' => $form->createView(),
                'facturation' => $facturation, 
                'achat' => $facturation, 
                'annotations'=>$annotations,
                'fournisseurfound'=>$fournisseurfound,
                'factureExiste'=> $factureExiste,
                'display_lot'=>true,
                "fieldsIaZone"=>[]
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    public function findByNameAlpn($fournisseurName, $entity){
        $fournisseurArr = [];
        if($entity == "fournisseur")
            $fournisseurfound = $this->fournisseurRepository->findByNameAlpn($fournisseurName);
        elseif($entity == "chantier")
            $fournisseurfound = $this->chantierRepository->findByNameAlpn($fournisseurName);


        if(count($fournisseurfound) > 0){
            foreach ($fournisseurfound as $value) {
                $fournisseurArr[] = $value;
            }
        }
        else{
            //$fournisseurNameAlpn = str_replace("  ", " ", preg_replace("/[^a-zA-Z0-9]+/", " ", $fournisseurName));
            $fournisseurNameAlpn = $fournisseurName;
            $fournisseurTmpArr = explode(" ", $fournisseurNameAlpn);
            $fournisseurTmpArrCpy = $fournisseurTmpArr;
            while (count($fournisseurTmpArr) > 0) {
                if(strlen(implode(" ", $fournisseurTmpArr)) < 4 ){
                    array_pop($fournisseurTmpArr);
                    continue;
                }

                if($entity == "fournisseur"){
                    $fournisseurfound = $this->fournisseurRepository->findByNameAlpn(implode(" ", $fournisseurTmpArr));
                }
                elseif($entity == "chantier"){
                    $fournisseurfound = $this->chantierRepository->findByNameAlpn(implode(" ", $fournisseurTmpArr));
                }
                if(count($fournisseurfound) > 0){
                    foreach ($fournisseurfound as $value){
                        $fournisseurArr[] = $value;
                    }
                    //break;
                }
                array_pop($fournisseurTmpArr);
            }   
            // fouille dans le sens inverse
            if(count($fournisseurArr) == 0){
                foreach ($fournisseurTmpArrCpy as $value) {
                    if(strlen($value) < 4 )
                        continue;

                    if($entity == "fournisseur"){
                        $fournisseurfound = $this->fournisseurRepository->findByNameAlpn($value);
                    }
                    elseif($entity == "chantier"){
                        $fournisseurfound = $this->chantierRepository->findByNameAlpn($value);
                    }

                    if(count($fournisseurfound) > 0){
                        foreach ($fournisseurfound as $value){
                            $fournisseurArr[] = $value;
                        }
                        //break;
                    }
                }
            }        
        }
        return $fournisseurArr;
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
                $datas['form'] = $this->renderView('facturation/modal_fournisseur_add.html.twig', [
                        'form' => $form->createView(),
                        'fournisseur' => $fournisseur,
                        'url'=> $this->generateUrl('facturation_fournisseur_add', [], UrlGenerator::ABSOLUTE_URL)
                    ]);
            }
            $response = new Response(json_encode($datas));
            return $response;
        }
        return  new Response("Attendez la fin du chargement de la page...");
    }


    /**
     * @Route("/attach-code-compta", name="attach_code_compta", methods={"POST"})
     */
    public function attachCodeComta(Request $request){
        $code =  $request->request->get('code_compta');
        $factureId =  $request->request->get('facture_id');

        $facture = $this->getDoctrine()->getRepository(Achat::class)->find($factureId);
        $facture->setCodeCompta($code);
        
        $entityManager = $this->getDoctrine()->getManager();
        
        $entityManager->flush();
        return $this->redirectToRoute('facturation_list');
    }

}



