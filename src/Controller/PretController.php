<?php

namespace App\Controller;

use App\Entity\Pret;
use App\Entity\Entreprise;
use App\Entity\Amortissement;
use App\Entity\Chantier;
use App\Form\PretType;
use App\Repository\PretRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;

/**
 * @Route("/pret")
 */
class PretController extends AbstractController
{
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="pret_index", methods={"GET"})
     */
    public function index(PretRepository $pretRepository): Response
    {   

        $sumCapitalRestantDu = $pretRepository->sumCapitalRestant();
        $sumCapital = $pretRepository->sumCapital();

        return $this->render('pret/index.html.twig', [
            'echeances'=>$this->global_s->getPretEcheance(),
            'diffusions'=>$this->global_s->getPretDiffusion(),
            'prets' => $pretRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]),
            'sumCapitalRestantDu'=> $sumCapitalRestantDu ? $sumCapitalRestantDu['sum'] : 0,
            'sumCapital'=> $sumCapital ? $sumCapital['sum'] : 0
        ]);
    }

    /**
     * @Route("/amortissement/{id}", name="pret_amortissement_index", methods={"GET"})
     */
    public function amortissement(PretRepository $pretRepository, $id): Response
    {
        $pret = $pretRepository->find($id);
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

        $amortissements = $this->calculAmortissement($pret, $dateDebut, $montantPret, $duree, $taux, $tauxAssurance, $typeDiffere, $dureeDiffere, $debutPrelevementInteret, $montantEcheance1, $datePremiereEcheance, $interet1, $remboursement1)['amortissements'];


        return $this->render('pret/amortissement.html.twig', [
            'amortissements' => $amortissements,
            'pret' => $pret,
        ]);
    }


    /**
     * @Route("/amortissement-xhr", name="pret_amortissement_xhr", methods={"POST"})
     */
    public function simuleCalculAmortissementXhr(Request $request){

        $pret = $pretRepository->find($request->request->get('pret_id'));
        $dateDebut = new \DateTime($request->request->get('pret[date_deblocage]'));
        $debutPrelevementInteret = new \DateTime($request->request->get('pret[debut_prelevement_interet]'));
        $montantPret = (float)$request->request->get('pret[capital]');
        $montantEcheance1 = (float)$request->request->get('pret[montant_echeance_1]');
        $interet1 = (float)$request->request->get('pret[interet1]');
        $remboursement1 = (float)$request->request->get('pret[remboursement1]');
        $datePremiereEcheance = $request->request->get('pret[date_premiere_echeance]');
        $duree = (int)$request->request->get('pret[duree]');
        $taux = (float)$request->request->get('pret[taux]');
        $tauxAssurance = (float)$request->request->get('pret[taux_assurance]');
        $typeDiffere = (int)$request->request->get('pret[differe]');
        $dureeDiffere = (int)$request->request->get('pret[duree_differe]');

        $amortissements = $this->calculAmortissement($pret, $dateDebut, $montantPret, $duree, $taux, $tauxAssurance, $typeDiffere, $dureeDiffere, $debutPrelevementInteret, $montantEcheance1, $datePremiereEcheance, $interet1, $remboursement1)['amortissements'];


        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('pret/amortissement_content.html.twig', [
                'amortissements' => $amortissements,
                'fields'=>[]
            ]);
        
        $response = new Response(json_encode($datas));

        return $response;
    }

    public function diffDateDay($date2, $date1){
        return floor(strtotime($date1->format('Y-m-d'))/(60*60*24)) - floor(strtotime($date2->format('Y-m-d'))/(60*60*24));
    }

    public function calculAmortissement($pret, $dateDebut, $montantPret, $duree, $taux, $tauxAssurance, $typeDiffere, $dureeDiffere, $dateDebutPrelevement, $montantEcheance1, $datePremiereEcheance, $interet1, $remboursement1, $anneeLimit = null){

        $sumCapitalRestantDu = 0;
        $montantEcheanceMois = 0;
        $assuranceMois = 0;
        $totalInteret = 0;
        $dateFin = null;

        if(is_null($dateDebutPrelevement))
            $dateDebutPrelevement = new \DateTime($dateDebut->format('Y-m-d'));

        $currentDate = new \DateTime($dateDebutPrelevement->format('Y-m-d'));
        $dateDeblocage = new \DateTime($dateDebut->format('Y-m-d'));

        $amortissements = [];
        $i = 0;
        $b0 = 0;
        $capitalRestantMois = 0;
        do{
            $date = new \DateTime($currentDate->format('Y-m-d'));

            if(!is_null($dateDebutPrelevement))
                $dateDebutPrelevement = new \DateTime($dateDebutPrelevement->format('Y-m-d')); 

            if($i == 0){
                $dateDebut = $dateDebutPrelevement;
                
                $numEcheance = $this->initEcheance($dateDebut, $montantPret, $taux, $typeDiffere, $dureeDiffere, $duree);

                if( (float)$numEcheance > 0 && ($typeDiffere == 2 || $typeDiffere == 3) && $b0 < $dureeDiffere){
                    $b = "D";
                }
                else{
                    $b = $numEcheance;
                }


                $nbDay = $this->diffDateDay($dateDeblocage, $dateDebutPrelevement);

                $interet = $interet1;

                $assurance = ((float)$numEcheance > 0) ? ($montantPret*$tauxAssurance)/100/12 : "";
                $mensualiteHorsAssurance =  $this->mensualiteHorsAssurance($b, $b0, $numEcheance, $typeDiffere, $montantPret, $taux, $duree, $dureeDiffere, $interet);

                $capitalRembourse = "";

                if($date ==  $datePremiereEcheance){
                    $capitalRembourse = $remboursement1;
                }
                else{
                    if($date > $datePremiereEcheance){
                        $capitalRembourse = (float)$montantEcheance1 - (float)$interet;
                    }
                    else{
                        if((float)$numEcheance > 0 && $b == (float)$numEcheance){
                            $capitalRembourse = $mensualiteHorsAssurance - $interet;
                        }
                        else if((float)$numEcheance > 0 && $b == "D"){
                            $capitalRembourse = 0;
                        }
                    }
                }

                $mensualiteAvecAssurance = (float)$capitalRembourse + (float)$interet + (float)$assurance;

                $capitalDu = $this->calculCapitalDu($b, $numEcheance, $typeDiffere, $montantPret, $interet, $mensualiteHorsAssurance);
                $cumulInteret = ((float)$numEcheance > 0) ? $interet : "";

            }
            else{
                $lastRow = $amortissements[$i-1];
                $numEcheance = ((float)$lastRow['num_echeance'] > 0 && (float)$lastRow['num_echeance'] < $duree ) ? (float)$lastRow['num_echeance']+1 : "" ;

                if( (float)$numEcheance > 0 && ($typeDiffere == 2 || $typeDiffere == 3) && (float)$lastRow['num_echeance'] < $dureeDiffere){
                    $b = "D";
                }
                else{
                    $b = $numEcheance;
                }                

                $capitalDu = (float)$amortissements[$i-1]['capital_restant_du'] - (float)$amortissements[$i-1]['capital_rembourse'];
                $interet = ((float)$numEcheance > 0 ) ? (float)$capitalDu*$taux/100/12 : "";

                $assurance = ((float)$numEcheance > 0 ) ? $montantPret*$tauxAssurance/100/12 : "";

                $mensualiteHorsAssurance = $this->mensualiteHorsAssuranceNext($b, $lastRow['init'], $numEcheance, $typeDiffere, $montantPret, $taux, $duree, $dureeDiffere, (float)$lastRow['capital_restant_du'], (float)$lastRow['mensualite_sans_assurance'], $interet);

                $capitalRembourse = "";
                if($date ==  $datePremiereEcheance){
                    $capitalRembourse = $remboursement1;
                }
                else{
                    if($date >=  $datePremiereEcheance){
                        $capitalRembourse = (float)$montantEcheance1 - (float)$interet;
                    }
                    else{
                        if((float)$numEcheance > 0 && $b == (float)$numEcheance){
                            $capitalRembourse = $mensualiteHorsAssurance - $interet;
                        }
                        else if((float)$numEcheance > 0 && $b == "D"){
                            $capitalRembourse = 0;
                        }
                    }
                }

                if(abs($capitalDu-$capitalRembourse) <5){
                    $capitalRembourse = $capitalDu;
                }

                $mensualiteAvecAssurance = (float)$capitalRembourse + (float)$interet + (float)$assurance;

                $cumulInteret = ((float)$numEcheance > 0) ? (float)$lastRow['cumul_interet']+$interet : "";
            }

            $row = [
                //'date'=> ((float)$numEcheance > 0) ? $date: "",
                'init'=>$b,
                'date'=> $date,
                'num_echeance'=> round($numEcheance, 2),
                'mensualite_avec_assurance'=> round($mensualiteAvecAssurance, 2),
                'mensualite_sans_assurance'=> round($mensualiteHorsAssurance, 2),
                'interet'=> round($interet, 2),
                'assurance'=> round($assurance, 2),
                'capital_rembourse'=> round($capitalRembourse, 2),
                'capital_restant_du'=> round($capitalDu, 2),
                'cumul_interet'=> round($cumulInteret,2)
            ];
            $sumCapitalRestantDu += (float)$capitalDu;
            $sumCapitalRestantDu += (float)$capitalDu;
            

            if($date->format('Y-m-')."01" == (new \DateTime())->format('Y-m-')."01"){
                $capitalRestantMois = (float)$row['capital_restant_du'];
                $montantEcheanceMois = (float)$row['mensualite_avec_assurance'];
                $assuranceMois = (float)$row['assurance'];
            }

            $totalInteret += (float)$row['interet'];

            $currentDate = new \DateTime(date('Y-m-d', strtotime("+1 months", strtotime($currentDate->format('Y-m-d')))));
            $amortissements[$i] = $row;
            $i++;

            if(!is_null($anneeLimit) && (int)$currentDate->format('Y') > $anneeLimit)
                break;

        } while ( $capitalDu > 0);

        // retire le dernier element du tableau car son montant du est null
        if(is_null($anneeLimit))
            array_pop($amortissements);


        if(count($amortissements) > 0){
            $lastRow = $amortissements[count($amortissements)-1];
            if((float)$lastRow['capital_restant_du'] <= (float)$lastRow['capital_rembourse']){
                $amortissements[count($amortissements)-1]['capital_restant_du'] = $lastRow['capital_rembourse'];
            }

            if(count($amortissements)){            
                $dateFin = $amortissements[count($amortissements)-1]['date'];
            }
        }

        return [
            "amortissements"=>$amortissements, 
            "capitalRestantMois"=>round($capitalRestantMois, 2),
            "montantEcheanceMois"=>round($montantEcheanceMois, 2),
            "assuranceMois"=>round($assuranceMois, 2),
            'dateFin'=> $dateFin,
            'totalInteret'=> round((float)$totalInteret, 2),
            'sumCapitalRestantDu'=> round((float)$sumCapitalRestantDu, 2),
        ];
    }


    public function test($dateDebut, $montantPret ,$dureepret, $tauxinteret){
    
        return ($dateDebut && $montantPret > 0 && $dureepret > 0 && $tauxinteret > 0) ? "OK" : "NO";      
    }

    public function test2($typediffere, $dureediffere ,$dureepret){

        if( ($typediffere == 3 || $typediffere == 2) && $dureediffere > 0 && $dureediffere < $dureepret )
            return "OK";
        else if($typediffere == 1 && $dureediffere >= 0)
            return "OK";

        return "NO";
    }

    public function mensualiteHorsAssurance($init, $initPrev, $numEcheance, $typediffere, $montantPret, $tauxinteret, $duree, $dureeDiffere, $interet){

        $result = "";
        if( (float)$numEcheance > 0 && (float)$numEcheance == $init && $typediffere == 1 ){
            $result = ($montantPret*$tauxinteret/100)/(12*(1-pow((1+(($tauxinteret/100)/12)), -$duree)));
        }
        else if( ((float)$numEcheance > 0 && $init == $numEcheance && $typediffere == 2) || ((float)$numEcheance > 0 && $init == $numEcheance && $initPrev == "D" && $typediffere == 3) ){
            $result = ($montantPret*$tauxinteret/100)/(12*(1-pow((1+(($tauxinteret/100)/12)), (-$duree+$dureeDiffere))));
        }
        else if((float)$numEcheance > 0 && $init == "D" && $typediffere == 2){
            $result = $interet;
        }
        else if( ((float)$numEcheance > 0 && $init == "D" && $typediffere == 3) || ((float)$numEcheance > 0 && $init == $numEcheance && $initPrev != "D" && $typediffere == 3) ){
            $result = 0;
        }

        return $result;
    }    

    public function mensualiteHorsAssuranceNext($init, $initPrev, $numEcheance, $typediffere, $montantPret, $tauxinteret, $duree, $dureeDiffere, $capitalDuPrev, $mensualiteHorsAssurancePrev, $interet){

        $result = "";
        if( (float)$numEcheance > 0 && (float)$numEcheance == $init && $typediffere == 1 ){
            $result = ($montantPret*$tauxinteret/100)/(12*(1-pow((1+(($tauxinteret/100)/12)), -$duree)));
        }
        else if( ((float)$numEcheance > 0 && $init == $numEcheance && $typediffere == 2) || ((float)$numEcheance > 0 && $init == $numEcheance && $initPrev == "D" && $typediffere == 3) ){
            $result = ($capitalDuPrev*$tauxinteret/100)/(12*(1-pow((1+(($tauxinteret/100)/12)), (-$duree+$dureeDiffere))));
        }
        else if((float)$numEcheance > 0 && $init == "D" && $typediffere == 2){
            $result = $interet;
        }
        else if( ((float)$numEcheance > 0 && $init == "D" && $typediffere == 3) || ((float)$numEcheance > 0 && $init == $numEcheance && $initPrev != "D" && $typediffere == 3) ){
            $result = $mensualiteHorsAssurancePrev;
        }

        return $result;
    }

    public function calculCapitalDu($init, $numEcheance, $typediffere, $montantPret, $interet, $mensualiteHorsAssurance){

        $result = "";
        if( ((float)$numEcheance > 0 && $init == $numEcheance) || ((float)$numEcheance > 0  && $init == "D" && $typediffere == 2))
            $result = $montantPret-$mensualiteHorsAssurance+$interet;
        else if((float)$numEcheance > 0 && $init == "D" && $typediffere == 3){
            $result = $montantPret+$interet;
        }

        return $result;
    }

    public function calculCapitalDuNext($init, $numEcheance, $typediffere, $capitalDuPrev, $interet, $mensualiteHorsAssurance){

        $result = "";
        if( ((float)$numEcheance > 0 && $init == $numEcheance) || ((float)$numEcheance > 0  && $init == "D" && $typediffere == 2))
            $result = $capitalDuPrev-$mensualiteHorsAssurance+$interet;
        else if((float)$numEcheance > 0 && $init == "D" && $typediffere == 3){
            $result = $capitalDuPrev+$interet;
        }

        return $result;
    }

    public function initEcheance($dateDebut, $montantPret, $tauxinteret, $typediffere, $dureediffere, $dureepret){
        $test = $this->test($dateDebut, $montantPret ,$dureepret, $tauxinteret);
        $test2 = $this->test2($typediffere, $dureediffere ,$dureepret);

        return ($test == "OK" && $test2 == "OK") ? 1 : "";
    }

    /**
     * @Route("/new", name="pret_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $pret = new Pret();
        $pret->setEntreprise($entreprise);
        $form = $this->createForm(PretType::class, $pret);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            
            /** @var UploadedFile $contrat */
            $uploadedFile = $form['contrat']->getData();
            if ($uploadedFile){
                $contrat = $this->global_s->saveImage($uploadedFile, '/public/uploads/pret/contrat/');
                $pret->setContrat($contrat);
            }   


            $anneeDiffere = $request->request->get('anneeDiffere');
            $moisDiffere = $request->request->get('moisDiffere');

            if($anneeDiffere && $moisDiffere){
                $dateDiffere = new \DateTime($anneeDiffere."-".$moisDiffere.'-01');
                $pret->setDateDiffere($dateDiffere);
            }

            $entityManager->persist($pret);
            $entityManager->flush();


            /* FIN DEBUT AMORTISSEMENT */  
            $amortissements = $request->request->get('amortissements');
            $amortissementsFile = $request->files->get('amortissements');

            if(!is_null($amortissements)){
                for ($i=0; $i < count($amortissements['date']); $i++) { 
                    if(!empty($amortissementsFile['file'][$i]) || !empty($amortissements['date'][$i])){
                        $amortissement = new Amortissement();
                        
                        if(!empty($amortissementsFile['file'][$i])){
                            /** @var UploadedFile $file */
                            $uploadedFile = $amortissementsFile['file'][$i];
                            if ($uploadedFile){
                                $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/pret/amortissement/');
                                $amortissement->setFile($file);
                            }
                        }
                        $amortissement->setDateCreation(new \DateTime($amortissements['date'][$i]));
                        $amortissement->setEntreprise($entreprise);

                        $entityManager->persist($amortissement);
                        $pret->addAmortissement($amortissement);    
                    }
                }
            }
            /* FIN SAUVEGARDE AMORTISSEMENT */  

            $entityManager->flush();

            return $this->redirectToRoute('pret_index');
        }

        return $this->render('pret/new.html.twig', [
            'pret' => $pret,
            'tabMois'=>array_flip($this->global_s->getMoisFull()),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="pret_show", methods={"GET"})
     */
    public function show(Pret $pret): Response
    {
        return $this->render('pret/show.html.twig', [
            'pret' => $pret,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="pret_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Pret $pret): Response
    {
        $form = $this->createForm(PretType::class, $pret);
        $form->handleRequest($request);

        $entityManager = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            
            /** @var UploadedFile $contrat */
            $uploadedFile = $form['contrat']->getData();
            if ($uploadedFile){
                $contrat = $this->global_s->saveImage($uploadedFile, '/public/uploads/pret/contrat/');
                $pret->setContrat($contrat);
            }

            /* SAUVEGARDE AMORTISSMENT */
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
            $amortissements = $pret->getAmortissements();
            $amortissementEdit = $request->request->get('amortissementsEdit');
            $amortissementEditFile = $request->files->get('amortissementsEdit');
            
            if(!is_null($amortissementEdit)){
                foreach ($amortissementEdit as $key => $value) {
                    $valueFile = $amortissementEditFile[$key];

                    $amortissement =  $this->getDoctrine()->getRepository(Amortissement::class)->find($key);
                    if(!empty($value['amortissement']) || !empty($value['date'])){
                        $amortissement->setEntreprise($entreprise);

                        if(!empty($valueFile['file'])){
                            /** @var UploadedFile $file */
                            $uploadedFile = $valueFile['file'];
                            if ($uploadedFile){
                                $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/pret/amortissement/');
                                $amortissement->setFile($file);
                            }
                        }
                        $amortissement->setDateCreation(new \DateTime($value['date']));
                    }
                    else{
                        $pret->removeAmortissement($amortissement);
                    }

                }
                foreach ($amortissements as $value) {
                    if(!array_key_exists($value->getId(), $amortissementEdit)){
                        $entityManager->remove($value);
                    }
                }
            }

            $amortissements = $request->request->get('amortissements');
            $amortissementsFile = $request->files->get('amortissements');

            if(!is_null($amortissements)){
                for ($i=0; $i < count($amortissements['date']); $i++) { 
                    if(!empty($amortissements['file'][$i]) || !empty($amortissements['date'][$i]) || !empty($amortissementsFile['file'][$i])){
                        $amortissement = new Amortissement();
                        $amortissement->setDateCreation(new \DateTime($amortissements['date'][$i]));

                        /** @var UploadedFile $file */
                        $uploadedFile = $amortissementsFile['file'][$i];
                        if ($uploadedFile){
                            $file = $this->global_s->saveImage($uploadedFile, '/public/uploads/pret/amortissement/');
                            $amortissement->setFile($file);
                        }
                        $amortissement->setEntreprise($entreprise);

                        $entityManager->persist($amortissement);
                        $pret->addAmortissement($amortissement);    
                    }
                }
            }
            /* FIN SAUVEGARDE AMORTISSMENT */  


            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('pret_index');
        }

        $dateDebut = $pret->getDateDeblocage();
        $debutPrelevementInteret = $pret->getDebutPrelevementInteret();
        $montantPret = (float)$pret->getCapital();
        $montantEcheance1 = (float)$pret->getMontantEcheance1();
        $interet1 = (float)$pret->getInteret1();
        $remboursement1 = (float)$pret->getRemboursement1();
        $datePremiereEcheance = $pret->getDatePremiereEcheance();
        $duree = (int)$pret->getDuree();
        $taux = (float)$pret->getTaux();
        $tauxAssurance = (float)$pret->getTauxAssurance();
        $typeDiffere = (int)$pret->getDiffere();
        $dureeDiffere = (int)$pret->getDureeDiffere();

        $amortissements = $this->calculAmortissement($pret, $dateDebut, $montantPret, $duree, $taux, $tauxAssurance, $typeDiffere, $dureeDiffere, $debutPrelevementInteret, $montantEcheance1, $datePremiereEcheance, $interet1, $remboursement1);

        $amortissementsAll = $amortissements['amortissements'];
        $capitalRestantMois = $amortissements['capitalRestantMois'];
        $montantEcheanceMois = $amortissements['montantEcheanceMois'];
        $assuranceMois = $amortissements['assuranceMois'];
        $dateFin = $amortissements['dateFin'];
        $totalInteret = $amortissements['totalInteret'];

        $pret->setCapitalRestant($capitalRestantMois);
        $pret->setMontantEcheance($montantEcheanceMois);
        $pret->setCoutInteret($totalInteret);
        $pret->setCoutAssurance($assuranceMois);
        $pret->setDateFin($dateFin);
        $pret->setCoutTotal($totalInteret+$pret->getCapital());

        $anneeDiffere = $request->request->get('anneeDiffere');
        $moisDiffere = $request->request->get('moisDiffere');

        if($anneeDiffere && $moisDiffere){
            $dateDiffere = new \DateTime($anneeDiffere."-".$moisDiffere.'-01');
            $pret->setDateDiffere($dateDiffere);
        }

        $this->getDoctrine()->getManager()->flush();

        $form = $this->createForm(PretType::class, $pret);


        return $this->render('pret/edit.html.twig', [
            'pret' => $pret,
            'tabMois'=>array_flip($this->global_s->getMoisFull()),
            'form' => $form->createView(),
            'amortissements'=>$amortissementsAll,
            'sumCapitalRestantDu'=>$amortissements['capitalRestantMois']
        ]);
    }

    /**
     * @Route("/edit-xhr", name="pret_edit_xhr", methods={"GET", "POST"})
     */
    public function editXhr(Request $request, PretRepository $pretRepository){

        $pret = $pretRepository->find($request->request->get('pret_id'));

        $form = $this->createForm(PretType::class, $pret);
        $form->handleRequest($request);

        $entityManager = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            
            /** @var UploadedFile $contrat */
            $uploadedFile = $form['contrat']->getData();
            if ($uploadedFile){
                $contrat = $this->global_s->saveImage($uploadedFile, '/public/uploads/pret/contrat/');
                $pret->setContrat($contrat);
            }

            $this->getDoctrine()->getManager()->flush();
        }

        $dateDebut = $pret->getDateDeblocage();
        $debutPrelevementInteret = $pret->getDebutPrelevementInteret();
        $montantPret = (float)$pret->getCapital();
        $montantEcheance1 = (float)$pret->getMontantEcheance1();
        $interet1 = (float)$pret->getInteret1();
        $remboursement1 = (float)$pret->getRemboursement1();
        $duree = (int)$pret->getDuree();
        $taux = (float)$pret->getTaux();
        $tauxAssurance = (float)$pret->getTauxAssurance();
        $datePremiereEcheance = $pret->getDatePremiereEcheance();
        $typeDiffere = (int)$pret->getDiffere();
        $dureeDiffere = (int)$pret->getDureeDiffere();

        $amortissements = $this->calculAmortissement($pret, $dateDebut, $montantPret, $duree, $taux, $tauxAssurance, $typeDiffere, $dureeDiffere, $debutPrelevementInteret, $montantEcheance1, $datePremiereEcheance, $interet1, $remboursement1);

        $amortissementsAll = $amortissements['amortissements'];
        $capitalRestantMois = $amortissements['capitalRestantMois'];
        $montantEcheanceMois = $amortissements['montantEcheanceMois'];
        $assuranceMois = $amortissements['assuranceMois'];
        $dateFin = $amortissements['dateFin'];
        $totalInteret = $amortissements['totalInteret'];

        $pret->setCapitalRestant($capitalRestantMois);
        $pret->setMontantEcheance($montantEcheanceMois);
        $pret->setCoutInteret($totalInteret);
        $pret->setCoutAssurance($assuranceMois);
        $pret->setDateFin($dateFin);
        $pret->setCoutTotal($totalInteret+$pret->getCapital());

        $anneeDiffere = $request->request->get('anneeDiffere');
        $moisDiffere = $request->request->get('moisDiffere');

        if($anneeDiffere && $moisDiffere){
            $dateDiffere = new \DateTime($anneeDiffere."-".$moisDiffere.'-01');
            $pret->setDateDiffere($dateDiffere);
        }

        $this->getDoctrine()->getManager()->flush();

        $form = $this->createForm(PretType::class, $pret);

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('pret/edit_xhr.html.twig', [
                'pret' => $pret,
                'form' => $form->createView(),
                'amortissements' => $amortissementsAll,
                'tabMois'=>array_flip($this->global_s->getMoisFull()),
                'sumCapitalRestantDu' => $amortissements['capitalRestantMois'],
                'fields'=>[]
            ]);
        
        $response = new Response(json_encode($datas));

        return $response;
    }

    /**
     * @Route("/{id}", name="pret_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Pret $pret): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pret->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($pret);
            $entityManager->flush();
        }

        return $this->redirectToRoute('pret_index');
    }

    /**
     * @Route("/delete-contrat/{id}/{type_document}", name="pret_delete_contrat")
     */
    public function deleteContrat(Request $request, $id, $type_document): Response
    {
        $pret =$this->getDoctrine()
                ->getRepository(Pret::class)
                ->find($id);

        switch ($type_document) {
            case 'contrat':
                $pret->setContrat(null);
                break;
            default:
                break;
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        $this->addFlash('success', 'Contrat supprimé avec succes');
        
        return $this->redirectToRoute('pret_edit', ['id'=>$id]);
    }
}