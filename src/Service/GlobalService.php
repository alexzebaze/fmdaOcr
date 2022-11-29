<?php
namespace App\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\RossumConfigRepository;
use App\Repository\ChantierRepository;
use App\Repository\FournisseursRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\MetaConfigRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
//use Symfony\Component\Templating\EngineInterface;
use Carbon\Carbon;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use App\Entity\Entreprise;
use App\Entity\Utilisateur;
use App\Entity\ConfigTotal;
use App\Entity\Passage;
use App\Entity\Paie;
use App\Entity\Fields;
use App\Entity\Fournisseurs;
use App\Entity\FieldsEntreprise;
use App\Entity\Page;
use App\Entity\MetaConfig;
use App\Entity\EmailDocumentPreview;
use App\Entity\Client;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Entity\ConfigImapEmail;
use App\Entity\OcrField;
use App\Entity\IAZone;
use App\Entity\ModelDocument;
use App\Entity\PreferenceField;
use App\Entity\Lot;
use App\Entity\Chantier;
use App\Entity\TmpOcr;
use \ConvertApi\ConvertApi;
use Ilovepdf\Ilovepdf;
use ZipArchive;
use setasign\Fpdi\Fpdi;

class GlobalService{

    private $params;
    private $public_path;
    private $username;
    private $password;
    private $queue_bonLv_id ;
    private $queue_paie_id ;
    private $queue_facturation_id ;
    private $ROSSUM_API_ENDPOINT ;
    private $rossumConfigRepository;
    private $fournisseurRepository;
    private $metaConfigRepository;
    private $chantierRepository;
    private $clientRepository;
    private $entrepriseRepository;
    private $utilisateurRepository;
    private $session;
    private $BASEURLSMS;
    private $IS_ASYNC;
    
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, \Swift_Mailer $mailer, SessionInterface $session, RossumConfigRepository $rossumConfigRepository, FournisseursRepository $fournisseurRepository, ChantierRepository $chantierRepository, ClientRepository $clientRepository, MetaConfigRepository $metaConfigRepository, EntrepriseRepository $entrepriseRepository, UtilisateurRepository $utilisateurRepository)
    {
        $this->params = $params;
        $this->rossumConfigRepository = $rossumConfigRepository;
        $this->metaConfigRepository = $metaConfigRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->chantierRepository = $chantierRepository;
        $this->clientRepository = $clientRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->mailer = $mailer;
        //$this->public_path = $this->request->server->get('DOCUMENT_ROOT');
        $this->em = $em;
        $this->username = "facture@armu.fr";
        $this->password = "Admin2019.";
        $this->session = $session;
        $this->BASEURLSMS = "https://api.smsenvoi.com/API/v1.0/REST/";
        $this->IS_ASYNC=null;

        $this->queue_bonLv_id = !is_null($this->rossumConfigRepository->findOneBy(['mkey'=>'bon_livraison', 'entreprise'=>$this->session->get('entreprise_session_id')])) ? $this->rossumConfigRepository->findOneBy(['mkey'=>'bon_livraison', 'entreprise'=>$this->session->get('entreprise_session_id')])->getValue() : 0000;

        $this->queue_paie_id = !is_null($this->rossumConfigRepository->findOneBy(['mkey'=>'fiche_paie', 'entreprise'=>$this->session->get('entreprise_session_id')])) ? $this->rossumConfigRepository->findOneBy(['mkey'=>'fiche_paie', 'entreprise'=>$this->session->get('entreprise_session_id')])->getValue() : 0000;

        $this->queue_facturation_id = !is_null($this->rossumConfigRepository->findOneBy(['mkey'=>'facture', 'entreprise'=>$this->session->get('entreprise_session_id')])) ? $this->rossumConfigRepository->findOneBy(['mkey'=>'facture', 'entreprise'=>$this->session->get('entreprise_session_id')])->getValue() : 0000 ;

        $this->queue_devis_fournisseur = !is_null($this->rossumConfigRepository->findOneBy(['mkey'=>'devis_fournisseur', 'entreprise'=>$this->session->get('entreprise_session_id')])) ? $this->rossumConfigRepository->findOneBy(['mkey'=>'devis_fournisseur', 'entreprise'=>$this->session->get('entreprise_session_id')])->getValue() : 0000 ;

        $this->queue_devis_client = !is_null($this->rossumConfigRepository->findOneBy(['mkey'=>'devis_client', 'entreprise'=>$this->session->get('entreprise_session_id')])) ? $this->rossumConfigRepository->findOneBy(['mkey'=>'devis_client', 'entreprise'=>$this->session->get('entreprise_session_id')])->getValue() : 0000 ;

        $this->queue_facture_client = !is_null($this->rossumConfigRepository->findOneBy(['mkey'=>'facture_client', 'entreprise'=>$this->session->get('entreprise_session_id')])) ? $this->rossumConfigRepository->findOneBy(['mkey'=>'facture_client', 'entreprise'=>$this->session->get('entreprise_session_id')])->getValue() : 0000 ;

        $this->ROSSUM_API_ENDPOINT = $this->params->get('ROSSUM_API_ENDPOINT');
    }
    
    public function getBASEURL_OCRAPI(){
        return "https://dockeo.fr/";
    }

    public function initEmailTransport($entreprise_id = null){

        if(is_null($entreprise_id)){
            $entreprise_id = $this->session->get('entreprise_session_id');
        }

        $emailServeurMail = !is_null($this->metaConfigRepository->findOneBy(['mkey'=>'email_serveur_mail', 'entreprise'=>$entreprise_id])) ? $this->metaConfigRepository->findOneBy(['mkey'=>'email_serveur_mail', 'entreprise'=>$entreprise_id ])->getValue() : "" ;

        $passwordServeurMail = !is_null($this->metaConfigRepository->findOneBy(['mkey'=>'password_serveur_mail', 'entreprise'=>$entreprise_id])) ? $this->metaConfigRepository->findOneBy(['mkey'=>'password_serveur_mail', 'entreprise'=>$entreprise_id])->getValue() : "" ;

        $hoteServeurMail = !is_null($this->metaConfigRepository->findOneBy(['mkey'=>'hote_serveur_mail', 'entreprise'=>$entreprise_id])) ? $this->metaConfigRepository->findOneBy(['mkey'=>'hote_serveur_mail', 'entreprise'=>$entreprise_id])->getValue() : "" ;

        $portServeurMail = !is_null($this->metaConfigRepository->findOneBy(['mkey'=>'port_serveur_mail', 'entreprise'=>$entreprise_id])) ? $this->metaConfigRepository->findOneBy(['mkey'=>'port_serveur_mail', 'entreprise'=>$entreprise_id])->getValue() : "" ;

    
        if($passwordServeurMail == "" || $emailServeurMail == "" || $hoteServeurMail == "" || $portServeurMail == ""){
            return null;
        }

        $dsn = "smtp://".$emailServeurMail.":".$passwordServeurMail."@".$hoteServeurMail.":".$portServeurMail;
        $transport = Transport::fromDsn($dsn);
        $customMailer = new Mailer($transport);

        return $customMailer;
    }

    public function TAB_TOTAL_HT_TEXT(){
        $configs = $this->em->getRepository(ConfigTotal::class)->findAll();
        if(count($configs) > 0){
            $tabTotal = explode(',', $configs[0]->getHt());
            $tabTotal = array_map(function($a){
                return strtoupper($a);
            }, $tabTotal);

            return $tabTotal;
        }

        return [];
    }

    public function TAB_TOTAL_TTC_TEXT(){
        $configs = $this->em->getRepository(ConfigTotal::class)->findAll();
        if(count($configs) > 0){
            $tabTotal = explode(',', $configs[0]->getTtc());
            $tabTotal = array_map(function($a){
                return strtoupper($a);
            }, $tabTotal);

            return $tabTotal;
        }

        return [];
    }

    public function getDefaultEntrepriseMail(){
        $entreprise = $this->entrepriseRepository->find($this->session->get('entreprise_session_id'));
        $sender_email = $entreprise->getSenderMail();
        $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();

        if(is_null($sender_email)){
            $emailServeurMail = $this->metaConfigRepository->findOneBy(['mkey'=>'email_serveur_mail', 'entreprise'=>$this->session->get('entreprise_session_id')]);

            $emailServeurMail = !is_null($emailServeurMail) ? $emailServeurMail->getValue() : "gestion@fmda.fr";
            $sender_email = !is_null($sender_email) ? $sender_email : $emailServeurMail;
        }
        return ['email'=>$sender_email, 'name'=>$sender_name];
    }

    public function getQueue($module){
        switch ($module) {
            case 'paie':
                return $this->queue_paie_id;
                break;
            case 'bonLv':
                return $this->queue_bonLv_id;
                break;
            case 'facturation':
                return $this->queue_facturation_id;
                break;
            case 'devis_fournisseur':
                return $this->queue_devis_fournisseur;
                break;
            case 'devis_client':
                return $this->queue_devis_client;
                break;
            case 'facture_client':
                return $this->queue_facture_client;
                break;
            
            default:
                return "";
                break;
        }
    }

    public function getRossumFolder(){
        return array(
                'FACTURE' => 'facture',
                'BON DE LIVRAISON' => 'bon_livraison',
                'FICHE DE PAIE' => 'fiche_paie',
                'DEVIS FOURNISSEUR' => 'devis_fournisseur',
                'DEVIS CLIENT' => 'devis_client',
                'FACTURE CLIENT' => 'facture_client',
            );
    }

    public function getRossumFolder2(){
        return array(
                'FACTURATION' => 'facturation',
                'BON DE LIVRAISON' => 'bon_livraison',
                'FICHE DE PAIE' => 'paie',
                'DEVIS FOURNISSEUR' => 'devis_pro',
                'DEVIS CLIENT' => 'devis_client',
                'FACTURE CLIENT' => 'facture_client',
            );
    }



    public function saveIAField($request, $form, $document){


        $entreprise = $this->em->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $fields = $this->em->getRepository(PreferenceField::class)->findAll();

        foreach ($fields as $value) {
            if(!isset($form[$value->getIdentifiant()]))
                continue;

            $currenField = $form[$value->getIdentifiant()]->getData();
            if(!is_null($currenField) && $currenField != ""){
                $position = explode("-", $request->request->get('field')[$value->getIdentifiant()]);
                if(count($position) == 4){
                    $field = new IAZone();
                    $field->setPositionLeft($position[0]);
                    $field->setPositionTop($position[1]);
                    $field->setSizeWidth($position[2]);
                    $field->setSizeHeight($position[3]);
                    $field->setField($value);
                    $field->setDocument($document);

                    $this->em->persist($field);
                }
            }
        }

        $this->em->flush();

        return 1;
    }

    public function saveOcrField($document, $dossier, $lastOcrFile){


        $entreprise = $this->em->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));


        $tmpOcr = $this->em->getRepository(TmpOcr::class)->findBy(['dossier'=>$dossier, "filename"=>$lastOcrFile, 'entreprise'=>$entreprise, 'blocktype'=>"LINE"]);

        $coutFieldOcr = count($tmpOcr);
        for ($i=0; $i < count($tmpOcr) ; $i++) {
            $value = $tmpOcr[$i];
            $field = new OcrField();
            $field->setPositionLeft($value->getPositionLeft());
            $field->setPositionTop($value->getPositionTop());
            $field->setSizeWidth($value->getSizeWidth());
            $field->setSizeHeight($value->getSizeHeight());
            $field->setDocument($document);
            $field->setDossier($dossier);
            $field->setEntreprise($entreprise);

            $this->em->persist($field);

            if($i == 49)
                break;
        }

        $end = 50;
        if(count($tmpOcr) < 50*2){
            $end = count($tmpOcr)-50;
        }
        $end--;
        
        for ($i=count($tmpOcr)-1; $i >= 0 ; $i--) {
            $value = $tmpOcr[$i];
            $field = new OcrField();
            $field->setPositionLeft($value->getPositionLeft());
            $field->setPositionTop($value->getPositionTop());
            $field->setSizeWidth($value->getSizeWidth());
            $field->setSizeHeight($value->getSizeHeight());
            $field->setDocument($document);
            $field->setDossier($dossier);
            $field->setEntreprise($entreprise);

            $this->em->persist($field);

            if($end == 0)
                break;
            
            $end--;
        }
        

        $this->em->flush();

        return 1;
    }


    public function getTypeDocument(){
        return ["carte_btp", "carte_vitale", "contrat"];
    }

    public function getStatusRemarque(){
        return array(
                'En attente' => 'en_attente',
                'En cours' => 'en_cours',
                'Date limite' => 'date_limite',
                'Urgent' => 'urgent',
                'Refusé' => 'refuse',
                'En retard' => 'en_retard',
                'Quitus transmis' => 'quitus_transmis',
                'Garder pour memoire' => 'garder_memoire',
                'Cloturé' => 'cloture',
            );
    }

    public function getFieldKey(){
        return array(
                'document_id'=>'DOCUMENT_ID',
                'prixttc'=> 'TTC',
                'prixht'=> 'HT',
                'tva'=> 'TVA',
                'devise'=> 'DEVISE',
                'facturedAt'=> 'FACTURED_AT',
                'dueAt'=> 'DUE_AT',
                'fournisseur'=> 'FOURNISSEUR',
                'client'=> 'CLIENT',
                'utilisateur'=> 'UTILISATEUR',
                'conges_paye'=> 'FICHE_PAIE_CONGES_PAYE',
                'heure_sup_1'=> 'FICHE_PAIE_H25',
                'heure_sup_2'=> 'FICHE_PAIE_H50',
                'heure_normale'=> 'FICHE_PAIE_HNORMAL',
                'trajet'=> 'FICHE_PAIE_TRAJET',
                'panier'=> 'FICHE_PAIE_PANIER_REPAS',
                'cout_global'=> 'FICHE_PAIE_COUT_GLOBAL',
                'salaire_net'=> 'FICHE_PAIE_SALAIRE_NET',
                'date_paie'=> 'FICHE_PAIE_DATE',
                'chantier'=> 'CHANTIER',
            );
    }

    public function getModuleStatus(){
        return array(
                '1'=>'Planning',
                '2'=>'Compte rendu'
            );
    }

    public function getTypeFields(){

        return [
            'DATE_CREATION'=> 'DATE CREATION',
            'DUE_DATE'=> 'DUE DATE',
            'DOCUMENT_PDF'=> 'DOCUMENT PDF',
            'DOCUMENT_ID'=> 'DOCUMENT ID',
            'MONTANT_TTC'=> 'MONTANT TTC',
            'MONTANT_HT'=> 'MONTANT HT',
            'TVA'=> 'TVA',
            'MTVA'=> 'MTVA',
            'DATE_EXPORT_COMPTA'=> 'DATE EXPORT COMPTA',
            'EXPORT_COMPTA'=> 'EXPORT COMPTA',
            'BONLIVRAISON_VALIDATION'=> 'BONLIVRAISON VALIDATION',
            'REGLEMENT'=> 'REGLEMENT',
            'DEVIS'=> 'DEVIS',
            'NUM_DEVIS'=> 'NUM DEVIS',
            'FACTURE'=> 'FACTURE',
            'DEVIS_FOURNISSEUR'=> 'DEVIS FOURNISSEUR',
            'STATUS'=> 'STATUS',
            'LIBELLE'=> 'LIBELLE',
            'FOURNISSEUR'=> 'FOURNISSEUR',
            'LOT'=> 'LOT',
            'CHANTIER'=> 'CHANTIER',
            'TYPE'=> 'TYPE',
            'NOTE'=> 'NOTE',
            'BUDGET'=> 'BUDGET',
            'DATE_GENERATE'=> 'DATE GENERATE',
            'DUE_DATE_GENERATE'=> 'DUE DATE GENERATE',
            'CAUTION'=> 'CAUTION',
            'PASSAGE'=> 'PASSAGE',
            'CODE_COMPTA'=> 'CODE COMPTA',
            'COMPTA'=> 'COMPTA',
            'TRIABLE'=> 'TRIABLE',
            'AUTOMATIQUE'=> 'AUTO',
            'PAYE'=> 'PAYE',
            'CLIENT'=> 'CLIENT',
            'QUITTANCE'=> 'QUITTANCE',
            'ATTESTATION'=> 'ATTESTATION',
            'IMAGE'=> 'IMAGE',
            'NOM'=> 'NOM',
            'TOTAL_HEURE'=> 'TOTAL HEURES',
            'COUT_MATERIEL'=> 'COUT MATERIEL',
            'MAP'=> 'MAP',
            'SUIVIT'=> 'SUIVIT',
            'ADRESSE'=> 'ADRESSE',
            'TELEPHONE1'=> 'TELEPHONE1',
            'TELECOPIE'=> 'TELECOPIE',
            'EMAIL'=> 'EMAIL',
            'DATE_NAISSANCE'=> 'DATE NAISSANCE',
            'LIEU_NAISSANCE'=> 'LIEU NAISSANCE',
            'CODE_CLIENT'=> 'CODE CLIENT',
            'CNI'=> 'CNI',
            'CODE_FOURNISSEUR'=> 'CODE FOURNISSEUR',
        ];
    }    

    public function getFieldsPage(){

        return [
            'CHANTIER'=> 'CHANTIER',
            'BONLIVRAISON'=> 'BON DE LIVRAISON',
            'FACTURE_FOURNISSEUR'=> 'FACTURE FOURNISSEUR',
            'DEVIS_FOURNISSEUR'=> 'DEVIS FOURNISSEUR',
            'FOURNISSEUR'=> 'FOURNISSEUR',
            'FACTURE_CLIENT'=> 'FACTURE CLIENT',
            'CLIENT'=> 'CLIENT',
            'FOURNISSEUR'=> 'FOURNISSEUR',
            'CHANTIER'=> 'CHANTIER',
            'CLIENT'=> 'CLIENT',
            'DEVIS_CLIENT'=> 'DEVIS CLIENT',
        ];
    }

    function stripAccents($str) {
        return mb_strtolower(strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'));
    }

    public function getMoisFull(){
        return [
            'janvier'=>'01',
            'fevrier'=>'02',
            'mars'=>'03',
            'avril'=>'04',
            'mai'=>'05',
            'juin'=>'06',
            'juillet'=>'07',
            'aout'=>'08',
            'septembre'=>'09',
            'octobre'=>'10',
            'novembre'=>'11',
            'decembre'=>'12'
        ];
    }

    public function getJourFr(){
        return ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
    }

    public function dateFormatToString($date){
        // format date en jjmmyyy

        $date = str_replace(" ", "", trim($date));
        $dateArr = null;
        if(count(explode('/', $date)) > 0){
            $dateArr = explode('/', $date);
        }
        elseif(count(explode('-', $date)) > 0){
            $dateArr = explode('-', $date);
        }
        elseif(count(explode('.', $date)) > 0){
            $dateArr = explode('.', $date);
        }
        else
            return null;

        if(count($dateArr) == 3){
            $day = $dateArr[0];
            $mois = $dateArr[1];
            $year = $dateArr[2];
            return Carbon::parse("$year-$mois-$day")->locale('fr')->isoFormat('MMMM YYYY');
        }
        return null;
    }

    public function detectTextOrientation($dataExtract){
        $compteur = 0;

        $limitCount = (count($dataExtract) >= 50) ? 50 : count($dataExtract);

        for ($i=0; $i < $limitCount; $i++) { 
            $data = $dataExtract[$i];

            if(array_key_exists("Text", $data) && array_key_exists("BlockType", $data) && ($data['BlockType'] == "LINE" || $data['BlockType'] == "WORD")){

                if($data['Geometry']['BoundingBox']['Width'] < $data['Geometry']['BoundingBox']['Height']){
                    $compteur++;
                }             
            }
        }

        return ($compteur >= 40) ? true : false;
    }

    public function reformatIfIsDate($text){
        $tabMonth = $this->getMoisFull();
        
        if(strpos($text, "/") === false){
            $text = str_replace(".", "/", $text);
            $text = str_replace("-", "/", $text);
            $text = str_replace(" ", "/", $text);
        }
        else{
            $text = str_replace(".", "", $text);
            $text = str_replace("-", "", $text);
            $text = str_replace(" ", "", $text);
        }
        $text = str_replace("//", "/", $text);
        $text = str_replace("///", "/", $text);
        $text = str_replace(" ", "", $text);
        $dataArr = explode('/', $text);

        $dataArrFinal = [];
        if(count($dataArr) >= 2){
            $dataArrReverse = [];
            for ($j=count($dataArr)-1; $j >= 0 ; $j--) { 
                $dataArrReverse[] = $dataArr[$j];
            }
            if(array_key_exists(strtolower($dataArrReverse[1]), $tabMonth)){
                $dataArrReverse[1] = $tabMonth[strtolower($dataArrReverse[1])];
            }
            
            if(count($dataArrReverse) == 2){
                for ($i=0; $i < 2; $i++) {
                    $dataArrFinal[] = $dataArrReverse[$i];
                }
                $dataArrFinal[2] = "01";
            }
            else{
                for ($i=0; $i < 3; $i++) {
                    $dataArrFinal[] = $dataArrReverse[$i];
                }
            }
            
            if(count($dataArrFinal) ==3){
                if(!ctype_digit($dataArrFinal[0])
                   || (!ctype_digit($dataArrFinal[1]) && array_key_exists(strtolower($dataArrReverse[1]), $tabMonth)) 
                   ||!ctype_digit($dataArrFinal[2])){
                    $dataArrFinal = [];
                }
            }
        }

        $dataArrFinal = array_reverse($dataArrFinal, true);

        return $dataArrFinal;
    }

    public function rebuildDate2($text, $dateFormat = "jjmmyyyy"){

        $formattedDate = null; 
        $date = $this->reformatIfIsDate($text);
        $dateTab = [];
        for ($j=count($date)-1; $j >= 0 ; $j--) { 
            $dateTab[] = $date[$j];
        }
        if(count($dateTab) == 3){
            if(strlen($dateTab[2])  == 2)
                $dateTab[2] = "20".$year;

            $dateTab[0] = $this->pad2($dateTab[0]);
            $dateTab[1] = $this->pad2($dateTab[1]);

            $formattedDate = implode("-", $dateTab);
        }
        
        return $formattedDate;
    }

    public function rebuildDate($text, $dateFormat = "jjmmyyyy"){

        $text = str_replace(",", "", $text);
        if(strpos($text, " ") !== false)
            $text = str_replace(".", "", $text);

        if(strpos($text, "/") === false){
            $text = str_replace(".", "/", $text);
            $text = str_replace("-", "/", $text);
            $text = str_replace(" ", "/", $text);
        }
        else{
            $text = str_replace(".", "", $text);
            $text = str_replace("-", "", $text);
            $text = str_replace(" ", "", $text);
        }
        
        $tabMonth = $this->getMoisFull();
        $tabDay = $this->getJourFr();
        $dateTab = explode("/", $text);

        $formattedDate = null; 
        if(count($dateTab) >= 3){
            
            $year = $dateTab[2];
            if(strlen($year)  == 2)
                $year = "20".$year;

            $month = "";
            $day = "";
            if(in_array(strtolower($dateTab[0]), $tabDay)){
                array_shift($dateTab);
                $day = $this->pad2($dateTab[0]);
                
                $month = $this->getMonthNumByMonthText($dateTab[1]);
                if($month == "")
                    $month = $this->getMonthNumByMonthText($this->stripAccents($dateTab[1]));

            }
            else{

                $day = $this->pad2($dateTab[0]);

                $month = $this->getMonthNumByMonthText($dateTab[1]);
                if($month == "")
                    $month = $this->getMonthNumByMonthText($this->stripAccents($dateTab[1]));                   
                
                if($month == "")
                    $month = $this->pad2($dateTab[1]);
                
            }

            if($month != "" && $day != "")
                $formattedDate =  $year."-".$month."-".$day;
        } 
        
        return $formattedDate;
    }

    public function getMonthNumByMonthText($monthText){

        $tabMonth = $this->getMoisFull();

        $monthNum = "";
        $monthTextSub = substr($monthText, 0, 3);
        foreach ($tabMonth as $key => $value) {
            $subValue = substr($key, 0, 3);
            if($subValue == $monthTextSub){
                return $value;
            }
        }

        return $monthNum;
    }

    public function pad2($n) {
        return (strlen($n)  < 2 ? '0' : ''). $n;
    }


    public function hydrateEntityWithTextFieldExtract($entity, $fieldPrefence, $value, &$datas, $filename, $dossier){
        $field = $fieldPrefence->getIdentifiant();
        $type = $fieldPrefence->getType();
        switch ($field) {
            case 'document_id':
                $value = str_replace("bl", "", strtolower($value));
                $value = str_replace(":", "", strtolower($value));
                $value = str_replace("*", "", strtolower($value));
                $value = str_replace("n°", "", strtolower($value));
                $value = str_replace("du", "", strtolower($value));
                $value = str_replace("numéro", "", strtolower($value));
                $value = str_replace("numéro", "", strtolower($value));
                $value = trim($value, " ");

                $value = explode(" ", $value);
                $value = array_unique($value);
                $value = implode(" ", $value);
                $entity->setDocumentId($value);
                break;
            case 'prixttc':
                $value = $this->correctFloatValue($value);
                $entity->setPrixttc(round((float)$value, 2));
                break;
            case 'prixht':
                $value = $this->correctFloatValue($value);
                $entity->setPrixht(round((float)$value, 2));
                break;
            // case 'facturedAt':
            //     $formattedDate = $this->rebuildDate($value, $type);
            //     if(!is_null($formattedDate)){
            //         if(strtotime($formattedDate)){
            //             $entity->setFacturedAt(new \DateTime($formattedDate));
            //         }
            //     }
            //     break;
            case 'dueAt':
                $formattedDate = $this->rebuildDate($value, $type);
                if(!is_null($formattedDate)){
                    if(strtotime($formattedDate)){
                        $entity->setDueAt(new \DateTime($formattedDate));
                    }
                }
                    
                break;
            case 'tva':
                $search = $this->searchEntity($field, $value);
                if($search['status'] == 200 && array_key_exists('data', $search)){
                    $tva = $this->em->getRepository(Tva::class)->find($search['data']['tva']);
                    $entity->setTva($tva);
                }
                break;
            case 'devise':
                $search = $this->searchEntity($field, $value);
                if($search['status'] == 200 && array_key_exists('data', $search)){
                    $devise = $this->em->getRepository(Tva::class)->find($search['data']['devise']);
                    $entity->setDevise($devise);
                }
                break;
            // case 'client':
            //     $search = $this->searchEntity($field, $value);

            //     if($search['status'] == 200 && array_key_exists('data', $search)){
            //         if($search['data']['client'] != ""){
            //             $client = $this->em->getRepository(Client::class)->find($search['data']['client']);
            //             $entity->setClient($client);
            //         }
            //         if($search['data']['lot'] != ""){
            //             $lot = $this->em->getRepository(Lot::class)->find($search['data']['lot']);
            //             $entity->setLot($lot);
            //         }
            //         $datas['clientfound'] = $search['data']['clientfound'];
            //     }
            //     break;
            case 'utilisateur':
                $search = $this->searchEntity($field, $value);

                if($search['status'] == 200 && array_key_exists('data', $search)){
                    if(method_exists($entity, 'setUtilisateur')){
                        if($search['data']['utilisateur'] != ""){
                            $utilisateur = $this->utilisateurRepository->find($search['data']['utilisateur']);
                            $entity->setUtilisateur($utilisateur);
                        }
                    }
                }
                break;
            case 'conges_paye':
                $entity->setCongesPaye((float)$value);
                break;
            case 'heure_sup_1':
                $entity->setHeureSup1((float)$value);
                break;
            case 'heure_sup_2':
                $entity->setHeureSup2((float)$value);
                break;
            case 'heure_normale':
                $entity->setHeureNormale((float)$value);
                break;
            case 'trajet':
                $entity->setTrajet((float)$value);
                break;
            case 'panier':
                $entity->setPanier((float)$value);
                break;
            case 'cout_global':
                $value = $this->correctFloatValue($value);
                $entity->setCoutGlobal(round((float)$value, 2));
                break;
            case 'salaire_net':
                $value = $this->correctFloatValue($value);
                $entity->setSalaireNet(round((float)$value, 2));
                break;
            case 'date_paie':
                $value = explode(" ", $value);
                $value = array_unique($value);
                $value = implode(" ", $value);
                $value = str_replace("période", "", strtolower($value));
                $value = str_replace(":", "", strtolower($value));
                $value = str_replace("  ", "", $value);
                $entity->setDatePaie(ucfirst($value));
                break;
            default:
                break;
        }

        return $entity;
    }

    public function correctFloatValue($val){
        if($val != ""){
            $val = strtolower($val);

            if(strpos($val, ",") !== false && strpos($val, ".") !== false){
                $val = str_replace(".", '', $val);
                $val = str_replace(",", '.', $val);
            } 
            elseif(strpos($val, ",")){
                $val = str_replace(",", '.', $val);
            } 

            $val = str_replace(",", '.', $val);
            $val = str_replace("euro", '', $val);
            $val = str_replace("HT", '', strtoupper($val));
            $val = str_replace("eur", '', $val);
            $val = str_replace("€", '', $val);
            $val = str_replace(" ", '', $val);
            $val = str_replace("E", '', $val);
            $val = str_replace("e", '', $val);

            $val = (float)$val;
            return $val;
        }
    }

    public function getNewTextByPostion($left, $top, $width, $height, $dossier, $filename){
        $endWidthPosition = $left+$width;
        $endHeightPosition = $top+$height;

        $fields = $this->em->getRepository(TmpOcr::class)->getNewTextByPostion($left, $top, $endWidthPosition, $endHeightPosition, $dossier, $filename);

        $tabText = [];
        foreach ($fields as $value) {
            $tabText[] = $value['description'];

        }
        $newText = implode(" ", $tabText);

        return $newText;
    }

    public function lancerIa($lastOcrFile, $entity, $dossier, $dirLandingImg, $entreprise = null){

        if(is_null($entreprise)){
            if($this->session->get('entreprise_session_id'))
                $entreprise = $this->entrepriseRepository->find($this->session->get('entreprise_session_id'));
        }

        $resultIa = $this->getResultIaLaunch($lastOcrFile, $dossier, $dirLandingImg, $entreprise);

        if(is_null($resultIa)){
            return null;
        }

        $documentId = $resultIa['document_id'];
        $score = $resultIa['score'];

        $fieldsExtract = [];
        $document = null;
        $fournisseurfound = []; $clientfound = []; $chantierfound = []; $tvaVal = 0; $color = "orange";

        $entityResult = $this->hydrateEntityWithDocumentPositionText($entity, $lastOcrFile, $dossier, $documentId, $entreprise);

        $oldDate = 0;
        if(!is_null($documentId)){
            $fieldsExtract =  $this->em->getRepository(IAZone::class)->findBy(['document'=> $documentId]);
            $document = $this->em->getRepository(ModelDocument::class)->find($documentId); 
            $entity = $entityResult[$dossier];
            $fournisseurfound = $entityResult['fournisseurfound']; 
            $clientfound = $entityResult['clientfound'];
            $chantierfound = $entityResult['chantierfound'];
            $tvaVal = $entityResult['tvaVal'];
            $color = $entityResult['color'];
            $oldDate = $entityResult['oldDate'];
        }

        $tmpOcr = $this->em->getRepository(TmpOcr::class)->findBy(['dossier'=>$dossier, "filename"=>$lastOcrFile, 'entreprise'=> $entreprise->getId(), 'blocktype'=>"WORD"]);

        $nbrPage = 0;

        $fieldsExtractArr = [];
        foreach ($fieldsExtract as $value) {
            $fieldsExtractArr[$value->getField()->getIdentifiant()] = $value->getPositionLeft().'-'.$value->getPositionTop().'-'.$value->getSizeWidth().'-'.$value->getSizeHeight();
        }

        $fieldPreference = $this->em->getRepository(PreferenceField::class)->findAll();
        $fieldPreferenceArr = [];
        foreach ($fieldPreference as $value) {
            $fieldPreferenceArr[$value->getIdentifiant()] = [
                'type'=>$value->getType(),
                'identifiant'=>$value->getIdentifiant(),
            ];
        }
        return [
            $dossier => $entity,
            'dossier'=>$dossier,
            'entity'=>$entity,
            'fieldsIaZone'=>$fieldsExtractArr,
            'fournisseurfound'=>$fournisseurfound,
            'chantierfound'=>$chantierfound,
            'clientfound'=>$clientfound,
            'modelDocument'=>$document,
            'fieldPreference'=> $fieldPreferenceArr,
            'nbrPage' => $nbrPage,
            'tmpOcr'=>$tmpOcr,
            'lastOcrFile'=>$lastOcrFile,
            'display_lot'=>true,
            'ia_launch'=>true,
            'add_mode'=>'manuel',
            'score'=>$score,
            'tvaVal'=>$tvaVal,
            'oldDate'=>$oldDate,
            'color'=>$color,
        ];
    }

    public function cronOcrIa($entreprise = null){

        if(!is_null($entreprise)){
            $documents = $this->em->getRepository(EmailDocumentPreview::class)->findBy(['execute'=>false, 'extension'=>'pdf', 'entreprise'=>$entreprise->getId()], ['id'=>"ASC"], 10);
        }
        else{
            $documents = $this->em->getRepository(EmailDocumentPreview::class)->findBy(['execute'=>false, 'extension'=>'pdf'], ['id'=>"ASC"], 10);
        }

        $documentToRotate = [];
        foreach ($documents as $value) {

                $value = $this->em->getRepository(EmailDocumentPreview::class)->find($value->getId());

                $value->setScore(0); 
                
                if($value->getIsConvert() && $value->getExecute())
                    continue;

                $datasResult = $this->launchIaDocumentAttente($value, $value->getEntreprise());
                

                if(is_null($datasResult)){
                    // $value->setScore(0); 
                    // $this->em->flush(); 
                    continue;
                }// pas besoin d'enregistrer les donnees doc email pour doc renversé car il sera retourné et relancé
                else{
                    if( array_key_exists("isRotation", $datasResult) && $datasResult['isRotation'] && array_key_exists("fournisseurfound", $datasResult) && count($datasResult['fournisseurfound']) > 0){
                        $four = $datasResult['fournisseurfound'][0];
                        $rotation = $four['rotation'];
                        // $value->setIsConvert(false);
                        // $value->setExecute(false);

                        try {
                            $this->prepareAndRotateDocument($value, $rotation);
                            $documentToRotate[] = $value;
                        } catch (Exception $e) {}

                    }
                    else
                        $this->updateEmailDocument($datasResult, $value);
                }            

        }

        foreach ($documentToRotate as $value) {
            try {
                $datasResult = $this->launchIaDocumentAttente($value, $value->getEntreprise());
                if(is_null($datasResult)){
                    continue;
                }
                else
                    $this->updateEmailDocument($datasResult, $value);

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), 1);
            }
        }

        $this->em->flush();

        return 1;
    }

    public function prepareAndRotateDocument($documentEmail, $rotation){
        $dir = "";
        switch ($documentEmail->getDossier()) {
            case 'facturation':
                $dir = "/uploads/achats/facturation";
                break;
            case 'bon_livraison':
                $dir =  "/uploads/factures";
                break;
            case 'devis_pro':
                $dir = "/uploads/devis";
                break;
            case 'facture_client':
                $dir = "/uploads/clients/factures";
                break;
            case 'devis_client':
                $dir = "/uploads/devis";
                break;
            case 'paie':
                $dir = "/uploads/paies";
                break;
            default:
                $dir = "";
                break;
        }

        $dir = $this->params->get('kernel.project_dir') . "/public".$dir;
        $pdf = $documentEmail->getDocument();
        //rotatation and reload OCR IA
        $pdfDir = $dir.'/'.$pdf;

        try {
            $this->rotateIfPaysage($dir, $pdfDir, $rotation);
            $imagename = strtolower($pdf);
            $imagename = str_replace(".pdf", '.jpg', $imagename);

            $this->em->getRepository(TmpOcr::class)->removesAll($documentEmail->getDossier(), $imagename);

            // TODO SEND FILE PDF ROTATE (Sauvegarde du doc jpg dans le dossier du module)

        } catch (Exception $e) {}

        return $documentEmail;
    }

    public function launchIaDocumentAttente($entity, $entreprise = null){
        
        $value = $entity;
        if(is_null($entreprise)){
            $entreprise = $value->getEntreprise();
            $this->session->set('entreprise_session_id', $value->getEntreprise()->getId());
        }

        $newFilename = $value->getDocument();
        $name_array = explode('.',$newFilename);
        $file_type=$name_array[sizeof($name_array)-1];
        $nameWithoutExt = str_replace(".".$file_type, "", $newFilename);
        $imagenameSaved = $nameWithoutExt . '.jpg';

        $path = "";
        $type = $value->getDossier();
        switch ($value->getDossier()) {
            case 'facturation':
                $path = "uploads/achats/facturation/";
                $value->setType($type);
                break;
            case 'bon_livraison':
                $path =  "uploads/factures/";
                $value->setType($type);
                break;
            case 'devis_pro':
                $path = "uploads/devis/";
                $value->setType($type);
                break;
            case 'facture_client':
                $path = "uploads/clients/factures/";
                $type = "facture";
                $value->setType($type);
                break;
            case 'devis_client':
                $path = "uploads/devis/";
                $value->setType($type);
                break;
            case 'paie':
                $path = "uploads/paies/";
                break;
            default:
                return null;
                break;
        }

        $landingFilejpgSaved = $this->convertPdfToImage2($path, $newFilename, $imagenameSaved);

        // TODO SEND FILE (Sauvegarde du doc jpg dans le dossier du module)
        if($landingFilejpgSaved != ""){

        }

        $entity->setIsConvert(true); 

        $isRotation = $this->saveOcrScan($path, $imagenameSaved, $value->getDossier(), false, $entreprise)['isRotation'];
        
        $entity->setExecute(true); 

        $this->em->flush(); 

        $dirLandingImg = $this->params->get('kernel.project_dir') . "/public/".$path.$imagenameSaved;

        try{
            $datasResult = $this->lancerIa($imagenameSaved, $value, $value->getDossier(), $dirLandingImg, $entreprise);
        } catch (\Exception $e) {
            throw new \Exception("Probleme rencontré lors du lancement de l'IA", 1);   
        }
        

        if(!is_null($datasResult))
            $datasResult['isRotation'] = $isRotation;

        return $datasResult;
    }

    public function rotateIfPaysage($dir, $file, $angle){
        if (file_exists($file)) {
            $ilovepdf = new Ilovepdf('project_public_931609e2e0edbf50c10eaeb858e470f3_fsjDZdb3b9cdf88dab190f9056c68e18de17a','secret_key_2b723a927cff7ba74a71d1fdfad73a9e_wF2qzfa225b5b9be2dbcc23bc29ba9b4c0722');
            $pdf = new Fpdi();
            $pdf->setSourceFile($file);

            for($i=1;$i<=1;$i++){ 
                $tpl = $pdf->importPage($i); 
                $size = $pdf->getTemplateSize($tpl); 
                $pdf->addPage(); 
                $pdf->useTemplate($tpl); 
                //Put the watermark 

                $myTask = $ilovepdf->newTask('rotate');
                $file1 = $myTask->addFile($file);
                $file1->setRotation($angle);
                $myTask->execute();
                $myTask->setOutputFilename("test");
                $myTask->download($dir);
            } 

            return $file;
        }

        return "";
    }

    public function updateEmailDocument($datas, $document){

        if(!is_null($datas)){
            $entity = $datas[$datas['dossier']];
            $entity->setRotation($datas['isRotation']);     
            $entity->setScore($datas['score']);     
            $entity->setExecute(true);      

            $imagename = $entity->getDocument();
            $imagename = str_replace(".pdf", '.jpg', $imagename);
            $imagename = str_replace(".PDF", '.jpg', $imagename);

            //$this->em->getRepository(TmpOcr::class)->removesAll($entity->getDossier(), $imagename);
        }
        else{
            $document->setScore(0);     
            $document->setIsRotation($datas['isRotation']);     
            $document->setExecute(true);    

            $imagename =  $document->getDocument();
            $imagename = str_replace(".pdf", '.jpg', $imagename);
            $imagename = str_replace(".PDF", '.jpg', $imagename);

            //$this->em->getRepository(TmpOcr::class)->removesAll($document->getDossier(), $imagename);
        }

        $this->em->flush(); 

        return 1;
    }

    public function groupTextByPosition($request, $lastOcrFile, $dossier){
        $left = round((float)$request->request->get('left'), 1);
        $top = round((float)$request->request->get('top'), 1);
        $width = round((float)$request->request->get('width'), 1);
        $height = round((float)$request->request->get('height'), 1);
        $typeField = $request->request->get('type_field');
        $fieldname = $request->request->get('fieldname');

        $newText = $this->getNewTextByPostion($left, $top, $width, $height, $dossier, $lastOcrFile);
        
        $newText = str_replace("*", "", strtolower($newText));
        $newText = str_replace("bl", "", strtolower($newText));
        $newText = str_replace(":", "", strtolower($newText));
        $newText = str_replace("n°", "", strtolower($newText));
        $newText = str_replace("du", "", strtolower($newText));
        $newText = str_replace("numéro", "", strtolower($newText));
        $newText = trim($newText, " ");

        if($request->request->get('fieldname') == "date_paie" || $request->request->get('fieldname') == "document_id" || $request->request->get('fieldname') == "facturedAt"){
            $newText = explode(" ", $newText);
            $newText = array_unique($newText);
            $newText = implode(" ", $newText);
        }
        if($request->request->get('fieldname') == "cout_global" || $request->request->get('fieldname') == "salaire_net"){
            $newText = round((float)$newText, 2);
        }

        $datas = [];
        $listFields = $this->getListEntityField();
        if(in_array($fieldname, $listFields)){
            $datas = $this->searchEntity($fieldname, $newText);
        }
        else{
            $datas["status"] = 200;
            $datas["data"] = $newText;
        }

        return $datas;
    }

    public function searchEntity($field, $value, $filename = "", $dossier = ""){
        if( ($field == "chantier") && !empty($value)) {
            $chantierfound = $this->findByNameAlpn($value, "chantier");
            if(count($chantierfound) == 0){
                $datas = ['status'=>500, 'issue'=>'chantier', 'sender_name'=>$value, "message"=>"Le chantier ".$value." n'existe pas"];
                return $datas;
            }
            else{
                $chantier = $this->em->getRepository(Chantier::class)->find($chantierfound[0]['id']);
                return ['status'=>200, 'data'=>['chantier'=>$chantier->getChantierId()]];
            }
        }
        if($field == "client"){
            if(!empty($value)){
                $clientfound = $this->findByNameAlpn($value, "client");
                if(count($clientfound) == 0){
                    $datas = ['status'=>500, 'issue'=>'client', 'sender_name'=>$value, "message"=>"Le client ".$value." n'existe pas"];

                    return $datas;
                }
                else{
                    $datas = ["client"=>"", "lot"=>"", 'clientfound'=>$clientfound];
                    $client = $this->em->getRepository(Client::class)->find($clientfound[0]['id']);

                    if(!is_null($client)){
                        $datas["client"] = $client->getId();
                    }

                    return ['status'=>200, 'data'=>$datas];
                }

            }
            else{
                $datas = ['status'=>500, 'issue'=>'client', 'sender_name'=>"", "message"=>"Vous devez fournir un nom de client"];
                return $datas;
            }
        }
        if($field == "utilisateur"){
            if(!empty($value)){
                $sender_name = strtolower($value);
                $sender_name = str_replace("monsieur ", "", $sender_name);
                $sender_name = str_replace("madame ", "", $sender_name);
                $utilisateur = $this->utilisateurRepository->getOneLikeName($sender_name);
 
                if(is_null($utilisateur)){
                    $datas = ['status'=>500, 'issue'=>'utilisateur', 'sender_name'=>$value, "message"=>"L'utilisateur ".$value." n'existe pas"];

                    return $datas;
                }
                else{
                    $datas = ["utilisateur"=>$utilisateur->getUid()];

                    return ['status'=>200, 'data'=>$datas];
                }

            }
            else{
                $datas = ['status'=>500, 'issue'=>'utilisateur', 'sender_name'=>"", "message"=>"Vous devez fournir un nom d'utilisateur"];
                return $datas;
            }
        }
        if( ($field == "tva") && !empty($value)){
            $tva = $this->em->getRepository(Tva::class)->findOneBy(["valeur"=>(float)$value]);
            if(!is_null($tva)){
                return ['status'=>200, 'data'=>['tva'=>$tva->getId()]];
            }
        }
        if($field == "devise"){
            $devise = $this->em->getRepository(Devise::class)->findOneBy(["nom"=>$value]);
            if(!is_null($devise)){
                return ['status'=>200, 'data'=>['devise'=>$devise->getId()]];
            }
        }
        return ['status'=>200, 'data'=>[]];
    }

    public function getType(){
        return array(
                'STRING' => 'STRING',
                'FLOAT' => 'FLOAT',
                'jjmmyyyy' => 'jjmmyyyy',
                'jjmmyy' => 'jjmmyy'
            );
    }

    public function getRossumFolderReverse(){
        $dossiers = $this->getRossumFolder();
        $dossiersReverse = array();
        foreach ($dossiers as $key => $value) {
            $dossiersReverse[$value] = $key; 
        }
        return $dossiersReverse;
    }

    // public function findAllFournisseurInTmpOcr($entityName, $entity, $filename, $dossier){
    //     if(strlen($entityName) > 255)
    //         return [];
 
    //     $entityName = str_replace("'", "", $entityName);
    //     $fournisseurArr = [];
    //     if($entity == "fournisseur"){

    //         $fournisseurs = $this->em->getRepository(Fournisseurs::class)->getByEntrepriseOdreByBl(['entreprise'=>$this->session->get('entreprise_session_id')]);

    //         $firstEltDocument = $this->em->getRepository(OcrField::class)->getFirstEltDocument($dossier, $this->session->get('entreprise_session_id'), $filename);
    //         foreach ($fournisseurs as $value) {
    //             if(strtolower($value['nom']) == 'a definir' || strtolower($value['nom']) == 'fmda construction')
    //                 continue;

    //             $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $value['nom'], $firstEltDocument['id']);

    //             /* autre tentative avec recherche sur les deux premiers mots */
    //             if(count($entityfound)==0){
    //                 $fournisseurName = str_replace("-", '', $value['nom']);
    //                 $fournisseurName = str_replace("  ", '', $fournisseurName);
    //                 $fournisseurNameArr = explode(' ', $fournisseurName);
    //                 if(count($fournisseurNameArr) >= 2){
    //                     $fournisseurName = $fournisseurNameArr[0].' '.$fournisseurNameArr[1];
    //                 }
    //                 $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpn($dossier, $this->session->get('entreprise_session_id'), $filename, $fournisseurName, $firstEltDocument['id']);
    //             }

    //             if(count($entityfound) > 0){
    //                 foreach ($entityfound as $val) {
    //                     if(array_search($value['id'], array_column($fournisseurArr, 'id')) === false) {
    //                         $fournisseurArr[] = ['id'=>$value['id'], 'nom'=>$value['nom']];
    //                     }
    //                 }
    //                 break;
    //             }
    //         }
    //     }
    //     return $fournisseurArr;
    // }

    public function findByNameAlpn($entityName, $entity){
        if(strlen($entityName) > 255)
            return [];
 
        $entityName = str_replace("'", "", $entityName);
        $fournisseurArr = [];
        if($entity == "fournisseur")
            $entityfound = $this->fournisseurRepository->findByNameAlpn($entityName);
        elseif($entity == "chantier")
            $entityfound = $this->chantierRepository->findByNameAlpn($entityName);
        elseif($entity == "client")
            $entityfound = $this->clientRepository->findByNameAlpn($entityName);

        if(count($entityfound) > 0){
            foreach ($entityfound as $value) {
                $fournisseurArr[] = $value;
            }
        }
        else{
            //$entityNameAlpn = str_replace("  ", " ", preg_replace("/[^a-zA-Z0-9]+/", " ", $entityName));
            $entityNameAlpn = $entityName;
            $fournisseurTmpArr = explode(" ", $entityNameAlpn);
            $fournisseurTmpArrCpy = $fournisseurTmpArr;
            while (count($fournisseurTmpArr) > 0) {

                if(strlen(implode(" ", $fournisseurTmpArr)) < 4 ){
                    array_pop($fournisseurTmpArr);
                    continue;
                }

                if($entity == "fournisseur"){
                    $entityfound = $this->fournisseurRepository->findByNameAlpn(implode(" ", $fournisseurTmpArr));
                }
                elseif($entity == "chantier"){
                    $entityfound = $this->chantierRepository->findByNameAlpn(implode(" ", $fournisseurTmpArr));
                }
                elseif($entity == "client"){
                    $entityfound = $this->clientRepository->findByNameAlpn(implode(" ", $fournisseurTmpArr));
                }
                if(count($entityfound) > 0){
                    foreach ($entityfound as $value){
                        $fournisseurArr[] = $value;
                    }
                    //break;
                }
                array_pop($fournisseurTmpArr);
            }   
            if(count($fournisseurArr) == 0){
                foreach ($fournisseurTmpArrCpy as $value) {
                    if(strlen($value) < 4 )
                        continue;
                    
                    if($entity == "fournisseur"){
                        $entityfound = $this->fournisseurRepository->findByNameAlpn($value);
                    }
                    elseif($entity == "chantier"){
                        $entityfound = $this->chantierRepository->findByNameAlpn($value);
                    }
                    elseif($entity == "client"){
                        $entityfound = $this->clientRepository->findByNameAlpn($value);
                    }

                    if(count($entityfound) > 0){
                        foreach ($entityfound as $value){
                            $fournisseurArr[] = $value;
                        }
                        //break;
                    }
                }
            }        
        }
        return $fournisseurArr;
    }

    public function getUserByMiniature($utilisateurs){
        $utilisateursArr = [];
        foreach ($utilisateurs as $value) {
            if(!is_null($value['image'])){
                $utilisateursArr[$value['uid']] = $this->compressbase64(str_replace("data:image/jpeg;base64,", "", $value['image']), 0.5);
            }
            else
                $utilisateursArr[$value['uid']] = $value['image'];
        }
        return $utilisateursArr;
    }

    public function miniatureImage($image){

        return $this->compressbase64(str_replace("data:image/jpeg;base64,", "", $image), 0.5);
        
    }

    public function calculTva($ttc, $ht){
        $ttc = is_null($ttc) ? 0 : $ttc;
        $ht = is_null($ht) ? 0 :  $ht;
        $dividende = (is_null($ht) || $ht == 0) ? 1  : $ht;
        $partE = (int)(($ttc - $ht) / $dividende * 100);
        $partD = (($ttc - $ht) / $dividende * 100) - $partE;
        if($partE == 0)
            return 0;
        else{
            if($partE == 5  && ($partD >= 0.2 && $partD <= 0.8))
               return 5.5;
            elseif( $partE == 20  and ($partD <= 0.3) )
                return 20;
            elseif( $partE == 19  and ($partD >= 0.7) )
                return 20;
            elseif( $partE == 10  and ($partD <= 0.3) )
                return 10;
            elseif( $partE == 9  and ($partD >= 0.3) )
                return 10;
            else
                return  number_format((($ttc - $ht) / $dividende * 100), 2, ',', '.');
        }
    }

    public function saveImage($uploadedFile,$dir, $nom = null){
        $destination = $this->params->get('kernel.project_dir').$dir;
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $name_file = $uploadedFile->getClientOriginalName();
        $sanitize_file = preg_replace('|[^-\\\\a-z0-9~+_.?\[\]^#=!&;,/:%@$\|*`\'<>"()\x80-\xff{}]|i', '', $name_file);

        $name_array = explode('.',$sanitize_file);
        $file_type=$name_array[sizeof($name_array)-1];

        if(is_null($nom)){
            $nameWithoutExt = str_replace(".".$file_type, "", $sanitize_file);
            $newFilename = $nameWithoutExt.'_'.uniqid().'.'.$file_type;
        }
        else{
            $nameWithoutExt = $nom;
            $newFilename = $nameWithoutExt.'.'.$file_type;
        }

        
        $uploadedFile->move(
            $destination,
            $destination.$newFilename
        );
        return $newFilename;
    }

    public function getLoginToken($verbose = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->ROSSUM_API_ENDPOINT.'auth/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            json_encode(array(
            'username' => $this->username,
            'password' => $this->password,
        )));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw_response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $response = json_decode($raw_response, true);
        if (!isset($response['key'])) {
            throw new Exception('Cannot obtain login token. Message: ' . $raw_response);
        }

        return $response['key'];
    }

    public function constructUrl($queue_id, $doc_id = null) {
        $queryParams = array(
            'format' => 'json',
            'status'=>"confirmed",
            'pageSize'=>"100"
        );
        if(!is_null($doc_id))
            $queryParams['id'] = $doc_id;

        return $this->ROSSUM_API_ENDPOINT . 'queues/' . $queue_id . '/export?' . http_build_query($queryParams);
    }

    public function makeRequest($url, $verbose = false) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: token ' . $this->getLoginToken(),
            ),
            CURLOPT_VERBOSE => $verbose,
        ));
        $raw_response = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw_response);
    }

    public function makeRequestPost($url, $verbose = false) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: token ' . $this->getLoginToken(),
            ),
            CURLOPT_VERBOSE => $verbose,
        ));
        $raw_response = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw_response);
    }

    public function deleteDocument($rossum_document_id){
        $urlDelete = $this->ROSSUM_API_ENDPOINT . 'annotations/' . $rossum_document_id . '/delete';
        $this->makeRequestPost($urlDelete);
        return 1;
    }

    public function send_mail($sujet, $contenu, $destinateur = null, $destinataire, $arr = null){
        $destinateur = is_null($destinateur) ? ['mdistri@gmail.com' => 'mdistri@gmail.com'] : $destinateur;
        try {
            $mail = (new \Swift_Message($sujet))
                ->setFrom($destinateur)
                ->setTo($destinataire)
                ->setCc(['alexngoumo.an@gmail.com'])
                ->setBody($contenu, 'text/html');
            if(!empty($arr['piecejointe'])){
                foreach ($arr['piecejointe'] as $value) {
                    $mail->attach(\Swift_Attachment::fromPath($value));
                }
            }

           $this->mailer->send($mail);
           return true;
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    public function downloadExternalFile($urlFile, $dirSave, $filenamedownloaded, $extension){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlFile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $headers = array();
        if(strtolower($extension) == "pdf"){
            $headers[] = 'Content-Disposition: attachment; filename=file.pdf';
            $headers[] = 'Content-Type: application/pdf';   
        }
        else{
            $headers[] = 'Content-Disposition: attachment; filename=file.png';
            $headers[] = 'Content-Type: image/*';
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        file_put_contents($dirSave.$filenamedownloaded, $result);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
    }

    public function retrieveDocFile($doc_file, $doc_file_name, $path = 'uploads/paies/')
    {   
        $name_array = explode('.',$doc_file_name);
        $file_type=$name_array[sizeof($name_array)-1];
        $nameWithoutExt = str_replace(".".$file_type, "", $doc_file_name);
        $newFilename = $nameWithoutExt.'-'.md5(time() . mt_rand(1,100000)).".".$file_type;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $doc_file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        $headers = array();
        $headers[] = 'Authorization: token '.$this->getLoginToken();
        if(strtolower($file_type) == "pdf"){
            $headers[] = 'Content-Disposition: attachment; filename=file.pdf';
            $headers[] = 'Content-Type: application/pdf';   
        }
        else{
            $headers[] = 'Content-Disposition: attachment; filename=file.png';
            $headers[] = 'Content-Type: image/*';
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        file_put_contents($path.$newFilename, $result);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $newFilename;
    }

    public function listRossumDoc($annotations, $achats){

        $achatsArr = [];
        if($achats){
            foreach ($achats as $value) {
                $achatsArr[] = $value['rossum_document_id'];
            }
        }
        $annotationsArr = [];
        foreach ($annotations as $annotation){
            $data = ['document_id'=>"", 'fournisseur'=>"", "sender_name"=>"", "status"=>"", "date_issue"=>"", "amount_total_base"=>"0", 'date_month'=>"", "cout_global"=>"0", 'chantier_name'=>""];
            $url_document = explode('/', $annotation->url);
            $data['document_id'] = end($url_document);

            /* ne lister ques les documents pas encore exporté en BD */
            if(in_array($data['document_id'], $achatsArr))
                continue;

            $data['status'] = $annotation->status;
            foreach ($annotation->content as $content){
                foreach ($content->children as $children) {
                    if($children->schema_id == "date_month" && !empty($children->value)){
                        $data['date_month'] = $children->value;
                    }
                    if($children->schema_id == "cout_global" && !empty($children->value)){
                        $data['cout_global'] = $children->value;
                    }

                    if( ($children->schema_id == "amount_total_base") && !empty($children->value)){
                        $data['amount_total_base'] = $children->value;
                    }
                    if($children->schema_id == "date_issue"){
                        $data['date_issue'] = $children->value;
                    }
                    if($children->schema_id == "sender_name"){
                        $data['fournisseur'] = $children->value;
                        $data['sender_name'] = $children->value;
                        //break;
                    }
                    if($children->schema_id == "recipient_name"){
                        $data['fournisseur'] = $children->value;
                        $data['sender_name'] = $children->value;
                        //break;
                    }
                    if( ($children->schema_id == "chantier") && !empty($children->value)) {
                        $chantierfound = $this->findByNameAlpn($children->value, "chantier");
                        if(count($chantierfound) > 0){
                            $chantier = $this->chantierRepository->find($chantierfound[0]['id']);
                            $data['chantier_name'] = $chantier->getNameentreprise();
                        }
                    }
                }
                /*if (!empty($data['fournisseur']))
                    break;*/
            }
            $annotationsArr[] = $data;
        }
        return $annotationsArr;
    }

    public function compressbase64($base64img, $ratio) {
        // Content type
    
        $data = base64_decode($base64img);
        $im = imagecreatefromstring($data);
        $width = imagesx($im);
        $height = imagesy($im);
        $newwidth = $width * $ratio;
        $newheight = $height * $ratio;
        $thumb = imagecreatetruecolor($newwidth, $newheight);
        imagecopyresized($thumb, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        ob_start (); 
        imagejpeg ($thumb);
        $image_data = ob_get_contents (); 
        ob_end_clean (); 

        return base64_encode($image_data); 
    }

    public function getMois()
    {
        $mois = array();
        $mois[0] = "--";
        for ($i = 1; $i <= 12; $i++) {

            $mois[$i] = ucfirst(Carbon::create()->day(1)->month($i)->locale('fr')->isoFormat('MMMM'));
        }
        return array_flip($mois);
    }

    public function getAnnee()
    {
        $years = array();
        $years['--'] = 0;
        $begin = (int)date('Y') - 2;
        $end = (int)date('Y');
        for ($annee = $begin; $annee <= $end; $annee++) {
            $years[$annee] = $annee;
        }
        return $years;
    }

    public function convertheightpercentDocument($dirImg, $size){
        list($landimgWidth, $landimgHeight) = getimagesize($dirImg);

        $width = $size*100/$landimgWidth;
        $height = $size*100/$landimgHeight;
        return [$width, $height];
    }

    public function isTmpfileOpen($dossier, $filename){
        
        $line = $this->em->getRepository(TmpOcr::class)->findOneBy(['dossier'=>$dossier, 'entreprise'=>$this->session->get('entreprise_session_id'), 'filename'=>$filename]);

        return $line;
    }

    public function saveOcrScan($dirLandingImg, $filename, $dossier, $isForm = false, $entreprise = null){

        $dir = $this->params->get('kernel.project_dir') . "/public/uploads/".$filename."/";
        $dirLandingImg = $this->params->get('kernel.project_dir') . "/public/".$dirLandingImg;
        $tmpdir = $this->params->get('kernel.project_dir') . "/public/uploads/".$filename;

        $files = [];
        if(is_dir($tmpdir))
            $files = array_diff(scandir($tmpdir), array('.', '..'));
        
        //MERGE FILES

        $nbrPage = count($files);
        $isRotation = false;
        if($nbrPage){

            list($landimgWidth, $landimgHeight) = getimagesize($dirLandingImg.$filename);

            $itemPage = 0; $isTotalHtFound = false; $isTotalTtcFound = false;

            foreach ($files as $value) {

                list($imgWidth, $imgHeight) = getimagesize($dir.$value);

                $documentPath = $tmpdir."/".$value;

                $fp_image = fopen($documentPath, 'r');
                $image = fread($fp_image, filesize($documentPath));
                fclose($fp_image);

                try {
                    $client = new \Aws\Textract\TextractClient([
                        'region' => 'us-east-1',
                        'version'  => 'latest',
                        'credentials' => array(
                            'key' => "AKIA4WXTGEBWCTPMQAXL",
                            'secret'  => "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",
                          )
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception("Erreur de connexion à l'API OCR", 1);   
                }

                $result = $client->detectDocumentText([
                    'Document' => [ // REQUIRED
                        'Bytes' => $image,
                    ],
                ]);

                $resultSummaryFields = $client->analyzeExpense ([
                    'Document' => [ // REQUIRED
                        'Bytes' => $image,
                    ],
                ]);

                $Blocks = $result['Blocks'];

                //dd($resultSummaryFields);

                if(array_key_exists("ExpenseDocuments", $resultSummaryFields)){
                    if(count($resultSummaryFields['ExpenseDocuments']) > 0)
                        $summaryFields = $resultSummaryFields["ExpenseDocuments"][0]['SummaryFields'];
                }
                else{
                    $resultSummaryFields = array_values((array) $resultSummaryFields);
                    if(count($resultSummaryFields) > 0 && array_key_exists("ExpenseDocuments", $resultSummaryFields[0])){
                        $resultSummaryFields = $resultSummaryFields[0];
                        if(count($resultSummaryFields['ExpenseDocuments']) > 0)
                            $summaryFields = $resultSummaryFields["ExpenseDocuments"][0]['SummaryFields'];
                    }
                }
                $datas = [];
                foreach ($Blocks as $data){
                    if(array_key_exists("Text", $data) && array_key_exists("BlockType", $data) && ($data['BlockType'] == "LINE" || $data['BlockType'] == "WORD")){
                        $datas[] = array(
                            "BlockType"=> $data["BlockType"],
                            "text"=>  $data["Text"],
                            "left"=> $data['Geometry']['BoundingBox']['Left']*100,
                            "top"=> $this->recalculResolution($landimgHeight, $imgHeight, $data['Geometry']['BoundingBox']['Top']*100) + (100*$itemPage)/$nbrPage,
                            "height"=>  $this->recalculResolution($landimgHeight, $imgHeight, $data['Geometry']['BoundingBox']['Height']*100),
                            "width"=>$data['Geometry']['BoundingBox']['Width']*100,
                        );                
                    }
                }

                // test sur la premiere page est suffisante
                if($itemPage == 0){
                    $isRotation = $this->detectTextOrientation($Blocks);
                }

                if(is_null($entreprise)){
                    if($this->session->get('entreprise_session_id'))
                        $entreprise = $this->entrepriseRepository->find($this->session->get('entreprise_session_id'));
                }

                foreach ($datas as $data){

                    $field = new TmpOcr();
                    $field->setName($data['text']);
                    $field->setDescription($data['text']);
                    $field->setSizeWidth($data['width']);
                    $field->setSizeHeight($data['height']);
                    $field->setSizeWidth($data['width']);
                    $field->setPositionTop($data['top']);
                    $field->setPositionLeft($data['left']);
                    $field->setDossier($dossier);
                    $field->setBlocktype($data['BlockType']);
                    $field->setEntreprise($entreprise);
                    $field->setFilename($filename);

                    $buildTotalHtIfExist = $this->buildTotalHtIfExist($summaryFields);
                    if (count($buildTotalHtIfExist) > 0 && !$isTotalHtFound) {
                        $field->setTotalHtList(implode('#', $buildTotalHtIfExist));
                        $isTotalHtFound = true;
                    }

                    $buildTotalTTCIfExist = $this->buildTotalTTCIfExist($summaryFields);
                    if(count($buildTotalTTCIfExist) > 0 && !$isTotalTtcFound){
                        $field->setTotalTtcList(implode('#', $buildTotalTTCIfExist));
                        $isTotalTtcFound = true;
                    }

                    $this->em->persist($field);
                }

                $this->em->flush();

                $itemPage++;
            }

            // if(count($tabTotalHtText) > 0)
            //     $field->setTotalHtList(implode('#', $tabTotalHtText));
            // if(count($tabTotalTtcText) > 0)
            //     $field->setTotalTtcList(implode('#', $tabTotalTtcText));

        }

        if($isForm){
            if (is_dir($dir)) {
                try {
                    $this->deleteDirectory($dir);
                } catch (\Exception $e) {
                    
                }
            }            
        }

        return ['nbrPage'=>$nbrPage, 'isRotation'=>$isRotation];
    }

    public function isOcrSave($filename, $dossier){
        $tmpOcr = $this->em->getRepository(TmpOcr::class)->findBy(['dossier'=>$dossier, "filename"=>$filename, 'entreprise'=>$this->session->get('entreprise_session_id'), 'blocktype'=>"WORD"]);

        if(count($tmpOcr) > 0)
            return true;

        return false;
    }

    public function isDocumentConvert($saveFile){
        $dir = $this->params->get('kernel.project_dir') . "/public/uploads/".$saveFile;
        if (is_dir($dir)) {
            return true;
        }
        return false;
    }

    public function recalculResolution($referent, $courant, $value ){
        return ($courant/$referent) * $value;
    }

    public function getListEntityField($dossier = null){
        $fields = ["tva", "fournisseur", "client", "devise", "utilisateur", "chantier"];

        return $fields;
    }

    // public function convertPdfToImage($dir, $filename, $saveFile){

    //     ConvertApi::setApiSecret('i9dLP37lQbkJFR19');

    //     $result = ConvertApi::convert('png', ['File' => $dir.$filename]);

    //     # save to file
    //     $result->getFile()->save($dir.$saveFile);

    //     # get file contents (without saving the file locally)
    //     //$contents = $result->getFile()->getContents();
    //     $this->make_thumb($dir.$saveFile, $dir.$saveFile, 814, 'png');

    //     return $saveFile;
    // }

    public function getExtentionFile($filename){
        $name_array = explode('.',$filename);
        $file_type=$name_array[sizeof($name_array)-1];
        return $file_type;
    }

    public function replaceExtenxionFilename($filename, $newExtension){
        $name_array = explode('.',$filename);
        $file_type=$name_array[sizeof($name_array)-1];
        $nameWithoutExt = str_replace(".".$file_type, "", $filename);

        return $nameWithoutExt . '.'.$newExtension;
    }

    /**
    * saveFile: nom du fichier sauvegardé en image utilise pour ocr
    * saveFile: nom du fichier sauvegardé en image utilise pour ocr
    */
    public function convertPdfToImage2($dirSave, $filename, $saveFile){

        try {
            $ilovepdf = new Ilovepdf('project_public_931609e2e0edbf50c10eaeb858e470f3_fsjDZdb3b9cdf88dab190f9056c68e18de17a','secret_key_2b723a927cff7ba74a71d1fdfad73a9e_wF2qzfa225b5b9be2dbcc23bc29ba9b4c0722');
        } catch (\Exception $e) {
            throw new \Exception("Erreur de connexion à l'API de conversion de pdf", 1);   
        }
        

        // chemin pour enregistrement de images indivuelle (page) issue de la converion du pdf
        $dir = $this->params->get('kernel.project_dir') . "/public/uploads/".$saveFile."/";
        try {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        } catch (FileException $e) {}

        $tmpdir = $this->params->get('kernel.project_dir') . "/public/uploads/".$saveFile;

        if(strtolower($this->getExtentionFile($filename)) != "pdf"){
            $myTask = $ilovepdf->newTask('imagepdf');
            $file1 = $myTask->addFile($this->params->get('kernel.project_dir') . "/public/".$dirSave.$filename);
            $myTask->execute();
            $myTask->setOutputFilename("test");
            $myTask->download($this->params->get('kernel.project_dir') . "/public/".$dirSave);

            $filename = $this->replaceExtenxionFilename($filename, 'pdf');
        }

        $myTask = $ilovepdf->newTask('pdfjpg');
        $file1 = $myTask->addFile($this->params->get('kernel.project_dir') . "/public/".$dirSave.$filename);
        $myTask->execute();
        $myTask->setOutputFilename("test");
        $myTask->download($tmpdir);

        // UNZIP FILE
        if (file_exists($tmpdir.'/output.zip')) {
            $zip = new ZipArchive();
            $res = $zip->open($tmpdir.'/output.zip');
            if ($res === TRUE) {
                $zip->extractTo($tmpdir);
                $zip->close();
            } 
            unlink($tmpdir.'/output.zip');
        }

        $files = [];
        if(is_dir($tmpdir))
            $files = array_diff(scandir($tmpdir), array('.', '..'));
        
        //MERGE LES FICHIERS IMAGES EN UNE SEULE POUR ENVOYER à AWS OCR
        if(count($files)){
            list($imgWidth, $imgHeight) = getimagesize($dir.array_values($files)[0]);

            $calque = imagecreatetruecolor($imgWidth, ($imgHeight*count($files)));

            $i = 0;
            foreach ($files as $value) {
                imagecopy($calque, imagecreatefromjpeg($dir.$value), 0, $imgHeight*$i, 0, 0, $imgWidth, $imgHeight);
                
                $i++;
            }
            $dirSave = $this->params->get('kernel.project_dir') . "/public/".$dirSave.$saveFile;
            imagejpeg($calque, $dirSave);
        }

        // if (is_dir($dir)) {
        //     try {
        //         $this->deleteDirectory($dir);
        //     } catch (Exception $e) {
                
        //     }
        // }

        return $saveFile;
    }


    // function deleteDirectory($dir) {
    //     if (!file_exists($dir)) {
    //         return true;
    //     }

    //     if (!is_dir($dir)) {
    //         return unlink($dir);
    //     }

    //     foreach (scandir($dir) as $item) {
    //         if ($item == '.' || $item == '..') {
    //             continue;
    //         }

    //         if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
    //             return false;
    //         }

    //     }

    //     return rmdir($dir);
    // }

    public function deleteDirectory($dir) {
        system('rm -rf -- ' . escapeshellarg($dir), $retval);
        return $retval == 0; // UNIX commands return zero on success
    }

    public function make_thumb($src, $dest, $desired_width, $extension) {

        /* read the source image */
        if(is_file($src)){
            if(strtolower($extension) == "png")
                $source_image = imagecreatefrompng($src);
            elseif(strtolower($extension) == "jpeg")
                $source_image = imagecreatefromjpeg($src);
            elseif(strtolower($extension) == "jpg")
                $source_image = imagecreatefromjpg($src);
            else
                return 1;
            $width = imagesx($source_image);
            $height = imagesy($source_image);

            /* find the "desired height" of this thumbnail, relative to the desired width  */
            $desired_height = floor($height * ($desired_width / $width));

            /* create a new, "virtual" image */
            $virtual_image = imagecreatetruecolor($desired_width, $desired_height);

            /* copy source image at a resized size */
            imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

            /* create the physical thumbnail image to its destination */
            imagejpeg($virtual_image, $dest);
        }
        
    }

    public function sortVenteByLot($ventes, $lot, $priceTarget){
        $sort1 = []; $sort2 = [];
        if(!is_null($lot)){
            foreach ($ventes as $value) {
                if(!is_null($value->getLot()) && (array_search($value->getLot()->getId(), array_column($lot, 'id')) !== false)){
                    $sort1[] = $value;
                }
                else{
                    $sort2[] = $value;
                }
            }
        }
        else{
            $sort2 = $ventes;
        }

        $sort2 = $this->sortToAmount($priceTarget , $sort2);
        $sort1 = $this->sortToAmount($priceTarget , $sort1);
        
        return array_merge($sort1, $sort2);
    }

    public function sortToAmount($target, $devis){
        $result = []; $diffSort = []; $tabDiff = [];
        foreach ($devis as $value) {
            $tabDiff[$value->getId()] = abs($value->getPrixht() - $target);
        }
        asort($tabDiff);

        foreach ($tabDiff as $key => $value) {
            $diffSort[] = $key;
        }

        foreach ($devis as $value) {
            $index = array_search($value->getId(), $diffSort);
            $result[$index] = $value;
        }

        ksort($result);
        return $result;
    }

    public function buildGenerateurLoyer(){
        $result = [];
        for ($i = -10; $i <5 ; $i++) { 
            $result[$i] = $i;
        }
        return $result;
    }
    public function utilisations(){
        $result = [
            '1'=>'Residence principale du locataire',
            '2'=>'Residence secondaire du locataire',
            '3'=>'Le locataire est autorisé à exercer son activité profesionnelle à l\'exclusion de tout activité commecial, industrielle',
        ];
        return $result;
    }

    public function getLocationType(){
        $result = [
            '1'=>"Bail d'habitation vide",
            '2'=>"Bail d'habitation meublé",
            '3'=>"Bail d'habitation étudiant",
            '4'=>"Bail mobilité",
            '5'=>"Bail location saisonnière",
            '6'=>"Bail parking / garage",
            '7'=>"Bail stockage",
            '8'=>"Bail mixte",
            '9'=>"Bail commercial",
            '10'=>"Bail profesionnel",
            '11'=>"Bail rural",
            '13'=>"Bail précaire",
            '14'=>"Bail civil",
            '15'=>"Bail domiciliation",
            '0'=>'Autre',
        ];
        return $result;
    }

    public function getLocationTypeIcone(){
        $result = [
            '1'=>"habitation-vide.png",
            '2'=>"meuble.png",
            '3'=>"etudiant.png",
            '4'=>"",
            '5'=>"saisoniere.png",
            '6'=>"parking.png",
            '7'=>"",
            '8'=>"",
            '9'=>"commerce.jpeg",
            '10'=>"pro-bail.png",
            '11'=>"",
            '13'=>"",
            '14'=>"",
            '15'=>"",
            '0'=>'Autre',
        ];
        return $result;
    }

    public function getResultIaLaunch($imgname, $dossier, $dirLandingImg, $entreprise = null){

        $tmpOcrFirst = $this->em->getRepository(TmpOcr::class)->getByParam($dossier, $imgname, "ASC", "LINE", 50, $entreprise->getId());
        
        $tmpOcrLast = [];
        //$tmpOcrLast = $this->em->getRepository(TmpOcr::class)->getByParam($dossier, $imgname, "DESC", "LINE", 50, $entreprise->getId());

        $tmpOcr = array_unique(array_merge($tmpOcrLast, $tmpOcrFirst), SORT_REGULAR);

        if(count($tmpOcr) == 0){
            return null;
        }

        $resultSearch = [];
        $tabCountFieldDocumentFound = [];

        //On ajoutera une marge de 5px de tollerance dans les recherche de text de correspondance
        $sizePercent = $this->convertheightpercentDocument($dirLandingImg, 5);

        foreach ($tmpOcr as  $value) {
            $result  = $this->em->getRepository(OcrField::class)->searchPosition($value['position_left'], $value['position_top'], $value['dossier'], $sizePercent, $entreprise->getId());
            if($result){
                $resultSearch[] = $result;
                if(!array_key_exists($result['document_id'], $tabCountFieldDocumentFound)){
                    $tabCountFieldDocumentFound[$result['document_id']] = 1;
                }
                else{
                    $tabCountFieldDocumentFound[$result['document_id']] = $tabCountFieldDocumentFound[$result['document_id']] + 1;
                }
            }
        }  

        //on recupere le document qui apparait avec la plus grande occurence dans les text
        arsort($tabCountFieldDocumentFound);

        $score = round(count($resultSearch)/count($tmpOcr), 2)*100;

        $documentId = null;
        if(count($tabCountFieldDocumentFound) && $score > 20)
            $documentId = key($tabCountFieldDocumentFound);

        return [
            'document_id'=>$documentId,
            'score'=>$score
        ];
    }

    public function hydrateEntityWithDocumentPositionText($entity, $filename, $dossier, $documentId, $entreprise = null){

        if(!is_null($documentId)){
            $modelDocument = $this->em->getRepository(ModelDocument::class)->find($documentId);
            $entity->setModelDocument($modelDocument);
        }

        if(is_null($entreprise)){
            if($this->session->get('entreprise_session_id'))
                $entreprise = $this->entrepriseRepository->find($this->session->get('entreprise_session_id'));
        }

        $datas = [];
        $fieldsExtract =  $this->em->getRepository(IAZone::class)->findBy(['document'=> $documentId]);

        $tabFieldFound = [];
        foreach ($fieldsExtract as $value) {
            $tabFieldFound[] = $value->getField()->getIdentifiant();
            $text = $this->getNewTextByPostion($value->getPositionLeft(), $value->getPositionTop(), $value->getSizeWidth(), $value->getSizeHeight(), $dossier, $filename);
                
            if($text != ""){
                $entity = $this->hydrateEntityWithTextFieldExtract($entity, $value->getField(), $text, $datas, $filename, $dossier);
            }
        }

        $fournisseurfound = [];
        $clientfound = [];
        $userfound = [];
        $chantierfound = [];


        $filenamepdf = str_replace('jpg', 'pdf', $filename);        
        $sender = $this->em->getRepository(EmailDocumentPreview::class)->findOneBy(['dossier'=>$dossier, "document"=>$filenamepdf]);
        
        $expediteurExiste = false;
        if(!is_null($sender) && ($dossier != "paie")){
            $expediteurExiste = $this->em->getRepository(Fournisseurs::class)->getWithEmailExist(strtolower($sender->getSender()), $dossier);
        }

        if($expediteurExiste){
            $fournisseurfound["1"] = [$expediteurExiste];
            $clientfound["1"] = [$expediteurExiste];
        }
        else{
            $firstEltDocument = $this->em->getRepository(OcrField::class)->getFirstEltDocument($dossier, $entreprise->getId(), $filename);

            $fournisseurs = $this->em->getRepository(Fournisseurs::class)->getByEntrepriseOdreByBl($entreprise->getId());
            $clients = $this->em->getRepository(Client::class)->findBy(['entreprise'=>$entreprise->getId()]);
            $users = $this->em->getRepository(Utilisateur::class)->findBy(['entreprise'=>$entreprise->getId()]);

            foreach ($fournisseurs as $value) {
                if(strtolower($value['nom']) == 'a definir' || strtolower($value['nom']) == 'fmda construction')
                    continue;

                $priority = "1";
                if(strpos(strtolower($value['nom']), "france") !== false){// exception pour le fournisseur france air
                    $entityfound = $this->em->getRepository(OcrField::class)->getByNameAndName2Alpn($dossier, $entreprise->getId(), $filename, $value['nom'], $firstEltDocument['id'], $value['nom2'], 30);
                }
                elseif(strpos(strtolower($value['nom']), "total") !== false){// exception pour le fournisseur france air
                    $entityfound = $this->em->getRepository(OcrField::class)->getByNameAndName2Alpn($dossier, $entreprise->getId(), $filename, $value['nom'], $firstEltDocument['id'], $value['nom2'], 30);
                }
                else{
                    $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpn($dossier, $entreprise->getId(), $filename, $value['nom'], $firstEltDocument['id'], "", 30);
                    if(count($entityfound) == 0){
                        $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpn($dossier, $entreprise->getId(), $filename, $value['nom'], $firstEltDocument['id'], $value['nom2'], 30);    
                        $priority = "2";                
                    }
                }
                    
                if(count($entityfound) > 0){
                    if(array_search($value['id'], array_column($fournisseurfound, 'id')) === false) {
                        $fournisseurfound[$priority][] = ['id'=>$value['id'], 'nom'=>$value['nom']];
                    }
                    //break;
                }
            }

            foreach ($clients as $value) {
                if(strtolower($value->getNom()) == 'a definir' || strtolower($value->getNom()) == 'fmda construction')
                    continue;

                $clientName = $value->getNom();
                if($clientName == "LORANGERIE")
                    $clientName = "L'ORANGERIE";
                
                $priority = "1";
                $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpnClient($dossier, $entreprise->getId(), $filename, $clientName, $firstEltDocument['id'], "", 30);
                
                if(count($entityfound) > 0){
                    if(array_search($value->getId(), array_column($clientfound, 'id')) === false) {
                        $clientfound[$priority][] = ['id'=>$value->getId(), 'nom'=>$value->getNom()];
                    }
                    break;
                }

            }
            
            if(count($clientfound) == 0 || ( count($clientfound) > 0 && array_key_exists("1", $clientfound) && count($clientfound["1"]) == 0)){
                foreach ($clients as $value) {
                    if(strtolower($value->getNom()) == 'a definir' || strtolower($value->getNom()) == 'fmda construction' || str_contains(strtolower($value->getNom()), 'hdbm') || str_contains(strtolower($value->getNom()), 'immo'))
                        continue;

                    $clientName = $value->getNom();
                    if($clientName == "LORANGERIE")
                        $clientName = "L'ORANGERIE";
                    
                    $priority = "2";
                    
                    $clientName = str_replace("/", "", $clientName);
                    $clientName = str_replace(".", "", $clientName);
                    $clientName = str_replace("-", "", $clientName);
                    $clientName = str_replace("SAS", "", strtoupper($clientName));
                    $clientName = str_replace("SARL", "", strtoupper($clientName));
                    $clientName = trim($clientName);

                    $tabnomClient = explode(" ", $clientName);
                    $trouve = false;
                    foreach ($tabnomClient as $nom) {
                        if(strlen($nom) >= 4){
                            $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpnClient($dossier, $entreprise->getId(), $filename, $nom, $firstEltDocument['id'], "", 30);

                            if(count($entityfound) > 0){
                                if(array_search($value->getId(), array_column($clientfound, 'id')) === false) {
                                    $clientfound[$priority][] = ['id'=>$value->getId(), 'nom'=>$value->getNom()];
                                }
                                $trouve = true;
                                break;
                            }
                        }
                    }
                    if($trouve)
                        break;
   
                }
            }

            if($dossier == "paie"){
                foreach ($users as $value) {
                    if(strtolower($value->getFirstname()) == 'a definir' || strtolower($value->getFirstname()) == 'fmda construction' || strtolower($value->getLastname()) == 'a definir' || strtolower($value->getLastname()) == 'fmda construction')
                        continue;

                    $userName = $value->getFirstname()." ".$value->getLastname();
                    $userName2 = $value->getLastname().' '.$value->getFirstname();

                    $priority = "1";
                    $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpnUser($dossier, $entreprise->getId(), $filename, strtolower($userName), strtolower($userName2), $firstEltDocument['id'], 100);
                    
                    if(count($entityfound) > 0){
                        if(array_search($value->getUid(), array_column($userfound, 'id')) === false) {
                            $userfound[$priority][] = ['id'=>$value->getUid(), 'nom'=>$value->getFirstname()." ".$value->getLastname()];
                        }
                        break;
                    }
                }
            }
        }

        $fournisseur = null;
        if(count($fournisseurfound) > 0){
            if(array_key_exists("1", $fournisseurfound) && count($fournisseurfound["1"]) > 0)
                $fournisseurfound = $fournisseurfound["1"];
            elseif(array_key_exists("2", $fournisseurfound))
                $fournisseurfound = $fournisseurfound["2"];

            $fournisseur = $this->em->getRepository(Fournisseurs::class)->find($fournisseurfound[0]['id']);
            if(method_exists($entity, 'setFournisseur')){
                $entity->setFournisseur($fournisseur);
            }

            if(method_exists($entity, 'setCodeCompta') && method_exists($entity, 'setLot')){
                $entity->setCodeCompta($fournisseur->getCodeCompta());
                $entity->setLot($fournisseur->getLot());
            }
        }

        $client = null;
        if(count($clientfound) > 0){
            if(array_key_exists("1", $clientfound) && count($clientfound["1"]) > 0)
                $clientfound = $clientfound["1"];
            elseif(array_key_exists("2", $clientfound))
                $clientfound = $clientfound["2"];

            $client = $this->em->getRepository(Client::class)->find($clientfound[0]['id']);
            if(method_exists($entity, 'setClient')){
                $entity->setClient($client);
            }

        }

        $user = null;
        if(count($userfound) > 0){
            if(array_key_exists("1", $userfound) && count($userfound["1"]) > 0)
                $userfound = $userfound["1"];
            elseif(array_key_exists("2", $userfound))
                $userfound = $userfound["2"];

            $user = $this->em->getRepository(Utilisateur::class)->find($userfound[0]['id']);
            if(method_exists($entity, 'setUtilisateur')){
                $entity->setUtilisateur($user);

            }

        }

        $totalTtcAndTotalHT = $this->em->getRepository(TmpOcr::class)->getsummaryFields($dossier, $filename, $entreprise->getId());

        if($totalTtcAndTotalHT){
            if(method_exists($entity, 'setPrixht')){
                $htExtract = $this->extractTotalHt($totalTtcAndTotalHT['total_ht_list']);

                if($htExtract)
                    $entity->setPrixht($htExtract);
            }
            if(method_exists($entity, 'setPrixttc')){
                $ttcExtract = $this->extractTotalTTC($totalTtcAndTotalHT['total_ttc_list']);

                if($ttcExtract)
                    $entity->setPrixttc($ttcExtract);
            }
        }


        $chantiers = $this->chantierRepository->getByActif($entreprise->getId());
        $firstEltDocument = $this->em->getRepository(OcrField::class)->getFirstEltDocument($dossier, $entreprise->getId(), $filename);

        $entityCompletfound = false;
        foreach ($chantiers as $value) {
            if(strtoupper($value['nameentreprise']) == 'TEST ADMIN' || strtoupper($value['nameentreprise']) == 'FORMATION')
                continue;

            $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpn($dossier, $entreprise->getId(), $filename, $value['nameentreprise'], $firstEltDocument['id'], "", 150);

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
                        $entityfound = $this->em->getRepository(OcrField::class)->getByNameAlpn($dossier, $entreprise->getId(), $filename, $val, $firstEltDocument['id'], "", 150);
                    }
                }
            }

            if(count($entityfound) > 0){
                if(array_search('information', array_column($entityfound, 'name')) === false && array_search('informations', array_column($entityfound, 'name')) === false){
                    if($entityCompletfound){
                        $chantierfound = [['id'=>$value['chantier_id'], 'name'=>$value['nameentreprise']]];
                        break;
                    }
                    $chantierfound[] = ['id'=>$value['chantier_id'], 'name'=>$value['nameentreprise']];
                }
            }
        }


        if(count($chantierfound) > 0){
            $chantier = $this->em->getRepository(Chantier::class)->find($chantierfound[0]['id']);
            if(method_exists($entity, 'setChantier')){
                $entity->setChantier($chantier);
            }
        }
    
        
        if(method_exists($entity, 'getType')){
            if($entity->getType() == "bon_livraison" && !is_null($entity->getFournisseur()) && $entity->getFournisseur()->getTypeBonLivraison() && $entity->getPrixht()){
                $entity->setPrixttc(round(($entity->getPrixht()*1.2), 2));
            }
        }
    
        $tvaVal = 0;$color = "orange";
        if(method_exists($entity, 'getPrixht')){
            $dividende = ((float)$entity->getPrixht() != 0) ? $entity->getPrixht() : 1 ;
            $tva = ( ((float)$entity->getPrixttc() - (float)$entity->getPrixht()) / $dividende )*100;
            $tva = round($tva, 2);
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

            $tva = $this->em->getRepository(Tva::class)->findOneBy(["valeur"=>$tvaVal]);
            $entity->setTva($tva);
        }


        $pdf = str_replace(".jpg", '.pdf', $filename);
        $documentsEmail = $this->em->getRepository(EmailDocumentPreview::class)->findOneBy(['entreprise'=>$entreprise->getId(), 'document'=>$pdf, 'dossier'=>$dossier]);

        if(!is_null($documentsEmail) && !is_null($documentsEmail->getFacturedAt())){
            if(method_exists($entity, 'setFacturedAt')){
                $entity->setFacturedAt($documentsEmail->getFacturedAt());
            }
        }
        else{
            $firstTmpOcrText = $this->em->getRepository(OcrField::class)->findFirstTmpOcrText($dossier, $entreprise->getId(), $filename, $firstEltDocument['id'], 300);
            foreach ($firstTmpOcrText as $value) {
                $dateSearch = str_replace('du ', "", strtolower($value['name']));
                $dateSearch = str_replace('du', "", strtolower($dateSearch));
                $formattedDate = $this->rebuildDate($dateSearch);

                if(!is_null($formattedDate)){
                    if(strtotime($formattedDate)){
                        if(method_exists($entity, 'setFacturedAt')){
                            $entity->setFacturedAt(new \DateTime($formattedDate));
                            break;
                        }
                    }
                }
            }
        }

        dd([$firstTmpOcrText]);

        if($dossier == "bon_livraison" && method_exists($entity, 'setPassageId')){
            /* couplage passage */
            $passageExist = null;

            $pasChantierId = !is_null($entity->getChantier()) ? $entity->getChantier()->getChantierId() : null;
            $pasFournisseurId = !is_null($entity->getFournisseur()) ? $entity->getFournisseur()->getId() : null;
            
            $passageExist =  $this->em->getRepository(Passage::class)->isPassage($pasChantierId, $pasFournisseurId, $entity->getFacturedAt()->format('Y-m-d'), $entreprise->getId());
            
            if($passageExist){
                $fournPassage = $this->em->getRepository(EmailDocumentPreview::class)->findByPassageId($passageExist['id']);
                if(count($fournPassage) == 0){
                    $entity->setPassageId($passageExist['id']);
                }
            }
        }

        $oldDate = 0;
        if(method_exists($entity, 'getFacturedAt')){
            if(!is_null($entity->getFacturedAt()) && $entity->getFacturedAt()->format('Y') < (new \DateTime())->format('Y')){
                $oldDate = 1;
            }
        }

        $clientfound = [];
        if(array_key_exists("clientfound", $datas))
            $clientfound = $datas["clientfound"];

        $userfound = [];
        if(array_key_exists("userfound", $datas))
            $userfound = $datas["userfound"];



        //Exception pour le champ document_id qui systematiquement recurere la position à partir du fournisseur

        if($entity->getDocumentId() != "")
            $entity->setDocumentIdSource(2);

        if(method_exists($entity, 'getFournisseur')){
            $fournisseur =  $entity->getFournisseur();
            if($fournisseur){
                
                $tabDocumentIdPosition = [];
                if($dossier == "bon_livraison"){
                    $tabDocumentIdPosition[] = $fournisseur->getDocumentIdPosition();
                    $tabDocumentIdPosition[] = $fournisseur->getDocumentIdPosition2();
                    $tabDocumentIdPosition[] = $fournisseur->getDocumentIdPosition3();
                }
                elseif($dossier == "facturation"){
                    $tabDocumentIdPosition[] = $fournisseur->getDocumentIdPositionFacture();
                    $tabDocumentIdPosition[] = $fournisseur->getDocumentIdPositionFacture2();
                    $tabDocumentIdPosition[] = $fournisseur->getDocumentIdPositionFacture3();
                }

                $tabDocumentIdText = [];
                foreach ($tabDocumentIdPosition as $valuePos) {
                
                    if($valuePos != "" && count(explode('-', $valuePos)) == 4){

                        $tabPosition = explode("-", $valuePos);

                        $text = $this->getNewTextByPostion($tabPosition[0], $tabPosition[1], $tabPosition[2], $tabPosition[3], $dossier, $filename);
                        if($text != ""){
                            $text = str_replace("*", "", strtolower($text));
                            $text = str_replace("bl", "", strtolower($text));
                            $text = str_replace(":", "", strtolower($text));
                            $text = str_replace("n°", "", strtolower($text));
                            $text = str_replace("du", "", strtolower($text));
                            $text = str_replace("numéro", "", strtolower($text));
                            $text = trim($text, " ");

                            $text = explode(" ", $text);
                            $text = array_unique($text);
                            $text = implode(" ", $text);

                            $tabDocumentIdText[$valuePos] = $text;
                        }
                    }
                }

                $text = ""; $pos = "";
                if(count($tabDocumentIdText) > 0){
                    $text = array_values($tabDocumentIdText)[0];
                    $countNbChiffre = $this->find_num_of_integers(array_values($tabDocumentIdText)[0]);
                    $pos = array_keys($tabDocumentIdText)[0];
                }
                
                foreach ($tabDocumentIdText as $keyPos => $valueText) {
                    $newCountNbChiffre = $this->find_num_of_integers($valueText);
                    if($newCountNbChiffre > $countNbChiffre){
                        $text = $valueText;
                        $countNbChiffre = $newCountNbChiffre;
                        $pos = $keyPos;
                    }
                }

                if($text != ""){
                    $entity->setDocumentId($text);
                    $entity->setDocumentIdPosition($pos);
                    $entity->setDocumentIdSource(1);
                }
            }
        }
        
        //Exception pour le champ document_id qui systematiquement recurere la position à partir du client
            
        if($dossier == "facture_client"){
            $documentIdPosition = "";

            $metaConfig =  $this->em->getRepository(MetaConfig::class)->findOneBy(['mkey'=>"document_id_position_facture", 'entreprise'=>$entity->getEntreprise()]);

            if(!is_null($metaConfig))
                $documentIdPosition = $metaConfig->getValue();

            if(!is_null($documentIdPosition) && $documentIdPosition != "" && count(explode('-', $documentIdPosition)) == 4){

                $tabPosition = explode("-", $documentIdPosition);

                $text = $this->getNewTextByPostion($tabPosition[0], $tabPosition[1], $tabPosition[2], $tabPosition[3], $dossier, $filename);
                if($text != ""){
                    $text = str_replace("*", "", strtolower($text));
                    $text = str_replace("bl", "", strtolower($text));
                    $text = str_replace(":", "", strtolower($text));
                    $text = str_replace("n°", "", strtolower($text));
                    $text = str_replace("du", "", strtolower($text));
                    $text = str_replace("numéro", "", strtolower($text));
                    $text = trim($text, " ");

                    $text = explode(" ", $text);
                    $text = array_unique($text);
                    $text = implode(" ", $text);
                    $entity->setDocumentId($text);

                    $entity->setDocumentIdSource(1);
                }
            }
        }

        //Exception pour le champ document_id qui systematiquement recurere la position à partir du client
            
        if($dossier == "devis_client"){
            $documentIdPosition = "";

            $metaConfig =  $this->em->getRepository(MetaConfig::class)->findOneBy(['mkey'=>"document_id_position_devis_client", 'entreprise'=>$entity->getEntreprise()]);

            if(!is_null($metaConfig))
                $documentIdPosition = $metaConfig->getValue();

            if(!is_null($documentIdPosition) && $documentIdPosition != "" && count(explode('-', $documentIdPosition)) == 4){

                $tabPosition = explode("-", $documentIdPosition);

                $text = $this->getNewTextByPostion($tabPosition[0], $tabPosition[1], $tabPosition[2], $tabPosition[3], $dossier, $filename);
                if($text != ""){
                    $text = str_replace("*", "", strtolower($text));
                    $text = str_replace("bl", "", strtolower($text));
                    $text = str_replace(":", "", strtolower($text));
                    $text = str_replace("n°", "", strtolower($text));
                    $text = str_replace("du", "", strtolower($text));
                    $text = str_replace("numéro", "", strtolower($text));
                    $text = trim($text, " ");

                    $text = explode(" ", $text);
                    $text = array_unique($text);
                    $text = implode(" ", $text);
                    $entity->setDocumentId($text);

                    $entity->setDocumentIdSource(1);
                }
            }
        }

                
        return [
            $dossier=>$entity,
            'fournisseurfound'=>$fournisseurfound,
            'chantierfound'=>$chantierfound,
            'tvaVal'=>round($tvaVal,2),
            'oldDate'=>$oldDate,
            'color'=>$color,
            'clientfound'=>$clientfound,
            'userfound'=>$userfound
        ];
    }


    public function find_num_of_integers($string) {
        //split the strings
        $chars = str_split($string);
        $count = 0;
        foreach($chars as $char) {
            //check if the character is a number
            if (is_numeric($char)) {
                //increment the count
                $count++;
            }
        }
        return $count;
    }


    public function getTotalTTCIfExist($summaryFields){
        foreach ($summaryFields as $value) {
            if( (array_key_exists('LabelDetection', $value) && strpos(strtoupper($value['LabelDetection']['Text']), "TOTAL") !== false) && 
            ( (strpos(strtoupper($value['LabelDetection']['Text']), "TTC") !== false) || (strpos(strtoupper($value['LabelDetection']['Text']), "T.T.C") !== false) ) ){
                return $this->correctFloatValue($value['ValueDetection']['Text']);
            }
        }

        return null;

    }

    public function getTotalHtIfExist($summaryFields){
        foreach ($summaryFields as $value) {
            if( array_key_exists('LabelDetection', $value) && 
                ((strpos(strtoupper($value['LabelDetection']['Text']), "TOTAL") !== false) || (strpos(strtoupper($value['LabelDetection']['Text']), "NET") !== false)) && 
                ((strpos(strtoupper($value['LabelDetection']['Text']), "HT") !== false) || (strpos(strtoupper($value['LabelDetection']['Text']), "H.T") !== false)) ){
                return $this->correctFloatValue($value['ValueDetection']['Text']);
            }
        }

        return null;
    }

    public function buildTotalHtIfExist($summaryFields){
        $tabText = [];
        for ($i=count($summaryFields)-1 ; $i >=0 ; $i--) { 
            $value = $summaryFields[$i];

            if( array_key_exists('LabelDetection', $value) && 
                ( (strpos(strtoupper($value['LabelDetection']['Text']), "TOTAL") !== false) || 
                    (strpos(strtoupper($value['LabelDetection']['Text']), "HT") !== false) || 
                    (strpos(strtoupper($this->stripAccents($value['LabelDetection']['Text'])), "H.T") !== false)) ){

                if(strpos(strtoupper($value['ValueDetection']['Text']), "MONTANT") === false && 
                    strpos(strtoupper($value['LabelDetection']['Text']), "MONTANT TVA") === false && 
                    strpos(strtoupper($value['LabelDetection']['Text']), "SOUS") === false && 
                    $value['ValueDetection']['Text'] != ""){
                    $tabText[] = $value['ValueDetection']['Text']."_".$value['LabelDetection']['Text'];
                }
            }
        }

        return $tabText;
    }

    public function buildTotalTTCIfExist($summaryFields){

        $tabText = [];
        for ($i=count($summaryFields)-1 ; $i >=0 ; $i--) { 
            $value = $summaryFields[$i];

            if( array_key_exists('LabelDetection', $value) && 
                ( (strpos(strtoupper($value['LabelDetection']['Text']), "TOTAL") !== false) || 
                    (strpos(strtoupper($value['LabelDetection']['Text']), "TTC") !== false) || 
                    (strpos(strtoupper($value['LabelDetection']['Text']), "T.T.C") !== false) ||
                    (strpos(strtoupper($this->stripAccents($value['LabelDetection']['Text'])), "A PAYER") !== false) ) ){

                if(strpos(strtoupper($value['ValueDetection']['Text']), "MONTANT") === false && 
                    strpos(strtoupper($value['LabelDetection']['Text']), "MONTANT TVA") === false && 
                    strpos(strtoupper($value['LabelDetection']['Text']), "SOUS") === false &&
                    $value['ValueDetection']['Text'] != ""){
                    $tabText[] = $value['ValueDetection']['Text']."_".$value['LabelDetection']['Text'];
                }
            }
        }

        return $tabText;
    }

    public function extractTotalHt($totalHtText){

        if($totalHtText){
            $totalHtText = trim(preg_replace('/\r|\n/', ' ', $totalHtText));
            $groupTotal = explode("#", $totalHtText);
            if(count($groupTotal) > 0){ 
                foreach ($groupTotal as $value) {
                    $labelVal = explode('_', $value);
                    foreach ($this->TAB_TOTAL_HT_TEXT() as $ttText) {
                        if(count($labelVal) > 0 && strpos(strtoupper($this->stripAccents($labelVal[1])), strtoupper($ttText)) !== false){
                            return $this->correctFloatValue($labelVal[0]);
                        } 
                    }
                }
            }
        }
        return null;
    }

    public function extractTotalTTC($totalTtcText){

        if($totalTtcText){
            $groupTotal = explode("#", $totalTtcText);
            if(count($groupTotal) > 0){
                foreach ($groupTotal as $value) {
                    $labelVal = explode('_', $value);
                    foreach ($this->TAB_TOTAL_TTC_TEXT() as $ttText) {
                        if(count($labelVal) > 0 && strpos(strtoupper($this->stripAccents($labelVal[1])), strtoupper($ttText)) !== false ){
                            return $this->correctFloatValue($labelVal[0]);
                        }     
                    }               
                }
            }
        }
        return null;
    }


    public function getPretEcheance(){
        $result = [
            '1'=>"Mensuelle",
            '2'=>"Trimestrielle",
            '3'=>"Annuelle",
        ];
        return $result;
    }
    public function getPretDiffusion(){
        $result = [
            '1'=>"Sans Différé",
            '2'=>"Différé Partiel (avec paiement des intérêts)",
            '3'=>"Différé Total (sans paiement des intérêts)",
        ];
        return $result;
    }

    public function getLogementType(){
        $result = [
            '1'=>"APPARTEMENT",
            '2'=>"COMMERCE",
            '3'=>"MAISON"
        ];
        return $result;
    }

    public function getEcheancePaiement(){
        return [
            '1'=>"Mensuel",
            '2'=>"Trimestriel",
            '3'=>"Semestriel",
            '4'=>"Annuel",
        ];
        return $result;
    }    
    public function getLocationReleve(){
        return [
            '1'=>"Eau",
            '2'=>"Electrcité",
            '3'=>"Gaz"
        ];
        return $result;
    }

    public function statusDevisPro(){
        return [
            'CONSULTATION'=>'CONSULTATION',
            'TRAITER'=>'TRAITER',
            'NEGOCIATION'=>'NEGOCIATION',
            'REFUSE'=>'REFUSE',
            'VALIDE'=>'VALIDE',
        ];
    }

    public function addZeroToDay($day){
        if((int)$day <= 9)
            return "0".$day;
        return $day;
    }

    public function getTabCodeCompta(){
        return [
            1=>"ACHAT",
            2=>"CHARGES"
        ];
    }    
    public function getTabFinancement(){
        return [
            1=>"ACHAT",
            2=>"Credit Bail"
        ];
    }    
    public function getVehiculeStatus(){
        return [
            1=>"PAYE",
            2=>"NON PAYE"
        ];
    }



    /**
     * Authenticates the user given it's username and password.
     * Returns the pair user_key, Session_key
     */
    public function login($username, $password) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->BASEURLSMS .
                    'login?username=' . $username .
                    '&password=' . $password);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] != 200) {
            return null;
        }

        return explode(";", $response);
    }

    /**
     * Sends an SMS message
     */
    public function sendSMS($auth, $sendSMS) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->BASEURLSMS . 'sms');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'user_key: ' . $auth[0],
            'Session_key: ' . $auth[1]
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sendSMS));
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] != 201) {
            return null;
        }

        return json_decode($response);
    }

    public function generateRandomString($length = 25)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function cronOcrImportDocument(){
        $configs = $this->em->getRepository(ConfigImapEmail::class)->findAll();
        foreach ($configs as $value) {
            $path = "";
            switch ($value->getDossier()){
                case 'facturation':
                    $path = "/uploads/achats/facturation/";
                    break;
                case 'bon_livraison':
                    $path =  "/uploads/factures/";
                    break;
                case 'devis_pro':
                    $path = "/uploads/devis/";
                    break;
                case 'facture_client':
                    $path = "/uploads/clients/factures/";
                    break;
                case 'devis_client':
                    $path = "/uploads/devis/";
                    break;
                case 'paie':
                    $path = "/uploads/paies/";
                    break;
                default:
                    break;
            }
            if($path != ""){
                $dir = $this->params->get('kernel.project_dir') .'/public'. $path;
                try{
                    $this->onlyLoadEmailDocument($value->getDossier(), $dir, $value->getEntreprise());
                    
                } catch (\Exception $e) {}
            }
        }

        return 1;
    }


    public function onlyLoadEmailDocument($dossier, $dir, $entreprise){

        $config = $this->em->getRepository(ConfigImapEmail::class)->findOneBy(['dossier'=> $dossier, 'entreprise'=>$entreprise]);

        if(is_null($config))
            return null;

        try {
                
            $hote = $config->getHote();
            $host = '{'.$hote.'/imap/ssl/novalidate-cert}INBOX';

            /* Your gmail credentials */
            $user = $config->getEmail();
            $password = $config->getPassword();
            /* Establish a IMAP connection */
            $inbox = imap_open($host, $user, $password)
            or die('unable to connect Host: ' . imap_last_error());
            /* grab emails */

        } catch (\Exception $e) {
            throw new \Exception("La connexion avec vos information IMAP a echoué. Veuillez verifier ces informations", 1);
        }

        $emails = imap_search($inbox, 'UNSEEN');
        /* if emails are returned, cycle through each... */
        if($emails) {
            /* begin output var */
            $output = '';
            /* put the newest emails on top */
            rsort($emails);
            foreach($emails as $email_number) {
                /* get information specific to this email */
                $overview = imap_fetch_overview($inbox,$email_number,0);
                $message = imap_fetchbody($inbox,$email_number,2);
                $structure = imap_fetchstructure($inbox,$email_number);
                $headers = imap_fetch_overview($inbox, $email_number, 0);

                $attachments = array();
                if(isset($structure->parts) && count($structure->parts)) {
                    for($i = 0; $i < count($structure->parts); $i++) {
                        $attachments[$i] = array(
                            'is_attachment' => false,
                            'filename' => '',
                            'name' => '',
                            'attachment' => '');

                        if($structure->parts[$i]->ifdparameters) {
                            foreach($structure->parts[$i]->dparameters as $object) {
                                if(strtolower($object->attribute) == 'filename') {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['filename'] = $object->value;
                                }
                            }
                        }
                        if($structure->parts[$i]->ifparameters) {
                            foreach($structure->parts[$i]->parameters as $object) {
                                if(strtolower($object->attribute) == 'name') {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['name'] = $object->value;
                                }
                            }
                        }
                        if($attachments[$i]['is_attachment']) {
                            $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1, FT_PEEK);
                            if($structure->parts[$i]->encoding == 3) { //3 = BASE64
                                $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                            }
                            elseif($structure->parts[$i]->encoding == 4) { //4 = QUOTED-PRINTABLE
                                $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                            }
                        }             
                    } //for($i = 0; $i < count($structure->parts); $i++)
                } //if(isset($structure->parts) && count($structure->parts))


                try {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                } catch (FileException $e) {}
            
                if(count($attachments)!=0){
                    foreach($attachments as $at){
                        if($at['is_attachment']==1){
                            $nameDecode = imap_utf8($at['filename']);
                            $name_array = explode('.', $nameDecode);
                            $file_type=$name_array[sizeof($name_array)-1];
                            $nameWithoutExt = str_replace(".".$file_type, "", $nameDecode);
                            $filename = $this->removeSpecialChar($nameWithoutExt .uniqid(). '.'.$file_type);

                            $extension = strtolower($this->getExtentionFile($filename));
                            if($extension == "pdf"){
                                file_put_contents($dir.$filename, $at['attachment']);
                                $this->saveDocument($filename, $config->getDossier(), $entreprise, $headers);
                            }
                        }
                    }
                }
            }
        } 

        /* close the connection */

        imap_close($inbox);

        return 1;
    }

    public function loadEmailDocument($dossier, $dir, $entreprise){

        $this->IS_ASYNC = true;
        $config = $this->em->getRepository(ConfigImapEmail::class)->findOneBy(['dossier'=> $dossier, 'entreprise'=>$entreprise]);

        if(is_null($config))
            return null;

        try {
            $hote = $config->getHote();
            $host = '{'.$hote.'/imap/ssl/novalidate-cert}INBOX';

            /* Your gmail credentials */
            $user = $config->getEmail();
            $password = $config->getPassword();
            /* Establish a IMAP connection */
            $inbox = imap_open($host, $user, $password)
            or die('unable to connect Host: ' . imap_last_error());
            /* grab emails */

        } catch (\Exception $e) {
            throw new \Exception("La connexion avec vos information IMAP a echoué. Veuillez verifier ces informations", 1);
        }

        try {   
            $emails = imap_search($inbox, 'UNSEEN');
            /* if emails are returned, cycle through each... */
            if($emails) {
                /* begin output var */
                $output = '';
                /* put the newest emails on top */
                rsort($emails);
                foreach($emails as $email_number) {
                    /* get information specific to this email */
                    $overview = imap_fetch_overview($inbox,$email_number,0);
                    $message = imap_fetchbody($inbox,$email_number,2);
                    $structure = imap_fetchstructure($inbox,$email_number);
                    $headers = imap_fetch_overview($inbox, $email_number, 0);

                    $attachments = array();
                    if(isset($structure->parts) && count($structure->parts)) {
                        for($i = 0; $i < count($structure->parts); $i++) {
                            $attachments[$i] = array(
                                'is_attachment' => false,
                                'filename' => '',
                                'name' => '',
                                'attachment' => '');

                            if($structure->parts[$i]->ifdparameters) {
                                foreach($structure->parts[$i]->dparameters as $object) {
                                    if(strtolower($object->attribute) == 'filename') {
                                        $attachments[$i]['is_attachment'] = true;
                                        $attachments[$i]['filename'] = $object->value;
                                    }
                                }
                            }
                            if($structure->parts[$i]->ifparameters) {
                                foreach($structure->parts[$i]->parameters as $object) {
                                    if(strtolower($object->attribute) == 'name') {
                                        $attachments[$i]['is_attachment'] = true;
                                        $attachments[$i]['name'] = $object->value;
                                    }
                                }
                            }
                            if($attachments[$i]['is_attachment']) {
                                $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1, FT_PEEK);
                                if($structure->parts[$i]->encoding == 3) { //3 = BASE64
                                    $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                                }
                                elseif($structure->parts[$i]->encoding == 4) { //4 = QUOTED-PRINTABLE
                                    $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                                }
                            }             
                        } //for($i = 0; $i < count($structure->parts); $i++)
                    } //if(isset($structure->parts) && count($structure->parts))

                    try {
                        if (!is_dir($dir)) {
                            mkdir($dir, 0777, true);
                        }
                    } catch (FileException $e) {}

                    if(count($attachments)!=0){
                        foreach($attachments as $at){
                            if($at['is_attachment']==1){
                                $nameDecode = imap_utf8($at['filename']);
                                $name_array = explode('.', $nameDecode);
                                $file_type=$name_array[sizeof($name_array)-1];
                                $nameWithoutExt = str_replace(".".$file_type, "", $nameDecode);
                                $filename = $this->removeSpecialChar($nameWithoutExt .uniqid(). '.'.$file_type);

                                $extension = strtolower($this->getExtentionFile($filename));
                                if($extension == "pdf"){
                                    file_put_contents($dir.$filename, $at['attachment']);
                                    // TODO SEND FILE (Sauvegarde du doc PDF dans le dossier du module)
                                    $this->saveDocument($filename, $config->getDossier(), $entreprise, $headers);
                                }
                            }
                        }
                    }
                }
            } 

            /* close the connection */

            imap_close($inbox);
        
        } catch (Exception $e) {
            throw new \Exception("Erreur extraction données IMAP");
        }
        
        //$this->cronOcrIa($entreprise);
        

        return 1;
    }

    public function saveDocument($filename, $dossier, $entreprise, $headers){
        
        $extension = strtolower($this->getExtentionFile($filename));

        $from = imap_utf8($headers[0]->from);
        if(strpos($from, "<") !== false){
            $fromArr = explode('<', $from);
            $from = $fromArr[1];
            $from = str_replace('>', "", $from);
        }

        $document = new EmailDocumentPreview();
        $document->setCreateAt(new \DateTime(imap_utf8($headers[0]->date)));
        $document->setSender($from);
        $document->setSujet(imap_utf8($headers[0]->subject));
        $document->setExtension($extension);
        $document->setEntreprise($entreprise);
        $document->setDocument($filename);
        $document->setDossier($dossier);
        $this->em->persist($document);

        $this->em->flush();            
        
        return 1;
    }

    public function removeEncoding($name){

        if (strtolower($this->getExtentionFile(imap_utf8($name))) != "pdf")
            return uniqid().".pdf";

        return $name;
    }

    public function removeSpecialChar($str)
    {
        $res = preg_replace('/[^A-Za-z0-9\-_.]/', '', $str);
        return $res;
    }

    public function getTypeArticles(){
         return [
            '1'=>"Simple",
            '2'=>"Nomenclature"
        ];
    }

    public function getTypeArticlesDevis(){
         return [
            '1'=>"DEVIS CLIENT",
            '2'=>"FACTURE CLIENT"
        ];
    }

    public function addColumnToEntreprise($entreprise){

        $pages = $this->em->getRepository(Page::class)->findAll();
        
        foreach ($pages as $page) {
            $columns = $page->getFields();
            foreach ($columns as $col) {
                $newVisible = new FieldsEntreprise();
                $newVisible->setColonne($col);
                $newVisible->setPage($page);
                $newVisible->setEntreprise($entreprise);

                $this->em->persist($newVisible);
            }
        }

        $this->em->flush();
        return 1;
    }

    public function addColumnToPage($page){
        
        $entreprises = $this->em->getRepository(Entreprise::class)->findAll();        
        foreach ($entreprises as $entreprise) {
            $columns = $page->getFields();
            foreach ($columns as $col) {
                $newVisible = new FieldsEntreprise();
                $newVisible->setColonne($col);
                $newVisible->setPage($page);
                $newVisible->setEntreprise($entreprise);

                $this->em->persist($newVisible);
            }
        }

        $this->em->flush();
        return 1;
    }
















    public function getCountries(){
        $countries = [
            "FR-France" => "France",
            "AF-Afghanistan" => "Afghanistan",
            "AX-Åland Islands" => "Åland Islands",
            "AL-Albania" => "Albania",
            "DZ-Algeria" => "Algeria",
            "AS-American Samoa" => "American Samoa",
            "AD-AndorrA" => "AndorrA",
            "AO-Angola" => "Angola",
            "AI-Anguilla" => "Anguilla",
            "AQ-Antarctica" => "Antarctica",
            "AG-Antigua and Barbuda" => "Antigua and Barbuda",
            "AR-Argentina" => "Argentina",
            "AM-Armenia" => "Armenia",
            "AW-Aruba" => "Aruba",
            "AU-Australia" => "Australia",
            "AT-Austria" => "Austria",
            "AZ-Azerbaijan" => "Azerbaijan",
            "BS-Bahamas" => "Bahamas",
            "BH-Bahrain" => "Bahrain",
            "BD-Bangladesh" => "Bangladesh",
            "BB-Barbados" => "Barbados",
            "BY-Belarus" => "Belarus",
            "BE-Belgium" => "Belgium",
            "BZ-Belize" => "Belize",
            "BJ-Benin" => "Benin",
            "BM-Bermuda" => "Bermuda",
            "BT-Bhutan" => "Bhutan",
            "BO-Bolivia" => "Bolivia",
            "BA-Bosnia and Herzegovina" => "Bosnia and Herzegovina",
            "BW-Botswana" => "Botswana",
            "BV-Bouvet Island" => "Bouvet Island",
            "BR-Brazil" => "Brazil",
            "IO-British Indian Ocean Territory" => "British Indian Ocean Territory",
            "BN-Brunei Darussalam" => "Brunei Darussalam",
            "BG-Bulgaria" => "Bulgaria",
            "BF-Burkina Faso" => "Burkina Faso",
            "BI-Burundi" => "Burundi",
            "KH-Cambodia" => "Cambodia",
            "CM-Cameroon" => "Cameroon",
            "CA-Canada" => "Canada",
            "CV-Cape Verde" => "Cape Verde",
            "KY-Cayman Islands" => "Cayman Islands",
            "CF-Central African Republic" => "Central African Republic",
            "TD-Chad" => "Chad",
            "CL-Chile" => "Chile",
            "CN-China" => "China",
            "CX-Christmas Island" => "Christmas Island",
            "CC-Cocos (Keeling) Islands" => "Cocos (Keeling) Islands",
            "CO-Colombia" => "Colombia",
            "KM-Comoros" => "Comoros",
            "CG-Congo" => "Congo",
            "CD-Congo, The Democratic Republic of the" => "Congo, The Democratic Republic of the",
            "CK-Cook Islands" => "Cook Islands",
            "CR-Costa Rica" => "Costa Rica",
            "CI-Cote D'Ivoire" => "Cote D'Ivoire",
            "HR-Croatia" => "Croatia",
            "CU-Cuba" => "Cuba",
            "CY-Cyprus" => "Cyprus",
            "CZ-Czech Republic" => "Czech Republic",
            "DK-Denmark" => "Denmark",
            "DJ-Djibouti" => "Djibouti",
            "DM-Dominica" => "Dominica",
            "DO-Dominican Republic" => "Dominican Republic",
            "EC-Ecuador" => "Ecuador",
            "EG-Egypt" => "Egypt",
            "SV-El Salvador" => "El Salvador",
            "GQ-Equatorial Guinea" => "Equatorial Guinea",
            "ER-Eritrea" => "Eritrea",
            "EE-Estonia" => "Estonia",
            "ET-Ethiopia" => "Ethiopia",
            "FK-Falkland Islands (Malvinas)" => "Falkland Islands (Malvinas)",
            "FO-Faroe Islands" => "Faroe Islands",
            "FJ-Fiji" => "Fiji",
            "FI-Finland" => "Finland",
            "GF-French Guiana" => "French Guiana",
            "PF-French Polynesia" => "French Polynesia",
            "TF-French Southern Territories" => "French Southern Territories",
            "GA-Gabon" => "Gabon",
            "GM-Gambia" => "Gambia",
            "GE-Georgia" => "Georgia",
            "DE-Germany" => "Germany",
            "GH-Ghana" => "Ghana",
            "GI-Gibraltar" => "Gibraltar",
            "GR-Greece" => "Greece",
            "GL-Greenland" => "Greenland",
            "GD-Grenada" => "Grenada",
            "GP-Guadeloupe" => "Guadeloupe",
            "GU-Guam" => "Guam",
            "GT-Guatemala" => "Guatemala",
            "GG-Guernsey" => "Guernsey",
            "GN-Guinea" => "Guinea",
            "GW-Guinea-Bissau" => "Guinea-Bissau",
            "GY-Guyana" => "Guyana",
            "HT-Haiti" => "Haiti",
            "HM-Heard Island and Mcdonald Islands" => "Heard Island and Mcdonald Islands",
            "VA-Holy See (Vatican City State)" => "Holy See (Vatican City State)",
            "HN-Honduras" => "Honduras",
            "HK-Hong Kong" => "Hong Kong",
            "HU-Hungary" => "Hungary",
            "IS-Iceland" => "Iceland",
            "IN-India" => "India",
            "ID-Indonesia" => "Indonesia",
            "IR-Iran, Islamic Republic Of" => "Iran, Islamic Republic Of",
            "IQ-Iraq" => "Iraq",
            "IE-Ireland" => "Ireland",
            "IM-Isle of Man" => "Isle of Man",
            "IL-Israel" => "Israel",
            "IT-Italy" => "Italy",
            "JM-Jamaica" => "Jamaica",
            "JP-Japan" => "Japan",
            "JE-Jersey" => "Jersey",
            "JO-Jordan" => "Jordan",
            "KZ-Kazakhstan" => "Kazakhstan",
            "KE-Kenya" => "Kenya",
            "KI-Kiribati" => "Kiribati",
            "KP-Korea, Democratic People'S Republic of" => "Korea, Democratic People'S Republic of",
            "KR-Korea, Republic of" => "Korea, Republic of",
            "KW-Kuwait" => "Kuwait",
            "KG-Kyrgyzstan" => "Kyrgyzstan",
            "LA-Lao People'S Democratic Republic" => "Lao People'S Democratic Republic",
            "LV-Latvia" => "Latvia",
            "LB-Lebanon" => "Lebanon",
            "LS-Lesotho" => "Lesotho",
            "LR-Liberia" => "Liberia",
            "LY-Libyan Arab Jamahiriya" => "Libyan Arab Jamahiriya",
            "LI-Liechtenstein" => "Liechtenstein",
            "LT-Lithuania" => "Lithuania",
            "LU-Luxembourg" => "Luxembourg",
            "MO-Macao" => "Macao",
            "MK-Macedonia, The Former Yugoslav Republic of" => "Macedonia, The Former Yugoslav Republic of",
            "MG-Madagascar" => "Madagascar",
            "MW-Malawi" => "Malawi",
            "MY-Malaysia" => "Malaysia",
            "MV-Maldives" => "Maldives",
            "ML-Mali" => "Mali",
            "MT-Malta" => "Malta",
            "MH-Marshall Islands" => "Marshall Islands",
            "MQ-Martinique" => "Martinique",
            "MR-Mauritania" => "Mauritania",
            "MU-Mauritius" => "Mauritius",
            "YT-Mayotte" => "Mayotte",
            "MX-Mexico" => "Mexico",
            "FM-Micronesia, Federated States of" => "Micronesia, Federated States of",
            "MD-Moldova, Republic of" => "Moldova, Republic of",
            "MC-Monaco" => "Monaco",
            "MN-Mongolia" => "Mongolia",
            "MS-Montserrat" => "Montserrat",
            "MA-Morocco" => "Morocco",
            "MZ-Mozambique" => "Mozambique",
            "MM-Myanmar" => "Myanmar",
            "NA-Namibia" => "Namibia",
            "NR-Nauru" => "Nauru",
            "NP-Nepal" => "Nepal",
            "NL-Netherlands" => "Netherlands",
            "AN-Netherlands Antilles" => "Netherlands Antilles",
            "NC-New Caledonia" => "New Caledonia",
            "NZ-New Zealand" => "New Zealand",
            "NI-Nicaragua" => "Nicaragua",
            "NE-Niger" => "Niger",
            "NG-Nigeria" => "Nigeria",
            "NU-Niue" => "Niue",
            "NF-Norfolk Island" => "Norfolk Island",
            "MP-Northern Mariana Islands" => "Northern Mariana Islands",
            "NO-Norway" => "Norway",
            "OM-Oman" => "Oman",
            "PK-Pakistan" => "Pakistan",
            "PW-Palau" => "Palau",
            "PS-Palestinian Territory, Occupied" => "Palestinian Territory, Occupied",
            "PA-Panama" => "Panama",
            "PG-Papua New Guinea" => "Papua New Guinea",
            "PY-Paraguay" => "Paraguay",
            "PE-Peru" => "Peru",
            "PH-Philippines" => "Philippines",
            "PN-Pitcairn" => "Pitcairn",
            "PL-Poland" => "Poland",
            "PT-Portugal" => "Portugal",
            "PR-Puerto Rico" => "Puerto Rico",
            "QA-Qatar" => "Qatar",
            "RE-Reunion" => "Reunion",
            "RO-Romania" => "Romania",
            "RU-Russian Federation" => "Russian Federation",
            "RW-RWANDA" => "RWANDA",
            "SH-Saint Helena" => "Saint Helena",
            "KN-Saint Kitts and Nevis" => "Saint Kitts and Nevis",
            "LC-Saint Lucia" => "Saint Lucia",
            "PM-Saint Pierre and Miquelon" => "Saint Pierre and Miquelon",
            "VC-Saint Vincent and the Grenadines" => "Saint Vincent and the Grenadines",
            "WS-Samoa" => "Samoa",
            "SM-San Marino" => "San Marino",
            "ST-Sao Tome and Principe" => "Sao Tome and Principe",
            "SA-Saudi Arabia" => "Saudi Arabia",
            "SN-Senegal" => "Senegal",
            "CS-Serbia and Montenegro" => "Serbia and Montenegro",
            "SC-Seychelles" => "Seychelles",
            "SL-Sierra Leone" => "Sierra Leone",
            "SG-Singapore" => "Singapore",
            "SK-Slovakia" => "Slovakia",
            "SI-Slovenia" => "Slovenia",
            "SB-Solomon Islands" => "Solomon Islands",
            "SO-Somalia" => "Somalia",
            "ZA-South Africa" => "South Africa",
            "GS-South Georgia and the South Sandwich Islands" => "South Georgia and the South Sandwich Islands",
            "ES-Spain" => "Spain",
            "LK-Sri Lanka" => "Sri Lanka",
            "SD-Sudan" => "Sudan",
            "SR-Suriname" => "Suriname",
            "SJ-Svalbard and Jan Mayen" => "Svalbard and Jan Mayen",
            "SZ-Swaziland" => "Swaziland",
            "SE-Sweden" => "Sweden",
            "CH-Switzerland" => "Switzerland",
            "SY-Syrian Arab Republic" => "Syrian Arab Republic",
            "TW-Taiwan, Province of China" => "Taiwan, Province of China",
            "TJ-Tajikistan" => "Tajikistan",
            "TZ-Tanzania, United Republic of" => "Tanzania, United Republic of",
            "TH-Thailand" => "Thailand",
            "TL-Timor-Leste" => "Timor-Leste",
            "TG-Togo" => "Togo",
            "TK-Tokelau" => "Tokelau",
            "TO-Tonga" => "Tonga",
            "TT-Trinidad and Tobago" => "Trinidad and Tobago",
            "TN-Tunisia" => "Tunisia",
            "TR-Turkey" => "Turkey",
            "TM-Turkmenistan" => "Turkmenistan",
            "TC-Turks and Caicos Islands" => "Turks and Caicos Islands",
            "TV-Tuvalu" => "Tuvalu",
            "UG-Uganda" => "Uganda",
            "UA-Ukraine" => "Ukraine",
            "AE-United Arab Emirates" => "United Arab Emirates",
            "GB-United Kingdom" => "United Kingdom",
            "US-United States" => "United States",
            "UM-United States Minor Outlying Islands" => "United States Minor Outlying Islands",
            "UY-Uruguay" => "Uruguay",
            "UZ-Uzbekistan" => "Uzbekistan",
            "VU-Vanuatu" => "Vanuatu",
            "VE-Venezuela" => "Venezuela",
            "VN-Viet Nam" => "Viet Nam",
            "VG-Virgin Islands, British" => "Virgin Islands, British",
            "VI-Virgin Islands, U.S." => "Virgin Islands, U.S.",
            "WF-Wallis and Futuna" => "Wallis and Futuna",
            "EH-Western Sahara" => "Western Sahara",
            "YE-Yemen" => "Yemen",
            "ZM-Zambia" => "Zambia",
            "ZW-Zimbabwe" => "Zimbabwe",
        ];
        return $countries;
    }

}