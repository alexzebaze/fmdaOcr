<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Client;
use App\Entity\Chantier;
use App\Entity\Fournisseurs;
use App\Entity\Lot;
use App\Entity\DocuNormenclature;
use App\Entity\DevisStyle;
use App\Entity\Tva;
use App\Entity\Entreprise;
use App\Entity\Docu;
use App\Entity\Article;
use App\Entity\Entdocu;
use App\Entity\Status;
use App\Form\EntdocuType;
use App\Entity\StatusModule;
use App\Entity\Vente;
use Symfony\Component\HttpFoundation\Request;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CreationDevisClientController extends AbstractController
{
    private $global_s;
    private $session;
    private $params;

    public function __construct(ParameterBagInterface $params, GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->params = $params;
        $this->session = $session;
    }

    /**
     * @Route("/creation/devis/client", name="creation_devis_client")
     */
    public function index(Request $request)
    {   
        if($request->query->get('sommeil')){
            $entetes = $this->getDoctrine()->getRepository(Entdocu::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'locked'=>1]);
            $sommeil = 1;
        }
        else{
            $entetes = $this->getDoctrine()->getRepository(Entdocu::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
            $sommeil = 0;
        }

        $status = $this->getDoctrine()->getRepository(StatusModule::class)->getStatusByModule("DEVIS_CLIENT");

        return $this->render('creation_devis_client/index.html.twig', [
            'entetes' => $entetes,
            'status' => $status,
            'sommeil' => $sommeil,
            'typeArticles'=>$this->global_s->getTypeArticlesDevis()
        ]);
    }

    /**
     * @Route("/creation/devis-client/getbyarticle", name="creation_devis_client_filter_article")
     */
    public function getByArticleXhr(Request $request)
    {
        $valFilter = $request->query->get('val');
        $articles = $this->getDoctrine()->getRepository(Article::class)->filterByLibelleAndCode($valFilter);

        $datas = ['status'=>200, "message"=>""];
        $datas['articles'] = $articles;
        $datas['nbrResult'] = count($articles);


        $datas['content'] = $this->renderView('creation_devis_client/article_content.html.twig', $datas);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/creation/devis/client/new", name="creation_devis_client_new", methods={"GET","POST"})
     */
    public function new(Request $request)
    {
        $entete = new Entdocu();

        $form = $this->createForm(EntdocuType::class, $entete);
        $form->handleRequest($request);

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entete->setEntreprise($entreprise);

            $lastEntete = $this->getDoctrine()->getRepository(Entdocu::class)->findOneBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type_docu'=>1], ['id'=>'DESC']);

            $typDoc = "";
            if(!is_null($lastEntete) && $lastEntete->getTypeDocu() == 1)
                $typDoc = "D";
            else
                $typDoc = "F";

            $docId = $typDoc."1"; 

            if(!is_null($lastEntete) && $lastEntete->getDocumentId()){
                $docId = (int)str_replace('d', '', strtolower($lastEntete->getDocumentId()));
                $docId = $typDoc.(++$docId);
            }

            $entete->setDocumentId($docId);
            $entete->setTypeDocu("1");


            $entete->setTotalTva55((float)$request->request->get('recap_tva_val5'));
            $entete->setTotalTva10((float)$request->request->get('recap_tva_val10'));
            $entete->setTotalTva20((float)$request->request->get('recap_tva_val20'));

            $entete->setRecapHtTva5((float)$request->request->get('recap_httva5'));
            $entete->setRecapHtTva10((float)$request->request->get('recap_httva10'));
            $entete->setRecapHtTva20((float)$request->request->get('recap_httva20'));

            $entete->setTotalTtc((float)$request->request->get('recap_ttc'));
            $entete->setRemise((float)$request->request->get('recap_remise_val'));
            $entete->setRemisePercent((float)$request->request->get('recap_remise'));
            $entete->setTotalNetHt((float)$request->request->get('recap_remise_total_ht'));


            $em->persist($entete);
            $em->flush();


            /* FIN SAUVEGARDE DOCUMENT */ 
            $documents = $request->request->get('documents');
            if(!is_null($documents)){
                for ($i=0; $i < count($documents['article']); $i++) { 
                    if(!empty($documents['article'])){
                        $document = new Docu();
                        $article = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['code'=>$documents['code'][$i]]);
                        $tva = $this->getDoctrine()->getRepository(Tva::class)->find($documents['tva'][$i]);
                        $document->setArticle($article);
                        $document->setLibelle($documents['libelle'][$i]);
                        $document->setTva($tva);

                        $document->setType('docu');
                        $document->setQte((float)$documents['qte'][$i]);
                        $document->setPvht((float)$documents['pvht'][$i]);
                        $document->setCode($documents['code'][$i]);
                        $document->setRang($documents['rang'][$i]);
                        $document->setTotalHt((float)$documents['total_ht'][$i]);
                        $document->setTypeArticle((int)$documents['type_article'][$i]);
                        $document->setRemise((float)$documents['remise'][$i]);
                        $document->setUnite($documents['unite'][$i]);
                        $document->setPrixAchat((float)$documents['prixAchat'][$i]);

                        $docId = $this->getDocumentIdDocu($entete);
                        $document->setDocumentId($docId);

                        // if(array_key_exists('offert', $documents))
                        //  $document->setOffert(true);
                        // else
                        //  $document->setOffert(false);

                        $em->persist($document);
                        $document->setEntdocu($entete);

                        $em->flush();
                        $styles = $request->request->get('styles');
                        $this->saveNewStyle($styles, $document, $i);
                    }
                }
                $em->flush();
            }

            $commentaires = $request->request->get('commentaires');
            if(!is_null($commentaires)){
                $this->saveNewCommentaire($commentaires, $entete, $request);
            }

            $sousTotaux = $request->request->get('sousTotaux');
            if(!is_null($sousTotaux)){
                $this->saveNewSousTotaux($sousTotaux, $entete);
            }

            /* FIN SAUVEGARDE DOCUMENT */  

            return $this->redirectToRoute('creation_devis_client');
        }

        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $chantiers = $this->getDoctrine()->getRepository(Chantier::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $tvas = $this->getDoctrine()->getRepository(Tva::class)->findAll();
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $articlesArr = [];
        foreach ($articles as $value) {
            $articlesArr[] = [
                'id'=>$value->getId(),
                'libelle'=>$value->getLibelle(),
                'pvht'=>$value->getPrixVenteHt(),
                'pvttc'=>$value->getPrixVenteTtc(),
                'code'=>$value->getCode(),
                'type_article'=>$value->getType(),
                'unite'=>$value->getUniteMesure()
            ];
        }
        $tvaArr = [];
        foreach ($tvas as $value) {
            $tvaArr[] = [
                'id'=>$value->getId(),
                'valeur'=>$value->getValeur(),
            ];
        }

        $clientsArr = [];
        foreach ($clients as $value) {
            $arr = [
                'id'=>$value->getId(),
                'nom'=>$value->getNom(),
                'adresse'=>$value->getAdresse(),
                'ville'=>$value->getVille(),
                'nameentreprise'=>"",
            ];
            if(count($value->getChantiers())){
                foreach ($value->getChantiers() as $ch) {
                    $arr['nameentreprise'] .= $ch->getNameentreprise()."<br>";
                }
            }

            $clientsArr[] = $arr;
        }

        $chantierArr = [];
        foreach ($chantiers as $value) {
            $chantierArr[] = [
                'id'=>$value->getChantierId(),
                'nom'=>$value->getNameentreprise(),
                'adresse'=>$value->getAddress(),
                'ville'=>$value->getCity(),
                'cp'=>$value->getCp(),
            ];
        }


        return $this->render('creation_devis_client/new.html.twig', [
            'entete'=>$entete,
            'form' => $form->createView(),
            'clients' => $clients,
            'clientsArr' => $clientsArr,
            'chantierArr' => $chantierArr,
            'tvas' => $tvaArr,
            'articles' => $articlesArr,
            'typeArticles'=>$this->global_s->getTypeArticlesDevis(),
            'typeArt'=>$this->global_s->getTypeArticles()
        ]);
    }

    /**
     * @Route("/creation-devis-update-status", name="creation_devis_client_update_status")
     */
    public function updateStatus(Request $request){
        $devis = $this->getDoctrine()->getRepository(Entdocu::class)->find($request->query->get('devisId'));
        $status = $this->getDoctrine()->getRepository(Status::class)->find($request->query->get('statusId'));
        $devis->setStatus($status);
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush(); 
        return new Response(json_encode(['status'=>200]));
    }

    /**
     * @Route("/creation/devis/client/export-docu/{id}", name="creation_devis_client_export_docu", methods={"GET"})
     */
    public function exportDocu(Entdocu $entete){

        $docus = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete], ['rang'=>'ASC']);

        $rowArray = [];
        foreach ($docus as $value) {
            if($value->getType() == "docu"){
                $currentDocu = [];
                $currentDocu[] = $value->getCode();
                $currentDocu[] = $value->getLibelle();
                $currentDocu[] = (float)$value->getQte();
                $currentDocu[] = (float)$value->getPvht();

                $rowArray[] = $currentDocu;
            }
            elseif($value->getType() == "line"){
                $currentDocu = [];
                $currentDocu[] = $value->getCommentaire();

                $rowArray[] = $currentDocu;
            }
        }
        
        array_unshift($rowArray, ["CODE ARTICLE", "DESIGNATION", "Quantite", "PRIX VENTE HT"]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()
            ->fromArray(
                $rowArray,   
                NULL,        
                'A1'           
            );

        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestDataColumn()) as $col) {
            $spreadsheet->getActiveSheet()
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }
        
        $sheet->setTitle("LISTE ARTICLE DEVIS");

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // In this case, we want to write the file in the public directory
        $publicDirectory = $this->params->get('kernel.project_dir') . '/public/uploads/tache_excel/';
        try {
            if (!is_dir($publicDirectory)) {
                mkdir($publicDirectory, 0777, true);
            }
        } catch (FileException $e) {}

        // e.g /var/www/project/public/my_first_excel_symfony4.xlsx
        $excelFilepath = $publicDirectory . 'article_devis_'.$entete->getId().'.xlsx';

        // Create the file
        $writer->save($excelFilepath);

        // Return a text response to the browser saying that the excel was succesfully created
        return $this->file($excelFilepath, 'article_devis_'.$entete->getId().'.xlsx', ResponseHeaderBag::DISPOSITION_INLINE);

    }


    /**
     * @Route("/duplique-docu", name="creation_devis_client_duplique_docu", methods={"POST"})
     */
    public function dupliqueDocu(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $entDocu = $this->getDoctrine()->getRepository(Entdocu::class)->find($request->request->get('entDocuId'));
        $tabDocument = explode('_', $request->request->get('list_docu'));

        $docus = $this->getDoctrine()->getRepository(Docu::class)->findByTabDocu($tabDocument);  
        $lastDocuByRang = $this->getDoctrine()->getRepository(Docu::class)->findOneBy(['entdocu'=>$entDocu], ['rang'=>'DESC']);
        if($lastDocuByRang)
            $lastDocuByRang = $lastDocuByRang->getRang()+1;
        else
            $lastDocuByRang = 0;

        $documentArr = [];
        foreach ($docus as $value) {
            $document = new Docu();
            $document->setArticle($value->getArticle());
            $document->setLibelle($value->getLibelle());
            $document->setTva($value->getTva());

            $document->setType($value->getType());
            $document->setQte((float)$value->getQte());
            $document->setCommentaire($value->getCommentaire());
            $document->setPvht($value->getPvht());
            $document->setCode($value->getCode());
            $document->setRang($lastDocuByRang++);
            $document->setTotalHt($value->getTotalHt());
            $document->setTypeArticle($value->getTypeArticle());
            $document->setRemise($value->getRemise());
            $document->setUnite($value->getUnite());
            $document->setPrixAchat((float)$value->getPrixAchat());

            $docId = $this->getDocumentIdDocu($entDocu);
            $document->setDocumentId($docId);

            $document->setEntdocu($entDocu);
            $em->persist($document);

            $documentArr[] = $document;
        }
        $em->flush();

        // $html = $this->renderView('creation_devis_client/content_docu_duplique.html.twig', [
        //     'entete'=>$entDocu,
        //     'section'=>$request->request->get('lastSection'),
        //     'documents'=>$documentArr,
        //     'tvas' => $tvaArr,
        //     'articles' => $articlesArr,
        //     'typeArticles'=>$this->global_s->getTypeArticlesDevis(),
        //     'typeArt'=>$this->global_s->getTypeArticles()
        // ]);

        $this->addFlash('success', "Vous devez enregistrer le formulaire afin de mettre à jour le nouveau recap");
        return new response(json_encode(['status'=>200, 'content'=>""]));
    }


    /**
     * @Route("/sortable-docu", name="docu_sortable", methods={"GET"})
     */
    public function sortableDocu(Request $request)
    {

        $orders = explode(',', $request->query->get('docu_sort'));
        $em = $this->getDoctrine()->getManager();
        $menus = $this->menuRepository->getMenuFirstNiveau();
        foreach ($menus as $value) {
            $index = array_search($value->getId(), $orders);
            if($index || $index == 0){
                $value->setRang($index);
            }
            $em->flush();  
        }
        
        $em->flush();  
        return new Response(json_encode(array('status'=>200)));
    }

    /**
     * @Route("/creation/devis/client/download/{devis_id}", name="creation_devis_client_download")
     */
    public function downloadPdf(Request $request, $devis_id){

        $entete = $this->getDoctrine()->getRepository(Entdocu::class)->find($devis_id);
        $documents = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete], ['rang'=>"ASC"]);
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $tabTva = [];
        $totalHT = 0;
        $styles = [];
        foreach ($documents as $value) {
            if(!is_null($value->getType()) && !is_null($value->getTva()) && !is_null($value->getTva()->getValeur())){
                $totalHT += $value->getTotalHt();
            }

            $currentStyle = $this->getDoctrine()->getRepository(DevisStyle::class)->findBy(['document'=>$value->getId()]);
            $styleField = [];
            if(count($currentStyle) > 0){
                foreach ($currentStyle as $val) {
                    $styleField[$val->getChamps()] = [
                        'document_id'=> $value->getId(),
                        'color'=>$val->getColor(),
                        'background'=>$val->getBackground(),
                        'weight'=>$val->getWeight(),
                        'champs'=>$val->getChamps()
                    ];
                }
            }
            $styles[$value->getId()] = $styleField;
            
        }

        if(count($documents) <= 6)
            $nbPage = 1;
        else
            $nbPage = ceil(abs(count($documents) - 9)/19)+1;

        $html = $this->renderView('creation_devis_client/devis_pdf_template.html.twig', [
            'nbPage'=>$nbPage,
            'entete'=>$entete,
            'documents'=>$documents,
            'totalHT'=>$totalHT,
            'styles'=>$styles,
            'entreprise'=>$entreprise
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

    /**
     * @Route("/creation/devis/client-lock/{id}", name="creation_devis_client_lock")
     */
    public function lockDevis(Entdocu $entete)
    {
        if($entete->getLocked())
            $entete->setLocked(false);
        else
            $entete->setLocked(true);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->redirectToRoute('creation_devis_client_edit', ['id'=>$entete->getId()]);
    }

    public function previsualisationPdf($entete, $form, $request){

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $entete->setEntreprise($entreprise);

        $entete->setTotalTva55((float)$request->request->get('recap_tva_val5'));
        $entete->setTotalTva10((float)$request->request->get('recap_tva_val10'));
        $entete->setTotalTva20((float)$request->request->get('recap_tva_val20'));
        $entete->setTotalTtc((float)$request->request->get('recap_ttc'));
        $entete->setRemise((float)$request->request->get('recap_remise_val'));
        $entete->setRemisePercent((float)$request->request->get('recap_remise'));
        $entete->setTotalNetHt((float)$request->request->get('recap_remise_total_ht'));

        $documents = $request->request->get('documents');
        $documentsEntity = [];
        if(!is_null($documents)){
            for ($i=0; $i < count($documents['article']); $i++) { 
                if(!empty($documents['article'])){
                    $document = new Docu();
                    $article = $this->getDoctrine()->getRepository(Article::class)->find($documents['article'][$i]);
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->find($documents['tva'][$i]);
                    $document->setArticle($article);
                    $document->setLibelle($documents['libelle'][$i]);
                    $document->setTva($tva);

                    $document->setType('docu');
                    $document->setQte((float)$documents['qte'][$i]);
                    $document->setPvht((float)$documents['pvht'][$i]);
                    $document->setCode($documents['code'][$i]);
                    $document->setRang($documents['rang'][$i]);
                    $document->setTotalHt((float)$documents['total_ht'][$i]);
                    $document->setTypeArticle((int)$documents['type_article'][$i]);
                    $document->setRemise((float)$documents['remise'][$i]);
                    $document->setUnite($documents['unite'][$i]);
                    $document->setPrixAchat((float)$documents['prixAchat'][$i]);

                    $docId = $this->getDocumentIdDocu($entete);
                    $document->setDocumentId($docId);
                    $document->setEntdocu($entete);

                    $documentsEntity[$documents['rang'][$i]] = $document;
                    //$styles = $request->request->get('styles');
                }
            }
        }
        $documentEdit = $request->request->get('documentsEdit');
        if(!is_null($documentEdit)){
            foreach ($documentEdit as $key => $value) {
                $document =  $this->getDoctrine()->getRepository(Docu::class)->find($key);
                if(!empty($value['article'])){
                    $article = $this->getDoctrine()->getRepository(Article::class)->find($value['article']);
                    $tva = $this->getDoctrine()->getRepository(Tva::class)->find($value['tva']);
                    $document->setArticle($article);
                    $document->setLibelle($value['libelle']);
                    $document->setTva($tva);
                    $document->setQte((float)$value['qte']);
                    $document->setPvht((float)$value['pvht']);
                    $document->setCode($value['code']);
                    $document->setRang($value['rang']);
                    $document->setTotalHt((float)$value['total_ht']);
                    $document->setTypeArticle((int)$value['type_article']);
                    $document->setRemise((float)$value['remise']);
                    $document->setUnite($value['unite']);
                    $document->setPrixAchat((float)$value['prixAchat']);

                    $documentsEntity[$value['rang']] = $document;
                }
            }
        }

        $commentaireEdit = $request->request->get('commentairesEdit');        
        if(!is_null($commentaireEdit)){
            foreach ($commentaireEdit as $key => $value) {
                $document =  $this->getDoctrine()->getRepository(Docu::class)->find($key);
                if( trim($value['commentaire']) || trim($value['commentaire']) != "" ){
                    $document->setRang($value['rang']);
                    $document->setCommentaire($value['commentaire']);
                }
                $documentsEntity[$value['rang']] = $document;
            }
        }        

        $commentaires = $request->request->get('commentaires');   
        if(!is_null($commentaires)){     
            for ($i=0; $i < count($commentaires['rang']); $i++) { 
                $document = new Docu();
                $document->setType('line');
                if(trim($commentaires['commentaire'][$i]) != ""){
                    $document->setCommentaire($commentaires['commentaire'][$i]);
                }
                $document->setRang($commentaires['rang'][$i]);
                $documentsEntity[$commentaires['rang'][$i]] = $document;
            }
        }
        
        $totalHT = 0;
        $styles = [];


        if(count($documentsEntity) <= 6)
            $nbPage = 1;
        else
            $nbPage = ceil(abs(count($documentsEntity) - 9)/19)+1;


        ksort($documentsEntity);
        $html = $this->renderView('creation_devis_client/devis_pdf_template.html.twig', [
            'nbPage'=>$nbPage,
            'entete'=>$entete,
            'documents'=>array_values($documentsEntity),
            'totalHT'=>$totalHT,
            'styles'=>$styles,
            'entreprise'=>$entreprise
        ]);

        $html2pdf = new Html2Pdf('P', 'A4', 'fr', true, 'UTF-8', array(0, 0, 0, 0));

        $html2pdf->addFont('helveticab','', realpath($_SERVER["DOCUMENT_ROOT"]).'/fonts/helveticaBoldItalic/helveticab.php');
        $html2pdf->addFont('helveticaob','', realpath($_SERVER["DOCUMENT_ROOT"]).'/fonts/helveticaOblique/helveticaob.php');
        $html2pdf->addFont('helveticabold','', realpath($_SERVER["DOCUMENT_ROOT"]).'/fonts/helveticabold/helveticabold.php');
        $html2pdf->addFont('helveticabl','', realpath($_SERVER["DOCUMENT_ROOT"]).'/fonts/helveticaligth/helveticabl.php');

        $html2pdf->writeHTML($html);
        $html2pdf->output('credit.pdf');

        return $html2pdf->stream("devis_client.pdf", array("Attachment" => false));
    }


    /**
     * @Route("/creation/devis/client/update-rang", name="creation_devis_client_update_rang")
     */
    public function updateRang(Request $request){

        $em = $this->getDoctrine()->getManager();

        $documentId1 = $request->query->get('document1');
        $documentId2 = $request->query->get('document2');

        $document1 = $this->getDoctrine()->getRepository(Docu::class)->find($documentId1);
        $document2 = $this->getDoctrine()->getRepository(Docu::class)->find($documentId2);

        $rang1 = $document1->getRang();
        $rang2 = $document2->getRang();

        $document1->setRang($rang2);
        $document2->setRang($rang1);

        $em->flush();

        return new Response(json_encode(array()));
    }

    public function getDocumentIdDocu($entete){
        $lastDocu = $this->getDoctrine()->getRepository(Docu::class)->findOneBy(['entdocu'=>$entete->getId()], ['id'=>'DESC']);

        $typDoc = "";
        if(!is_null($entete) && $entete->getTypeDocu() == 1)
            $typDoc = "D";
        else
            $typDoc = "F";

        $docId = $typDoc."1"; 

        if(!is_null($entete) && $entete->getDocumentId() && !is_null($lastDocu)){
            $docId = (int)str_replace('d', '', strtolower($lastDocu->getDocumentId()));
            $docId = (int)str_replace('f', '', strtolower($lastDocu->getDocumentId()));
            $docId = $typDoc.(++$docId);
        }
        return $docId;
    }



    public function saveStyle($styleEdit){
        $em = $this->getDoctrine()->getManager();
        if(!is_null($styleEdit)){
            foreach ($styleEdit as $key => $value) {
                $document =  $this->getDoctrine()->getRepository(Docu::class)->find($key);
                $styles =  $this->getDoctrine()->getRepository(DevisStyle::class)->findBy(['document'=>$key]);
                $stylesArr = [];
                foreach ($styles as $val) {
                    $stylesArr[$val->getChamps()] = [
                        'id'=>$val->getId(),
                        'color'=>$val->getColor(),
                        'background'=>$val->getBackground(),
                        'weight'=>$val->getWeight(),
                        'champs'=>$val->getChamps()
                    ];
                }

                foreach ($value as $k => $val) {
                    if(array_key_exists($k, $stylesArr)){
                        $style = $this->getDoctrine()->getRepository(DevisStyle::class)->find($stylesArr[$k]['id']);
                    }
                    else{
                        $style = new DevisStyle();
                        $style->setChamps($k);
                        $style->setDocument($document);
                    }
                    if($k == "article"){
                        if($val['color'])
                            $style->setColor($val['color']);
                        if($val['bg'])
                            $style->setBackground($val['bg']);
                    }

                    if($val['gras'])
                        $style->setWeight($val['gras']);

                    $em->persist($style);
                }
            }

            $em->flush();
        }

        return 1;
    }

    public function saveNewStyle($styles, $document, $i){
        $em = $this->getDoctrine()->getManager();
        if(!is_null($styles)){

            foreach ($styles as $key => $value) {

                $style = new DevisStyle();

                if(array_key_exists($i, $value['gras'])){
                    $gras = $value['gras'][$i];
                    $style->setWeight($gras);
                }

                if($key == "article"){
                    if(array_key_exists($i, $value['bg'])){
                        $bg = $value['bg'][$i];
                        $style->setBackground($bg);
                    }

                    if(array_key_exists($i, $value['color'])){
                        $color = $value['color'][$i];
                        $style->setColor($color);
                    }
                }
                
                $style->setChamps($key);
                $style->setDocument($document);
            

                $em->persist($style);
                
            }

            $em->flush();
        }

        return 1;
    }   

    public function saveNewCommentaire($commentaires, $entete, $request){

        $em = $this->getDoctrine()->getManager();
        for ($i=0; $i < count($commentaires['rang']); $i++) { 
            $document = new Docu();
            $document->setType('line');
            if(trim($commentaires['commentaire'][$i]) != ""){
                $document->setCommentaire($commentaires['commentaire'][$i]);
            }
            $document->setRang($commentaires['rang'][$i]);

            $em->persist($document);
            $document->setEntdocu($entete);
            $em->flush();

            $styles = $request->request->get('styles');
            $this->saveNewStyle($styles, $document, $i);
        }

        return 1;
    }    

    public function saveNewSousTotaux($sousTotaux, $entete){

        $em = $this->getDoctrine()->getManager();
        for ($i=0; $i < count($sousTotaux['rang']); $i++) { 
            $document = new Docu();

            $document->setType('sous_total');
            $document->setRang($sousTotaux['rang'][$i]);
            $document->setPvht((float)$sousTotaux['pvht'][$i]);
            $document->setTotalHt((float)$sousTotaux['total_ht'][$i]); 

            $em->persist($document);
            $document->setEntdocu($entete);
            $em->flush();

            //$styles = $request->request->get('styles');
            //$this->saveNewStyle($styles, $document, $i);
        }

        return 1;
    }

    public function saveCommentaire($commentaireEdit, $entete){

        $em = $this->getDoctrine()->getManager();

        $commentaires = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete->getId(), 'type'=>'line']);
        
        if(!is_null($commentaireEdit)){
            foreach ($commentaireEdit as $key => $value) {
                $document =  $this->getDoctrine()->getRepository(Docu::class)->find($key);
                if( trim($value['commentaire']) || trim($value['commentaire']) != "" ){
                    $document->setRang($value['rang']);
                    $document->setCommentaire($value['commentaire']);
                }
                else{
                    $em->remove($document);
                }
            }
            foreach ($commentaires as $value) {
                if(!array_key_exists($value->getId(), $commentaireEdit)){
                    $em->remove($value);
                }
            }
        }
        else{
            foreach ($commentaires as $value) {
                $em->remove($value);
            }
        }
        

        $em->flush();

        return 1;
    }

    public function saveSousTotaux($sousTotauxEdit, $entete){

        $em = $this->getDoctrine()->getManager();

        $sousTotaux = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete->getId(), 'type'=>'sous_total']);
            
        if(!is_null($sousTotauxEdit)){
            foreach ($sousTotauxEdit as $key => $value) {
                $document =  $this->getDoctrine()->getRepository(Docu::class)->find($key);

                $document->setPvht((float)$value['pvht']);
                $document->setRang($value['rang']);
                $document->setTotalHt((float)$value['total_ht']);                
            }
            foreach ($sousTotaux as $value) {
                if(!array_key_exists($value->getId(), $sousTotauxEdit)){
                    $em->remove($value);
                }
            }
        }
        else{
            foreach ($sousTotaux as $value) {
                $em->remove($value);
            }
        }
        

        $em->flush();

        return 1;
    }


    /**
     * @Route("/creation/devis/client/editxhr", name="creation_devis_client_edit_xhr", methods={"POST"})
     */
    public function editXhr(Request $request)
    {
        $entete = $this->getDoctrine()->getRepository(Entdocu::class)->find($request->request->get('enteteId'));
        
        $form = $this->createForm(EntdocuType::class, $entete);
        $form->handleRequest($request);

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        if ($form->isSubmitted() && $form->isValid()) {
            if($request->request->get('download') && $request->request->get('download') == 1){

                return $this->previsualisationPdf($entete, $form, $request);
            }

            $em = $this->getDoctrine()->getManager();
            $entete->setEntreprise($entreprise);


            $entete->setTotalTva55((float)$request->request->get('recap_tva_val5'));
            $entete->setTotalTva10((float)$request->request->get('recap_tva_val10'));
            $entete->setTotalTva20((float)$request->request->get('recap_tva_val20'));

            $entete->setRecapHtTva5((float)$request->request->get('recap_httva5'));
            $entete->setRecapHtTva10((float)$request->request->get('recap_httva10'));
            $entete->setRecapHtTva20((float)$request->request->get('recap_httva20'));

            $entete->setTotalTtc((float)$request->request->get('recap_ttc'));
            $entete->setRemise((float)$request->request->get('recap_remise_val'));
            $entete->setRemisePercent((float)$request->request->get('recap_remise'));
            $entete->setTotalNetHt((float)$request->request->get('recap_remise_total_ht'));


            $em->persist($entete);
            $em->flush();


            /* SAUVEGARDE DOCUMENT */            

            $styleEdit = $request->request->get('styleEdit');  
            $this->saveStyle($styleEdit);
            
            $documents = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete->getId(), 'type'=>'docu']);
            $documentEdit = $request->request->get('documentsEdit');
            if(!is_null($documentEdit)){
                foreach ($documentEdit as $key => $value) {
                    $document =  $this->getDoctrine()->getRepository(Docu::class)->find($key);
                    if(!empty($value['article'])){
                        $article = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['code'=>$value['code']]);
                        $tva = $this->getDoctrine()->getRepository(Tva::class)->find($value['tva']);
                        $document->setArticle($article);
                        $document->setLibelle($value['libelle']);
                        $document->setTva($tva);

                        $document->setQte((float)$value['qte']);
                        $document->setPvht((float)$value['pvht']);
                        $document->setCode($value['code']);
                        $document->setRang($value['rang']);
                        $document->setTotalHt((float)$value['total_ht']);
                        $document->setTypeArticle((int)$value['type_article']);
                        $document->setRemise((float)$value['remise']);
                        $document->setUnite($value['unite']);
                        $document->setPrixAchat((float)$value['prixAchat']);
                        
                        if(array_key_exists('offert', $value))
                            $document->setOffert(true);
                        else
                            $document->setOffert(false);
                    }
                    else{
                        $em->remove($document);
                    }
                }
                foreach ($documents as $value) {
                    if(!array_key_exists($value->getId(), $documentEdit)){
                        $em->remove($value);
                    }
                }
            }
            else{
                foreach ($documents as $value) {
                    $em->remove($value);
                }
            }

            $commentaireEdit = $request->request->get('commentairesEdit');
            $this->saveCommentaire($commentaireEdit, $entete);

            $sousTotauxEdit = $request->request->get('sousTotauxEdit');
            $this->saveSousTotaux($sousTotauxEdit, $entete);
            

            $documents = $request->request->get('documents');
            if(!is_null($documents)){
                $lastDocu = $this->getDoctrine()->getRepository(Docu::class)->findOneBy(['entdocu'=>$entete->getId()], ['id'=>'DESC']);

                for ($i=0; $i < count($documents['rang']); $i++) { 
                    if( !empty($documents['article']) ){
                        $document = new Docu();
                        $article = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['code'=>$documents['code'][$i]]);
                        $tva = $this->getDoctrine()->getRepository(Tva::class)->find($documents['tva'][$i]);
                        $document->setType('docu');
                        $document->setArticle($article);
                        $document->setLibelle($documents['libelle'][$i]);
                        $document->setTva($tva);
                        $document->setQte((float)$documents['qte'][$i]);

                        $document->setPvht((float)$documents['pvht'][$i]);
                        $document->setCode($documents['code'][$i]);
                        $document->setRang($documents['rang'][$i]);
                        $document->setTotalHt((float)$documents['total_ht'][$i]);
                        $document->setTypeArticle((int)$documents['type_article'][$i]);
                        $document->setRemise((float)$documents['remise'][$i]);
                        $document->setUnite($documents['unite'][$i]);
                        $document->setPrixAchat((float)$documents['prixAchat'][$i]);

                        $docId = $this->getDocumentIdDocu($entete);
                        $document->setDocumentId($docId);

                        if(array_key_exists('offert', $documents))
                            $document->setOffert(true);
                        else
                            $document->setOffert(false);
                        
                        $em->persist($document);
                        $document->setEntdocu($entete);
                        $em->flush();

                        $styles = $request->request->get('styles');
                        $this->saveNewStyle($styles, $document, $i);
                    }
                }

            }
            
            $commentaires = $request->request->get('commentaires');
            if(!is_null($commentaires)){
                $this->saveNewCommentaire($commentaires, $entete, $request);
            }

            $sousTotaux = $request->request->get('sousTotaux');
            if(!is_null($sousTotaux)){
                $this->saveNewSousTotaux($sousTotaux, $entete);
            }

            $em->flush();
        }

        return new Response(json_encode(['status'=>200]));
    }    

    /**
     * @Route("creation/devis/maj-normenclature", name="creation_devis_maj_normenclature", methods={"GET"})
     */
    public function majNormenclature(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();

        $docuId = $request->query->get('docu_id');

        $docu = $this->getDoctrine()->getRepository(Docu::class)->find($docuId);
        // $docuNormenclature = $this->getDoctrine()->getRepository(DocuNormenclature::class)->findBy(['devis'=>$docu->getEntdocu(), 'articleNormenclature'=>$docu->getArticle()]);

        // foreach ($docuNormenclature as $value) {
        //     $em->remove($value);
        // }
        // $em->flush();

        $article = $docu->getArticle();
        $entete = $docu->getEntdocu();

        if($article->getType() == 2){
            $normenclatures = $article->getNormenclatures();

            foreach ($normenclatures as $value) {
                if($value->getArticleReference()){
                    $articleRef = $value->getArticleReference();
                
                    $docuNorm = new DocuNormenclature();

                    $docuNorm->setCodeArticle($articleRef->getCode());
                    $docuNorm->setLibelle($articleRef->getLibelle());
                    $docuNorm->setPrixAchat((float)$articleRef->getPrixAchat());
                    $docuNorm->setPrixVente((float)$articleRef->getPrixVenteHt());
                    $docuNorm->setQte((float)$value->getQte());
                    $docuNorm->setUnite($articleRef->getUniteMesure());
                    $docuNorm->setPourcentageMarge((float)$articleRef->getPourcentageMarge());
                    $docuNorm->setMargeBrut((float)$articleRef->getMargeBrut());
                    $docuNorm->setDevis($entete);
                    $docuNorm->setArticleNormenclature($article);

                    if($value->getArticleReference()){
                        $docuNorm->setArticleReference($value->getArticleReference());
                    }
                    
                    $em->persist($docuNorm);
                }
            }

            $em->flush();
        }

        $datas = ['status'=>200, "message"=>""];
        $response = new Response(json_encode($datas));
        return $response;
    }


    /**
     * @Route("/article-maj", name="check_article_devis_update", methods={"GET"})
     */
    public function updateDevisUpdate(Request $request)
    {
        $devisId = $request->query->get('devisId');
        $docus = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$devisId]);

        $datas = ['status'=>200, "message"=>""];

        $docusArr = [];
        foreach ($docus as $docu) {
            $docuItem = [];
            if(!is_null($docu->getArticle())){
                $articleDocu = $docu->getArticle();

                $docuItem['docu'] = $docu;
                $docuNormenclature = $this->getDoctrine()->getRepository(DocuNormenclature::class)->findBy(['devis'=>$devisId, 'articleNormenclature'=>$articleDocu]);

                $docuItem['normenclatures'] = $docuNormenclature;

                $docusArr[] = $docuItem;
            }
            
        }

        $datas['content'] = $this->renderView('creation_devis_client/article_maj.html.twig', ['docus'=>$docusArr]);

        return new Response(json_encode($datas));



        $docusArr = ['normenclature'=>[], 'docu'=>[]];
        foreach ($docus as $value) {
            if($value->getArticle()){
                if($value->getArticle()->getType() == 1){
                    $docusArr['docu'][] = $value;
                }
                else{
                    $docusArr['normenclature'][] = $value;
                }
                // if($this->isMaj($value, $value->getArticle(), 1)){
                //     $docusArr['docu'][] = $value;
                // }
                // if($value->getArticle()->getType() == 2){
                //     foreach ($value->getArticle()->getNormenclatures() as $norm) {

                //         if($norm->getArticleNormenclature()){
                //             $article = $this->getDoctrine()->getRepository(Article::class)->find($norm->getArticleNormenclature()->getId());
                //             if($this->isMaj($norm, $norm->getArticleNormenclature(), 2)){
                //                 $docusArr['normenclature'][] = $norm;
                //             }
                //         }
                //     }
                // }                
            }
            
        }

        $datas = ['status'=>200, "message"=>""];
        $datas['content'] = $this->renderView('creation_devis_client/article_maj.html.twig', ['docus'=>$docus]);

        return new Response(json_encode($datas));
    }

    public function isMaj($docu, $article, $type){
        if( $type == 1 AND (strtolower($article->getLibelle()) != strtolower($docu->getLibelle()) OR $article->getPrixVenteHt() != $docu->getPvht() OR $article->getUniteMesure() != $docu->getUnite())){
            return true;
        }
        elseif( $type == 2 AND (strtolower($article->getLibelle()) != strtolower($docu->getLibelle()) OR $article->getUniteMesure() != $docu->getUnite())){
            return true;
        }

        return false;
    }

    /**
     * @Route("/creation/devis/noremenclature/new", name="creation_devis_client_normenclature_new", methods={"GET"})
     */
    public function addDocuNomenclature(Request $request){
        $articleId = $request->query->get('article_id');
        $enteteId = $request->query->get('entete_id');

        $em = $this->getDoctrine()->getManager();

        if(!empty($articleId) && !empty($enteteId)){

            // $docuNormenclature = $this->getDoctrine()->getRepository(DocuNormenclature::class)->findBy(['devis'=>$enteteId, 'articleNormenclature'=>$articleId]);

            // foreach ($docuNormenclature as $value) {
            //     $em->remove($value);
            // }
            // $em->flush();

            $article = $this->getDoctrine()->getRepository(Article::class)->find($articleId);
            $entete = $this->getDoctrine()->getRepository(Entdocu::class)->find($enteteId);

            if($article->getType() == 2){
                $normenclatures = $article->getNormenclatures();

                foreach ($normenclatures as $value) {
                    if($value->getArticleReference()){
                        $articleRef = $value->getArticleReference();
                    
                        $docuNorm = new DocuNormenclature();

                        $docuNorm->setCodeArticle($articleRef->getCode());
                        $docuNorm->setLibelle($articleRef->getLibelle());
                        $docuNorm->setPrixAchat((float)$articleRef->getPrixAchat());
                        $docuNorm->setPrixVente((float)$articleRef->getPrixVenteHt());
                        $docuNorm->setQte((float)$value->getQte());
                        $docuNorm->setUnite($articleRef->getUniteMesure());
                        $docuNorm->setPourcentageMarge((float)$articleRef->getPourcentageMarge());
                        $docuNorm->setMargeBrut((float)$articleRef->getMargeBrut());
                        $docuNorm->setDevis($entete);
                        $docuNorm->setArticleNormenclature($article);

                        if($value->getArticleReference()){
                            $docuNorm->setArticleReference($value->getArticleReference());
                        }
                        
                        $em->persist($docuNorm);
                    }
                }

                $em->flush();
            }
        } 
        else{
            return new Response(json_encode(['status'=>400, 'message'=>"L'article et l'entete ne sont pas fournis"]));
        }

        return new Response(json_encode([]));
    }

    /**
     * @Route("/creation/devis/client/edit/{id}", name="creation_devis_client_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Entdocu $entete)
    {
        $form = $this->createForm(EntdocuType::class, $entete);
        $form->handleRequest($request);

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        if ($form->isSubmitted() && $form->isValid()) {

            if($entete->getLocked()){
                $this->addFlash('error', "Vous ne pouvez pas modifier ce devis car il est verouillé");
                return $this->redirectToRoute('creation_devis_client_edit', ['id'=>$entete->getId()]);
            }

            if($request->request->get('download') && $request->request->get('download') == 1){

                return $this->previsualisationPdf($entete, $form, $request);
            }

            $em = $this->getDoctrine()->getManager();
            $entete->setEntreprise($entreprise);

            $entete->setTotalTva55((float)$request->request->get('recap_tva_val5'));
            $entete->setTotalTva10((float)$request->request->get('recap_tva_val10'));
            $entete->setTotalTva20((float)$request->request->get('recap_tva_val20'));

            $entete->setRecapHtTva5((float)$request->request->get('recap_httva5'));
            $entete->setRecapHtTva10((float)$request->request->get('recap_httva10'));
            $entete->setRecapHtTva20((float)$request->request->get('recap_httva20'));

            $entete->setTotalTtc((float)$request->request->get('recap_ttc'));
            $entete->setRemise((float)$request->request->get('recap_remise_val'));
            $entete->setRemisePercent((float)$request->request->get('recap_remise'));
            $entete->setTotalNetHt((float)$request->request->get('recap_remise_total_ht'));

            $em->persist($entete);
            $em->flush();

            /* SAUVEGARDE DOCUMENT */            

            $styleEdit = $request->request->get('styleEdit');  
            $this->saveStyle($styleEdit);
            
            $documents = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete->getId(), 'type'=>'docu']);
            $documentEdit = $request->request->get('documentsEdit');
            if(!is_null($documentEdit)){
                foreach ($documentEdit as $key => $value) {
                    $document =  $this->getDoctrine()->getRepository(Docu::class)->find($key);
                    if(!empty($value['article'])){
                        $article = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['code'=>$value['code']]);
                        $tva = $this->getDoctrine()->getRepository(Tva::class)->find($value['tva']);
                        $document->setArticle($article);
                        $document->setLibelle($value['libelle']);
                        $document->setTva($tva);

                        $document->setQte((float)$value['qte']);
                        $document->setPvht((float)$value['pvht']);
                        $document->setCode($value['code']);
                        $document->setRang($value['rang']);
                        $document->setTotalHt((float)$value['total_ht']);
                        $document->setTypeArticle((int)$value['type_article']);
                        $document->setRemise((float)$value['remise']);
                        $document->setUnite($value['unite']);
                        $document->setPrixAchat((float)$value['prixAchat']);
                        
                        if(array_key_exists('offert', $value))
                            $document->setOffert(true);
                        else
                            $document->setOffert(false);
                    }
                    else{
                        $em->remove($document);
                    }
                }
                foreach ($documents as $value) {
                    if(!array_key_exists($value->getId(), $documentEdit)){
                        $em->remove($value);
                    }
                }
            }
            else{
                foreach ($documents as $value) {
                    $em->remove($value);
                }
            }

            $commentaireEdit = $request->request->get('commentairesEdit');
            $this->saveCommentaire($commentaireEdit, $entete);

            $sousTotauxEdit = $request->request->get('sousTotauxEdit');
            $this->saveSousTotaux($sousTotauxEdit, $entete);
            

            $documents = $request->request->get('documents');
            if(!is_null($documents)){
                $lastDocu = $this->getDoctrine()->getRepository(Docu::class)->findOneBy(['entdocu'=>$entete->getId()], ['id'=>'DESC']);

                for ($i=0; $i < count($documents['rang']); $i++) { 
                    if( !empty($documents['article']) ){
                        $document = new Docu();
                        $article = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['code'=>$documents['code'][$i]]);
                        $tva = $this->getDoctrine()->getRepository(Tva::class)->find($documents['tva'][$i]);
                        $document->setType('docu');
                        $document->setArticle($article);
                        $document->setLibelle($documents['libelle'][$i]);
                        $document->setTva($tva);
                        $document->setQte((float)$documents['qte'][$i]);

                        $document->setPvht((float)$documents['pvht'][$i]);
                        $document->setCode($documents['code'][$i]);
                        $document->setRang($documents['rang'][$i]);
                        $document->setTotalHt((float)$documents['total_ht'][$i]);
                        $document->setTypeArticle((int)$documents['type_article'][$i]);
                        $document->setRemise((float)$documents['remise'][$i]);
                        $document->setUnite($documents['unite'][$i]);
                        $document->setPrixAchat((float)$documents['prixAchat'][$i]);

                        $docId = $this->getDocumentIdDocu($entete);
                        $document->setDocumentId($docId);

                        if(array_key_exists('offert', $documents))
                            $document->setOffert(true);
                        else
                            $document->setOffert(false);
                        
                        $em->persist($document);
                        $document->setEntdocu($entete);
                        $em->flush();

                        $styles = $request->request->get('styles');
                        $this->saveNewStyle($styles, $document, $i);
                    }
                }

            }
            
            $commentaires = $request->request->get('commentaires');
            if(!is_null($commentaires)){
                $this->saveNewCommentaire($commentaires, $entete, $request);
            }

            $sousTotaux = $request->request->get('sousTotaux');
            if(!is_null($sousTotaux)){
                $this->saveNewSousTotaux($sousTotaux, $entete);
            }

            $em->flush();

            return $this->redirectToRoute('creation_devis_client');
            /* FIN SAUVEGARDE DOCUMENT */  
        }

        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $chantiers = $this->getDoctrine()->getRepository(Chantier::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $tvas = $this->getDoctrine()->getRepository(Tva::class)->findAll();
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $articlesArr = [];
        foreach ($articles as $value) {
            $articlesArr[] = [
                'id'=>$value->getId(),
                'libelle'=>$value->getLibelle(),
                'pvht'=>$value->getPrixVenteHt(),
                'pvttc'=>$value->getPrixVenteTtc(),
                'prix_achat'=>$value->getPrixAchat(),
                'code'=>$value->getCode(),
                'image'=>$value->getImage(),
                'type_article'=>$value->getType(),
                'unite'=>$value->getUniteMesure(),
            ];
        }
        
        $tvaArr = [];
        foreach ($tvas as $value) {
            $tvaArr[] = [
                'id'=>$value->getId(),
                'valeur'=>$value->getValeur(),
            ];
        }

        $documents = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete->getId()], ['rang'=>"ASC"]);

        $clientsArr = [];
        foreach ($clients as $value) {
            $arr = [
                'id'=>$value->getId(),
                'nom'=>$value->getNom(),
                'adresse'=>$value->getAdresse(),
                'ville'=>$value->getVille(),
                'nameentreprise'=>"",
            ];
            if(count($value->getChantiers())){
                foreach ($value->getChantiers() as $ch) {
                    $arr['nameentreprise'] .= $ch->getNameentreprise()."<br>";
                }
            }

            $clientsArr[] = $arr;
        }

        $chantierArr = [];
        foreach ($chantiers as $value) {
            $chantierArr[] = [
                'id'=>$value->getChantierId(),
                'nom'=>$value->getNameentreprise(),
                'adresse'=>$value->getAddress(),
                'ville'=>$value->getCity(),
                'cp'=>$value->getCp(),
            ];
        }

        $documents = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete], ['rang'=>"ASC"]);
        $styles = [];

        foreach ($documents as $value) {
            $currentStyle = $this->getDoctrine()->getRepository(DevisStyle::class)->findBy(['document'=>$value->getId()]);

            $styleField = [];
            if(count($currentStyle) > 0){
                foreach ($currentStyle as $val) {
                    $styleField[$val->getChamps()] = [
                        'document_id'=> $value->getId(),
                        'color'=>$val->getColor(),
                        'background'=>$val->getBackground(),
                        'weight'=>$val->getWeight(),
                        'champs'=>$val->getChamps()
                    ];
                }
            }
            $styles[$value->getId()] = $styleField;
        }

        return $this->render('creation_devis_client/edit.html.twig', [
            'styles'=>$styles,
            'entete'=>$entete,
            'documents'=>$documents,
            'form' => $form->createView(),
            'clients' => $clients,
            'clientsArr' => $clientsArr,
            'chantierArr' => $chantierArr,
            'tvas' => $tvaArr,
            'articles' => $articlesArr,
            'typeArticles'=>$this->global_s->getTypeArticlesDevis(),
            'typeArt'=>$this->global_s->getTypeArticles()
        ]);
    }

    /**
     * @Route("/export-commande/{id}", name="export_commande_fournisseur", methods={"GET"})
     */
    public function exportCommandFournisseur(Request $request, Fournisseurs $fournisseur){
        $enteteId = $request->query->get('devisId');
        $docu = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$enteteId]);

        $articlesArr = [];

        foreach ($docu as $value) {
            if($value->getArticle()){
                $article = $value->getArticle();
                if($article->getType() == 2){
                    $articlesArrItem = [];
                    foreach ($article->getNormenclatures() as $norm) {
                        $articleNorm = $norm->getArticleReference();
                        if($this->isInFournisseur($fournisseur, $articleNorm->getFournisseurs()) && strpos('oeuvre', strtolower($norm->getLibelle())) == false && strpos('divers', strtolower($norm->getLibelle())) == false){
                            
                            $articlesArrItem[$articleNorm->getCode()][] = [
                                'docu_qte'=>$value->getQte(),
                                'image'=>$articleNorm->getImage(),
                                'code'=>$articleNorm->getCode(),
                                'libelle'=>$norm->getLibelle(),
                                'qte'=>$norm->getQte(),
                                'code_fournisseur'=>$articleNorm->getCodeArticleFournisseur(),
                                'prix_achat'=>$articleNorm->getPrixAchat(),
                                'prix_vente'=>$articleNorm->getPrixVenteHt(),
                                'fournisseur'=>count($articleNorm->getFournisseurs()) > 0 ? ($articleNorm->getFournisseurs())[0]->getNom() : "",
                                'fabricant'=> !is_null($articleNorm->getFabricant()) ? $articleNorm->getFabricant()->getNom() : ""
                            ];
                        }
                    }

                    foreach ($articlesArrItem as $key => $art) {
                        if(!empty($art)){
                            $sumQte = array_sum(array_column($art, "qte"));
                            $normenItem = $art[0];
                            $normenItem['qte'] = $sumQte * $normenItem['docu_qte'];
                            $articlesArr[$normenItem['code']][] = $normenItem;
                        }
                    }
                }
            }
        }

        $normenclatureArr = [];
        foreach ($articlesArr as $key => $value) {
            if(!empty($value)){
                $sumQte = array_sum(array_column($value, "qte"));
                $normenItem = $value[0];
                $normenItem['qte'] = $sumQte;
                $normenclatureArr[] = $normenItem;
            }
        }

        $rowArray = [];
        foreach ($normenclatureArr as $value) {
            $currentHoraire = [];
            $currentHoraire[] = $value['code'];
            $currentHoraire[] = $value['libelle'];
            $currentHoraire[] = $value['qte'];
            $currentHoraire[] = $value['fournisseur'];
            $currentHoraire[] = $value['fabricant'];
            $currentHoraire[] = $value['code_fournisseur'];
            $currentHoraire[] = $value['prix_achat'];
            $currentHoraire[] = $value['prix_vente'];

            $rowArray[] = $currentHoraire;
        }
        
        array_unshift($rowArray, ["CODE ARTICLE", "LIBELLE", "QTE", "FOURNISSEUR", "FABRICANT", "CODE FOURNISSEUR", "PRIX ACHAT", "PRIX VENTE"]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()
            ->fromArray(
                $rowArray,   
                NULL,        
                'A1'           
            );

        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestDataColumn()) as $col) {
            $spreadsheet->getActiveSheet()
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }
        
        $sheet->setTitle("COMMANDES FOURNISSEURS");

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // In this case, we want to write the file in the public directory
        $publicDirectory = $this->params->get('kernel.project_dir') . '/public/uploads/tache_excel/';
        try {
            if (!is_dir($publicDirectory)) {
                mkdir($publicDirectory, 0777, true);
            }
        } catch (FileException $e) {}

        // e.g /var/www/project/public/my_first_excel_symfony4.xlsx
        $excelFilepath = $publicDirectory . 'commande_fournisseur_'.$fournisseur->getNom().'.xlsx';

        // Create the file
        $writer->save($excelFilepath);

        // Return a text response to the browser saying that the excel was succesfully created
        return $this->file($excelFilepath, 'commande_fournisseur_'.$fournisseur->getNom().'.xlsx', ResponseHeaderBag::DISPOSITION_INLINE);

    }

    /**
     * @Route("/apercu-commande/", name="apercu_commande_fournisseur", methods={"GET"})
     */
    public function apercuCommande(Request $request){
        $enteteId = $request->query->get('devisId');
        $docu = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$enteteId]);

        $fournisseurArr = [];
        foreach ($docu as $value) {
            if($value->getArticle()){
                $article = $value->getArticle();
                if($article->getType() == 2){
                    foreach ($article->getNormenclatures() as $norm) {
                        $articleRef = $norm->getArticleReference();
                        foreach ($articleRef->getFournisseurs() as $fourn) {
                            $fournisseurArr[$fourn->getId()] = $fourn;
                        }
                    }
                }
            }
            elseif($value->getType() == 1){
                $article = $value->getArticle();
                foreach ($article->getFournisseurs() as $fourn) {
                    $fournisseurArr[$fourn->getId()] = $fourn;
                }
            }
        }

        $datas = ['status'=>200, "message"=>""];

        $datas['content'] = $this->renderView('creation_devis_client/commande_fournisseur.html.twig', ['fournisseurs'=>$fournisseurArr, 'enteteId'=>$enteteId]);
        
        $response = new Response(json_encode($datas));
        return $response; 
    }    

    /**
     * @Route("/commande-article/", name="apercu_commande_article", methods={"GET"})
     */
    public function commandeArticle(Request $request){
        $enteteId = $request->query->get('devisId');
        $fournisseurId = $request->query->get('fournisseurId');
        $docu = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$enteteId]);
        $fournisseurCommande = $this->getDoctrine()->getRepository(Fournisseurs::class)->find($fournisseurId);

        $articlesArr = [];
        // foreach ($docu as $value) {
        //     if($value->getArticle()){
        //         $article = $value->getArticle();
        //         if($article->getType() == 2){
        //             foreach ($article->getNormenclatures() as $norm) {
        //                 $articleNorm = $norm->getArticleReference();
        //                 if($this->isInFournisseur($fournisseurCommande, $articleNorm->getFournisseurs()) && strpos('oeuvre', strtolower($norm->getLibelle())) == false && strpos('divers', strtolower($norm->getLibelle())) == false){
        //                     $articlesArr[$articleNorm->getCode()][] = [
        //                         'docu_qte'=>$value->getQte(),
        //                         'image'=>$articleNorm->getImage(),
        //                         'code'=>$articleNorm->getCode(),
        //                         'libelle'=>$norm->getLibelle(),
        //                         'qte'=>$norm->getQte(),
        //                         'code_fournisseur'=>$articleNorm->getCodeArticleFournisseur(),
        //                         'prix_achat'=>$articleNorm->getPrixAchat(),
        //                         'prix_vente'=>$articleNorm->getPrixVenteHt(),
        //                         'fournisseur'=>count($articleNorm->getFournisseurs()) > 0 ? ($articleNorm->getFournisseurs())[0]->getNom() : "",
        //                         'fabricant'=> !is_null($articleNorm->getFabricant()) ? $articleNorm->getFabricant()->getNom() : ""
        //                     ];
        //                 }
        //             }
        //         }
        //     }
        // }


        foreach ($docu as $value) {
            if($value->getArticle()){
                $article = $value->getArticle();
                if($article->getType() == 2){
                    $articlesArrItem = [];
                    foreach ($article->getNormenclatures() as $norm) {
                        $articleNorm = $norm->getArticleReference();
                        if($this->isInFournisseur($fournisseurCommande, $articleNorm->getFournisseurs()) && strpos('oeuvre', strtolower($norm->getLibelle())) == false && strpos('divers', strtolower($norm->getLibelle())) == false){
                            
                            $articlesArrItem[$articleNorm->getCode()][] = [
                                'docu_qte'=>$value->getQte(),
                                'image'=>$articleNorm->getImage(),
                                'code'=>$articleNorm->getCode(),
                                'libelle'=>$norm->getLibelle(),
                                'qte'=>$norm->getQte(),
                                'code_fournisseur'=>$articleNorm->getCodeArticleFournisseur(),
                                'prix_achat'=>$articleNorm->getPrixAchat(),
                                'prix_vente'=>$articleNorm->getPrixVenteHt(),
                                'fournisseur'=>count($articleNorm->getFournisseurs()) > 0 ? ($articleNorm->getFournisseurs())[0]->getNom() : "",
                                'fabricant'=> !is_null($articleNorm->getFabricant()) ? $articleNorm->getFabricant()->getNom() : ""
                            ];
                        }
                    }

                    foreach ($articlesArrItem as $key => $art) {
                        if(!empty($art)){
                            $sumQte = array_sum(array_column($art, "qte"));
                            $normenItem = $art[0];
                            $normenItem['qte'] = $sumQte * $normenItem['docu_qte'];
                            $articlesArr[$normenItem['code']][] = $normenItem;
                        }
                    }
                }
            }
        }

        $normenclatureArr = [];
        foreach ($articlesArr as $key => $value) {
            if(!empty($value)){
                $sumQte = array_sum(array_column($value, "qte"));
                $normenItem = $value[0];
                $normenItem['qte'] = $sumQte;
                $normenclatureArr[] = $normenItem;
            }
        }

        $datas = ['status'=>200, "message"=>""];

        $datas['content'] = $this->renderView('creation_devis_client/commande_article.html.twig', [
            'articles'=>$normenclatureArr,
            'fournisseurCommande'=>$fournisseurCommande,
        ]);
        
        $response = new Response(json_encode($datas));
        return $response; 
    }

    public function isInFournisseur($fournisseur, $tabFournisseur){

        foreach ($tabFournisseur as $value) {
            if($value->getId() == $fournisseur->getId()){
                return true;
            }
        }

        return false;
    }


    /**
     * @Route("/duplique/", name="creation_devis_client_duplique", methods={"POST"})
     */
    public function dupliqueArticle(Request $request){

        $tabDevis = explode('-', $request->request->get('list_devis'));

        $devisSelect = $this->getDoctrine()->getRepository(Entdocu::class)->findByTabDocu($tabDevis);  

        $entDocuClone = null;
        foreach ($devisSelect as $value) {
            $entDocuClone = $this->cloneAndSaveEnteteDocu($value);
        }

        if(count($devisSelect) >=2){
            $this->addFlash('success', "devis dupliqués avec succes");
            return $this->redirectToRoute('creation_devis_client', []);
            
        }
        elseif(!is_null($entDocuClone)){
            return $this->redirectToRoute('creation_devis_client_edit', ['id'=>$entDocuClone->getId()]);
        }
        return $this->redirectToRoute('creation_devis_client', []);
    }

    public  function cloneAndSaveEnteteDocu($entete){
        $em = $this->getDoctrine()->getManager();
        $entDocu = $this->getDoctrine()->getRepository(Entdocu::class)->find($entete->getId());

        $entDocuClone = new Entdocu();
        $entDocuClone = clone $entDocu;
        $entDocuClone->setClient(null);
        $entDocuClone->setChantier(null);
        $entDocuClone->setCreateAt(new \Datetime());

        $lastEntete = $this->getDoctrine()->getRepository(Entdocu::class)->findOneBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type_docu'=>$entDocuClone->getTypeDocu()], ['id'=>'DESC']);


        $typDoc = "";
        if($entDocuClone->getTypeDocu() == 1){
            $typDoc = "D";
            if(!is_null($lastEntete) && $lastEntete->getDocumentId()){
                $docId = (int)str_replace('d', '', strtolower($lastEntete->getDocumentId()));
                $docId = $typDoc.(++$docId);
            }
            else
                $docId = $typDoc."1"; 
        }
        elseif($entDocuClone->getTypeDocu() == 2){
            $typDoc = "F";
            if(!is_null($lastEntete) && $lastEntete->getDocumentId()){
                $docId = (int)str_replace('f', '', strtolower($lastEntete->getDocumentId()));
                $docId = $typDoc.(++$docId);
            }
            else
                $docId = $typDoc."1"; 
        }
            
        $entDocuClone->setDocumentId($docId);

        $em->persist($entDocuClone);
        $em->flush();

        return $this->saveCloneDocuEntDocu($entDocu, $entDocuClone);

    }

    public function saveCloneDocuEntDocu($entDocu, $entDocuClone){
        $em = $this->getDoctrine()->getManager();
        $docus = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entDocu->getId()]);  
        foreach ($docus as $value) {
            $document = new Docu();
            $document->setArticle($value->getArticle());
            $document->setLibelle($value->getLibelle());
            $document->setTva($value->getTva());

            $document->setType($value->getType());
            $document->setQte((float)$value->getQte());
            $document->setCommentaire($value->getCommentaire());
            $document->setTotalHt($value->getTotalHt()); 
            $document->setPvht($value->getPvht());
            $document->setCode($value->getCode());
            $document->setRang($value->getRang());
            $document->setTypeArticle($value->getTypeArticle());
            $document->setRemise($value->getRemise());
            $document->setUnite($value->getUnite());
            $document->setPrixAchat((float)$value->getPrixAchat());

            $docId = $this->getDocumentIdDocu($entDocuClone);
            $document->setDocumentId($docId);

            $document->setEntdocu($entDocuClone);
            $em->persist($document);
        }
        $em->flush();

        return $entDocuClone;
    }

    /**
     * @Route("/creation/devis/client/print/{id}", name="creation_devis_client_print", methods={"GET"})
     */
    public function print(Request $request, Entdocu $entete)
    {
        $form = $this->createForm(EntdocuType::class, $entete);
        $form->handleRequest($request);

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $chantiers = $this->getDoctrine()->getRepository(Chantier::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $tvas = $this->getDoctrine()->getRepository(Tva::class)->findAll();
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);

        $articlesArr = [];
        foreach ($articles as $value) {
            $articlesArr[] = [
                'id'=>$value->getId(),
                'libelle'=>$value->getLibelle(),
                'pvht'=>$value->getPrixVenteHt(),
                'pvttc'=>$value->getPrixVenteTtc(),
                'code'=>$value->getCode(),
                'image'=>$value->getImage(),
                'type_article'=>$value->getType(),
                'unite'=>$value->getUniteMesure(),
            ];
        }
        
        $tvaArr = [];
        foreach ($tvas as $value) {
            $tvaArr[] = [
                'id'=>$value->getId(),
                'valeur'=>$value->getValeur(),
            ];
        }

        $documents = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete->getId()], ['rang'=>"ASC"]);

        $clientsArr = [];
        foreach ($clients as $value) {
            $arr = [
                'id'=>$value->getId(),
                'nom'=>$value->getNom(),
                'adresse'=>$value->getAdresse(),
                'ville'=>$value->getVille(),
                'nameentreprise'=>"",
            ];
            if(count($value->getChantiers())){
                foreach ($value->getChantiers() as $ch) {
                    $arr['nameentreprise'] .= $ch->getNameentreprise()."<br>";
                }
            }

            $clientsArr[] = $arr;
        }

        $chantierArr = [];
        foreach ($chantiers as $value) {
            $chantierArr[] = [
                'id'=>$value->getChantierId(),
                'nom'=>$value->getNameentreprise(),
                'adresse'=>$value->getAddress(),
                'ville'=>$value->getCity(),
            ];
        }

        $documents = $this->getDoctrine()->getRepository(Docu::class)->findBy(['entdocu'=>$entete], ['rang'=>"ASC"]);
        $styles = [];

        foreach ($documents as $value) {
            $currentStyle = $this->getDoctrine()->getRepository(DevisStyle::class)->findBy(['document'=>$value->getId()]);

            $styleField = [];
            if(count($currentStyle) > 0){
                foreach ($currentStyle as $val) {
                    $styleField[$val->getChamps()] = [
                        'document_id'=> $value->getId(),
                        'color'=>$val->getColor(),
                        'background'=>$val->getBackground(),
                        'weight'=>$val->getWeight(),
                        'champs'=>$val->getChamps()
                    ];
                }
            }
            $styles[$value->getId()] = $styleField;
        }

        return $this->render('creation_devis_client/devis_client_print.html.twig', [
            'styles'=>$styles,
            'entete'=>$entete,
            'documents'=>$documents,
            'form' => $form->createView(),
            'clients' => $clients,
            'clientsArr' => $clientsArr,
            'chantierArr' => $chantierArr,
            'tvas' => $tvaArr,
            'articles' => $articlesArr,
            'typeArticles'=>$this->global_s->getTypeArticlesDevis(),
            'typeArt'=>$this->global_s->getTypeArticles()
        ]);
    }

    /**
     * @Route("/{id}", name="creation_devis_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Entdocu $entete): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entete->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($entete);
            $entityManager->flush();
        }

        return $this->redirectToRoute('creation_devis_client');
    }
}
