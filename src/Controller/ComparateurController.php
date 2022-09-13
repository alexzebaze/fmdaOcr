<?php

namespace App\Controller;
use Doctrine\ORM\EntityRepository;
use App\Entity\Achat;
use App\Form\AchatType;
use App\Form\FournisseursType;
use App\Form\ReglementType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Controller\Traits\CommunTrait;
use App\Entity\Fournisseurs;
use App\Entity\Chantier;
use App\Entity\Entreprise;
use App\Entity\Reglement;
use App\Entity\Tva;
use App\Entity\Devise;
use App\Repository\ChantierRepository;
use App\Repository\PaiementRepository;
use App\Repository\ReglementRepository;
use App\Repository\AchatRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\FournisseursRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Service\GlobalService;
use Carbon\Carbon;
use Pagerfanta\Pagerfanta;

$GLOBALS['BL_RESULT_TAB'] = [];
/**
 * @Route("/", name="comparateur_bl_fc_")
 */
class ComparateurController extends Controller
{   
    use CommunTrait;
    private $global_s;
    private $chantierRepository;
    private $achatRepository;
    private $fournisseurRepository;
    private $reglementRepository;
    private $session;

    public function __construct(GlobalService $global_s, ChantierRepository $chantierRepository, AchatRepository $achatRepository, FournisseursRepository $fournisseurRepository, ReglementRepository $reglementRepository, SessionInterface $session){
        $this->global_s = $global_s;
        $this->chantierRepository = $chantierRepository;
        $this->achatRepository = $achatRepository;
        $this->fournisseurRepository = $fournisseurRepository;
        $this->reglementRepository = $reglementRepository;
        $this->session = $session;
    }

    public function array_kshift(&$arr)
    {
      list($k) = array_keys($arr);
      $r  = array($k=>$arr[$k]);
      unset($arr[$k]);
      return $r;
    }

    
    /**
     * @Route("/comparateur", name="index", methods={"GET", "POST"})
     */
    public function index(Request $request, Session $session, PaiementRepository $paiementRepository)
    {
        $reglement = new Reglement();
        $formR = $this->createForm(ReglementType::class, $reglement);

        $form = $request->request->get('form', null);

        if((int)(new \DateTime())->format('m') >=2 ){
            $mois = (int)(new \DateTime())->format('m') - 1;
        }
        
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $fournisseurId = (!(int)$form['fournisseur']) ? null : (int)$form['fournisseur'];

            $session->set('mois_comp', $mois); 
            $session->set('annee_comp', $annee); 
            $session->set('fournisseur_comp', $fournisseurId);
        } else {
            $mois = $session->get('mois_comp', null);
            //$annee = $session->get('annee_comp', null);
            $annee = (new \DateTime())->format("Y");
            $fournisseurId = $session->get('fournisseur_comp', null);
        }

        if($fournisseurId){
            $bon_livraisons = $this->achatRepository->findByfacturedDateCompar($mois, $annee, 'bon_livraison', $fournisseurId);
            $factures = $this->achatRepository->findByfacturedDateCompar($mois, $annee, 'facturation', $fournisseurId);    
        }
        else{
            $bon_livraisons = $this->achatRepository->findByfacturedDateCompar($mois, $annee, 'bon_livraison');
            $factures = $this->achatRepository->findByfacturedDateCompar($mois, $annee, 'facturation');  
        }

        $arrBuildForm = array(
            'mois' => (int)$mois,
            'annee' => $annee
        );
        if($fournisseurId){
            $arrBuildForm['fournisseur'] = $this->fournisseurRepository->find($fournisseurId);
        }

        $tabMois = $this->global_s->getMois();
        array_shift($tabMois);

        $tabAnnee = $this->global_s->getAnnee();
        $this->array_kshift($tabAnnee);

        $form = $this->createFormBuilder($arrBuildForm)
        ->add('mois', ChoiceType::class, array(
            'label' => "Mois",
            'choices' => $tabMois,
            'attr' => array(
                'required' => false,
                'class' => 'form-control'
            )
        ))
        ->add('annee', ChoiceType::class, array(
            'label' => "Année",
            'choices' => $tabAnnee,
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('fournisseur', EntityType::class, array(
            'class' => Fournisseurs::class,
            'query_builder' => function(EntityRepository $repository) use ($mois, $annee){ 
                return $this->fournisseurRepository->findByDateAchat($mois, $annee);
            },
            'required' => false,
            'label' => "Fournisseur",
            'choice_label' => 'nom',
            'attr' => array(
                'required' => false,
                'class' => 'form-control'
            )
        ))->getForm();

        $entityManager = $this->getDoctrine()->getManager();      

        $montant = $entityManager->getRepository(Achat::class)->countMontantByfacturedDate($mois, $annee, 'bon_livraison');

        $doublon = $entityManager->getRepository(Achat::class)->findDoublonDocumentId('bon_livraison');
        $tabDoublon = [];
        foreach ($bon_livraisons as $value) {
            if(array_search($value->getId(), array_column($doublon, 'id')) !== false) {
                $tabDoublon[] = $value->getId();
            }
        }

        return $this->render('comparateur/index.html.twig', [
            'bon_livraisons' => $bon_livraisons,
            'factures' => $factures,
            'tabDoublon'=>$tabDoublon,
            'mois'=>$mois,
            'annee'=>$annee,
            'form' => $form->createView(),
            'formR' => $formR->createView(),
            'montant'=>$montant,
            'mode_reglements'=>$paiementRepository->findAll(),
            'url'=> $this->generateUrl('comparateur_bl_fc_reglement_post', [], UrlGenerator::ABSOLUTE_URL)
        ]);
    }

    /**
     * @Route("/comparateur/bl-match", name="bl_match", methods={"GET", "POST"})
     */
    public function blMatch(Request $request, Session $session){
        $mois = $session->get('mois_comp', null);
        $annee = $session->get('annee_comp', null);
        $fournisseurId = $session->get('fournisseur_comp', null);
        if($fournisseurId){
            $bon_livraisons = $this->achatRepository->findByfacturedDateCompar($mois, $annee, 'bon_livraison', $fournisseurId);
        }
        else{
            $bon_livraisons = $this->achatRepository->findByfacturedDateCompar($mois, $annee, 'bon_livraison');
        }
        $blObjet = [];
        foreach ($bon_livraisons as $value) {
            $blObjet[] = [$value->getId(), $value->getPrixht()];
        }
        
        $factureHFrais = $request->query->get('factureHFrais');

        $blObjet = array_filter($blObjet, function($a) use ($factureHFrais){
            return(float)$a[1] <= (float)$factureHFrais;
        });
        usort($blObjet, function($a, $b) {
            return $b[1] - $a[1];
        });
        $this->subsetSum($blObjet, $factureHFrais);
        $datas = ['status'=>200, "message"=>"bon trouvés", "datas"=>$GLOBALS['BL_RESULT_TAB']];
        $response = new Response(json_encode($datas));
        return $response;
    }

    public function sortByOrder($a, $b) {
        return $b[1] - $a[1];
    }
    public function subsetSum($numbers, $target, $partial=[], $tab3 = []){
        $GLOBALS['BL_RESULT_TAB'];
      $s; $n; $remaining;
      // sum partial

      $s = array_sum($tab3);
      // check if the partial sum is equals to target
      if ( (number_format((float)$s, 2, '.', '.')) == (number_format((float)$target, 2, '.', '.'))) {
        return $GLOBALS['BL_RESULT_TAB'][] = $partial;
      }


      if((float)$target > 0){
          if ((float)$s > (float)$target) {
            return;  // if we reach the number why bother to continue
          }
      }
      else{
        if ((float)$s > (float)$target && (float)$s != 0) {
            return;  // if we reach the number why bother to continue
        }
      }

      for ($i = 0; $i < count($numbers); $i++) {
        $n = $numbers[$i];
        $remaining = array_slice($numbers, ($i + 1));
        $t = [];
        foreach ($partial as $value) {
            $t[] = $value;
        }
        $t[] = $n[0];

        $tt = [];
        foreach ($tab3 as $value) {
            $tt[] = $value;
        }
        $tt[] = $n[1];

        $this->subsetSum($remaining, $target, $t, $tt);
      }
    }

    /**
     * @Route("/comparateur/validation", name="validation", methods={"GET", "POST"})
     */
    public function validation(Request $request){
        $em = $this->getDoctrine()->getManager();
        $this->get('session')->getFlashBag()->clear();
        if(!$request->request->get('list-facture-id')){
            $this->addFlash('error', "Aucune facture selectionnée");
            return $this->redirectToRoute('comparateur_bl_fc_index');
        }
        
        if($request->request->get('list-bl-id'))
            $listBl = str_replace("-", ",", $request->request->get('list-bl-id'));
        else{
            $listBl = "0000";
            $this->addFlash('success', "Vous devez associer un bon de livraison");
            return $this->redirectToRoute('comparateur_bl_fc_index');
        }

        $tabFacture = explode('-', $request->request->get('list-facture-id'));
        $this->getDoctrine()->getRepository(Achat::class)->validFacture($tabFacture, 'facturation', $listBl);

        if($request->request->get('list-bl-id')){
            $tabBl = explode('-', $request->request->get('list-bl-id'));
            $this->getDoctrine()->getRepository(Achat::class)->validBl($tabBl, 'bon_livraison', $tabFacture[0]);
        }
        $this->addFlash('success', "Operation effectuée avec succès");
        return $this->redirectToRoute('comparateur_bl_fc_index');
    }

    /**
     * @Route("/reglement-post", name="reglement_post", methods={"POST"})
     */
    public function reglementPost(Request $request){
        $em = $this->getDoctrine()->getManager();
        $this->get('session')->getFlashBag()->clear();
        if(!$request->request->get('list-facture-id')){
            $this->addFlash('error', "Aucune facture selectionnée");
            return $this->redirectToRoute('comparateur_bl_fc_index');
        }

        $reglement = new Reglement();
        $form = $this->createForm(ReglementType::class, $reglement);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $sumTtc = $prixht = 0;
                $tabFacture = explode('-', $request->request->get('list-facture-id'));
                $fournisseur = new Fournisseurs();
                $achat1 = $this->achatRepository->find($tabFacture[0]);
                for ($i=0; $i < count($tabFacture); $i++) { 
                    $achat = $this->achatRepository->find($tabFacture[$i]);
                    $sumTtc += $achat->getPrixttc();
                    $prixht += $achat->getPrixht();
                    $reglement->addAchat($achat);

                    /* valider si la facture n'est pas validée */
                    if(is_null($achat->getBlValidation())){
                        //$achat->setBlValidation("0000");
                    }
                }

                $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
                if(!is_null($entreprise))
                    $reglement->setEntreprise($entreprise);

                /** @var UploadedFile $document */
                $fichiers = $form->get('document')->getData();
                if ($fichiers) {
                    $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/reglement/entreprise_" . $this->session->get('entreprise_session_id') . "/";

                    $compressed_dir = $this->get('kernel')->getProjectDir() . "/public/uploads/reglement/entreprise_" . $this->session->get('entreprise_session_id') . "/compressed/";
                    try {
                        if (!is_dir($dir)) {
                            mkdir($dir, 0777, true);
                        }
                        if (!is_dir($compressed_dir)) {
                            mkdir($compressed_dir, 0777, true);
                        }
                    } catch (FileException $e) {}
                    $documentArr = [];
                    foreach ($fichiers as $value) {
                        $originalFilename = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $this->slugify($originalFilename);
                        $extension = $value->guessExtension();
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $value->guessExtension();
                       $value->move($dir, $newFilename);
                       $documentArr[] = $newFilename;
                        $this->global_s->make_thumb($dir.$newFilename, $compressed_dir.$newFilename,200,$extension);
                    }
                    $reglement->setDocument(serialize($documentArr));
                }

                $reglement->setDueAt($achat1->getDueAt());
                $reglement->setTva($achat1->getTva());
                $reglement->setDevise($achat1->getDevise());
                $reglement->setUpdatedAt(new \DateTime());
                $reglement->setSolde($sumTtc);
                $reglement->setPrixttc($sumTtc);
                $reglement->setPrixht($prixht);
                $reglement->setFournisseur($achat1->getFournisseur());
                $em->persist($reglement);
                $em->flush(); 
                $this->addFlash('success', "Operation effectuée avec succès"); 
            }
        }
        
        if($request->query->get('facturation'))
            return $this->redirectToRoute('facturation_list');
        return $this->redirectToRoute('comparateur_bl_fc_index');
    }

    /**
     * @Route("/reglement", name="reglement", methods={"GET"})
     */
    public function reglement(Request $request){
        $em = $this->getDoctrine()->getManager();

        $reglements = $em->getRepository(Reglement::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['id'=>'DESC']);
        $page = $request->query->get('page', 1);
        $adapter = new ArrayAdapter($reglements);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(100000);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        } 
        return $this->render('comparateur/reglement.html.twig', [
            'pager' => $pager,
            'reglements' => $reglements
        ]);

    }

    /**
     * @Route("/{reglementId}/delete-reglement", name="delete")
     */
    public function delete($reglementId)
    {
        $reglement = $this->getDoctrine()->getRepository(Reglement::class)->find($reglementId);

        $entityManager = $this->getDoctrine()->getManager();

        try {
            $entityManager->remove($reglement);
            $entityManager->flush();
            $this->addFlash('success', "Suppression effectuée avec succès");
        } catch (\Exception $e) {
            $this->addFlash('error', "Vous ne pouvez supprimer cet element s'il est lié à d'autres elements");
        }

        return $this->redirectToRoute('comparateur_bl_fc_reglement');
    }
}