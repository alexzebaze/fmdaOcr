<?php

namespace App\Controller;


use Doctrine\ORM\EntityRepository;
use App\Entity\Achat;
use App\Entity\Vente;
use App\Entity\Client;
use App\Entity\TmpOcr;
use App\Entity\Galerie;
use App\Entity\Lot;
use App\Entity\Pret;
use App\Entity\EmailDocumentPreview;
use App\Form\AchatType;
use App\Form\FournisseursType;
use App\Form\ChantierType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Entity\Fournisseurs;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Repository\ChantierRepository;
use App\Repository\HoraireRepository;
use App\Repository\PaieRepository;
use App\Repository\FournisseursRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\VenteRepository;
use App\Repository\AchatRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\GalerieRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Service\GlobalService;
use Carbon\Carbon;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Repository\MetaConfigRepository;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

use Ilovepdf\Ilovepdf;
use Ilovepdf\CompressTask;

use \ConvertApi\ConvertApi;
use ZipArchive;
use setasign\Fpdi\Fpdi;

use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use App\Controller\PretController;

class HomeController extends Controller
{
    private $global_s;
    private $chantierRepository;
    private $achatRepository;
    private $entrepriseRepository;
    private $fournisseurRepository;
    private $venteRepository;
    private $galerieRepository;
    private $utilisateurRepository;
    private $PaieRepository;
    private $horaireRepository;
    private $session;
    private $pretController;

    public function __construct(GlobalService $global_s, GalerieRepository $galerieRepository, ChantierRepository $chantierRepository, AchatRepository $achatRepository, EntrepriseRepository $entrepriseRepository,FournisseursRepository $fournisseurRepository, PaieRepository $paieRepository, VenteRepository $venteRepository, HoraireRepository $horaireRepository, UtilisateurRepository $utilisateurRepository, SessionInterface $session, PretController $pretController, MetaConfigRepository $metaConfigRepository){
        $this->global_s = $global_s;
        $this->chantierRepository = $chantierRepository;
        $this->galerieRepository = $galerieRepository;
        $this->venteRepository = $venteRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->achatRepository = $achatRepository;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->paieRepository = $paieRepository;
        $this->horaireRepository = $horaireRepository;
        $this->pretController = $pretController;
        $this->metaConfigRepository = $metaConfigRepository;
        $this->session = $session;
    }

    public function getBaseUrl(){
        $baseUrl = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
        return new Response($baseUrl);
    }

    /**
     * @Route("/merge", name="page_merge")
     */
    public function mergeImage(){

        // $ilovepdf = new Ilovepdf('project_public_931609e2e0edbf50c10eaeb858e470f3_fsjDZdb3b9cdf88dab190f9056c68e18de17a','secret_key_2b723a927cff7ba74a71d1fdfad73a9e_wF2qzfa225b5b9be2dbcc23bc29ba9b4c0722');
        // $filename = "20220108110103_00161d96b2f6c9d3.jpg";
        //     $myTask = $ilovepdf->newTask('imagepdf');
        //     $file1 = $myTask->addFile($this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/".$filename);
        //     $myTask->execute();
        //     $myTask->setOutputFilename("test.pdf");
        //     $myTask->download($this->get('kernel')->getProjectDir() . "/public/uploads/achats/facturation/");

        //     dd($file1);
        

        return new Response('d');
    }

    // public function repaireExtensionDoc{
    //     $em = $this->getDoctrine()->getManager();
    //     $achats = $this->achatRepository->findBy(['type'=>'devis_pro']);
    //     foreach ($achats as $value) {
    //         if($value->getDocumentFile()){
    //             $pdf = $this->get('kernel')->getProjectDir()."/public/uploads/devis/".$this->global_s->replaceExtenxionFilename($value->getDocumentFile(), 'pdf');
    //             $PDF = $this->get('kernel')->getProjectDir()."/public/uploads/devis/".$this->global_s->replaceExtenxionFilename($value->getDocumentFile(), 'PDF');
                
    //             if (file_exists($pdf)) {
    //                 $value->setDocumentFile($this->global_s->replaceExtenxionFilename($value->getDocumentFile(), 'pdf'));
    //             }
    //             elseif (file_exists($PDF)) {
    //                 $value->setDocumentFile($this->global_s->replaceExtenxionFilename($value->getDocumentFile(), 'PDF'));
    //             }
    //         }
    //     }

    //     $em->flush();
    // }

    /**
     * @Route("/503", name="page_503")
     */
    public function page503(Request $request)
    {
        $this->global_s->cronOcrIa();

        dd('sds');
        return $this->render('error/503.html.twig');
    }
    /**
     * @Route("/import", name="page_import")
     */
    public function import(Request $request)
    {
        $this->global_s->cronOcrImportDocument();
        
        dd("import");
    }

    public function rotateIfPaysage($dir, $file, $angle){
        try {
            if (file_exists($file)) {
                $ilovepdf = new Ilovepdf('project_public_931609e2e0edbf50c10eaeb858e470f3_fsjDZdb3b9cdf88dab190f9056c68e18de17a','secret_key_2b723a927cff7ba74a71d1fdfad73a9e_wF2qzfa225b5b9be2dbcc23bc29ba9b4c0722');
                
                // $pdf = new Fpdi();
                // $pdf->setSourceFile($file);

                for($i=1;$i<=1;$i++){ 
                    // $tpl = $pdf->importPage($i); 
                    // $size = $pdf->getTemplateSize($tpl); 
                    // $pdf->addPage(); 
                    // $pdf->useTemplate($tpl); 

                    $myTask = $ilovepdf->newTask('rotate');
                    $file1 = $myTask->addFile($file);
                    $file1->setRotation($angle);
                    $myTask->execute();
                    $myTask->setOutputFilename("test");
                    $myTask->download($dir);
                    
                } 
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
                  
        }
    }


    /**
     * @Route("/document-rotation", name="document_rotation")
     */
    public function documentRotation(Request $request)
    {
        $pdfDir = $request->query->get('pdf');
        $rotation = (int)$request->query->get('rotation');
        $dossier = $request->query->get('dossier');

        $arr = explode('/', $pdfDir);
        $pdf = array_pop($arr);
        $dir = $this->get('kernel')->getProjectDir()."/public".implode('/', $arr);
        $pdfDir = $dir.'/'.$pdf;
        
        try {
            $this->rotateIfPaysage($dir, $pdfDir, $rotation);
        } catch (\Exception $e) {
            return new Response(json_encode([
                'status'=>400, 
                'message'=>$e->getMessage()
            ]));  
        }
        

        $docPreview =  $this->getDoctrine()->getRepository(EmailDocumentPreview::class)->findOneBy(['dossier'=>$dossier, 'document'=>$pdf]);
        if(!is_null($docPreview)){
            $docPreview->setIsConvert(false);
            $docPreview->setExecute(false);
        }

        $em = $this->getDoctrine()->getManager();

        $imagename = strtolower($pdf);
        $imagename = str_replace(".pdf", '.jpg', $imagename);

        $em->getRepository(TmpOcr::class)->removesAll($dossier, $imagename);

        $em->flush();

        $urlRedirect = "";
        switch ($dossier) {
            case 'facturation':
                $urlRedirect = "facturation_import_document";
                break;
            case 'bon_livraison':
                $urlRedirect = "bon_livraison_import_document";
                break;
            case 'devis_pro':
                $urlRedirect = "devis_pro_import_document";
                break;
            case 'facture_client':
                $urlRedirect = "facture_client_import_document";
                break;
            case 'devis_client':
                $urlRedirect = "devis_client_import_document";
                break;
            case 'paie':
                $urlRedirect = "paie_import_document";
                break;
        }

        if($urlRedirect != ""){
            return new Response(json_encode([
                'status'=>200, 
                "datas"=>[
                    'redirect'=>$this->generateUrl($urlRedirect, ['scan'=>1], UrlGenerator::ABSOLUTE_URL)
                ]
            ]));    
        }
    }


    /**
     * @Route("/testPdf", name="home_test_pdf")
     */
    public function testPdf()
    {
        $products = [];
        for ($i=0; $i < 9 ; $i++) { 
            $products[] = 
                array(
                    'number' => '', 
                    'title' => 'Devis n°687 du 22/09/2021',
                    'description' => 'Annule et remplace le devis 668 changement de modeconstructif',
                    'qte' => '1.0000',
                    'u' => 'U',
                    'pu' => '350,00 €',
                    'rem' => '',
                    'total' => '350,00 €',
                    'tva' => '20,00'
                );
        }

        if(count($products) <= 6)
            $nbPage = 1;
        else
            $nbPage = ceil((count($products) - 6)/19)+1;
        
        $html = $this->renderView('home/testPdfGenerate.html.twig', [
            'products'=>$products,
            'nbPage'=>$nbPage
        ]);

        $html2pdf = new Html2Pdf('P', 'A4', 'fr', true, 'UTF-8', array(0, 0, 0, 0));

        $html2pdf->addFont('helveticab','', realpath($_SERVER["DOCUMENT_ROOT"]).'/fonts/helveticaBoldItalic/helveticab.php');
        $html2pdf->addFont('helveticaob','', realpath($_SERVER["DOCUMENT_ROOT"]).'/fonts/helveticaOblique/helveticaob.php');
        $html2pdf->addFont('helveticabold','', realpath($_SERVER["DOCUMENT_ROOT"]).'/fonts/helveticabold/helveticabold.php');
        $html2pdf->addFont('helveticabl','', realpath($_SERVER["DOCUMENT_ROOT"]).'/fonts/helveticaligth/helveticabl.php');

        $html2pdf->writeHTML($html);
        $html2pdf->output('credit.pdf');

        return $html2pdf->stream("pdf_filename.pdf", array("Attachment" => false));
    }

    public function getAmmortissements($annee){

        $prets = $this->getDoctrine()->getRepository(Pret::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $amortissementsArr = [];
        foreach ($prets as $pret) {
            $dateDebut = $pret->getDateDeblocage();
            $debutPrelevementInteret = $pret->getDebutPrelevementInteret();
            $montantEcheance1 = (float)$pret->getMontantEcheance1();
            $montantEcheance1 = (float)$pret->getMontantEcheance1();
            $interet1 = (float)$pret->getInteret1();
            $remboursement1 = (float)$pret->getRemboursement1();
            $datePremiereEcheance = $pret->getDatePremiereEcheance();
            $montantPret = (float)$pret->getCapital();
            $duree = (int)$pret->getDuree();
            $taux = (float)$pret->getTaux();
            $tauxAssurance = (float)$pret->getTauxAssurance();
            $typeDiffere = (int)$pret->getDiffere();
            $dureeDiffere = (int)$pret->getDureeDiffere();

            $amortissements = $this->pretController->calculAmortissement($pret, $dateDebut, $montantPret, $duree, $taux, $tauxAssurance, $typeDiffere, $dureeDiffere, $debutPrelevementInteret, $montantEcheance1, $datePremiereEcheance, $interet1, $remboursement1, $annee)['amortissements'];

            foreach ($amortissements as $value) {
                if(($value['date'])->format('Y') == $annee){
                    $amortissementsArr[($value['date'])->format('Y-m')][] = ['pret'=>$pret->getId(), 'date'=>($value['date'])->format('Y-m-d'), 'mensualite_avec_assurance'=>$value['mensualite_avec_assurance']];
                }
            }
        }

        $amortissementsArr = array_map(function($amortissement){
            return [
                'date'=>$amortissement[0]['date'],
                'mensualite_avec_assurance' => array_reduce(
                        $amortissement, function($sum, $item){
                        return $sum + $item['mensualite_avec_assurance'];
                    })
                ];
        }, $amortissementsArr);

        return $amortissementsArr;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(Request $request, Session $session)
    {
        $form = $request->request->get('form', null);

        $fournisseurId = $chantierId = $clientId =  null;
        $moisList =array_flip($this->global_s->getMois());
        if ($form) {
            $mois = null;
            $monthText = !is_null($mois) ? $moisList[$mois] : "";
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            
            $session->set('home_mois_bl', $mois);
            $session->set('home_annee_bl', $annee);
            $session->set('home_chantier_bl', $chantierId);
        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois =  $session->get('home_mois_bl') ?? null;
            $monthText = $mois ? $moisList[$mois] : "";
            $annee = $session->get('home_annee_bl') ?? $day[0];
            $chantierId = $session->get('home_chantier_bl') ?? null;
        }

        $form = $this->createFormBuilder(array(
            'annee' => $annee,

        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $this->global_s->getAnnee(),
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $entityManager = $this->getDoctrine()->getManager();

        $montant = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDate($mois, $annee, 'bon_livraison', $chantierId, $fournisseurId);

        $montantNonRelie = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDate($mois, $annee, 'bon_livraison', $chantierId, $fournisseurId, null, false);

        $montantPayeFact = $entityManager->getRepository(Achat::class)->countMontantByfacturedPayeDate($mois, $annee, 'facturation', $chantierId, $fournisseurId);
        $montantFact = $entityManager->getRepository(Achat::class)->countMontantByfacturedGroupDate($mois, $annee, 'facturation', $chantierId, $fournisseurId);
        $montantFactClient = $entityManager->getRepository(Vente::class)->countMontantByVenteGroupDate($mois, $annee, 'facture', $chantierId, $clientId);
        $coutGlobals = $this->paieRepository->getCoutGlobalGroupDate($monthText, $annee);

        $em = $this->getDoctrine()->getManager();
        $montantFactApayer = $entityManager->getRepository(Vente::class)->countMontantByVenteGroupDate($mois, $annee, 'devis_client', $chantierId, $clientId);

        $valChartAmortMensAssurance = ["","","","","","","","","","","",""];
        $sumMontantAmortMensAssurance = ['mensualite_avec_assurance' => 0];

        $gestionFinancements = $this->metaConfigRepository->findOneBy(['mkey'=>'gestion_financement', 'entreprise'=>$this->session->get('entreprise_session_id')]);


        $isgestionFinancements = 0;
        if(!is_null($gestionFinancements) && !is_null($gestionFinancements->getValue())){
            $amortissements = $this->getAmmortissements($annee);
            foreach ($amortissements as $key => $value) {
                $valChartAmortMensAssurance[(int)(new \DateTime($value['date']))->format('m')-1] = number_format($value['mensualite_avec_assurance'], 2, '.', '');
                $sumMontantAmortMensAssurance['mensualite_avec_assurance'] =  $sumMontantAmortMensAssurance['mensualite_avec_assurance'] + $value['mensualite_avec_assurance'];
            }

            $isgestionFinancements = 1;
        }

        $ventesArr = [];
        $valChartFactApayer = ["","","","","","","","","","","",""];
        $sumFactApayer = ['facture_a_payer' => 0];
        foreach ($montantFactApayer as $value) {
            $valChartFactClient[(int)$value['mois']-1] = number_format($value['sum_ht'], 2, '.', '');
            $listIdDevis = explode(',', $value['list_id']);
            $sumFacutreAssocieDevis = 0;
            foreach ($listIdDevis as $val) {
                $sumFacutreAssocieDevis += $this->venteRepository->getSumMontantByDevis($val)['sum_ht'];
            }
            $currentFacture_a_payer = $value['sum_ht'] - $sumFacutreAssocieDevis;
            $sumFactApayer['facture_a_payer'] +=  $currentFacture_a_payer;
            $valChartFactApayer[(int)$value['mois']-1] = number_format($currentFacture_a_payer, 2, '.', '');
        } 

        $valChartFact = ["","","","","","","","","","","",""];
        $sumMontantFact = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($montantFact as $value) {
            $valChartFact[(int)$value['mois']-1] = number_format($value['sum_ht'], 2, '.', '');
            $sumMontantFact['prixttc'] =  $sumMontantFact['prixttc'] + $value['prixttc'];
            $sumMontantFact['sum_ht'] =  $sumMontantFact['sum_ht'] + $value['sum_ht'];
        }

        $valChart = ["","","","","","","","","","","",""];
        $sumMontant = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($montant as $value) {
            $valChart[(int)$value['mois']-1] = number_format($value['sum_ht'], 2, '.', '');
            $sumMontant['prixttc'] =  $sumMontant['prixttc'] + $value['prixttc'];
            $sumMontant['sum_ht'] =  $sumMontant['sum_ht'] + $value['sum_ht'];
        }

        $valChartNonLie = ["","","","","","","","","","","",""];
        $sumMontantNonLie = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($montantNonRelie as $value) {
            $valChartNonLie[(int)$value['mois']-1] = number_format($value['sum_ht'], 2, '.', '');
            $sumMontantNonLie['prixttc'] =  $sumMontantNonLie['prixttc'] + $value['prixttc'];
            $sumMontantNonLie['sum_ht'] =  $sumMontantNonLie['sum_ht'] + $value['sum_ht'];
        }

        $valChartFactClient = ["","","","","","","","","","","",""];
        $sumMontantFactClient = ['prixttc' => 0, 'sum_ht'=>0];
        $tabMois = [];
        for ($i=1; $i <= 12 ; $i++) { 
            $tabMois[] = $i;
        }

        foreach ($montantFactClient as $value) {
            $valChartFactClient[(int)$value['mois']-1] = number_format($value['sum_ht'], 2, '.', '');
            $sumMontantFactClient['prixttc'] =  $sumMontantFactClient['prixttc'] + $value['prixttc'];
            $sumMontantFactClient['sum_ht'] =  $sumMontantFactClient['sum_ht'] + $value['sum_ht'];
        }

        $valChartCoutGb = ["","","","","","","","","","","",""];
        $sumCoutGb = 0;
        $getMois = $this->global_s->getMois();

        $tabMoisWithPaid = [];
        foreach ($coutGlobals as $value) {
            $currentMois = $getMois[ucfirst(explode(" ", $value['datePaie'])[0])];
            $valChartCoutGb[(int)$currentMois-1] = number_format($value['cout_global'], 2, '.', '');
            $sumCoutGb += $value['cout_global'];

            $tabMoisWithPaid[] = (int)$currentMois;
        }

        $tabMois = array_diff($tabMois, $tabMoisWithPaid);

        // tableau pour completer la valeur des coup des mois sans fiche par les horaire
        foreach ($tabMois as $value) {
            if(is_null($mois) || (!is_null($mois) && (int)$mois == $value )){
                $totaleGroup = $this->calculCoutGlobalWithoutPaidOnMonth($chantierId, $value, $annee);
                $cout_global = $totaleGroup['tx_moyen']*$totaleGroup['heure'];
                $valChartCoutGb[$value-1] = number_format(((float)$valChartCoutGb[$value-1] + $cout_global), 2, '.', '');
                $sumCoutGb += $cout_global;
            }
        }

        /* pour le mois en cour cour sans fiche, calculer sur les tx_moyen des horaires */
        $lastPaie = $this->paieRepository->findBy([], ["id"=>"DESC"], 1);
        if(count($lastPaie) && strtolower($lastPaie[0]->getDatePaie()) == strtolower((new \DateTime())->format('m Y')) ){

            $currenDateMonthYear = ucfirst(Carbon::parse((new \DateTime())->format('Y-m-d'))->locale('fr')->isoFormat('MMMM YYYY'));
            $currentMois = $getMois[ucfirst(explode(" ", $currenDateMonthYear)[0])];
            $totaleGroup = $this->calculCoutGlobalWithoutPaidOnMonth($chantierId);
            $lastVal = $totaleGroup['tx_moyen']*$totaleGroup['heure'];
            $sumCoutGb += $lastVal;
            $valChartCoutGb[(int)$currentMois-1] = number_format( ((float)$valChartCoutGb[(int)$currentMois-1] + $lastVal), 2, '.', '');
        }   

        $chartMarge = ["","","","","","","","","","","",""];
        $tauxMargin = [0,0,0,0,0,0,0,0,0,0,0,0];
        $chartColor = ["green","green","green","green","green","green","green","green","green","green","green","green"];
        $sumMarge = 0;
        $sumMarge_1 = 0;
        $moisToday = (int)(new \DateTime())->format('m');

        $tauxMarginCumulle = 0;
        $countMonth = 0;
        for ($i=0; $i < count($chartMarge) ; $i++) { 
            $chartMarge[$i] = round(((float)$valChartFactClient[$i]-(float)$valChartFact[$i]-(float)$valChartCoutGb[$i]-(float)$valChartNonLie[$i]), 2);
            $sumMarge += (float)$chartMarge[$i];
            if($i < ($moisToday-1)){
                $sumMarge_1 += (float)$chartMarge[$i];
            }
            if($chartMarge[$i] < 0){
                $chartColor[$i] = "red";
            }

            if($valChartFactClient[$i] != 0){
                $currentTaux = ( (float)$chartMarge[$i]/$valChartFactClient[$i] )  * 100;
                $tauxMargin[$i] = number_format($currentTaux, 2, '.', '');
            }
        }

        if($sumMontantFactClient && $sumMontantFactClient['sum_ht'] != 0){
            $tauxMarginCumulle = ($sumMarge / $sumMontantFactClient['sum_ht']) * 100;
        }

        // $galeries = $this->loadGalerie(0);

        // $tabFileDim = [];
        // foreach ($galeries as $value) {
        //     $tabFileDim[$value->getId()] = ['width'=>0, 'height'=>0];
        //     try {
        //         $dir = $this->get('kernel')->getProjectDir()."/public/galerie/";
        //         $file = $dir.$value->getEntreprise()->getId() . "/" . $value->getCreatedAt()->format('Y-m-d') . "/" . $value->getNom();
        //         if(is_file($file)){
        //             list($orig_width, $orig_height) = getimagesize($file);
        //             $width = $orig_width;
        //             $height = $orig_height;
        //             $tabFileDim[$value->getId()] = ['width'=>$width, 'height'=>$height];
        //         }
        //     } catch (Exception $e) {
                
        //     }            
        // }

        $full_month = $annee;
        if($mois && !empty($mois) && $annee)
            $full_month = Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM YYYY');

        if($request->query->get('test')){
            $galeries = [];
        }

        $galleryUser = $this->galerieRepository->getGalleryGroupByUser();
        
        usort($galleryUser, function($a, $b) {
            return $b['nbr_gallery'] - $a['nbr_gallery'];
        });
        $maxPhotoVal = (count($galleryUser) > 0) ? $galleryUser[0]['nbr_gallery'] : 0;
        

        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true);

        $countDocumentAttente = $entityManager->getRepository(EmailDocumentPreview::class)->countGroupByDossier();

        $dossiers = [];
        foreach ($countDocumentAttente as $value) {
            switch ($value['dossier']) {
                case "facturation":
                    $dossiers[] = [
                        'dossier'=> "Facture Fournisseur",
                        'entity'=>$value
                    ];
                    break;
                case "bon_livraison":
                    $dossiers[] = [
                        'entity'=>$value,
                        'dossier'=> "Bon de Livraison"
                    ];
                    break;
                case "facture_client":
                    $dossiers[] = [
                        'entity'=>$value,
                        'dossier'=> "Facture Client"
                    ];
                    break;
                case "devis_client":
                    $dossiers[] = [
                        'entity'=>$value,
                        'dossier'=> "Devis Client"
                    ];
                    break;
                case "devis_pro":
                    $dossiers[] = [
                        'entity'=>$value,
                        'dossier'=> "Devis Fournisseur"
                    ];
                    break;
                case "paie":
                    $dossiers[] = [
                        'entity'=>$value,
                        'dossier'=> "Fiche de Paie"
                    ];
                    break;
                
                default:
                    # code...
                    break;
            }
        }

        return $this->render('home/index.html.twig', [
            'full_month'=> $full_month,
            'valChart'=> $valChart,
            'valChartNonLie'=> $valChartNonLie,
            'valChartFact'=> $valChartFact,
            'valChartFactClient'=> $valChartFactClient,
            'valChartCoutGb'=> $valChartCoutGb,
            'chartMarge'=> $chartMarge,
            'tauxMargin'=> $tauxMargin,
            'tauxMarginCumulle'=> $tauxMarginCumulle,
            'valChartFactApayer'=> $valChartFactApayer,
            'valChartAmortMensAssurance'=> $valChartAmortMensAssurance,
            'mois'=>$mois,
            'annee'=>$annee,
            'form' => $form->createView(),
            'montant'=>$sumMontant,
            'montantBlNonRelie'=>$sumMontantNonLie,
            'montantFact'=>$sumMontantFact,
            'montantFactClient'=>$sumMontantFactClient,
            'montantPayeFact'=>$montantPayeFact,
            'sumMontantAmortMensAssurance'=>$sumMontantAmortMensAssurance,
            'isgestionFinancements'=>$isgestionFinancements,
            'sumCoutGb'=>$sumCoutGb,
            'sumMarge'=>$sumMarge,
            'sumMarge_1'=>$sumMarge_1,
            'sumFactApayer'=>$sumFactApayer,
            'chartColor'=>$chartColor,
            'galleryUser'=>$galleryUser,
            'maxPhotoVal'=>$maxPhotoVal,
            'countDocumentAttente'=>$dossiers,
            'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs),
            'ouvriers' => $utilisateurs,
            'currentMonth'=> Carbon::parse( (new \DateTime())->format('Y-m-d') )->locale('fr')->isoFormat('MMMM YYYY')
        ]);
    }

    /**
     * @Route("/load-galerie-xhr", name="load_galerie_xhr", methods={"GET"})
     */
    public function loadGalerieXhr(Request $request){
        $page = $request->query->get('page');
        $offset = $page*36;
        $galeries = $this->loadGalerie($offset);

        $tabFileDim = [];
        foreach ($galeries as $value) {
            $tabFileDim[$value->getId()] = ['width'=>0, 'height'=>0];
            try {
                $dir = $this->get('kernel')->getProjectDir()."/public/galerie/";
                $file = $dir.$value->getEntreprise()->getId() . "/" . $value->getCreatedAt()->format('Y-m-d') . "/" . $value->getNom();
                if(is_file($file)){
                    list($orig_width, $orig_height) = getimagesize($file);
                    $width = $orig_width;
                    $height = $orig_height;
                    $tabFileDim[$value->getId()] = ['width'=>$width, 'height'=>$height];
                }
            } catch (Exception $e) {
                
            }            
        }

        $datas = ['status'=>200, "message"=>""];

        if(count($galeries) == 0){
            $datas['status'] = 300;
        }

        $datas['preview'] = $this->renderView('home/galerie.html.twig', [
            'galeries' => $galeries,
            'page'=>$page,
            'tabFileDim' => $tabFileDim
        ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    public function loadGalerie($offset = 0){
        $galeries = $this->galerieRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['id'=>'DESC'], 36, $offset);
        
        // $galeries = $entityManager->createQueryBuilder()
        //     ->select('g')
        //     ->from('App\Entity\Galerie', 'g')
        //     ->andWhere('g.entreprise = :entreprise')
        //     ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
        //     ->orderBy('g.id', 'DESC')
        //     ->setMaxResults(36)
        //     ->getQuery()
        //     ->getResult();

        return $galeries;
    }

    public function calculCoutGlobalWithoutPaidOnMonth($chantierId = null, $mois=null, $annee=null){

        $currentMonth = !is_null($mois) ? $mois : (new \DateTime())->format('m');
        $currentYear = !is_null($annee) ? $annee : (new \DateTime())->format('Y');
        $horaires = $this->horaireRepository->findByParams($chantierId, null, null, $currentMonth, $currentYear, null, null);
        $absences = [
            1=>"Congés Payés",
            2=> "En arrêt",
            3=>"Chômage Partiel", 
            4=>"Absence", 
            5=>"Formation", 
            6=> "RTT", 
            7=>"Férié"
        ];
        $horairesArr = [];
        $txMoyen = 0;
        $totaux = ['heure'=>0, 'fictif'=>0, 'tx_moyen'=>0];
        if(count($horaires)){

            $tabUserId = [];
            foreach ($horaires as $value) {
                $paie = $this->paieRepository->getLastPaieWithTx($value['user_id']);
                $value['tx_moyen'] = $paie ? $paie['tx_moyen'] : 0;

                $totaux['heure'] +=  $value['time'];
                if(!array_key_exists($value['absence'], $absences))
                    $totaux['fictif'] +=  $value['fictif'];
                elseif($value['absence'] == 5){
                    $totaux['fictif'] +=  7;
                }

                //nous avons juste besoins d'une seule valuer par user
                if(!in_array($value['user_id'], $tabUserId)){
                    if($value['tx_moyen'] > 0){
                        $txMoyen += $value['tx_moyen'];
                        $tabUserId[] = $value['user_id'];
                    }
                }
            } 
            if(count($tabUserId))
                $txMoyen = $txMoyen / count($tabUserId);  
        }
        
        $totaux['tx_moyen'] = $txMoyen;

        return $totaux;
    }

}
