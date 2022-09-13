<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Chantier;
use App\Entity\Remarque;
use App\Entity\Galerie;
use App\Entity\CompteRendu;
use App\Entity\Horaire;
use App\Entity\Status;
use App\Entity\OcrField;
use App\Entity\Fournisseurs;
use App\Entity\Vente;
use App\Entity\Page;
use App\Entity\FieldsEntreprise;
use App\Entity\Module;
use App\Entity\FournisseurCompteRendu;
use App\Entity\StatusModule;
use App\Entity\Paie;
use App\Entity\AssuranceAttestation;
use App\Entity\AssuranceContrat;
use App\Entity\AssuranceQuittance;
use App\Entity\Category;
use App\Entity\Achat;
use App\Form\ChantierType;
use App\Form\CompteRenduType;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\GlobalService;
use Carbon\Carbon;
use App\Entity\Entreprise;
use App\Entity\HoraireObservation;
use App\Service\ChantierService;
use App\Repository\PaieRepository;
use App\Repository\ChantierRepository;
use App\Repository\AchatRepository;
use App\Repository\CompteRenduRepository;
use App\Repository\RemarqueRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\VenteRepository;
use App\Repository\StatusRepository;
use App\Repository\HoraireRepository;
use App\Repository\GalerieRepository;

/**
 * @Route("/chantier", name="chantier_")
 */
class ChantierController extends Controller
{
    private $chantier_s;
    private $global_s;
    private $paieRepository;
    private $achatRepository;
    private $galerieRepository;
    private $compteRenduRepository;
    private $remarqueRepository;
    private $utilisateurRepository;
    private $statusRepository;
    private $venteRepository;
    private $HoraireRepository;
    private $session;

    public function __construct(GlobalService $global_s, ChantierService $chantier_s, PaieRepository $paieRepository, ChantierRepository $chantierRepository, AchatRepository $achatRepository, SessionInterface $session, CompteRenduRepository $compteRenduRepository, RemarqueRepository $remarqueRepository, UtilisateurRepository $utilisateurRepository, StatusRepository $statusRepository, VenteRepository $venteRepository, HoraireRepository $horaireRepository, GalerieRepository $galerieRepository){
        $this->chantier_s = $chantier_s;
        $this->global_s = $global_s;
        $this->paieRepository = $paieRepository;
        $this->galerieRepository = $galerieRepository;
        $this->chantierRepository = $chantierRepository;
        $this->venteRepository = $venteRepository;
        $this->achatRepository = $achatRepository;
        $this->compteRenduRepository = $compteRenduRepository;
        $this->remarqueRepository = $remarqueRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->statusRepository = $statusRepository;
        $this->horaireRepository = $horaireRepository;
        $this->session = $session;
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $status = $request->query->get('status', 1);

        $form = $request->request->get('form', null);
        if ($form) {
            if($form['mois'] == 0){
                $this->get('session')->getFlashBag()->add('error', 'Entrer le mois');
                return $this->redirectToRoute('chantier_list');
            }
            $mois = $form['mois']; $annee = $form['annee'];

            $mois2 = ($form['mois2']) ? $form['mois2'] : $mois;
                $nbrDay = cal_days_in_month(CAL_GREGORIAN, $mois2, $annee);
            $start_s = "01/$mois/$annee";
            $end_s = "$nbrDay/$mois2/$annee";
            $dateForm = $start_s."-".$end_s;

            $date = explode('-', $dateForm);
            $start = \DateTime::createFromFormat('d/m/Y', trim($date[0]));
            $start->setTime(7, 0, 0);
            $end = \DateTime::createFromFormat('d/m/Y', trim($date[1]));
            $end->setTime(20, 0, 0);
            $date = $dateForm;

        }else{
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $end = $start = $mois = $mois2 = "";
            $annee = $day[0];
        }
        
        $defaultGallery = $this->galerieRepository->findDefaultGallery();
        $defaultGalleryArr = [];
        foreach ($defaultGallery as $value) {
            $defaultGalleryArr[$value['default_galerie_id']] = $value;
        }

        $page = $em->getRepository(Page::class)->findOneBy(['cle'=>"CHANTIER"]);
        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }
        
        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }


        $datasResult = $this->buildDataChantier($start, $end, $form, $user, false, $status);
        $datasResult['default_galerie'] = $defaultGalleryArr;
        $datasResult['columns'] = $columns;
        $datasResult['columnsVisibileId'] = $columnsVisibileIdArr;
        $datasResult['tabColumns'] = $this->global_s->getTypeFields();


        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'mois2' => (int)$mois2,
            'annee' => $annee
        ))
        ->add('mois', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('mois2', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $this->global_s->getAnnee(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $datasResult['form'] = $form->createView();
        return $this->render('chantier/index.html.twig', $datasResult);
    }

    /**
     * @Route("/find", name="find", methods={"GET"})
     */
    public function findChantier(Request $request)
    {
        $dossier = $request->query->get('dossier');
        $filename = $request->query->get('filename');
        $chantierName = $request->query->get('chantier');

        $found = 0;
        $chantierArr = [];
        if($dossier != "" && $filename != ""){
            

            $firstEltDocument = $this->getDoctrine()->getRepository(OcrField::class)->getFirstEltDocument($dossier, $this->session->get('entreprise_session_id'), $filename);

            $entityfound = [];

            if(isset($chantierName) && $chantierName != ""){
                
                $entityfound = $this->getDoctrine()->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $chantierName, $firstEltDocument['id'], "", 150);

                /* autre tentative avec recherche sur les deux premiers mots */
                if(count($entityfound)==0){
                    $chantierName = str_replace("-", '', $chantierName);
                    $chantierName = str_replace("  ", ' ', $chantierName);
                    $chantierNameArr = explode(' ', $chantierName);
                    if(count($chantierNameArr) >= 2){
                        $val = $chantierNameArr[0];
                        if(strlen($val) >= 3){
                            $entityfound = $this->getDoctrine()->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $val, $firstEltDocument['id'], "", 150);
                        }
                        
                    }
                }

                $chantierSelect = $this->chantierRepository->findOneBy(['nameentreprise'=>$request->query->get('chantier')]);
                if(count($entityfound) > 0){
                    if(array_search('information', array_column($entityfound, 'name')) === false && array_search('informations', array_column($entityfound, 'name')) === false){
                        $found = 1;
                        $chantierArr[] = ['id'=>$chantierSelect->getChantierId(), 'name'=>$chantierSelect->getNameentreprise()];
                    }
                }
            }
            else{
                $chantiers = $this->chantierRepository->getByActif($this->session->get('entreprise_session_id'));

                $entityCompletfound = false;
                foreach ($chantiers as $value) {
                    if(strtoupper($value['nameentreprise']) == 'TEST ADMIN' || strtoupper($value['nameentreprise']) == 'FORMATION')
                        continue;

                    $entityfound = $this->getDoctrine()->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $value['nameentreprise'], $firstEltDocument['id'], "", 150);
                    
                    if(count($entityfound) > 0)
                        $entityCompletfound = true;

                    /* autre tentative avec recherche sur les deux premiers mots */
                    if(count($entityfound)==0){
                        $chantierName = str_replace("-", '', $value['nameentreprise']);
                        $chantierName = str_replace("  ", ' ', $chantierName);
                        $chantierNameArr = explode(' ', $chantierName);
                        if(count($chantierNameArr) >= 2){
                            $val = $chantierNameArr[0];
                            if(strlen($val) >= 3){
                                $entityfound = $this->getDoctrine()->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $val, $firstEltDocument['id'], "", 150);
                            }
                            
                        }
                    }

                    if(count($entityfound) > 0){
                        if(array_search('information', array_column($entityfound, 'name')) === false && array_search('informations', array_column($entityfound, 'name')) === false){
                            if($entityCompletfound){
                                $chantierArr = [['id'=>$value['chantier_id'], 'name'=>$value['nameentreprise']]];
                                break;
                            }
                            $chantierArr[] = ['id'=>$value['chantier_id'], 'name'=>$value['nameentreprise']];
                        }
                    }
                }

                if(count($chantierArr) > 0)
                    $found = 1;
            }
            
        }

        $response = new Response(json_encode([
            'count'=>$found,
            'chantiers'=>$chantierArr
        ]));
        return $response;
    }

    /**
     * @Route("/assurance", name="assurance", methods={"GET", "POST"})
     */
    public function assurance(Request $request, Session $session)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $status = $request->query->get('status', 1);

        $form = $request->request->get('form', null);
        if ($form) {
            if($form['mois'] == 0){
                $this->get('session')->getFlashBag()->add('error', 'Entrer le mois');
                return $this->redirectToRoute('chantier_list');
            }
            $mois = $form['mois']; $annee = $form['annee'];

            $mois2 = ($form['mois2']) ? $form['mois2'] : $mois;
                $nbrDay = cal_days_in_month(CAL_GREGORIAN, $mois2, $annee);
            $start_s = "01/$mois/$annee";
            $end_s = "$nbrDay/$mois2/$annee";
            $dateForm = $start_s."-".$end_s;

            $date = explode('-', $dateForm);
            $start = \DateTime::createFromFormat('d/m/Y', trim($date[0]));
            $start->setTime(7, 0, 0);
            $end = \DateTime::createFromFormat('d/m/Y', trim($date[1]));
            $end->setTime(20, 0, 0);
            $date = $dateForm;

        }else{
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $end = $start = $mois = $mois2 = "";
            $annee = $day[0];
        }
        
        $defaultGallery = $this->galerieRepository->findDefaultGallery();
        $defaultGalleryArr = [];
        foreach ($defaultGallery as $value) {
            $defaultGalleryArr[$value['default_galerie_id']] = $value;
        }
        $datasResult = $this->buildDataChantier($start, $end, $form, $user, false, $status);
        $datasResult['default_galerie'] = $defaultGalleryArr;

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'mois2' => (int)$mois2,
            'annee' => $annee
        ))
        ->add('mois', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('mois2', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $this->global_s->getAnnee(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $attestationsArr = []; $contratArr = []; $quittanceArr = [];
        $attestations = $this->chantierRepository->getAttestations();
        $contrats = $this->chantierRepository->getContrats();

        foreach ($attestations as $val) {
            $attestationsArr[$val['chantier_id']] = $val["file"];
        }
        foreach ($contrats as $val) {
            $contratArr[$val['chantier_id']] = $val["file"];
        }

        $datasResult['form'] = $form->createView();
        $datasResult['attestations'] = $attestationsArr;
        $datasResult['contrats'] = $contratArr;

        return $this->render('assurance/index.html.twig', $datasResult);
    }

    public function buildDataChantier($start, $end, $form, $user, $isFilter, $status =null, $valFilter = ""){
        if(!$isFilter){
            $chantiers =  $this->getDoctrine()
                ->getRepository(Horaire::class)
                ->findChantierBydateHoraire($start, $end, $form, $user, $status);
        }
        else{
            $chantiers =  $this->getDoctrine()
                ->getRepository(Horaire::class)
                ->filterChantierBydateHoraire($start, $end, $user, $valFilter);
        }

        $coutMatArr = []; $totalHeure = 0;
        $devisClient = []; $factureClient = []; $diffDevisFactureClient = [];
        $em = $this->getDoctrine()->getManager();
        foreach ($chantiers as $value) {
            $montant_facture = $em->getRepository(Vente::class)->countMontantByVenteDate(null, null, 'facture', $value['chantier_id']);
            $montant_devis = $em->getRepository(Vente::class)->countMontantByVenteDate(null, null, 'devis_client', $value['chantier_id']);
            $diffDevisFactureClient[$value['chantier_id']] = (float)($montant_devis['sum_ht']-$montant_facture['sum_ht']);

            $coutMatArr[$value['chantier_id']] = $this->achatRepository->countCoutGlobalByChantier($value['chantier_id'], 'bon_livraison');
            $totalHeure += $value['time'];
            $devisClient[$value['chantier_id']] = $this->venteRepository->coutDevisClientByChantier($value['chantier_id']);
            $factureClient[$value['chantier_id']] = $this->venteRepository->coutFactureClientByChantier($value['chantier_id']);
        }
        return [
            'chantiers' => $chantiers,
            'coutMatArr' => $coutMatArr,
            'diffDevisFactureClient' => $diffDevisFactureClient,
            'devisClient' => $devisClient,
            'factureClient' => $factureClient,
            'verification' => $status, 
            'totalHeure' => $totalHeure, 
        ];
    }

    /**
     * @Route("/datas-filter", name="datas_filter")
    */
    public function filterChantier(Request $request){

        $em = $this->getDoctrine()->getManager();
        
        $valFilter = $request->query->get('val');
        $datasResult = $this->buildDataChantier(null, null, null, $this->getUser(), true, null, $valFilter);
        $datas = ['status'=>200, "message"=>""];


        $defaultGallery = $this->galerieRepository->findDefaultGallery();
        $defaultGalleryArr = [];
        foreach ($defaultGallery as $value) {
            $defaultGalleryArr[$value['default_galerie_id']] = $value;
        }
        $datasResult['default_galerie'] = $defaultGalleryArr;



        $page = $em->getRepository(Page::class)->findOneBy(['cle'=>"CHANTIER"]);
        $columns = []; $columnsVisibile = [];
        if(!is_null($page)){
            $columns = $page->getFields();
            $columnsVisibile = $this->getDoctrine()->getRepository(FieldsEntreprise::class)->findBy(['page'=>$page, 'entreprise'=>$this->session->get('entreprise_session_id')]);
        }

        $columnsVisibileIdArr = [];
        foreach ($columnsVisibile as $value) {
            $columnsVisibileIdArr[$value->getColonne()->getId()] = $value->getColonne()->getCle();
        }

        $datasResult['columnsVisibileId'] = $columnsVisibileIdArr;


        if($request->query->get('page') && $request->query->get('page') == "assurance")
            $datas['content'] = $this->renderView('assurance/table_content.html.twig', $datasResult);
        else
            $datas['content'] = $this->renderView('chantier/table_content.html.twig', $datasResult);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/list-bl-associe-devis/{devisId}", name="list_bl_associe_devis")
    */
    public function listBlDevis(Request $request, $devisId){

        $bon_livraisons = $this->achatRepository->findBy(['type'=>"bon_livraison", 'devis'=>$devisId]);
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

        return $this->render('chantier/list_bon_livraison.html.twig', [
            'pager' => $pager,
            'nbr_bl'=>count($bon_livraisons),
            'montants'=>$this->achatRepository->countMontantByDevis('bon_livraison', $devisId)
        ]);
    }

        /**
     * @Route("/infos-tx-devis/{devisId}", name="info_tx_devis")
    */
    public function infoTHDevis(Request $request, $devisId){

        $horairesArr = $this->infoTHDevisFunc($devisId);
        return $this->render('chantier/infos_devis_th.html.twig', [
            'horairesInfos' => $horairesArr['horairesArr'],
            'sumTxMoy'=>$horairesArr['sumTxMoy'],
            'sumNbHeure'=>$horairesArr['sumNbHeure'],
            'totalTH'=>$horairesArr['totalTH'],
        ]);
    }

    public function infoTHDevisFunc($devisId){

        $devis = $this->venteRepository->find($devisId);
        $horaires = $this->horaireRepository->getHoraireDetailByDevis($devisId);
        $horairesArr = [];
        $sumTxMoy = 0; $sumNbHeure = 0; $totalTH = 0;
        $nbrEltNonNul = 0;
        foreach ($horaires as $value) {
            $horaireCur = [];
            $horaireCur['heure'] = $value['heure'];
            $paie = $this->paieRepository->getByUserAndDate($value['userid'], Carbon::parse(new \DateTime($devis->getFacturedAt()->format('Y-m-d')))->locale('fr')->isoFormat('MMMM YYYY'));
            $horaireCur['tx_moyen'] = $paie ? $paie['tx_moyen'] : 0;
            $horaireCur['tx_horaire'] = $paie ? $paie['tx_horaire'] : 0;
            $horaireCur['document_paie'] = $paie ? $paie['document_file'] : '';
            $horaireCur['document_devis'] = $devis->getDocumentFile();
            $user = $this->utilisateurRepository->find($value['userid']);
            $horaireCur['utilisateur'] = $user->getLastname().' '.$user->getFirstname();
            $horairesArr[] = $horaireCur;
            $sumTxMoy += $horaireCur['tx_moyen'];
            if($horaireCur['tx_moyen'] > 0){
                $nbrEltNonNul++;
                $sumNbHeure += $horaireCur['heure'];
                $totalTH += ($horaireCur['tx_moyen']*$horaireCur['heure']);
            }
        }

        return [
            'horairesArr'=>$horairesArr, 
            'sumNbHeure'=>$sumNbHeure,
            'sumTxMoy'=>($nbrEltNonNul != 0) ? ($sumTxMoy/$nbrEltNonNul) : 0,
            'totalTH'=>$totalTH
        ];
    }

    /**
     * @Route("/compte-rendu-chantier", name="compte_rendu_chantier")
    */
    public function CompteRenduChantier(Request $request, Session $session)
    {
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $verif = $request->query->get('verif', 1);
        $chantiers = $em->createQueryBuilder()
            ->select('l')
            ->from('App\Entity\Chantier', 'l')
            ->andWhere('l.entreprise = :entreprise')
            ->andWhere('l.status = :status')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('status', $verif)
            ->orderBy('l.chantierId', 'desc');

        if($request->get('q')){
            $chantiers = $chantiers->andWhere('LOWER(l.nameentreprise) LIKE :nameentreprise')
                ->setParameter('nameentreprise', strtolower($request->get('q'))."%");
        }

        $adapter = new DoctrineORMAdapter($chantiers);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }

        return $this->render('chantier/compte_rendu_chantier.html.twig', [
            'pager' => $pager,
            'q' => $request->get('q'),
        ]);
    }

    /**
     * @Route("/{id}/archive-compte-rendu", name="compte_rendu_archive")
     */
    public function archiveCompterRendu(Request $request, CompteRendu $compteRendu): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        if($compteRendu->getArchived())
            $compteRendu->setArchived(false);
        elseif(!$compteRendu->getArchived())
            $compteRendu->setArchived(true);

        $entityManager->flush();

        $this->get('session')->getFlashBag()->clear();
        $this->addFlash('success', "Operation effectuée avec succès");

        return $this->redirectToRoute('chantier_compte_rendu', ['chantier_id'=>$compteRendu->getChantier()->getChantierId()]);
    }

    /**
     * @Route("/{id}/delete-compte-rendu-remarque", name="compte_rendu_delete_remarque")
     */
    public function deleteRemarque(Request $request, Remarque $remarque): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($remarque);
        $entityManager->flush();

        $this->get('session')->getFlashBag()->clear();
        $this->addFlash('success', "Suppression effectuée avec succès");

        return $this->redirectToRoute('chantier_remarque_compte_rendu', ['compte_rendu_id'=>$remarque->getCompteRendu()->getId()]);
    }

    /**
     * @Route("/chantier-attach-fournisseurs/{chantier_id}", name="attach_fournisseurs", methods={"POST"})
    */
    public function attachFournisseurs(Request $request, $chantier_id)
    {
        $em = $this->getDoctrine()->getManager();
        $chantier = $this->chantierRepository->find($chantier_id);
        $Tabfournisseurs = $request->request->get('fournisseurs');
        $Tabfournisseurs = $Tabfournisseurs ?? [];

        $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->getByTabFournisseurEntity($Tabfournisseurs);  

        $fournisseurToDetach = $chantier->fournisseursToRemove($Tabfournisseurs);         
        foreach ($fournisseurs as $value) {
            $chantier->addFournisseur($value);
        }
        
        // dettache fournisseur de compte rendu
        $fournisseurCompteCrRemove =  $this->getDoctrine()->getRepository(FournisseurCompteRendu::class)->getByTabFournisseurAndChantier($chantier->getChantierId(), $fournisseurToDetach);
        foreach ($fournisseurCompteCrRemove as $value) {
            $em->remove($value);
        }

        if(count($fournisseurToDetach)){
            // dettache fournisseur remarque
            $this->getDoctrine()->getRepository(Remarque::class)->dettachFournisseur($chantier_id, $fournisseurToDetach);
        }

        
        $em->flush();

        return $this->redirectToRoute('chantier_compte_rendu', ['chantier_id'=>$chantier_id]);
    }

    /**
     * @Route("/compte-rendu/{chantier_id}", name="compte_rendu")
    */
    public function CompteRendu(Request $request, Session $session, $chantier_id)
    {
        $page = $request->query->get('page', 1);

        $chantier = $this->chantierRepository->find($chantier_id);
        //$compteRendu = $this->compteRenduRepository->findBy(['chantier'=>$chantier_id], ['numero_visite'=>'ASC']);
        $compteRendu = $this->compteRenduRepository->getCompteRenduByChantier($chantier_id);
        //$compteRendu = $this->compteRenduRepository->getCompteRenduByChantier(['chantier'=>$chantier_id]);

        $compteRenduArr = [];
        foreach ($compteRendu as $value) {
            $countRemarqueCloture = 0; $item = [];
            $countRemarqueCloture = $this->compteRenduRepository->countRemarqueCompteRendu($value->getId());
            if($countRemarqueCloture == 0)
                $item['is_cloture'] = -1;
            elseif(count($value->getRemarques()) != $countRemarqueCloture)
                $item['is_cloture'] = 1;
            elseif(count($value->getRemarques()) == $countRemarqueCloture)
                $item['is_cloture'] = 0;
            
            $item['data'] = $value;
            $compteRenduArr[] = $item;
        }
        $adapter = new ArrayAdapter($compteRenduArr);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }  
        
        $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $fournisseursArr = [];
        foreach ($fournisseurs as $value) {
            $fournisseursArr[] = [
                'id'=>$value->getId(),
                'nom'=>$value->getNom(),
            ];
        }

        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("COMPTE_RENDU");

        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise();
        return $this->render('chantier/compte_rendu.html.twig', [
            'pager' => $pager,
            'compteRendu' => $compteRendu,
            'chantier_id'=>$chantier_id,
            'chantier'=>$chantier,
            'fournisseurs'=>$fournisseurs,
            'fournisseursArr'=>$fournisseursArr,
            'status'=>$status,
            'fournisseursChantier'=>$chantier->getFournisseurs(),
            'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs)
        ]);
    }



    /**
     * @Route("/compte-rendu-load-fournisseur-xhr", name="compte_rendu_load_fournisseur_xhr")
    */
    public function loadFournisseurXhr(Request $request)
    { 
        $em = $this->getDoctrine()->getManager();
        $compte_rendu = $this->getDoctrine()->getRepository(CompteRendu::class)->find($request->query->get('compte_rendu_id'));

        $fournisseurs = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Fournisseurs', 'c')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'));
            
        $query = $fournisseurs->getQuery();
        $fournisseurs = $query->execute();

        $fournisseursCompteR = $this->getDoctrine()->getRepository(FournisseurCompteRendu::class)->findBy(['compteRendu'=>$compte_rendu]);

        $fournisseursCompteRenduIdArr = [];
        foreach ($fournisseursCompteR as $value) {
            $fournisseursCompteRenduIdArr[] = $value->getFournisseur()->getId();
        }

        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("COMPTE_RENDU");

        $datas = ['status'=>200];

        $fournisseursChantier = $compte_rendu->getChantier()->getFournisseurs();
        $datas['content'] = $this->renderView('chantier/compte_rendu_fournisseur_popup.html.twig', [
            'compte_rendu' => $compte_rendu,
            'fournisseurs'=>$fournisseurs,
            'fournisseursCompteR'=>$fournisseursCompteR,
            'fournisseursCompteRenduIdArr'=>$fournisseursCompteRenduIdArr,
            'fournisseursChantier'=>$fournisseursChantier,
            'presences'=>$status,
        ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }


    /**
     * @Route("/galeries", name="galeries", methods={"GET"})
     */
    public function getGaleries(Request $request)
    {
        $id = $request->query->get('chantierId');
        
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($id);
        if(!$chantier){
            $this->get('session')->getFlashBag()->add('error', 'Désolé ! Ce chantier n\'existe pas');
            return $this->redirectToRoute('galerie_list');
        }

        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $chantiers = $em->createQueryBuilder()
            ->select('g')
            ->from('App\Entity\Galerie', 'g')
            ->andWhere('g.entreprise = :entreprise')
            ->andWhere('g.chantier = :chantier')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('chantier', $id)
            ->orderBy('g.created_at', 'desc');

        $adapter = new DoctrineORMAdapter($chantiers);
        $pager = new PagerFanta($adapter);

        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }
        /** @var Galerie $file */
        foreach ($pager->getCurrentPageResults() as $file) {
            if ($file->getExtension() == "mp4") {
                /* $path = '/galerie/'.$user->getEntreprise()->getId()."/".$file->getCreatedAt()->format('Y-m-d')."/".$file->getNom();
                 $ffmpeg = FFMpeg::create();
                 $video = $ffmpeg->open($path);
                 $frame = $video->frame(TimeCode::fromSeconds(42));
                 dump($frame);die();*/
                $file->setThumbnail('');
            } else {
                $file->setThumbnail('/galerie/' . $file->getEntreprise()->getId() . "/" . $file->getCreatedAt()->format('Y-m-d') . "/" . $file->getNom());
            }

            try {
                list($width, $height) = getimagesize($this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL).$file->getUrl());
                $file->width = $width;
                $file->height = $height;
            } catch (\Exception $e) {
                $file->width = 0;
                $file->height = 0;
            }

        }

        $datas = ['status'=>200];

        $datas['content'] = $this->renderView('chantier/galerie_chantier_popup.html.twig', [
            'pager' => $pager,
            'chantier' => $chantier,
            'compte_rendu_id'=>$request->query->get('compte_rendu_id')
        ]);

        $response = new Response(json_encode($datas));
        return $response;
        
    }

    /**
     * @Route("/compte-rendu-save-image/{chantier_id}", name="compte_rendu_save_image")
    */
    public function saveImageCompteRendu(Request $request, $chantier_id){

        $em = $this->getDoctrine()->getManager();

        $compteRenduId = $request->request->get('compte_rendu_id');
        $galerieId = $request->request->get('image');

        $compteRendu = $this->compteRenduRepository->find($compteRenduId);
        $galerie = $this->getDoctrine()->getRepository(Galerie::class)->find($galerieId);

        $compteRendu->setDefaultGalerie($galerie);

        $em->flush();
        
        return $this->redirectToRoute('chantier_compte_rendu', ['chantier_id'=>$chantier_id]);
    }

    /**
     * @Route("/compterendu-fournisseur-status-edit/{chantier_id}", name="compterendu_fournisseur_status_edit")
    */
    public function saveFournisseurCompteRenduStatus(Request $request){

        $em = $this->getDoctrine()->getManager();

        $compteRenduId = $request->request->get('compte_rendu_id');
        $fournisseurId = $request->request->get('fournisseur_id');
        $statusId = $request->request->get('presence');
        $fournComptReExist = $this->getDoctrine()->getRepository(FournisseurCompteRendu::class)->findOneBy(['compteRendu'=>$compteRenduId, 'fournisseur'=>$fournisseurId]);

        $presence = $this->getDoctrine()->getRepository(Status::class)->find($statusId);
        if(is_null($fournComptReExist)){
            $compteRendu = $this->getDoctrine()->getRepository(CompteRendu::class)->find($compteRenduId);
            $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurId);

            $fournComptReExist = new FournisseurCompteRendu();
            $fournComptReExist->setStatus($presence);
            $fournComptReExist->setCompteRendu($compteRendu);
            $fournComptReExist->setFournisseur($fournisseur);
            $em->persist($fournComptReExist);
        }
        else{
            $fournComptReExist->setStatus($presence);
        }

        $em->flush();
        
        return $this->redirectToRoute('chantier_remarque_compte_rendu', ['compte_rendu_id'=>$compteRenduId]);
    }


    /**
     * @Route("/dettach-fournisseur/{chantier_id}/{fournisseur_id}", name="dettach_fournisseur")
    */
    public function dettachFournisseur(Request $request, Session $session, $chantier_id, $fournisseur_id){ 
        $em = $this->getDoctrine()->getManager();
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantier_id);
        $fournisseur = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseur_id);

        if(!is_null($chantier)){
            $chantier->removeFournisseur($fournisseur);
            
            $fournisseurCompteCrRemove = $this->getDoctrine()->getRepository(FournisseurCompteRendu::class)->getByTabFournisseurAndChantier($chantier->getChantierId(), [$fournisseur_id]);
            foreach ($fournisseurCompteCrRemove as $value) {
                $em->remove($value);                
            }
            try {
                 $this->getDoctrine()->getRepository(Remarque::class)->dettachFournisseur($chantier_id, [$fournisseur_id]);
            } catch (\Exception $e) {
                $this->addFlash('error', "Un probleme a été rencontré lors de la suppression");
            }
        }

        $em->flush();

        return $this->redirectToRoute('chantier_compte_rendu', ['chantier_id'=>$chantier_id]);
    }
    
    /**
     * @Route("/compte-rendu-remarque/{compte_rendu_id}", name="remarque_compte_rendu")
    */
    public function CompteRenduRemarque(Request $request, Session $session, $compte_rendu_id)
    { 
        $em = $this->getDoctrine()->getManager();

        $page = $request->query->get('page', 1);
        $compteRendu = $this->compteRenduRepository->find($compte_rendu_id);
        //$remarques = $this->remarqueRepository->findBy(['compte_rendu'=>$compte_rendu_id]);
        $remarques = $this->remarqueRepository->getRemarqueByCompteRendu($compte_rendu_id);
        $adapter = new ArrayAdapter($remarques);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }  

        $fournisseurs = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Fournisseurs', 'c')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'));
            
        $query = $fournisseurs->getQuery();
        $fournisseurs = $query->execute();

        $fournisseursCompteR = $this->getDoctrine()->getRepository(FournisseurCompteRendu::class)->findBy(['compteRendu'=>$compteRendu]);

        $fournisseursCompteRenduIdArr = [];
        foreach ($fournisseursCompteR as $value) {
            $fournisseursCompteRenduIdArr[] = $value->getFournisseur()->getId();
        }
        $fournisseursChantier = $compteRendu->getChantier()->getFournisseurs();

        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("PRESENCE");


        return $this->render('chantier/remarque.html.twig', [
            'pager' => $pager,
            'remarques' => $remarques,
            'compteRendu'=>$compteRendu,
            'fournisseurs'=>$fournisseurs,
            'fournisseursCompteR'=>$fournisseursCompteR,
            'fournisseursCompteRenduIdArr'=>$fournisseursCompteRenduIdArr,
            'fournisseursChantier'=>$fournisseursChantier,
            'presences'=>$status,
            'status'=>array_flip($this->global_s->getStatusRemarque())
        ]);
    }

    /**
     * @Route("/compte-rendu-remarque-print/{compte_rendu_id}", name="remarque_compte_rendu_print")
    */
    public function CompteRenduRemarquePrint(Request $request, Session $session, $compte_rendu_id)
    { 
        $page = $request->query->get('page', 1);
        $compteRendu = $this->compteRenduRepository->find($compte_rendu_id);
        $chantier = $this->chantierRepository->find($compteRendu->getChantier()->getChantierId());
        //$remarques = $this->remarqueRepository->findBy(['compte_rendu'=>$compte_rendu_id]);
        $remarques = $this->remarqueRepository->getRemarqueByCompteRendu($compte_rendu_id);
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $adapter = new ArrayAdapter($remarques);
        $pager = new PagerFanta($adapter);

        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }  

        $presences = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("PRESENCE");

        $fournisseurCompteRendu = $this->getDoctrine()->getRepository(FournisseurCompteRendu::class)->findBy(['compteRendu'=>$compteRendu]);

        return $this->render('chantier/remarque_print.html.twig', [
            'pager' => $pager,
            'remarques' => $remarques,
            'presences' => $presences,
            'chantier' => $chantier,
            'compteRendu'=>$compteRendu,
            'fournisseurCompteRendu'=>$fournisseurCompteRendu,
            'entreprise'=>[
                'nom'=>$entreprise->getName(),
                'adresse'=>$entreprise->getAddress(),
                'ville'=>$entreprise->getCity(),
                'postalcode'=>$entreprise->getCp(),
                'phone'=>$entreprise->getPhone(),
                'email'=>$entreprise->getEmail()
            ],
            'status'=>array_flip($this->global_s->getStatusRemarque())
        ]);
    }

    /**
     * @Route("/compte-new/{chantier_id}", name="compte_rendu_new", methods={"GET","POST"})
    */
    public function CompteRenduNew(Request $request, Session $session, $chantier_id)
    {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('count(c.id)');
        $qb->from('App\Entity\CompteRendu', 'c');
        $qb->where('c.chantier = :chantier_id');
        $qb->setParameter('chantier_id', $chantier_id);
        $countCompteRendu = $qb->getQuery()->getSingleScalarResult() ?? 0;

        $chantier = $this->chantierRepository->find($chantier_id);
        $compte_rendu = new CompteRendu();


        $compte_rendu->setNom($chantier->getNameentreprise(). " ".($countCompteRendu+1));
        $compte_rendu->setNumeroVisite($countCompteRendu+1);
        $compte_rendu->setDateVisite(new \DateTime());

        $form = $this->createForm(CompteRenduType::class, $compte_rendu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $dateVisite = explode("/", $request->request->get('date_visite'));
            $dateVisite = $dateVisite[2]."-".$dateVisite[1]."-".$dateVisite[0];
            $time = $request->request->get('time-compte-rendu');
            $dateVisite = ($time != "") ? $dateVisite." ".$time : $dateVisite;

            $ouvriers = $request->request->get('ouvriers');
            $ouvriers = $ouvriers ?? [];
            $compte_rendu->setDateVisite(new \DateTime($dateVisite));
            $compte_rendu->setOuvrier(implode("-", $ouvriers));

            $compte_rendu->setChantier($chantier);

            $em->persist($compte_rendu);
            $em->flush();

            $this->saveFirstRemarque($request, $compte_rendu);
            return $this->redirectToRoute('chantier_compte_rendu', ['chantier_id'=>$chantier->getChantierId()]);
        }

        $utilisateurs = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.etat = :verif')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('verif', 1)
            ->setParameter('etat', false);
            
        $query = $utilisateurs->getQuery();
        $utilisateurs = $query->execute();

        $fournisseurs = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Fournisseurs', 'c')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'));
            
        $query = $fournisseurs->getQuery();
        $fournisseurs = $query->execute();

        $fournisseursArr = [];
        foreach ($fournisseurs as $value) {
            $fournisseursArr[] = [
                'id'=>$value->getId(),
                'nom'=>$value->getNom(),
            ];
        }

        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("COMPTE_RENDU");

        return $this->render('chantier/compte_rendu_new.html.twig', [
            'compte_rendu' => $compte_rendu,
            'form' => $form->createView(),
            'utilisateurs'=>$utilisateurs,
            'fournisseursAll'=>$fournisseurs,
            'chantier'=>$chantier,
            'fournisseursCompteR'=>[],
            'fournisseursArr'=>$fournisseursArr,
            'status'=>$status
        ]);
    }

    public function saveFirstRemarque($request, $compteRendu){
        $remarque = new Remarque();
        $remarque->setMessage($request->request->get('message'));
        $remarque->setStatus($this->statusRepository->find($request->request->get('status')));
        $remarque->setCompteRendu($compteRendu);
        $remarque->setDatePost(new \DateTime());
        $remarque->setNum(1);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($remarque);
        $entityManager->flush();
        return 1;
    }

    /**
     * @Route("/compte-edit/{compte_rendu_id}", name="compte_rendu_edit", methods={"GET","POST"})
    */
    public function CompteRenduEdit(Request $request, Session $session, $compte_rendu_id)
    {
        $em = $this->getDoctrine()->getManager();
        $compte_rendu = $this->compteRenduRepository->find($compte_rendu_id);
        $form = $this->createForm(CompteRenduType::class, $compte_rendu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateVisite = explode("/", $request->request->get('date_visite'));
            $dateVisite = $dateVisite[2]."-".$dateVisite[1]."-".$dateVisite[0];
            $time = $request->request->get('time-compte-rendu');
            $ouvriers = $request->request->get('ouvriers');
            $compte_rendu->setDateVisite(new \DateTime($dateVisite." ".$time));
            $ouvriers = $ouvriers ?? [];
            $compte_rendu->setOuvrier(implode("-", $ouvriers));

            $em->flush();

            return $this->redirectToRoute('chantier_compte_rendu', ['chantier_id'=>$compte_rendu->getchantier()->getChantierId()]);
        }

        $utilisateurs = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.entreprise = :entreprise')
            ->andWhere('c.etat = :verif')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('etat', false)
            ->setParameter('verif', 1);
            
        $query = $utilisateurs->getQuery();
        $utilisateurs = $query->execute();

        $fournisseurs = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Fournisseurs', 'c')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'));
            
        $query = $fournisseurs->getQuery();
        $fournisseurs = $query->execute();

        $fournisseursArr = [];
        foreach ($fournisseurs as $value) {
            $fournisseursArr[] = [
                'id'=>$value->getId(),
                'nom'=>$value->getNom(),
            ];
        }

        $fournisseursCompteR = $this->getDoctrine()->getRepository(FournisseurCompteRendu::class)->findBy(['compteRendu'=>$compte_rendu]);

        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("COMPTE_RENDU");

        return $this->render('chantier/compte_rendu_edit.html.twig', [
            'compte_rendu' => $compte_rendu,
            'form' => $form->createView(),
            'utilisateurs'=>$utilisateurs,
            'fournisseurs'=>$fournisseurs,
            'fournisseursArr'=>$fournisseursArr,
            'fournisseursCompteR'=>$fournisseursCompteR,
            'status'=>$status,
            'ouvriers'=> explode("-", $compte_rendu->getOuvrier()),
        ]);
    }


    /**
     * @Route("/save-compte-rendu-fournisseur/{compte_rendu_id}", name="save_compte_rendu_fournisseur", methods={"POST"})
    */
    public function saveCompteRenduFournisseur(Request $request, $compte_rendu_id)
    {
        $em = $this->getDoctrine()->getManager();

        $compte_rendu =  $this->getDoctrine()->getRepository(CompteRendu::class)->find($compte_rendu_id);
        /* SAUVEGARDE FOURNISSEUR */

        $fournisseurs = $compte_rendu->getFournisseurs();
        $fournisseurEdit = $request->request->get('fournisseursEdit');
        
        if(!is_null($fournisseurEdit)){
            foreach ($fournisseurEdit as $key => $value) {
                $fournisseurCr =  $this->getDoctrine()->getRepository(FournisseurCompteRendu::class)->find($key);
                if(!empty($value['fournisseur']) && !empty($value['status'])){
                    $fournisseurExist = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($value['fournisseur']);
                    if(!is_null($fournisseurExist)){
                        $fournisseurCr->setFournisseur($fournisseurExist);
                    }

                    $statusExist = $this->getDoctrine()->getRepository(Status::class)->find($value['status']);
                    if(!is_null($statusExist)){
                        $fournisseurCr->setStatus($statusExist);
                    }
                }
                else{
                    $compte_rendu->removeFournisseur($fournisseurCr);
                }
            }
        }
        else
            $fournisseurEdit = [];

        foreach ($fournisseurs as $value) {
            if(!array_key_exists($value->getId(), $fournisseurEdit)){
                $em->remove($value);
            }
        }

        $fournisseurs = $request->request->get('fournisseurs');
        if(!is_null($fournisseurs)){
            for ($i=0; $i < count($fournisseurs['fournisseur']); $i++) { 
                if(!empty($fournisseurs['fournisseur'][$i]) && !empty($fournisseurs['status'][$i])){
                    $fournisseurCr = new FournisseurCompteRendu();

                    $fournisseurExist = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurs['fournisseur'][$i]);
                    if(!is_null($fournisseurExist)){
                        $fournisseurCr->setFournisseur($fournisseurExist);
                    }

                    $statusExist = $this->getDoctrine()->getRepository(Status::class)->find($fournisseurs['status'][$i]);
                    if(!is_null($statusExist)){
                        $fournisseurCr->setStatus($statusExist);
                    }

                    $em->persist($fournisseurCr);
                    $compte_rendu->addFournisseur($fournisseurCr);    
                }
            }
        }
        /* FIN SAUVEGARDE FOURNISSEUR */    


        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Enregistrement effectué avec success');
        return $this->redirectToRoute('chantier_remarque_compte_rendu', ['compte_rendu_id'=>$compte_rendu->getId()]);
    }
    /**
     * @Route("/remarque-add/{compte_rendu_id}", name="remarque_add", methods={"GET","POST"})
     */
    public function remarqueNew(Request $request, $compte_rendu_id)
    {
        $remarque = new Remarque();
        $compteRendu = $this->compteRenduRepository->find($compte_rendu_id);
        if ($request->isMethod('post')) {
            $em = $this->getDoctrine()->getManager();
            $qb = $em->createQueryBuilder();
            $qb->select('count(r.id)');
            $qb->from('App\Entity\Remarque', 'r');
            $qb->where('r.compte_rendu = :compte_rendu');
            $qb->setParameter('compte_rendu', $compte_rendu_id);
            $countRemarque = $qb->getQuery()->getSingleScalarResult() ?? 0;

            $remarque->setMessage($request->request->get('message'));
            $remarque->setStatus($this->statusRepository->find($request->request->get('status')));
            $remarque->setCompteRendu($compteRendu);
            $remarque->setDatePost(new \DateTime());
            $remarque->setNum($countRemarque+1);
            $entityManager = $this->getDoctrine()->getManager();

            $Tabfournisseurs = $request->request->get('fournisseurs');
            $Tabfournisseurs = $Tabfournisseurs ?? [];

            $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->getByTabFournisseurEntity($Tabfournisseurs);  
            
            foreach ($fournisseurs as $value) {
                $remarque->addFournisseur($value);
            }


            $entityManager->persist($remarque);
            $entityManager->flush();

            return $this->redirectToRoute('chantier_remarque_compte_rendu', ['compte_rendu_id'=>$compte_rendu_id]);
        }

        $fournisseurs = $compteRendu->getChantier()->getFournisseurs();
        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("COMPTE_RENDU");
        return $this->render('chantier/remarque_new.html.twig', [
            'remarque' => $remarque,
            'compteRendu'=>$compteRendu,
            'status'=> $status,
            'fournisseurs'=>$fournisseurs
        ]);
    }

    /**
     * @Route("/remarque-edit/{remarque_id}", name="remarque_edit", methods={"GET","POST"})
     */
    public function remarqueEdit(Request $request, $remarque_id)
    {
        $remarque = $this->remarqueRepository->getRemarqueById($remarque_id);
        if ($request->isMethod('post')) {
            $remarque->setMessage($request->request->get('message'));
            $remarque->setStatus($this->statusRepository->find($request->request->get('status')));
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($remarque);

            $Tabfournisseurs = $request->request->get('fournisseurs');
            $Tabfournisseurs = $Tabfournisseurs ?? [];

            $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->getByTabFournisseurEntity($Tabfournisseurs);  

            
            $fournisseurToDetach = $remarque->fournisseursToRemove($Tabfournisseurs);         
            foreach ($fournisseurs as $value) {
                $remarque->addFournisseur($value);
            }

            $entityManager->flush();

            return $this->redirectToRoute('chantier_remarque_compte_rendu', ['compte_rendu_id'=>$remarque->getCompteRendu()->getId()]);
        }
        if(is_null($remarque)){
            return $this->redirectToRoute('chantier_compte_rendu_chantier');
        }

        $fournisseurs = $remarque->getCompteRendu()->getChantier()->getFournisseurs();

        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("COMPTE_RENDU");
        return $this->render('chantier/remarque_edit.html.twig', [
            'remarque' => $remarque,
            'status'=>$status,
            'fournisseurs'=>$fournisseurs,
            'fournisseursRemarque'=>$remarque->getFournisseurs()
        ]);
    }

    /**
     * @Route("/add", name="add", methods={"GET","POST"})
     */
    public function new(Request $request)
    {
        $user = $this->getUser();
        $chantier = new Chantier();
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        if(!is_null($entreprise))
            $chantier->setEntreprise($entreprise);
        
        $form = $this->createForm(ChantierType::class, $chantier);
        $form->handleRequest($request);
        $city = $request->request->get('chantier_city');
        if ($form->isSubmitted() && $form->isValid()) {
            $chantier->setCity($city);
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($chantier);
            $entityManager->flush();

            return $this->redirectToRoute('chantier_list');
        }

        return $this->render('chantier/add.html.twig', [
            'chantier' => $chantier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reste-a-facturer", name="reste_facturer", methods={"GET","POST"})
     */
    public function resteFacturer(session $session, Request $request){   
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $em = $this->getDoctrine()->getManager();
        $form = $request->request->get('form', null);
        if ($form) {
            if($form['mois'] == 0){
                $this->get('session')->getFlashBag()->add('error', 'Entrer le mois');
                return $this->redirectToRoute('chantier_reste_facturer');
            }
            $mois = $form['mois']; $annee = $form['annee'];

            $mois2 = ($form['mois2']) ? $form['mois2'] : $mois;
                $nbrDay = cal_days_in_month(CAL_GREGORIAN, $mois2, $annee);
            $start_s = "01/$mois/$annee";
            $end_s = "$nbrDay/$mois2/$annee";
            $dateForm = $start_s."-".$end_s;

            $date = explode('-', $dateForm);
            $start = \DateTime::createFromFormat('d/m/Y', trim($date[0]));
            $start->setTime(7, 0, 0);
            $end = \DateTime::createFromFormat('d/m/Y', trim($date[1]));
            $end->setTime(20, 0, 0);
            $date = $dateForm;
        } else {
            $mois = $mois2 = "";
            $annee = (new \DateTime())->format('Y');
            $date = '';
        }
        
        $ventes = $em->createQueryBuilder()
            ->select('v')
            ->from('App\Entity\Vente', 'v')
            ->andwhere('v.type = :type')
            ->setParameter('type', 'devis_client');
            if(isset($start) && isset($end)) {
                $ventes = $ventes->andWhere('v.facturedAt BETWEEN :start AND :end')
                    ->setParameter('start', $start->format('Y/m/d')." 00:00:00")
                    ->setParameter('end' , $end->format('Y/m/d')." 23:59:59");
            }
            $ventes = $ventes->getQuery()
            ->getResult();
        $ventesArr = []; $ventesArr2 = []; $ventesArr3 = [];
        $totalResteFact = 0; $montant_devis = ['sum_ht' => 0];
        foreach ($ventes as $value) {
            $data3 = [];

            if(is_null($value->getChantier()))
                break;

            /* data,groupé par chantier avec sum groupée */
            if(array_key_exists($value->getChantier()->getChantierId(), $ventesArr2))
                $data = $ventesArr2[$value->getChantier()->getChantierId()];
            else
                $data = ['id'=>0, 'nbHeure'=>0, 'ht'=>0, 'sum_ht'=>0, 'nbHeure'=>0, 'tx_horaire'=>0, 'sumVente'=>['sum_ht'=>0, 'sum_ttc'=>0]];

            $data['id'] = $value->getId();
            $data['client'] = !is_null($value->getClient()) ? $value->getClient()->getNom() : "";
            $data['lot'] = !is_null($value->getLot()) ? $value->getLot()->getLot() : "";
            $data['ht'] += $value->getPrixht();
            $data['document_file'] = $value->getDocumentFile();
            $data['document_id'] = $value->getDocumentId();
            $data['chantier_id'] = $value->getChantier()->getChantierId();
            $data['chantier'] = $value->getChantier()->getNameentreprise();
            $data['factured_at'] = $value->getFacturedAt();
            $nbHeure = $this->horaireRepository->countTimeByDevis($value->getId(), $value->getChantier()->getChantierId());
            $data['nbHeure'] += $nbHeure;
            $data['sum_ht'] += $this->achatRepository->getSumHTByDevis($value->getId(),$value->getChantier()->getChantierId(), 'bon_livraison');

            $data['list_bl'] = $this->achatRepository->findBy(['devis'=>$value->getId(), 'type'=>'bon_livraison'], null, 1);

            $horairesArr = $this->infoTHDevisFunc($value->getId());
            $data['tx_horaire'] += $horairesArr['totalTH']; 
            
            $sumMontantByDevis = $this->venteRepository->getSumMontantByDevis($value->getId());
            $data['sumVente']['sum_ht'] += $sumMontantByDevis['sum_ht'];
            $data['sumVente']['sum_ttc'] += $sumMontantByDevis['sum_ttc'];

            $montant_devis['sum_ht'] += $data['ht'] ;
            $ventesArr[] = $data;
            if( ($data['ht'] - $data['sumVente']['sum_ht']) != 0)
                $ventesArr2[$value->getChantier()->getChantierId()] = $data;

            /* data3 groupé par chantier mais avec liste de tout les devis */
            $data3 = $data;
            $data3['ht'] = $value->getPrixht();
            $data3['sumVente'] = $sumMontantByDevis;
            $data3['sum_ht'] = $this->achatRepository->getSumHTByDevis($value->getId(),$value->getChantier()->getChantierId(), 'bon_livraison');
            $data3['nbHeure'] = $nbHeure;
            $data3['tx_horaire'] = $horairesArr['totalTH'];
            $data3['totalResteFact'] = $value->getPrixht() - $sumMontantByDevis['sum_ht'];
            $totalResteFact += $data3['totalResteFact'];
            if( (int)$data3['totalResteFact'] != 0)
                $ventesArr3[$value->getChantier()->getChantierId()][] = $data3;
        }

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'mois2' => (int)$mois2,
            'annee' => $annee
        ))
        ->add('mois', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('mois2', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $this->global_s->getAnnee(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        return $this->render('chantier/reste_facturer.html.twig', [
            'ventes'=>$ventesArr3,
            'ventes2'=>$ventesArr2,
            'form' => $form->createView(),
            'montant_devis'=>$montant_devis,
            'totalResteFacture'=>$totalResteFact
        ]);
    }

    /**
     * @Route("/{chantierId}", name="show", methods={"GET","POST"})
     */
    public function show(session $session, Request $request, $chantierId){   
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $entityManager = $this->getDoctrine()->getManager();
        $form = $request->request->get('form', null);
        $tab = "devis-tab";
        if($request->query->get('tab'))
            $tab = $request->query->get('tab');
        if ($form) {
            if($form['mois'] == 0){
                $mois = "1";
                $mois2 = "12";
            }
            else{
                $mois = $form['mois']; 
                $mois2 = ($form['mois2']) ? $form['mois2'] : $mois;
            }
            $annee = $form['annee'];
            $nbrDay = cal_days_in_month(CAL_GREGORIAN, $mois2, $annee);
            $start_s = "01/$mois/$annee";
            $end_s = "$nbrDay/$mois2/$annee";
            $dateForm = $start_s."-".$end_s;

            $date = explode('-', $dateForm);
            $start = \DateTime::createFromFormat('d/m/Y', trim($date[0]));
            $start->setTime(7, 0, 0);
            $end = \DateTime::createFromFormat('d/m/Y', trim($date[1]));
            $end->setTime(20, 0, 0);
            $date = $dateForm;
            $tab = $request->request->get('tab');
        } else {
            $mois = $mois2 = "";
            //$annee = (new \DateTime())->format('Y');
            $annee = "";
            $date = '';
        }

        /** @var Chantier $chantier */
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);
        $em = $this->getDoctrine()->getManager();
        $qb = $horaires = $this->getDoctrine()
            ->getRepository(Horaire::class)
            ->getHoraireByChantier($chantierId);

        if(isset($start) && isset($end)) {
            $qb->andWhere('h.datestart BETWEEN :start AND :end')
                ->setParameter('start', $start->format('Y/m/d')." 00:00:00")
                ->setParameter('end' , $end->format('Y/m/d')." 23:59:59");
            $dateInterval = [clone $start, clone $end];
        }

        $horaires = $qb->orderBy('h.datestart', 'asc')->getQuery()->getResult();
        if(count($horaires) > 0 && empty($date)) {
            $dateInterval = [$horaires[0]['datestart'], $horaires[count($horaires)-1]['datestart']];
            $date = ($dateInterval[0])->format('d/m/Y')." - ".($dateInterval[1])->format('d/m/Y');
        }

        $total = 0;
        $total_price = $total_tx = $total_cout_global = 0;


        //$ventes = $this->venteRepository->getDevisWithHeureAndTx($chantierId);

        $ventes = $em->createQueryBuilder()
            ->select('v')
            ->from('App\Entity\Vente', 'v')
            ->andwhere('v.chantier = :chantier_id')
            ->setParameter('chantier_id', $chantierId)
            ->andwhere('v.type = :type')
            ->setParameter('type', 'devis_client');
            if(isset($start) && isset($end)) {
                $ventes = $ventes->andWhere('v.facturedAt BETWEEN :start AND :end')
                    ->setParameter('start', $start->format('Y/m/d')." 00:00:00")
                    ->setParameter('end' , $end->format('Y/m/d')." 23:59:59");
            }
            $ventes = $ventes->getQuery()
            ->getResult();
        $ventesArr = [];
        $totalResteFact = 0; $totalHeure = 0; $totalTXMoyen = 0; $totalMO = 0; $totalht = 0;$totalttc = 0;  
        foreach ($ventes as $value) {
            $data = [];
            $data['id'] = $value->getId();
            $data['ht'] = $value->getPrixht();
            $data['ttc'] = $value->getPrixttc();
            $data['client'] = !is_null($value->getClient()) ? $value->getClient()->getNom() : "";
            $data['lot'] = !is_null($value->getLot()) ? $value->getLot()->getLot() : "";
            $data['document_file'] = $value->getDocumentFile();
            $data['document_id'] = $value->getDocumentId();
            $data['factured_at'] = $value->getFacturedAt();
            $nbHeure = $this->horaireRepository->countTimeByDevis($value->getId(), $chantierId);
            $data['nbHeure'] = $nbHeure;
            $data['sum_ht'] = $this->achatRepository->getSumHTByDevis($value->getId(),$chantierId, 'bon_livraison');

            $data['list_bl'] = $this->achatRepository->findBy(['devis'=>$value->getId(), 'type'=>'bon_livraison'], null, 1);

            $horairesArr = $this->infoTHDevisFunc($value->getId());
            $data['tx_moyen'] = $horairesArr['sumTxMoy']; 
            $data['total_mo'] = $nbHeure*$data['tx_moyen']; 
            
            $data['sumVente'] = $this->venteRepository->getSumMontantByDevis($value->getId());
            $totalResteFact += $data['ht'] - $this->venteRepository->getSumMontantByDevis($value->getId())['sum_ht'];
            //if( ($data['ht'] - $this->venteRepository->getSumMontantByDevis($value->getId())['sum_ht']) != 0 )
                $ventesArr[] = $data;

            $totalHeure += $data['nbHeure'];
            $totalTXMoyen += $data['tx_moyen'];
            $totalMO += $data['total_mo'];
            $totalht += $data['ht'];
            $totalttc += $data['ttc'];
        }

        if(count($ventes)!=0){
            $totalTXMoyen = $totalTXMoyen/count($ventes);
        }

        $full_adress = !is_null($chantier) ? urlencode($chantier->getAddress(). " ". $chantier->getCp() . " ". $chantier->getCity()) : "";

        if(isset($start) && isset($end)) {
            $bon_livraisons = $entityManager->getRepository(Achat::class)->findByChantierBetweenDate([$start->format('Y/m/d'), $end->format('Y/m/d')], $chantierId, 'bon_livraison');
            $coutBl = $entityManager->getRepository(Achat::class)->countMontantByfacturedDateInterval('bon_livraison', $chantierId, $start->format('Y/m/d'), $end->format('Y/m/d'));

            $factures_client = $entityManager->getRepository(Vente::class)->findByChantierBetweenDate([$start->format('Y/m/d'), $end->format('Y/m/d')], $chantierId, 'facture');

            $devis = $entityManager->getRepository(Vente::class)->findByChantierBetweenDate([$start->format('Y/m/d'), $end->format('Y/m/d')], $chantierId, 'devis_client');

            $montant = $entityManager->getRepository(Vente::class)->countMontantByVenteDate(null, null, 'facture', $chantierId, null, [$start->format('Y/m/d'), $end->format('Y/m/d')] );
            $montant_devis = $entityManager->getRepository(Vente::class)->countMontantByVenteDate(null, null, 'devis_client', $chantierId, null, [$start->format('Y/m/d'), $end->format('Y/m/d')] );
        }
        else{
            $bon_livraisons = $entityManager->getRepository(Achat::class)->findBy(['chantier'=>$chantierId, 'type'=>'bon_livraison']);
            $coutBl = $entityManager->getRepository(Achat::class)->countMontantByfacturedDateInterval('bon_livraison', $chantierId);

            $factures_client = $entityManager->getRepository(Vente::class)->findBy(['chantier'=>$chantierId, 'type'=>'facture']);

            $devis = $entityManager->getRepository(Vente::class)->findBy(['chantier'=>$chantierId, 'type'=>'devis_client']);

            $montant = $entityManager->getRepository(Vente::class)->countMontantByVenteDate(null, null, 'facture', $chantierId);
            $montant_devis = $entityManager->getRepository(Vente::class)->countMontantByVenteDate(null, null, 'devis_client', $chantierId);

            
        }

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'mois2' => (int)$mois2,
            'annee' => $annee
        ))
        ->add('mois', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('mois2', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $this->global_s->getAnnee(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();  

        return $this->render('chantier/show.html.twig', [
            'galeries'=> $this->buildGalerie($request, $chantier->getChantierId()),
            'devis'=>$devis,
            'chantier' => $chantier,
            'ventes' => $ventesArr,
            'total' => $total,
            'total_price' => $total_price,
            'full_adress' => $full_adress,
            'form' => $form->createView(),
            //'fullDate' => Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY'),
            'fullDate' => str_replace("-", " au ", $date),
            'coutBl'=>$coutBl,
            'bon_livraisons'=>$bon_livraisons,
            'factures_client'=>$factures_client,
            'tab'=>$tab,
            'cout_materiel'=>$coutBl['sum_ht'],
            'montant'=>$montant,
            'montant_devis'=>$montant_devis,
            'totalResteFacture'=>$totalResteFact,
            'totalHeure'=>$totalHeure,
            'totalTXMoyen'=>$totalTXMoyen,
            'totalMO'=>$totalMO,
            'totalht'=>$totalht,
            'totalttc'=>$totalttc,
        ]);
    }

    public function buildGalerie($request, $chantierId){
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $chantiers = $em->createQueryBuilder()
            ->select('g')
            ->from('App\Entity\Galerie', 'g')
            ->andWhere('g.chantier = :chantier')
            ->setParameter('chantier', $chantierId)
            ->orderBy('g.created_at', 'desc');

        $adapter = new DoctrineORMAdapter($chantiers);
        $pager = new PagerFanta($adapter);
        //$pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }
        /** @var Galerie $file */
        foreach ($pager->getCurrentPageResults() as $file) {
            if ($file->getExtension() == "mp4") {
                /* $path = '/galerie/'.$user->getEntreprise()->getId()."/".$file->getCreatedAt()->format('Y-m-d')."/".$file->getNom();
                 $ffmpeg = FFMpeg::create();
                 $video = $ffmpeg->open($path);
                 $frame = $video->frame(TimeCode::fromSeconds(42));
                 dump($frame);die();*/
                $file->setThumbnail('');
            } else {
                $file->setThumbnail('/galerie/' . $file->getEntreprise()->getId() . "/" . $file->getCreatedAt()->format('Y-m-d') . "/" . $file->getNom());
            }

            try {
                list($width, $height) = getimagesize($this->generateUrl('home', array(), UrlGeneratorInterface::ABSOLUTE_URL).$file->getUrl());
                $file->width = $width;
                $file->height = $height;
            } catch (\Exception $e) {
                $file->width = 0;
                $file->height = 0;
            }

        }
        return $pager;
    }

    public function getTxHoraireByUserAndDate($dateInterval, $userId, $chantierId){
        $annee = $dateInterval[0]->format('Y');
        $tabMonth = [];
        $start = $dateInterval[0];
        $start->modify('first day of this month');
        $end = $dateInterval[1];
        $end->modify('first day of next month');
        $interval = new \DateInterval('P1M');
        $period   = new \DatePeriod($start, $interval, $end);

        $moisList =array_flip($this->global_s->getMois());
        $today = (new \DateTime())->format('m-Y');
        foreach ($period as $dt) {
            if($dt->format("m-Y") == $today)
                continue;
            $tabMonth[] = Carbon::parse($dt->format("Y-m-d"))->locale('fr')->isoFormat('MMMM YYYY');
        }
        $avg_tx_horaire = $this->getDoctrine()->getRepository(Paie::class)->getTxHoraireByUserAndDate($tabMonth, $userId, $chantierId);
        if(!$avg_tx_horaire){
            $avg_tx_horaire = $this->getDoctrine()->getRepository(Paie::class)->getTxHoraireByUserAndCurrentYear($annee, $userId, $chantierId);
        }
        //dd([$tabMonth, $userId, $chantierId, $avg_tx_horaire]);
        return $avg_tx_horaire;
    }

    public function calculCoutGlobale($session, $chantierId, $userId, $dateInterval, $fonction = null){
        $em = $this->getDoctrine()->getManager();
        $qb = $this->getDoctrine()
            ->getRepository(Horaire::class)
            ->getHoraireByChantier($chantierId)
            ->andWhere('h.userid = :userId')
            ->andWhere('h.datestart BETWEEN :start AND :end');
        if(!is_null($fonction)){
            $qb->andWhere('h.fonction = :fonction')
            ->setParameter('fonction',$fonction);
        }

        $qb->setParameter('userId',$userId)
            ->setParameter('start', $dateInterval[0]->format('Y/m/d')." 00:00:00")
            ->setParameter('end' , $dateInterval[1]->format('Y/m/d'." 23:59:59"));

        $horaires = $qb->getQuery()->getResult();
        $totalHeureGenerale = $cout = $tx_horaire = 0;
        foreach ($horaires as $horaire) {
            $mois = $horaire['datestart']->format('m');
            $annee = $horaire['datestart']->format('Y');
            //$totalHeureGenerale = $this->getGeneraleHour($session, $userId, $mois, $annee);
            $totalHeureGenerale = $em->getRepository(Horaire::class)->getGeneraleHourFictif($userId, $mois, $annee);

            $paie = $this->paieRepository->getByUserAndDate($userId, Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM')." ".$annee);
            $cout_global = $paie ? $paie['cout_global'] : 0;

            $tx_horaire += ($totalHeureGenerale != 0) ? $cout_global/$totalHeureGenerale : 0 ;
            $cout += ($totalHeureGenerale != 0) ? ($cout_global/$totalHeureGenerale)*$horaire['time'] : 0;
            
            //$total_tx_horaire += number_format($horaire['tx_horaire'], 2, '.', ' ');
            //$total_cout_global += number_format($horaire['cout'], 2, '.', ' ');
        }
        return ['tx_horaire'=>$tx_horaire, 'cout'=>$cout];
    }

    public function calculTxHoraire($userId, $chantier, $heure){
        
        $entityManager = $this->getDoctrine()->getManager();
        $horaires = $entityManager->getRepository(Horaire::class)->getHoraireByUserAndChantier($userId, $chantier, $heure);

        $tabMonth = [];
        foreach ($horaires as $value) {
            $tabMonth[] = Carbon::parse($value['datestart'])->locale('fr')->isoFormat('MMMM YYYY');
        }
        $coutGlobal = $entityManager->getRepository(Paie::class)->calculCoutGlobalByDate($tabMonth, $userId);
        return (count($tabMonth) > 0 && $heure > 0) ? ($coutGlobal/$heure)/ count($tabMonth) : 0;
    }

    /**
     * @Route("/{chantierId}/user/{userId}", name="show_user", methods={"GET","POST"})
     */
    public function show_user(Request $request, Session $session, $chantierId, $userId)
    {
        $form = $request->request->get('form', null);
        if ($form) {
            if($form['mois'] == 0){
                $this->get('session')->getFlashBag()->add('error', 'Entrer le mois');
                return $this->redirectToRoute('chantier_show_user', ['chantierId'=>$chantierId, 'userId'=>$userId]);
            }
            $mois = $form['mois']; $annee = $form['annee'];

            $mois2 = ($form['mois2']) ? $form['mois2'] : $mois;
                $nbrDay = cal_days_in_month(CAL_GREGORIAN, $mois2, $annee);
            $start_s = "01/$mois/$annee";
            $end_s = "$nbrDay/$mois2/$annee";
            $dateForm = $start_s."-".$end_s;

            $date = explode('-', $dateForm);
            $start = \DateTime::createFromFormat('d/m/Y', trim($date[0]));
            $start->setTime(7, 0, 0);
            $end = \DateTime::createFromFormat('d/m/Y', trim($date[1]));
            $end->setTime(20, 0, 0);
            $date = $dateForm;
        } else {
            $mois = $mois2 = "";
            $annee = (new \DateTime())->format('Y');
            $date = '';
        }


        /** @var Chantier $chantier */
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId);
        $em = $this->getDoctrine()->getManager();
        $qb = $horaires = $this->getDoctrine()
            ->getRepository(Horaire::class)
            ->getHoraireByChantier($chantierId)
            ->andWhere('h.userid = :userId')
            ->setParameter('userId',$userId);

        if(isset($start) && isset($end)) {
            $qb->andWhere('h.datestart BETWEEN :start AND :end')
                ->setParameter('start', $start->format('Y/m/d'). " 23:59:59")
                ->setParameter('end' , $end->format('Y/m/d'). " 00:00:00");
        }

        $horaires = $qb->getQuery()->getResult();

        $debut = date('d/m/Y');
        $fin = date('d/m/Y');
        if(count($horaires) > 0 && empty($date)) {
            $debut = $horaires[0]['datestart']->format('d/m/Y');
            $fin = $horaires[count($horaires)-1]['datestart']->format('d/m/Y');
        }

        $taches = [];
        $taches["nom"] = $user->getFirstname()." ".$user->getLastname();
        $taches["userid"] = $user->getUid();
        $total = 0;
        $total_price = $cout_global = $tx_horaire = $total_cout_global = $total_tx_horaire = 0;
        $horairesArray = [];
        $total_time = 0;
        foreach ($horaires as $horaire) {
            $fonction = $horaire['fonction'];
            $total_time = $this->getDoctrine()->getRepository(Horaire::class)->findTotalHoraireByUserAndDate($horaire['datestart']->format('Y'),$horaire['datestart']->format('m'),$userId);
            unset($horaire['fonction'],$horaire['userid'],$horaire['firstname'],$horaire['lastname']);
            $args = ['annee' => $horaire['datestart']->format('Y'), 'mois' => Carbon::parse($horaire['datestart']->format('Y')."-".$horaire['datestart']->format('m')."-01")->locale('fr')->isoFormat('MMMM'), 'user' => $user];
            $observation = $this->getDoctrine()->getRepository(HoraireObservation::class)->findOneBy($args);
            $horaire['cout_global'] = $observation ? $observation->getCoutGlobal() : 0;
            //$horaire['cout'] = ($horaire['cout_global']/$total_time) * $horaire['time'];

            /* start add Z */
            $moisCur = $horaire['datestart']->format('m');
            $anneeCur = $horaire['datestart']->format('Y');
            //$totalHeureGenerale  = $this->getGeneraleHour($session, $userId, $mois, $annee);
            $totalHeureGenerale = $em->getRepository(Horaire::class)->getGeneraleHourFictif($userId, $moisCur, $anneeCur);
            $paie = $this->paieRepository->getByUserAndDate($userId, Carbon::parse("$anneeCur-$moisCur-01")->locale('fr')->isoFormat('MMMM')." ".$anneeCur);
            $cout_global = $paie ? $paie['cout_global'] : 0;
            $horaire['cout'] = ($totalHeureGenerale != 0) ? ($cout_global/$totalHeureGenerale)*$horaire['time'] : 0;
            $horaire['tx_horaire'] = ($totalHeureGenerale != 0) ? $cout_global/$totalHeureGenerale : 0 ;
            $total_tx_horaire += number_format($horaire['tx_horaire'], 2, '.', ' ');
            $total_cout_global += number_format($horaire['cout'], 2, '.', ' ');
            /* end add Z */

            $total_price+= ($total_time != 0) ? ($horaire['cout_global']/$total_time) * $horaire['time']: 0;
            $horaire['idsession'] = $horaire['idsession'];
            $taches['data'][$fonction][] = $horaire;
            $taches['data'][$fonction]['time'] = isset($taches['data'][$fonction]['time']) ? (float)$taches['data'][$fonction]['time'] + $horaire['time'] : $horaire['time'];

            $total = $horaire['time'] + $total;
            $horairesArray[] = $horaire;
        }

        $horaires = $horairesArray;

        $full_adress = urlencode($chantier->getAddress(). " ". $chantier->getCp() . " ". $chantier->getCity());


        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'mois2' => (int)$mois2,
            'annee' => $annee
        ))
        ->add('mois', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'required'=>false,
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('mois2', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $this->global_s->getMois(),
            'required'=>false,
            'attr' => array(
                'class' => 'form-control',
                'required'=>false
            )
        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $this->global_s->getAnnee(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        return $this->render('chantier/show_user.html.twig', [
            'chantier' => $chantier,
            'taches' => $taches,
            'total' => $total,
            'total_time' => $total_time,
            'total_price' => $total_price,
            'total_cout_global' => $total_cout_global,
            'total_tx_horaire' => $total_tx_horaire,
            'cout_global' => $cout_global,
            'full_adress' => $full_adress,
            'form' => $form->createView(),
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    /**
     * @Route("/{horaireId}/detail-horaire", name="user_horaire_detail", methods={"GET","POST"})
     */
    public function userHoraireDetail(Request $request, $horaireId){
        $horaire = $this->horaireRepository->find($horaireId);
        if ($request->isMethod('post')) {
            $entityManager = $this->getDoctrine()->getManager();

            $horaire->setFonction($request->request->get('tache'));

            $entityManager->flush();
            $this->get('session')->getFlashBag()->add('success', 'Modification effectuée avec succes');

            return $this->redirectToRoute('horaire_horaire_devis');
        }


        $horaireArr = [];
        $horaireArr['heure'] = $horaire->getTime();
        $horaireArr['tache'] = strtolower($horaire->getFonction());
        $horaireArr['date_start'] = $horaire->getDatestart();
        if($horaire->getUserid()){
            $horaireArr['user'] = $this->getDoctrine()->getRepository(Utilisateur::class)->find($horaire->getUserid());
        }

        $categories = $this->getDoctrine()->getRepository(Category::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['category'=>'ASC']);

        return $this->render('chantier/show_user_horaire.html.twig', [
            'horaire' => $horaireArr,
            'categories' => $categories
        ]);
    }

    /**
     * @Route("/{chantierId}/edit-assurance", name="edit_assurance", methods={"GET","POST"})
     */
    public function editAssurance(Request $request, $chantierId)
    {
        $dirAttestation = $this->get('kernel')->getProjectDir() . "/public/uploads/chantier/assurance/attestation/";                
        $dirContrat = $this->get('kernel')->getProjectDir() . "/public/uploads/chantier/assurance/contrat/";                
        $dirQuittance = $this->get('kernel')->getProjectDir() . "/public/uploads/chantier/assurance/quittance/";

        try {
            if (!is_dir($dirAttestation)) {
                mkdir($dirAttestation, 0777, true);
            }
            if (!is_dir($dirContrat)) {
                mkdir($dirContrat, 0777, true);
            }
            if (!is_dir($dirQuittance)) {
                mkdir($dirQuittance, 0777, true);
            }
        } catch (FileException $e) {}


        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);

        if ($request->isMethod('post')) {
            $entityManager = $this->getDoctrine()->getManager();

            /* SAUVEGARDE ATTESTATION */
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $attestations = $chantier->getAttestations();
            $attestationEdit = $request->request->get('attestationsEdit');
            $attestationEditFile = $request->files->get('attestationsEdit');
            
            if(!is_null($attestationEdit)){
                foreach ($attestationEdit as $key => $value) {
                    $valueFile = $attestationEditFile[$key];

                    $attestation =  $this->getDoctrine()->getRepository(AssuranceAttestation::class)->find($key);
                    if(!empty($value['attestation']) || !empty($value['date'])){
                        $attestation->setEntreprise($entreprise);

                        if(!empty($valueFile['file'])){
                            /** @var UploadedFile $file */
                            $uploadedFile = $valueFile['file'];
                            if ($uploadedFile){
                                $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/chantier/assurance/attestation/');
                                $attestation->setFile($file);
                            }
                        }
                        $attestation->setCreateAt(new \DateTime($value['date']));
                    }
                    else{
                        $chantier->removeAttestation($attestation);
                    }

                }
            }
            else
                $attestationEdit = [];
            
            foreach ($attestations as $value) {
                if(!array_key_exists($value->getId(), $attestationEdit)){
                    $entityManager->remove($value);
                }
            }
            

            $attestations = $request->request->get('attestations');
            $attestationsFile = $request->files->get('attestations');

            if(!is_null($attestations)){
                for ($i=0; $i < count($attestations['date']); $i++) { 
                    if(!empty($attestations['file'][$i]) || !empty($attestations['date'][$i]) || !empty($attestationsFile['file'][$i])){
                        $attestation = new AssuranceAttestation();
                        $attestation->setCreateAt(new \DateTime($attestations['date'][$i]));

                        /** @var UploadedFile $file */
                        $uploadedFile = $attestationsFile['file'][$i];
                        if ($uploadedFile){
                            $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/chantier/assurance/attestation/');
                            $attestation->setFile($file);
                        }
                        $attestation->setEntreprise($entreprise);

                        $entityManager->persist($attestation);
                        $chantier->addAttestation($attestation);    
                    }
                }
            }
            /* FIN SAUVEGARDE ATTESTATION */  

            /* SAUVEGARDE CONTRAT */
            $contrats = $chantier->getContrats();
            $contratEdit = $request->request->get('contratsEdit');
            $contratEditFile = $request->files->get('contratsEdit');
            
            if(!is_null($contratEdit)){
                foreach ($contratEdit as $key => $value) {
                    $valueFile = $contratEditFile[$key];

                    $contrat =  $this->getDoctrine()->getRepository(AssuranceContrat::class)->find($key);
                    if(!empty($value['contrat']) || !empty($value['date'])){
                        $contrat->setEntreprise($entreprise);

                        if(!empty($valueFile['file'])){
                            /** @var UploadedFile $file */
                            $uploadedFile = $valueFile['file'];
                            if ($uploadedFile){
                                $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/chantier/assurance/contrat/');
                                $contrat->setFile($file);
                            }
                        }
                        $contrat->setCreateAt(new \DateTime($value['date']));
                    }
                    else{
                        $chantier->removeContrat($contrat);
                    }

                }
            }
            else
                $contratEdit = [];

            foreach ($contrats as $value) {
                if(!array_key_exists($value->getId(), $contratEdit)){
                    $entityManager->remove($value);
                }
            }

            $contrats = $request->request->get('contrats');
            $contratsFile = $request->files->get('contrats');

            if(!is_null($contrats)){
                for ($i=0; $i < count($contrats['date']); $i++) { 
                    if(!empty($contrats['file'][$i]) || !empty($contrats['date'][$i]) || !empty($contratsFile['file'][$i])){
                        $contrat = new AssuranceContrat();
                        $contrat->setCreateAt(new \DateTime($contrats['date'][$i]));

                        /** @var UploadedFile $file */
                        $uploadedFile = $contratsFile['file'][$i];
                        if ($uploadedFile){
                            $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/chantier/assurance/contrat/');
                            $contrat->setFile($file);
                        }
                        $contrat->setEntreprise($entreprise);

                        $entityManager->persist($contrat);
                        $chantier->addContrat($contrat);    
                    }
                }
            }
            /* FIN SAUVEGARDE CONTRAT */  

            /* SAUVEGARDE QUITTANCE */
            $quittances = $chantier->getQuittances();
            $quittanceEdit = $request->request->get('quittancesEdit');
            $quittanceEditFile = $request->files->get('quittancesEdit');
            
            if(!is_null($quittanceEdit)){
                foreach ($quittanceEdit as $key => $value) {
                    $valueFile = $quittanceEditFile[$key];

                    $quittance =  $this->getDoctrine()->getRepository(AssuranceQuittance::class)->find($key);
                    if(!empty($value['quittance']) || !empty($value['date'])){
                        $quittance->setEntreprise($entreprise);

                        if(!empty($valueFile['file'])){
                            /** @var UploadedFile $file */
                            $uploadedFile = $valueFile['file'];
                            if ($uploadedFile){
                                $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/chantier/assurance/quittance/');
                                $quittance->setFile($file);
                            }
                        }
                        $quittance->setCreateAt(new \DateTime($value['date']));
                    }
                    else{
                        $chantier->removeQuittance($quittance);
                    }

                }
            }
            else
                $quittanceEdit = [];

            foreach ($quittances as $value) {
                if(!array_key_exists($value->getId(), $quittanceEdit)){
                    $entityManager->remove($value);
                }
            }
            

            $quittances = $request->request->get('quittances');
            $quittancesFile = $request->files->get('quittances');

            if(!is_null($quittances)){
                for ($i=0; $i < count($quittances['date']); $i++) { 
                    if(!empty($quittances['file'][$i]) || !empty($quittances['date'][$i]) || !empty($quittancesFile['file'][$i])){
                        $quittance = new AssuranceQuittance();
                        $quittance->setCreateAt(new \DateTime($quittances['date'][$i]));

                        /** @var UploadedFile $file */
                        $uploadedFile = $quittancesFile['file'][$i];
                        if ($uploadedFile){
                            $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/chantier/assurance/quittance/');
                            $quittance->setFile($file);
                        }
                        $quittance->setEntreprise($entreprise);

                        $entityManager->persist($quittance);
                        $chantier->addQuittance($quittance);    
                    }
                }
            }
            /* FIN SAUVEGARDE QUITTANCE */  

            
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('chantier_assurance');
        }

        return $this->render('assurance/edit.html.twig', [
            'chantier' => $chantier,
        ]);
    }

    /**
     * @Route("/{chantierId}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $chantierId)
    {
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);
        $form = $this->createForm(ChantierType::class, $chantier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            // $city = $request->request->get('chantier_city');
            // $chantier->setCity($city);
            
            $this->getDoctrine()->getManager()->flush();

            if($request->query->get('page') && $request->query->get('page') == "assurance")
                return $this->redirectToRoute('chantier_assurance');
            else
                return $this->redirectToRoute('chantier_list');
        }

        return $this->render('chantier/edit.html.twig', [
            'chantier' => $chantier,
            'form' => $form->createView(),
            'city' => $chantier->getCity()
        ]);
    }

    /**
     * @Route("/{chantierId}/status", name="status", methods={"GET","POST"})
     */
    public function changeStatus(Request $request, $chantierId)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var Chantier $chantier */
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);
        if ($chantier->getStatus() == 1) {
            $chantier->setStatus(0);
        } else {
            $chantier->setStatus(1);
        }
        $entityManager->flush();


        return $this->redirectToRoute('chantier_list');
    }

    /**
     * @Route("/{chantierId}/delete", name="delete")
     */
    public function delete(Request $request, $chantierId)
    {
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId);
        $horaires = $this->getDoctrine()->getRepository(Horaire::class)->findBy(array('chantierid' => $chantierId));
        if($horaires) {
            $this->get('session')->getFlashBag()->add('error', 'Vous ne pouvez pas supprimer le chantier si vous avez des horaires associés');
            return $this->redirectToRoute('chantier_list');
        }
        try {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($chantier);
            $entityManager->flush();
            $this->get('session')->getFlashBag()->add('info', 'Le chantier a bien été supprimé');
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', 'Vous ne pouvez pas supprimer le chantier si vous avez des horaires associés');
        }

        return $this->redirectToRoute('chantier_list');
    }

    /**
     * @Route("/map/{id}", name="map", methods={"GET"})
     */
    public function map(Request $request, $id)
    {
        $apikey = $this->getParameter('google_api');
        /** @var Chantier $chantier */
        $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($id);
        //$json = file_get_contents("https://maps.google.com/maps/api/geocode/json?address=".$chantier->getFullAddressUrlEncoded()."&sensor=false&key=".$apikey);
        //$output = json_decode($json);
        $adresse = $chantier->getFullAddressUrlEncoded();
        if(!empty($adresse)) {
            //$latitude = $output->results[0]->geometry->location->lat;
            //$longitude = $output->results[0]->geometry->location->lng;
            return $this->redirect("https://www.google.fr/maps/place/".$adresse."/");
        } else {
            return $this->redirectToRoute('chantier_list');
        }
    }

}
