<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Entreprise;
use App\Entity\HoraireObservation;
use App\Entity\HoraireValidation;
use App\Entity\Chantier;
use App\Entity\UserSms;
use App\Entity\UserHoraireAbsent;
use App\Entity\Vente;
use App\Entity\Paie;
use App\Entity\Prime;
use App\Entity\Horaire;
use App\Form\HoraireEditType;
use App\Form\HoraireType;
use App\Form\ChantierType;
use DateInterval;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Carbon\Carbon;
use App\Controller\Traits\CommunTrait;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Galerie;

use App\Repository\PaieRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\HoraireRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ChantierRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\GlobalService;
use Pagerfanta\Adapter\ArrayAdapter;

/**
 * @Route("/horaire", name="horaire_")
 */
class HoraireController extends Controller
{

    use CommunTrait;

    private $paieRepository;
    private $global_s;
    private $utilisateurRepository;
    private $entrepriseRepository;
    private $horaireRepository;
    private $chantierRepository;
    private $session;

    public function __construct(ChantierRepository $chantierRepository, PaieRepository $paieRepository, UtilisateurRepository $utilisateurRepository, HoraireRepository $horaireRepository, EntrepriseRepository $entrepriseRepository, SessionInterface $session, GlobalService $global_s){
        $this->paieRepository = $paieRepository;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->chantierRepository = $chantierRepository;
        $this->horaireRepository = $horaireRepository;
        $this->session = $session;
        $this->global_s = $global_s;
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     * @Route("/collaborator/{id}", name="list_collaborator", methods={"GET", "POST"})
    */ 
    public function index(Request $request, Session $session, $id = 0)
    {
        $me = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $form = $request->request->get('form', null);

        if ($form) {
            $mois = (int)$form['mois'];
            $annee = (int)$form['annee'];
            $chantier = null;
            //$chantier = (int)$form['chantier'];
            //$eChantier = $this->getDoctrine()->getRepository(Chantier::class)->find($chantier);
        } else {
            $mois = $session->get('mois', date('m'));
            $annee = $session->get('annee', date('Y'));
            $chantier = null;
            //$eChantier = null;
        }

        if($mois<10){
            $mois = "0".$mois;
        }
        if($annee<10){
            $annee = "0".$annee;
        }

        $session->set('mois', $mois);
        $session->set('annee', $annee);
        //$session->set('chantier', $chantier);


        $query_user = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('etat', false);
            
        $query = $query_user->getQuery();
        $query_user = $query->execute();

        $ids = [];
        foreach ($query_user as $key => $value) {
            if( ($value->getRawDateEntree() && ( (int)($annee.$mois) >= (int)($value->getRawDateEntree()->format('Ym')) )) && ($value->getRawDateSortie() && ( (int)($annee.$mois) <= (int)($value->getRawDateSortie()->format('Ym')) ))){
                $ids[]=$value->getUid();
            }
            elseif($value->getRawDateEntree() && ( (int)($annee.$mois) >= (int)($value->getRawDateEntree()->format('Ym')) ) && !$value->getRawDateSortie()){
                $ids[]=$value->getUid();
            }
        }

        $query_user = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.entreprise = :entreprise')
            ->andWhere("c.uid IN(:ids)")
            ->orderBy('c.num', 'asc')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('etat', false)
            ->setParameter('ids', array_values($ids));


        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            //'chantier' => $eChantier,
        ))
            ->add('mois', ChoiceType::class, array(
                'label' => "Mois",
                'choices' => $this->getMois(),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))
            ->add('annee', ChoiceType::class, array(
                'label' => "Année",
                'choices' => $this->getAnnee(),
                'attr' => array(
                    'class' => 'form-control'
                )
            ))->getForm();
        /*->add('chantier', EntityType::class, array(
            'class' => Chantier::class,
            'choice_label' => 'nameentreprise',
            'placeholder' => 'Selectionnez un chantier',
            'attr' => array(
                'class' => 'form-control'
            ),
            'required' => false
        ))*/


        $adapter = new DoctrineORMAdapter($query_user);
        $pager = new PagerFanta($adapter);
        $pager->setMaxPerPage(75);
        if ($pager->getNbPages() >= $page) {
            $pager->setCurrentPage($page);
        } else {
            $pager->setCurrentPage($pager->getNbPages());
        }

        $nb_ouvriers = 0;
        foreach ($pager as $value) {
            if($value->getEtat())
                $nb_ouvriers++;
        }

        $user = null;
        if ($id == 0) {
            $old_user = $session->get('user', null);
            if (!$old_user) {
                $users = $pager->getCurrentPageResults();
                if (isset($users[0]))
                    $user = $users[0];
            } else {
                $repositoryUser = $this->getDoctrine()->getRepository(Utilisateur::class);
                $user = $repositoryUser->find($old_user->getUid());
            }
            $repositoryGalerie = $this->getDoctrine()->getRepository(Galerie::class);
            $docs = $repositoryGalerie->findBy(['user'=>$user,'type'=>4]);

        } else {
            $repositoryUser = $this->getDoctrine()->getRepository(Utilisateur::class);
            $user = $repositoryUser->find($id);
            $repositoryGalerie = $this->getDoctrine()->getRepository(Galerie::class);
            $docs = $repositoryGalerie->findBy(['user'=>$user,'type'=>4]);
        }

        $horaires = null;
        if ($user) {
            $horaires = $this->getDoctrine()
                ->getRepository(Horaire::class)
                ->findHoraireByUserAndDate($annee, $mois, $user, $chantier);
        }

        $session->set('user', $user);

        $mois = (int)$mois;
        $annee = (int)$annee;

        $max = Carbon::parse("$annee-$mois-01")->lastOfMonth()->day;
        # API ferié
        $content = file_get_contents('https://jours-feries-france.antoine-augusti.fr/api/' . $annee);
        $feries = json_decode($content);

        # on vérifie si le premier jour de la semaine est un lundi
        $isLundi = false;
        $m = $this->getDataHoraire($annee, $mois, $feries, $max, $horaires);
        $cp = $m;
        $cp = reset($cp);
        if (!empty($cp)) {
            $copy = reset($cp);
            if (strpos($copy['jour'], 'lundi') !== false)
                $isLundi = true;
        }

        $mp = array();
        $total_heure = 0;
        $total_heure_fictif = 0;
        if (!$isLundi && $user) {
            $start = Carbon::parse("$annee-$mois-01")->locale('fr')->startOfWeek()->day;
            $moisp = Carbon::parse("$annee-$mois-01")->locale('fr')->startOfWeek()->month;
            $anneep = Carbon::parse("$annee-$mois-01")->locale('fr')->startOfWeek()->year;
            $maxp = Carbon::parse("$annee-$mois-01")->locale('fr')->startOfWeek()->lastOfMonth()->day;
            $horairesp = $this->getDoctrine()
                ->getRepository(Horaire::class)
                ->findHoraireByUserAndDate($anneep, $moisp, $user, $chantier);
            $mp = $this->getDataHoraire($anneep, $moisp, $feries, $maxp, $horairesp, $start, true);
            foreach ($mp as $semaine) {
                foreach ($semaine as $jour) {
                    $total_heure += $jour['time'];
                    $total_heure_fictif += (float)$jour['absence'];
                }
            }
        }

        $logo_entreprise = null;
        if ($user && $user->getEntreprise() && $user->getEntreprise()->getLogo()) {
            $logo_entreprise = base64_encode(file_get_contents('logo/' . $user->getEntreprise()->getLogo()));
        }


        $args = ['annee' => $annee, 'mois' => Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM'), 'user' => $user];
        $prime = $this->getDoctrine()->getRepository(Prime::class)->findOneBy($args);
        $observation = $this->getDoctrine()->getRepository(HoraireObservation::class)->findOneBy($args);
        $validation = $this->getDoctrine()->getRepository(HoraireValidation::class)->findOneBy($args);

        $currentFullMonth = array_flip($this->global_s->getMois())[(int)(new \DateTime())->format('m')];
        $annee = (new \DateTime())->format('Y');
        $paie = $this->paieRepository->findOneBy(['date_paie'=>$currentFullMonth.' '.$annee]);

        $count_jour_precedent = 0;
        if(count($mp)){
            foreach ($mp as $value) {
                foreach ($value as $val) {
                    if(array_key_exists('time', $val)){
                        if($val['time'] > 0)
                            $count_jour_precedent ++;
                    }
                }
                
            }
        }

        $horairesArr = array_merge($mp, $m);
        
        if($request->query->get('tesst'))
            dd($horairesArr);

        $dataReturn = [
            'count_jour_precedent'=> $count_jour_precedent,
            'pager' => $pager,
            'user' => $user,
            'horaires' => $horairesArr,
            'mois' => Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('MMMM'),
            'mois_numerique' => Carbon::parse("$annee-$mois-01")->locale('fr')->format('m'),
            'annee' => $annee,
            'form' => $form->createView(),
            'prime' => $prime,
            'observation' => $observation,
            'logo_entreprise' => $logo_entreprise,
            'validation' => $validation,
            'total_heure' => $total_heure,
            'total_heure_fictif' => $total_heure_fictif,
            'docs'=>$docs,
            'nb_ouvriers'=>$nb_ouvriers,
            'paie'=>$paie
        ];

        return $this->render('horaire/index.html.twig', $dataReturn);
    }
    
    
    /**
     * @Route("/recap-horaire-fictive", name="recap_horaire_fictive", methods={"GET", "POST"})
     */
    public function recapHoraireFictive(Request $request, Session $session)
    {
        $form = $request->request->get('form', null);

        $utilisateurId = $fournisseurId = $is_bl = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $utilisateurId = (!(int)$form['utilisateur']) ? null : (int)$form['utilisateur'];

            $horaires = $this->horaireRepository->findHoraireFictiveGroupByUser($mois, $annee, $utilisateurId);

            $session->set('mois_pas', $mois);
            $session->set('annee_pas', $annee);
            $session->set('utilisateur_fictif', $utilisateurId);
        }        
        else{
            $today = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_pas', $today[1]);
            $annee = $session->get('annee_pas', $today[0]);
            $utilisateurId = $session->get('utilisateur_fictif', null);

            $horaires = $this->horaireRepository->findHoraireFictiveGroupByUser($mois, $annee, $utilisateurId);
        }   

        $absences = [
            1=>"Congés Payés",
            2=> "En arrêt",
            3=>"Chômage Partiel", 
            4=>"Absence", 
            5=>"Formation", 
            6=> "RTT", 
            7=>"Férié"
        ];

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'utilisateur' => (!is_null($utilisateurId)) ? $this->utilisateurRepository->find($utilisateurId) : "",

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
        ->add('utilisateur', EntityType::class, array(
            'class' => Utilisateur::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('u')
                ->where('u.entreprise = :entreprise_id')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->andwhere('u.etat = :etat')
                ->setParameter('etat', 1)
                ->orderBy('u.lastname', 'ASC');
            },
            'required' => false,
            'label' => "Utilisateur",
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();
        
        $users = $this->utilisateurRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'etat'=>1], ['firstname'=>'ASC']);

        $allUtilisateursEntrep = $this->utilisateurRepository->getUserByEntreprise();

        $horairesArr = [];
        foreach ($horaires as $value) {
            $date = new \DateTime($value['datestart']);
            $horairesArr[$date->format('m-Y')][$value['userid']][] = $value;
        }

        $horairesArr2 = [];
        foreach ($horairesArr as $key => $horaire) {//iteration mois
            
            foreach ($horaire as $horaireUser) {//iteration user

                $dataInitUser = ["heure"=>0, "fictif"=>0];
                foreach ($horaireUser as $value) {// iteration horaire user
                    $dataInitUser['heure'] +=  $value['heure'];

                    if(!array_key_exists($value['absence'], $absences))
                        $dataInitUser['fictif'] +=  $value['fictif'];
                    elseif($value['absence'] == 5){
                        $dataInitUser['fictif'] +=  7;
                    }  
                }
                $dataInitUser['datestart'] = "01-".$key;
                $dataInitUser['firstname'] = $value['firstname'];
                $dataInitUser['lastname'] = $value['lastname'];
                $dataInitUser['userid'] = $value['userid'];

                $horairesArr2[] = $dataInitUser;
            }
            
        }
        $horairesArr2 = array_map(function($val){
            $val['datestart'] = Carbon::parse($val['datestart'])->locale('fr')->isoFormat('MMMM YYYY');
            return  $val;
        }, $horairesArr2);

        $totalHeure = 0;
        $totalFictif = 0;
        foreach ($horairesArr2 as $value) {
            $totalHeure += $value['heure'];
            $totalFictif += $value['fictif'];
        }

        return $this->render('horaire/horaire_fictive.html.twig', [
            'horaires' => $horairesArr2,
            'users'=>$users,
            'mois'=>$mois,
            'annee'=>$annee,
            'totalHeure'=>$totalHeure,
            'totalFictif'=>$totalFictif,
            'allUtilisateursEntrep'=>$this->global_s->getUserByMiniature($allUtilisateursEntrep),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/tache", name="get_detail", methods={"GET", "POST"})
    */ 
    public function getHoraires(Request $request, Session $session)
    {
        
        $form = $request->request->get('form', null);
        $userId = $chantierId = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $chantierId = (!(int)$form['chantier']) ? null : (int)$form['chantier'];
            $userId = (!(int)$form['user']) ? null : (int)$form['user'];
            $horaires = $this->horaireRepository->findHoraireUserByDateAndChantier($mois, $annee, $userId, $chantierId);

            $session->set('mois_hr', $mois);
            $session->set('annee_hr', $annee);
            $session->set('chantier_hr', $chantierId);
            $session->set('user_hr', $userId);
        } else {
            $day = explode('-', (new \DateTime())->format('Y-m-d'));
            $mois = $session->get('mois_hr', $day[1]);
            $annee = $session->get('annee_hr', $day[0]);
            $chantierId = $session->get('chantier_hr', null);
            $userId = $session->get('user_hr', null);
            $horaires = $this->horaireRepository->findHoraireUserByDateAndChantier($mois, $annee, $userId, $chantierId);
            
        }

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => $annee,
            'chantier' => (!is_null($chantierId)) ? $this->chantierRepository->find($chantierId) : "",
            'user' => (!is_null($userId)) ?  $this->utilisateurRepository->find($userId) : ""

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
        ->add('user', EntityType::class, array(
            'class' => Utilisateur::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('f')
                ->where('f.entreprise = :entreprise_id')
                ->andwhere('f.etat = :etat')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->setParameter('etat', 1)
                ->orderBy('f.lastname', 'ASC');
            },
            'required' => false,
            'label' => "Utilisateur",
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();

        $horaires = array_map(function($val){
            $val['datestart'] = Carbon::parse($val['datestart'])->locale('fr')->isoFormat('D MMMM YYYY');
            return  $val;
        }, $horaires);

        return $this->render('horaire/index_detail.html.twig', [
            'horaires' => $horaires,
            'mois'=>((int)$mois >= 10) ? $mois : "0".$mois,
            'annee'=>$annee,
            'user'=>$userId,
            'chantier'=>$chantierId,
            'annee'=>$annee,
            'chantiers'=>$this->chantierRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['nameentreprise'=>'ASC']),
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/order", name="order")
     */
    public function order(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $orders = $request->request->get('order', null);
        $this->orderRecursive(json_decode($orders));
        $entityManager->flush();
        return $this->redirectToRoute('horaire_list');
    }
    
    /**
     * @Route("/edit-chantier", name="edit_chantier")
     */
    public function editChantier(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $horaireId = $request->request->get('horaire_id');
        $chantierId = $request->request->get('chantier');
        $horaire = $this->horaireRepository->find($horaireId);
        $horaire->setChantierid($chantierId);

        $entityManager->flush();
        return $this->redirectToRoute('horaire_horaire_devis');
    }

    private function orderRecursive($orders, $parent = null)
    {
        foreach ($orders as $key => $order) {
            if (isset($order)) {
                /** @var Category $user */
                $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($order);
                if ($user) {
                    $user->setNum($key);
                }
            }
        }
    }

    private function getDataHoraire($annee, $mois, $feries, $max, $horaires, $start = 1, $bool = false)
    {
        $k = 0;
        for ($i = $start; $i <= $max; $i++) {
            if (Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "dimanche") {
                continue;
            }

            # Les Lundi, on fait un autre array
            if ($i > 1 && Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "lundi") {
                $k++;
            }

            # API ferié
            $jour = Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd Do MMMM YYYY');
            foreach ($feries as $ferie) {
                if ($ferie->date == "$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i)) {
                    $jour = '<strong>' . $jour . ' - FERIE - ' . $ferie->nom_jour_ferie . '</strong>';
                }
            }

            # Récupération des données du jour
            $m[$k][$i] = array(
                'timestamp' => Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->timestamp,
                'jour' => $jour,
                'heures' => $this->checkDate("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'jour_numerique' => Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->format('Y-m-d'),
                'fictif' => $this->checkFictif("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'time' => $this->checkTime("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'pause' => $this->checkTimePause("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'absence' => $this->checkFictifOrAbsence("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'mois_precedent' => $bool,
            );
            if (Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "samedi") {
                if (count($m[$k][$i]['heures']) == 0) {
                    unset($m[$k][$i]);
                }
            }
        }
        # nettoyage de l'array
        $m = array_filter($m, function ($value) {
            return !empty($value);
        });
        return $m;
    }

    private function checkDate($date, $horaires)
    {
        if (!$horaires)
            return array();

        $heure = array();
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                $heure[] = array(
                    'time' => $horaire->getTime(),
                    'fictif' => $horaire->getFictif(),
                    'chantier' => $horaire->getChantierid() ? $this->getDoctrine()->getRepository(Chantier::class)->find($horaire->getChantierid()) : '',
                    'id' => $horaire->getIdsession(),
                    'absence' => $horaire->getAbsence(),
                );
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    $heure[] = array(
                        'time' => $horaire->getTime(),
                        'fictif' => $horaire->getFictif(),
                        'chantier' => $horaire->getChantierid() ? $this->getDoctrine()->getRepository(Chantier::class)->find($horaire->getChantierid()) : '',
                        'id' => $horaire->getIdsession(),
                        'absence' => $horaire->getAbsence(),
                    );
                }
            }
        }
        return $heure;
    }

    private function checkFictif($date, $horaires)
    {
        if (!$horaires)
            return 0;
        $time = 0;
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                $time = $horaire->getFictif() + $time;
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    $time = $horaire->getFictif() + $time;
                }
            }
        }
        return $time;
    }


    private function checkFictifOrAbsence($date, $horaires)
    {
        if (!$horaires)
            return 0;
        $time = 0;
        $absence = null;
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                if ($horaire->getAbsence() == 0) {
                    $time = $horaire->getFictif() + $time;
                } else {
                    if ($horaire->getAbsence() == 1) {
                        $absence['motif'] = "Congés Payés";
                    } elseif ($horaire->getAbsence() == 2) {
                        $absence['motif'] = "En arrêt";
                    } elseif ($horaire->getAbsence() == 3) {
                        $absence['motif'] = "Chômage Partiel";
                    } elseif ($horaire->getAbsence() == 4) {
                        $absence['motif'] = "Absence";
                    } elseif ($horaire->getAbsence() == 5) {
                        $absence['motif'] = "Formation";
                    } elseif ($horaire->getAbsence() == 6) {
                        $absence['motif'] = "RTT";
                    } elseif ($horaire->getAbsence() == 7) {
                        $absence['motif'] = "Férié";
                    }
                    $absence['id'] = $horaire->getAbsence();
                }
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    if ($horaire->getAbsence() == 0) {
                        $time = $horaire->getFictif() + $time;
                    } else {
                        if ($horaire->getAbsence() == 1) {
                            $absence['motif'] = "Congés Payés";
                        } elseif ($horaire->getAbsence() == 2) {
                            $absence['motif'] = "En arrêt";
                        } elseif ($horaire->getAbsence() == 3) {
                            $absence['motif'] = "Chômage Partiel";
                        } elseif ($horaire->getAbsence() == 4) {
                            $absence['motif'] = "Absence";
                        } elseif ($horaire->getAbsence() == 5) {
                            $absence['motif'] = "Formation";
                        } elseif ($horaire->getAbsence() == 6) {
                            $absence['motif'] = "RTT";
                        } elseif ($horaire->getAbsence() == 7) {
                            $absence['motif'] = "Férié";
                        }
                        $absence['id'] = $horaire->getAbsence();
                    }
                }
            }
        }

        if ($absence !== null && $time == 0) {
            return $absence;
        } else {
            return $time;
        }

    }

    private
    function checkTime($date, $horaires)
    {
        if (!$horaires)
            return 0;

        $time = 0;
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                $time = $horaire->getTime() + $time;
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    $time = $horaire->getTime() + $time;
                }
            }
        }
        return $time;
    }

    private
    function checkTimePause($date, $horaires)
    {
        if (!$horaires)
            return 0;
        
        $pause = 0;
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                $pause = $horaire->getPause() + $pause;
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    $pause = $horaire->getPause() + $pause;
                }
            }
        }
        return $pause;
    }

    private
    function getMois()
    {
        $mois = array();
        for ($i = 1; $i <= 12; $i++) {
            $mois[$i] = ucfirst(Carbon::create()->day(1)->month($i)->locale('fr')->isoFormat('MMMM'));
        }
        return array_flip($mois);
    }

    private
    function getAnnee()
    {
        $years = array();
        $begin = (int)date('Y') - 2;
        $end = (int)date('Y') + 2;
        for ($annee = $begin; $annee <= $end; $annee++) {
            $years[$annee] = $annee;
        }
        return $years;
    }

    /**
     * @Route("/add/{id}", name="add", methods={"GET","POST"})
     * @Route("/add/{id}/jour/{timestamp}", name="add_from_date", methods={"GET","POST"})
     */
    public
    function new(Request $request, $id, $timestamp = null)
    {
        $me = $this->getUser();
        $date_debut = '';
        $date_fin = '';
        if ($timestamp) {
            $date = Carbon::createFromTimestamp($timestamp)->locale('fr_FR')->format('d/m/Y');
            $date_debut = $date . ' 07:30';
            $date_fin = $date . ' 17:00';
        }

        $horaire = new Horaire();
        $form = $this->createForm(HoraireType::class, $horaire, array('entreprise' => $this->session->get('entreprise_session_id')));
        $form->handleRequest($request);
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($id);
        $horaire->setUserid($id);
        if ($form->isSubmitted() && $form->isValid()) {
            $heureStart = explode(" ", $form->get('datestart')->getData())[1];
            $heureEnd = explode(" ", $form->get('dateend')->getData())[1];
            $pause =  $form->get('pause')->getData();
            
            $heureStart = explode(":", $heureStart);
            $heureStart = ((int)$heureStart[0]*60)+(int)$heureStart[1];
            $heureEnd = explode(":", $heureEnd);
            $heureEnd = ((int)$heureEnd[0]*60)+(int)$heureEnd[1];
            $pause = $pause*60;
            $time = $heureEnd - $heureStart - $pause;
            $time = $time/60;

            $horaire->setTime($time);
            $horaire->setPause($form->get('pause')->getData());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($horaire);
            $entityManager->flush();

            return $this->redirectToRoute('horaire_list');
        }

        return $this->render('horaire/add.html.twig', [
            'chantier' => $horaire,
            'form' => $form->createView(),
            'user' => $user,
            'debut' => $date_debut,
            'fin' => $date_fin,
        ]);
    }

    /**
     * @Route("/{horaire}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $horaire)
    {
        $me = $this->getUser();
        $horaire = $this->getDoctrine()->getRepository(Horaire::class)->find($horaire);
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($horaire->getUserid());
        $chantier = null;
        if ($horaire->getChantierid())
            $chantier = $this->getDoctrine()->getRepository(Chantier::class)->find($horaire->getChantierid());

        $form = $this->createForm(HoraireEditType::class, $horaire, array(
            'chantierid' => $chantier,
            'entreprise' => $this->session->get('entreprise_session_id')
        ));

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($horaire);
            $entityManager->flush();

            return $this->redirectToRoute('horaire_list');
        }

        return $this->render('horaire/edit.html.twig', [
            'horaire' => $horaire,
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/prime/user/{uid}/mois/{mois}/annee/{annee}", name="prime")
     */
    public function prime(Request $request, $uid, $mois, $annee)
    {
        $montant = $request->request->get('prime');
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($uid);
        $args = ['annee' => $annee, 'mois' => $mois, 'user' => $user];
        $prime = $this->getDoctrine()->getRepository(Prime::class)->findOneBy($args);
        if (!$prime) {
            $prime = new Prime();
        }
        $prime->setUser($user);
        $prime->setMois($mois);
        $prime->setAnnee($annee);
        $prime->setPrime($montant);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($prime);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }


    /**
     * @Route("/observation/user/{uid}/mois/{mois}/annee/{annee}", name="observation")
     */
    public function observation(Request $request, $uid, $mois, $annee)
    {
        $text = $request->request->get('observation');
        $cout_global = $request->request->get('cout_global');
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($uid);
        $args = ['annee' => $annee, 'mois' => $mois, 'user' => $user];
        $obs = $this->getDoctrine()->getRepository(HoraireObservation::class)->findOneBy($args);
        if (!$obs) {
            $obs = new HoraireObservation();
        }
        $obs->setUser($user);
        $obs->setMois($mois);
        $obs->setAnnee($annee);
        if($text)
            $obs->setObservation($text);
        if($cout_global)
            $obs->setCoutGlobal((float)$cout_global);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($obs);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/galerie/{user_id}/{galerie_id}/delete", name="delete_galerie")
     */
    public function delete_galerie(Request $request, $user_id, $galerie_id)
    {

        $galerie = $this->getDoctrine()->getRepository(Galerie::class)->findOneBy(['user'=>$user_id,'id'=>$galerie_id]);

        if($galerie){
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($galerie);
            $entityManager->flush();
        }

        $docs = $this->getDoctrine()->getRepository(Galerie::class)->findBy(['user'=>$user_id,'type'=>4]);
        $docs = $this->renderView('horaire/docs.html.twig',['docs'=>$docs,'uid'=>$user_id]);

        return new JsonResponse(['success' => true,'docs'=>$docs]);
    }

    /**
     * @Route("/file_galerie", name="file_galerie")
     */
    public function file_galerie(Request $request)
    {
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($request->request->get('uid'));
        $galerie = new Galerie();
        $fichier = $request->files->get('fichier');
        if ($fichier) {
            $originalFilename = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
            // this is needed to safely include the file name as part of the URL
            $safeFilename = $this->slugify($originalFilename);
            $extension = $fichier->guessExtension();
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $fichier->guessExtension();

            // Move the file to the directory where brochures are stored
            try {
                $dir = $this->get('kernel')->getProjectDir() . "/public/galerie/" . $this->session->get('entreprise_session_id') . "/" . date('Y-m-d') . "/";
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $fichier->move(
                    $dir,
                    $newFilename
                );
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }

            // updates the 'brochureFilename' property to store the PDF file name
            // instead of its contents
            $galerie->setNom($newFilename);
            $galerie->setUser($user);
            $galerie->setExtension($extension);
            
            $galerie->setType(4);

            $galerie->setEntreprise($this->entrepriseRepository->find($this->session->get('entreprise_session_id')));
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($galerie);
        $entityManager->flush();

        $docs = $this->getDoctrine()->getRepository(Galerie::class)->findBy(['user'=>$user,'type'=>4]);
        $docs = $this->renderView('horaire/docs.html.twig',['docs'=>$docs,'uid'=>$user->getUid()]);

        return new JsonResponse(['success' => true,'docs'=>$docs]);
    }

    /**
     * @Route("/modif", name="modif")
     */
    public function modif(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $id = $request->request->get('id');
        $heure = str_replace(',', '.', $request->request->get('heure'));
        $horaire = $this->getDoctrine()->getRepository(Horaire::class)->find($id);
        $horaire->setFictif((float)$heure);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/modif-without-id", name="modif_without_id")
     */
    public function modifWithoutId(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $date = $request->request->get('date');
        $user = $request->request->get('user');
        $heure = str_replace(',', '.', $request->request->get('heure'));
        $horaire = $this->getDoctrine()->getRepository(Horaire::class)->findOneBy(array(
            'chantierid' => null,
            'datestart' => Carbon::parse($date)->setTime(0, 0, 0),
            'userid' => $user
        ));
        if (!$horaire)
            $horaire = new Horaire();
        $horaire->setFictif((float)$heure);
        $horaire->setTime(0);
        $horaire->setDatestart(Carbon::parse($date)->setTime(0, 0, 0));
        $horaire->setUserid($user);
        $entityManager->persist($horaire);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }


    /**
     * @Route("/absence", name="absence")
     */
    public function absence(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $date = $request->request->get('date');
        $user = $request->request->get('user');
        $id = $request->request->get('id');
        $absence = $request->request->get('absence');
        if ((int)$id > 0) {
            $horaire = $this->getDoctrine()->getRepository(Horaire::class)->find($id);
        } else {
            $horaire = $this->getDoctrine()->getRepository(Horaire::class)->findOneBy(array(
                'chantierid' => null,
                'datestart' => Carbon::parse($date)->setTime(0, 0, 0),
                'userid' => $user
            ));
            if (!$horaire) {
                $horaire = new Horaire();
            }
            $horaire->setTime(0);
            $horaire->setFictif(0);
            $horaire->setDatestart(Carbon::parse($date)->setTime(0, 0, 0));
            $horaire->setUserid($user);
        }
        $horaire->setAbsence($absence);
        $entityManager->persist($horaire);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }


    /**
     * @Route("/validation/annee/{annee}/mois/{mois}/user/{userid}", name="validation")
     */
    public function validation(Request $request, $annee, $mois, $userid)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userid);
        $args = ['mois' => $mois, 'annee' => $annee, 'user' => $user];
        $validation = $this->getDoctrine()->getRepository(HoraireValidation::class)->findOneBy($args);
        if (!$validation) {
            $validation = new HoraireValidation();
            $validation->setUser($user);
            $validation->setMois($mois);
            $validation->setAnnee($annee);
            $validation->setIsValide(true);
            $entityManager->persist($validation);
        } else {
            $validation->setIsValide(!$validation->getIsValide());
        }
        $entityManager->flush();

        return $this->redirectToRoute('horaire_list');
    }

    /**
     * @Route("/{horaire}/delete", name="delete")
     */
    public function delete(Request $request, $horaire)
    {
        $horaire = $this->getDoctrine()->getRepository(Horaire::class)->find($horaire);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($horaire);
        $entityManager->flush();

        return $this->redirectToRoute('horaire_list');
    }


    /**
     * @Route("/print/mois/{mois}/annee/{annee}", name="print")
     */
    public function print(Request $request, $mois, $annee)
    {   
        $userFilter = $request->query->get('user');
        $chantierFilter = $request->query->get('chantier');

        $me = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $spreadsheet = new Spreadsheet();
        // backup
        $saveMois = $mois;
        $saveAnnee = $annee;

        /** @var SheetView $sheet */
        $collumn = 2;
        $rows = 3;
        $sheet = $spreadsheet->getActiveSheet();

        $query_user = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->where('c.sous_traitant = :etat or c.sous_traitant is null')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
            ->setParameter('etat', false);
        if($userFilter){
            $query_user = $query_user->andWhere('c.uid = :userId')
            ->setParameter('userId', $userFilter);
        }
            
        $query = $query_user->getQuery();
        $query_user = $query->execute();

        $ids = [];
        foreach ($query_user as $key => $value) {
            if( ($value->getRawDateEntree() && ( (int)$annee.$mois >= (int)$value->getRawDateEntree()->format('Ym') )) && ($value->getRawDateSortie() && ( (int)$annee.$mois <= (int)$value->getRawDateSortie()->format('Ym') ))){
                $ids[]=$value->getUid();
            }
            elseif($value->getRawDateEntree() && ( (int)$annee.$mois >= (int)$value->getRawDateEntree()->format('Ym') ) && !$value->getRawDateSortie()){
                $ids[]=$value->getUid();
            }
        }

        /** @var Utilisateur[] $users */
        $users = $em->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Utilisateur', 'c')
            ->andWhere('c.etat = :etat')
            ->andWhere('c.sous_traitant = :st or c.sous_traitant is null')
            ->andWhere("c.uid IN(:ids)")
            ->orderBy('c.num', 'asc')
            ->setParameter('etat', 1)
            ->setParameter('st', false)
            ->setParameter('ids', array_values($ids))
            ->getQuery()
            ->getResult();
        $d = false;

        # Api Feriés
        $content = file_get_contents('https://jours-feries-france.antoine-augusti.fr/api/' . $annee);
        $feries = json_decode($content);
        $sheet->getStyle(1)->getAlignment()->setHorizontal('center');
        $sheet->getStyle(1)->getFont()->setBold(true);
        $sheet->getCellByColumnAndRow(1, 2)->setValue("Type de contrat");
        $sheet->getStyle("A2")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A2")->getFont()->setBold(true);

        // $sheet->getStyle('B3:B7')->getFill()
        //     ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::BORDER_THICK)
        //     ->getStartColor()->setARGB('FFFF0000');
        $merge_collumns = array();
        $border_rows  = array( 1 );
        $periode_list = array( );
        foreach ($users as $u => $user) {
            $rows = 4;
            if ($u > 0) {
                $d = true;
            }

            $sheet->getCellByColumnAndRow($collumn, 1)->setValue($user->getFirstname() . ' ' . $user->getLastname());
            $type_contrat = $user->getTypeContrat() ? $user->getTypeContrat()->getNom() : "";
            $sheet->getCellByColumnAndRow($collumn, 2)->setValue($type_contrat);

            $horaires = $em->createQueryBuilder()
                ->select('h')
                ->from('App\Entity\Horaire', 'h')
                ->where('h.datestart BETWEEN :debut AND :fin')
                ->andwhere('h.userid = :user')
                ->setParameter('debut', Carbon::parse("$annee-$mois-01")->subMonth()->format('Y-m-d') . ' 00:00:00')
                ->setParameter('fin', Carbon::parse("$annee-$mois-01")->lastOfMonth()->format('Y-m-d') . ' 23:59:59')
                ->setParameter('user', $user->getUid());

                if($userFilter){
                    $horaires = $horaires->andWhere('h.userid = :userId')
                    ->setParameter('userId', $userFilter);
                }
                if($chantierFilter){
                    $horaires = $horaires->andWhere('h.chantierid = :chantierId')
                    ->setParameter('chantierId', $chantierFilter);
                }
                $horaires = $horaires->orderBy('h.userid')
                ->getQuery()
                ->getResult();

            $max = Carbon::parse("$annee-$mois-01")->lastOfMonth()->day;


            // Si le premier du mois n'est pas un lundi, on recupère les jours de la semaine
            $diff = 0;
            if ( (Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('dddd') != "lundi") && 
                !in_array(Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('dddd'), array('samedi', 'dimanche')) ) {
                $diff = Carbon::parse("$saveAnnee-$saveMois-01")->startOfWeek()->locale('fr')->diffInDays(Carbon::parse("$saveAnnee-$saveMois-01")->locale('fr'));
            }


            $k = 0;
            $hSemaine = 0;
            $hCongesSemaine = 0;
            $hFerieSemaine = 0;
            $chomage_partiel_semaine = null;
            $nb_heure_abs_fictive = 0;
            $hSemainePrec = 0;
            $hTotal = 0;
            $hSuppTotal = 0;
            $hFerieTotal = 0;
            $hSuppTotal50 = 0;
            $hAbsenceTotalNa = 0;
            $hAbsenceTotal = 0;
            $chomage_partiel = null;
            $heure_normales_fictives = 0;
            $nbJoursTravailles = null;
            $h_length = 0;
            $conges = 0;
            $a = $diff != 0 ? 1 - $diff : 1;
            $oldI = null;

            for ($i = $a; $i <= $max; $i++) {
                // Si mois précedent, alors
                if ($i <= 0) {
                    $oldI = $i;
                    $mois_precedent = true;
                    $i = Carbon::parse("$saveAnnee-$saveMois-01")->subDays(abs($i - 1))->format('d');
                    $mois = Carbon::parse("$saveAnnee-$saveMois-01")->subDays(abs($i - 1))->format('m');
                    $annee = Carbon::parse("$saveAnnee-$saveMois-01")->subDays(abs($i - 1))->format('Y');
                } else {
                    $mois_precedent = false;
                    $mois = Carbon::parse("$saveAnnee-$saveMois-" . ($i < 10 ? "0$i" : $i))->format('m');
                    $annee = Carbon::parse("$saveAnnee-$saveMois-" . ($i < 10 ? "0$i" : $i))->format('Y');
                }

                if (Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "dimanche" 
                    || (Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('dddd') == "lundi" && $i == $a)
                ) {
                    $h_length = min($h_length, 5);
                    if (!$mois_precedent) {


                        $periode = strtoupper(
                            Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i) )->locale('fr')->isoFormat('MMMM YYYY') );

                        if(! in_array($periode, $periode_list) ){

                            // $rows++;
                            $sheet->getCellByColumnAndRow(1, $rows)->setValue($periode);
                            $mergeCells[] = $rows;

                            $periode_list[]=$periode; 

                        }  

                        if($i != $a){
                            $this->totalSemaine($sheet, $user, $rows, $collumn,$h_length, $hSemaine, $hTotal, $hSuppTotal, $hSuppTotal50, $hCongesSemaine, $hAbsenceTotal, $conges, $hFerieSemaine, $hFerieTotal, $chomage_partiel_semaine, $chomage_partiel, $hSemainePrec, $nb_heure_abs_fictive, $hAbsenceTotalNa, $heure_normales_fictives,$spreadsheet);
                        
                        }
                        else
                            $rows++;

                        $hSemaine = 0;
                        $hCongesSemaine = 0;
                        $hFerieSemaine = 0;
                        $nb_heure_abs_fictive = 0;
                        $chomage_partiel_semaine = 0;
                        $h_length = 0;
                        $hSemainePrec = 0;
                    }

                    if($i != $a)
                        continue;
                }

                if ($i > 1 && Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "lundi") {
                    $k++;
                }

                if (!$d) {
                    $jour = Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd Do MMMM YYYY');
                    foreach ($feries as $ferie) {
                        if ($ferie->date == "$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i)) {
                            $jour .= ' - FERIE - ' . $ferie->nom_jour_ferie;
                        }
                    }
                    if( ($i != $a) || ( $i == $a && Carbon::parse("$annee-$mois-01")->locale('fr')->isoFormat('dddd') == "lundi")){
                        $sheet->getCellByColumnAndRow(1, $rows)->setValue($jour?$jour:'');
                    }
                }
                $hJour = $this->checkFictifOrAbsence("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires);
                if (!$mois_precedent) {

                    if (!in_array(Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd'), array('samedi', 'dimanche'))) {
                        $h_length++;
                    }
                    if (is_numeric($hJour)) {
                        if ($hJour > 0) {
                            $nbJoursTravailles++;
                            $hSemaine = $hSemaine + $hJour;
                        } else {
                            // S'il n'y a pas d'heure sur un jour ouvré alors 7 en absence non autorisé
                            if("samedi" != Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd')) {
                                $nb_heure_abs_fictive = $nb_heure_abs_fictive - ($user->getHeureHebdo() / 5);
                            }
                        }
                    } else {
                        // Congés payés
                        if ($hJour["id"] == 1 && $mois_precedent == false) {
                            $hCongesSemaine = $hCongesSemaine + ($user->getHeureHebdo() / 5);
                        } // Fériés
                        elseif ($hJour["id"] == 7 && $mois_precedent == false) {
                            $hFerieSemaine = $hFerieSemaine + ($user->getHeureHebdo() / 5);
                        } // Chomage partiel
                        elseif ($hJour["id"] == 3 && $mois_precedent == false) {
                            $chomage_partiel_semaine = 1;
                        } // Absence
                        elseif ($hJour["id"] == 4 && "samedi" != Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd')) {
                            $nb_heure_abs_fictive = $nb_heure_abs_fictive - ($user->getHeureHebdo() / 5);
                        }
                        $hJour = $hJour["motif"];
                    }

                } else {
                    if (is_numeric($hJour)) {
                        if ($hJour > 0) {
                            $hSemainePrec = $hSemainePrec + $hJour;
                        }
                    } else {
                        $hJour = $hJour["motif"];
                    }
                }
                $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hJour?$hJour:'');

                $border_rows[] = $rows;

                $rows++;

                // Si mois précedent, alors on remet le compteur en place
                if ($oldI !== null) {
                    $i = $oldI;
                    $oldI = null;
                }
            }
            if ($h_length != 0)
                $this->totalSemaine($sheet, $user, $rows, $collumn, $h_length, $hSemaine, $hTotal, $hSuppTotal, $hSuppTotal50, $hCongesSemaine, $hAbsenceTotal, $conges, $hFerieSemaine, $hFerieTotal, $chomage_partiel_semaine, $chomage_partiel, $hSemainePrec, $nb_heure_abs_fictive, $hAbsenceTotalNa, $heure_normales_fictives,$spreadsheet);
            $rows++;
            /* Merge for  TOTAL ROW */
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL GENERAL');
             $sheet->mergeCells('A'.$rows.':J'.$rows);
            $sheet->getStyle('A'.$rows)->getFont()->setSize(25);

            $sheet->getStyle('A'.$rows)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);

            

            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);

            $border_rows[] = $rows;
            $rows++;
            $rows++;

            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL GENERAL HEURES');

            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hTotal?$hTotal:'');
            $border_rows[] = $rows;
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL HEURES SUP à 25%');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hSuppTotal?$hSuppTotal:'');
            $border_rows[] = $rows;
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL HEURES SUP à 50%');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hSuppTotal50?$hSuppTotal50:'');
            $border_rows[] = $rows;
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL ABSENCES NON AUTORISÉS');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hAbsenceTotalNa?$hAbsenceTotalNa:'');
            $border_rows[] = $rows;
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL ABSENCES AUTORISÉS');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hAbsenceTotal?$hAbsenceTotal:'');
            $border_rows[] = $rows;
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL HEURES NORMALES');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($heure_normales_fictives?$heure_normales_fictives:'');
            $border_rows[] = $rows;
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL Congés Payés');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($conges?$conges:'');
            $border_rows[] = $rows;
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL FÉRIÉS');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hFerieTotal?$hFerieTotal:'');
            $border_rows[] = $rows;
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL CHOMÂGE PARTIEL');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($chomage_partiel?$chomage_partiel:'');
            $border_rows[] = $rows;
            $rows = $rows + 2;
            $args = ['annee' => $annee, 'mois' => Carbon::create()->month($mois)->endOfMonth()->locale('fr')->isoFormat('MMMM'), 'user' => $user];
            /** @var Prime $prime */
            $prime = $this->getDoctrine()->getRepository(Prime::class)->findOneBy($args);
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('PRIME');

            $sheet->getStyle('A'.$rows.':'.$spreadsheet->getActiveSheet()->getHighestColumn().$rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF92D050');

            if ($prime) {
                $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($prime->getPrime());
            } else {
                $sheet->getCellByColumnAndRow($collumn, $rows)->setValue('');
            }
            $rows++;

            $sheet->getCellByColumnAndRow(1, $rows)->setValue('NOMBRE DE JOURS TRAVAILLES');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $border_rows[] = $rows;
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($nbJoursTravailles?$nbJoursTravailles:'');
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('NOMBRE DE TRAJETS');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($user->getTrajet() == 1 ? $nbJoursTravailles : '');
            $border_rows[] = $rows;



            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('NOMBRE DE PANIERS');
            $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
            $border_rows[] = $rows;
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($user->getPanier() == 1 ? $nbJoursTravailles : '');
            $collumn++;
        }



        # Resize collumn
        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestDataColumn()) as $col) {
            $spreadsheet->getActiveSheet()
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }

        # On supprime les lignes ou le samedi il y a 0
        $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
        $highestCollumn = $spreadsheet->getActiveSheet()->getHighestColumn();
        for ($i = 1; $i <= $highestRow; $i++) {
            $value = $sheet->getCellByColumnAndRow(1, $i)->getValue();
            if (strpos($value, 'samedi') !== false) {
                # Resize collumn
                foreach (range('A', $spreadsheet->getActiveSheet()->getHighestDataColumn()) as $p => $col) {
                    $z = $p + 1;

                    $zvalue = (float)$sheet->getCellByColumnAndRow($z, $i)->getValue();
                    if ($zvalue > 0 || !is_numeric($sheet->getCellByColumnAndRow($z, $i)->getValue()))
                        continue 2;
                }
                $sheet->removeRow($i);
            }
        }
        foreach ($mergeCells as $key => $value) {
            
            $sheet->getStyle('A'.$value)->getFont()->setSize(25);
            $sheet->getStyle('A'.$value)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A'.$value)->getFont()->setBold(true);
            $sheet->mergeCells('A'.$value.':'.$spreadsheet->getActiveSheet()->getHighestColumn().''.$value);

        }
        foreach ($border_rows as $key => $value) {
           
            $coordinate = 'A'.$value.':'.$spreadsheet->getActiveSheet()->getHighestColumn().''.$value;
            $sheet->getStyle($coordinate)->applyFromArray(
                [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ]
                ]
            );

            $sheet->getStyle('B'.$value.':'.$spreadsheet->getActiveSheet()->getHighestColumn().$rows)->getAlignment()->setHorizontal('center');


        }

        $sheet->setTitle("Liste des horaies");

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // In this case, we want to write the file in the public directory
        $publicDirectory = $this->get('kernel')->getProjectDir() . '/public';
        // e.g /var/www/project/public/my_first_excel_symfony4.xlsx
        $excelFilepath = $publicDirectory . '/liste_des_horaires.xlsx';
        try {
            if (!is_dir($publicDirectory)) {
                mkdir($publicDirectory, 0777, true);
            }
        } catch (FileException $e) {}
        // Create the file
        $writer->save($excelFilepath);

        // Return a text response to the browser saying that the excel was succesfully created
        return $this->file($excelFilepath, 'liste_des_horaires.xlsx', ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/print-tache/mois/{mois}/annee/{annee}", name="print_tache")
    */
    public function printTache(Request $request, $mois = null, $annee = null)
    {
        $userFilter = $chantierFilter = null;
        if($request->query->get('user'))
            $userFilter = $request->query->get('user');
        if($request->query->get('chantier'))
            $chantierFilter = $request->query->get('chantier');

        $mois = ((int)$mois == 0) ? null : (int)$mois;
        $annee = ((int)$annee == 0) ? null : (int)$annee;

        $horaires = $this->horaireRepository->findHoraireUserByDateAndChantier($mois, $annee, $userFilter, $chantierFilter);

        $horaires = array_map(function($val){
            $val['datestart'] = Carbon::parse($val['datestart'])->locale('fr')->isoFormat('D MMMM YYYY');
            return  $val;
        }, $horaires);

        $em = $this->getDoctrine()->getManager();
        
        $rowArray = [];
        foreach ($horaires as $value) {
            $currentHoraire = [];
            $currentHoraire[] = $value['lastname'] .' '.$value['firstname'];
            $currentHoraire[] = $value['nameentreprise'];
            $currentHoraire[] = $value['datestart'];
            $currentHoraire[] = $value['fonction'];
            $currentHoraire[] = $value['time'];
            $currentHoraire[] = $value['document_id'];

            $rowArray[] = $currentHoraire;
        }
        
        array_unshift($rowArray, ["Utilisateur", "Chantier", "Date", "Tache", "Heure", "Devis"]);

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
        
        $sheet->setTitle("Liste taches");

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // In this case, we want to write the file in the public directory
        $publicDirectory = $this->get('kernel')->getProjectDir() . '/public/uploads/tache_excel/';
        try {
            if (!is_dir($publicDirectory)) {
                mkdir($publicDirectory, 0777, true);
            }
        } catch (FileException $e) {}

        // e.g /var/www/project/public/my_first_excel_symfony4.xlsx
        $excelFilepath = $publicDirectory . 'liste_taches.xlsx';

        // Create the file
        $writer->save($excelFilepath);

        // Return a text response to the browser saying that the excel was succesfully created
        return $this->file($excelFilepath, 'liste_taches.xlsx', ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/horaire-devis-attach", name="devis_attach")
     */
    public function attachHoraireDevis(Request $request, Session $session)
    {
        $listHoraire = explode('-', $request->request->get('list-elt-id'));
        $devisId = $request->request->get('devis');
        $this->horaireRepository->attachDevis($listHoraire, $devisId);

        return $this->redirectToRoute('horaire_horaire_devis');
    }

    /**
     * @Route("/dettach-devis/{horaireId}", name="dettach_horaire_devis")
     */
    public function detachHoraireDevis(Request $request, Session $session, $horaireId)
    {
        $horaire = $this->horaireRepository->find($horaireId);
        $horaire->setDevis(null);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
        return $this->redirectToRoute('horaire_horaire_devis');
    }

    /**
     * @Route("/horaire-devis", name="horaire_devis")
     */
    public function horaireDevis(Request $request, Session $session)
    {
        $em = $this->getDoctrine()->getManager();
        $page = $request->query->get('page', 1);
        $form = $request->request->get('form', null);

        $chantierId = $ouvrierId = $devisId = $is_devis_rattach = $tache = null;
        if ($form) {
            $mois = (!(int)$form['mois']) ? null : (int)$form['mois'];
            $annee = (!(int)$form['annee']) ? null : (int)$form['annee'];
            $chantierId = (!(int)$form['chantier']) ? null : (int)$form['chantier'];
            $ouvrierId = (!(int)$form['ouvrier']) ? null : (int)$form['ouvrier'];
            $tache = (!$form['tache']) ? null : $form['tache'];
            $is_devis_rattach = (!(int)$form['is_devis_rattach']) ? null : (int)$form['is_devis_rattach'];
            $devisId = (!(int)$form['devis']) ? null : (int)$form['devis'];
            $horaires = $this->horaireRepository->findByParams($chantierId, $ouvrierId, $devisId, $mois, $annee, $is_devis_rattach, $tache);

            $tabTaches = $this->horaireRepository->getAllTacheByParams($chantierId, $ouvrierId, $devisId, $mois, $annee, $is_devis_rattach);

            $session->set('mois_h_devis', $mois);
            $session->set('annee_h_devis', $annee);
            $session->set('chantier_h_devis', $chantierId);
            $session->set('ouvrier_h_devis', $ouvrierId);
            $session->set('ouvrier_h_tache', $tache);
            $session->set('is_devis_rattach_h_devis', $is_devis_rattach);
            $session->set('devis_h_devis', $devisId);
        } else {
            $mois = $session->get('mois_h_devis', date('m'));
            $annee = $session->get('annee_h_devis', date('Y'));
            $chantierId = $session->get('chantier_h_devis', null);
            $ouvrierId = $session->get('ouvrier_h_devis', null);
            $tache = $session->get('ouvrier_h_tache', null);
            $is_devis_rattach = $session->get('is_devis_rattach_h_devis', null);
            $devisId = $session->get('devis_h_devis', null);
            $horaires = $this->horaireRepository->findByParams($chantierId, $ouvrierId, $devisId, $mois, $annee, $is_devis_rattach, $tache);

            $tabTaches = $this->horaireRepository->getAllTacheByParams($chantierId, $ouvrierId, $devisId, $mois, $annee, $is_devis_rattach);
        }

        if(!is_null($chantierId)){
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'chantier'=>$chantierId, 'type'=>'devis_client']);
        }
        else{
            $devis = $this->getDoctrine()->getRepository(Vente::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>'devis_client']);
        }

        $tacheFilter = [];
        foreach ($tabTaches as $key => $value) {
            if(!is_null($value['fonction']))
                $tacheFilter[$value['fonction']] = $value['fonction'];
        }

        if(strlen($mois) < 2 && $mois<10){
            $mois = "0".$mois;
        }
        $session->set('mois', $mois);
        $session->set('annee', $annee);

        $chantierHoraires = $this->horaireRepository->getAllTacheByParams($chantierId, $ouvrierId, $devisId, $mois, $annee, $is_devis_rattach, 1);
        $tabChantierHoraire = [];
        foreach ($chantierHoraires as $value) {
            $tabChantierHoraire[] = $value['chantier_id'];
        }

        $form = $this->createFormBuilder(array(
            'mois' => (int)$mois,
            'annee' => (int)$annee,
            'tache' => $tache,
            'chantier' => (!is_null($chantierId)) ? $this->getDoctrine()->getRepository(Chantier::class)->find($chantierId) : "",
            'ouvrier' => (!is_null($ouvrierId)) ?  $this->getDoctrine()->getRepository(Utilisateur::class)->find($ouvrierId) : "",
            'devis' => (!is_null($devisId)) ?  $this->getDoctrine()->getRepository(Vente::class)->find($devisId) : "",
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
            'query_builder' => function(EntityRepository $repository) use ($tabChantierHoraire){ 
                return $repository->createQueryBuilder('c')
                ->andwhere('c.chantierId IN (:in)')
                ->andWhere('c.entreprise = :entreprise')
                ->setParameter('in', $tabChantierHoraire)
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
        ->add('tache', ChoiceType::class, array(
            'label' => "Taches",
            'choices' => $tacheFilter,
            'required'=>false,
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('ouvrier', EntityType::class, array(
            'class' => Utilisateur::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('c')
                ->where('c.sous_traitant = :etat or c.sous_traitant is null')
                ->andWhere('c.entreprise = :entreprise')
                ->andWhere('c.etat = :verif')
                ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                ->setParameter('etat', false)
                ->setParameter('verif', 1)
                ->orderBy('c.lastname', 'ASC');
            },
            'required' => false,
            'label' => "Ouvrier",
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
        ->add('devis', EntityType::class, array(
            'class' => Vente::class,
            'query_builder' => function(EntityRepository $repository) use ($chantierId) { 
                $req = $repository->createQueryBuilder('v')
                ->leftjoin('v.chantier', 'c')
                ->addSelect('c');
                if(!is_null($chantierId)){
                    $req = $req->andWhere('c.chantierId = :chantierId')
                        ->setParameter('chantierId', $chantierId);
                }
                $req = $req->andWhere('v.entreprise = :entreprise')
                ->andWhere('v.type = :type')
                ->setParameter('entreprise', $this->session->get('entreprise_session_id'))
                ->setParameter('type', 'devis_client')
                ->orderBy('c.nameentreprise', 'ASC');

                return $req;
            },
            'required' => false,
            'label' => "devis",
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();
        
        $dataGroup = $this->buildHoraireGroupSemaine($horaires, $chantierId, $ouvrierId, $is_devis_rattach);
        $horairesSemaines = $dataGroup['horairesArr'];
        $totaux = $dataGroup['totaux'];

        $userSms = $this->getDoctrine()->getRepository(UserSms::class)->findBy(["entreprise"=>$this->session->get('entreprise_session_id')]);

        $userSmsArr = [];
        foreach ($userSms as $value) {
            $date_horaire = Carbon::parse( $value->getDateHoraire()->format('Y-m-d'))->locale('fr')->isoFormat('dddd Do MMMM YYYY');
            $userSmsArr[] = $value->getUtilisateur()->getUid().'-'.$date_horaire;
        }

        $userMarkIgnored = $this->getDoctrine()->getRepository(UserHoraireAbsent::class)->findBy(["entreprise"=>$this->session->get('entreprise_session_id')]);
        $userMarkIgnoredInfo = [];
        $userMarkIgnoredArr = [];
        foreach ($userMarkIgnored as $value) {
            $date_horaire = Carbon::parse( $value->getDateHoraire()->format('Y-m-d'))->locale('fr')->isoFormat('dddd Do MMMM YYYY');
            $userMarkIgnored[] = $value->getUtilisateur()->getUid().'-'.$date_horaire;
            $userMarkIgnoredInfo[$value->getUtilisateur()->getUid().'-'.$date_horaire] = $value;
        }

        $utilisateurs = $this->utilisateurRepository->getUserByEntreprise();
        return $this->render('horaire/horaire_devis.html.twig', [
            'horairesSemaines' => $horairesSemaines,
            'totaux' => $totaux,
            'devis'=>$devis,
            'is_devis_rattach'=>$is_devis_rattach,
            'mois'=>$mois,
            'annee'=>$annee,
            'chantier'=>$chantierId,
            'user'=>$ouvrierId,
            'form' => $form->createView(),
            'utilisateurs'=>$this->global_s->getUserByMiniature($utilisateurs),
            'chantiers'=>$this->chantierRepository->findBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['nameentreprise'=>'ASC']),
            'entreprises'=>$this->entrepriseRepository->findAll(),
            'user_sms'=>$userSmsArr,
            'user_mark_ignored'=>$userMarkIgnored,
            'user_mark_ignored_infos'=>$userMarkIgnoredInfo,
            "abscence"=>[
                1=>"Congés Payés",
                2=> "En arrêt",
                3=>"Chômage Partiel", 
                4=>"Absence", 
                5=>"Formation", 
                6=> "RTT", 
                7=>"Férié"
            ]
        ]);
    }


    public function buildHoraireGroupSemaine($horaires, $chantierId, $ouvrierId, $is_devis_rattach){

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

                $currentYear = (new \DateTime($value['datestart']))->format('Y');
                $currentMonth = (new \DateTime($value['datestart']))->format('m');
                $week = (new \DateTime($value['datestart']))->format("W");
                $key = "Semaine ".$week."  année ".$currentYear;

                $paie = $this->paieRepository->getLastPaieWithTx($value['user_id']);
                $value['tx_moyen'] = $paie ? $paie['tx_moyen'] : 0;

                $horairesArr[$key][] = $value;
                $totaux['heure'] +=  $value['time'];


                if(!array_key_exists($value['absence'], $absences))
                    $totaux['fictif'] +=  $value['fictif'];
                elseif($value['absence'] == 5){
                    $totaux['fictif'] +=  7;
                }


                //$totaux['tx_moyen'] +=  $value['tx_moyen'];

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

        
        foreach ($horairesArr as $key => $value) {
            $horairesArr[$key] = $this->groupByDay($value, $chantierId, $ouvrierId, $is_devis_rattach);   
        }
        $totaux['tx_moyen'] = $txMoyen;
        return ['totaux'=> $totaux,  'horairesArr'=> $horairesArr];
    }

    public function groupByDay($horaires, $chantierId, $ouvrierId, $is_devis_rattach){
        
        $horairesArr = [];
        if(count($horaires)){
            foreach ($horaires as $value) {
                $currentDay = Carbon::parse((new \DateTime($value['datestart']))->format('Y-m-d'))->locale('fr')->isoFormat('dddd Do MMMM YYYY');
                $horairesArr[$currentDay][] = $value;
            }
        }
        foreach ($horairesArr as $key => $value) {
            $horairesArr[$key] = $this->groupByUser($value, $chantierId, $ouvrierId, $is_devis_rattach);   
        }
        return $horairesArr;
    }

    public function groupByUser($horaires, $chantierId, $ouvrierId, $is_devis_rattach){
        $horairesArr = [];
        if(count($horaires)){
            foreach ($horaires as $key => $value) {
                $value['datestart'] = Carbon::parse($value['datestart'])->locale('fr')->isoFormat('dddd Do MMMM YYYY');
                $value['dateend'] = Carbon::parse($value['dateend'])->locale('fr')->isoFormat('dddd Do MMMM YYYY');
                $value['time'] = (float)$value['time'];
                if($value['absence'] == 1 || $value['absence'] == 5)
                    $value['fictif'] = 7;

                $horairesArr[$value['user_id']][] = $value;
            }

            $is_week = false;
            if($value['datestart'] != ""  && in_array(strtolower(explode(" ", $value['datestart'])[0]), ["samedi", "dimanche"])){
                $is_week = true;
            }
            
            if(is_null($chantierId) && is_null($ouvrierId) && !$is_week){
                if( is_null($is_devis_rattach) || (!is_null($is_devis_rattach) && $is_devis_rattach != 2)){
                    
                    $utilisateurs = $this->utilisateurRepository->getUserByEntreprise(true);
                    foreach ($utilisateurs as $key => $value) {
                        if(!array_key_exists($value['uid'], $horairesArr)){
                            $horairesArr[$value['uid']][] = [ 
                                    "firstname" => $value['firstname']." ".$value['lastname'],
                                    "poste" => $value['poste'],
                                    "user_id" => $value['uid'],
                                    "image"=> $value['image'],
                                    "datestart" => "",
                                    "absence" => "",
                                    "dateend" => "",
                                    "fonction" => "",
                                    "fictif" => "",
                                    "idsession" => "",
                                    "time" => "",
                                    "nameentreprise" => "",
                                    "chantier_id" => null,
                                    "document_file" => null,
                                    "document_id" => null,
                                    "date_entree" => null,
                                    "date_sortie" => null
                                ]; 
                        }
                    }
                }
            }
        }

        return $horairesArr;
    }

    /**
     * @Route("/user-mark-ignored", name="user_mark_ignored")
     */
    public function markUserIgnored(Request $request, Session $session)
    {
        $userId = $request->request->get('user_id');
        $dateHoraire = $request->request->get('date_horaire');
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userId);
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));

        $dateFormat = Carbon::parseFromLocale($dateHoraire, 'fr', 'UTC');
        $date = new \DateTime($dateFormat->isoFormat('YYYY-MM-DD')); 

        $userHoraireAbsent = new UserHoraireAbsent();
        $userHoraireAbsent->setDateHoraire($date);
        $userHoraireAbsent->setUtilisateur($user);
        $userHoraireAbsent->setEntreprise($entreprise);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($userHoraireAbsent);
        $entityManager->flush();

        return new JsonResponse(['status' => 200]);
    }

    function weekOfMonth($date) {
        //Get the first day of the month.
        $firstOfMonth = strtotime(date("Y-m-01", $date));
        //Apply above formula.
        return $this->weekOfYear($date) - $this->weekOfYear($firstOfMonth) + 1;
    }

    function weekOfYear($date) {
        $weekOfYear = intval(date("W", $date));
        if (date('n', $date) == "1" && $weekOfYear > 51) {
            // It's the last week of the previos year.
            return 0;
        }
        else if (date('n', $date) == "12" && $weekOfYear == 1) {
            // It's the first week of the next year.
            return 53;
        }
        else {
            // It's a "normal" week.
            return $weekOfYear;
        }
    }


    /**
     * @Route("/print-pdf/mois/{mois}/annee/{annee}/user/{userid}", name="print_pdf")
     */
    public function printPDF(Request $request, $mois, $annee, $userid)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->find($userid);

        $logo_entreprise = null;
        $entreprise = $this->entrepriseRepository->find($this->session->get('entreprise_session_id'));
        if ($entreprise && $entreprise->getLogo()) {
            $logo_entreprise = base64_encode(file_get_contents('logo/' . $entreprise->getLogo()));
        }

        $horaires = null;
        if ($user) {
            $query = $em->createQueryBuilder()
                ->select('s')
                ->from('App\Entity\Horaire', 's')
                ->where('s.userid = :user_id')
                ->andWhere('YEAR(s.datestart) = :year')
                ->andWhere('MONTH(s.datestart) = :month')
                ->setParameter('user_id', $user->getUid())
                ->setParameter('year', $annee)
                ->setParameter('month', $mois);

            if (!empty($chantier)) {
                $query->andWhere('s.chantierid = :chantier')
                    ->setParameter('chantier', $chantier);
            }

            $horaires = $query
                ->getQuery()
                ->getResult();
        }


        $max = Carbon::parse("$annee-$mois-01")->lastOfMonth()->day;
        $k = 0;
        for ($i = 1; $i <= $max; $i++) {
            if (Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "dimanche") {
                continue;
            }
            if ($i > 1 && Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "lundi") {
                $k++;
            }
            $m[$k][$i] = array(
                'timestamp' => Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->timestamp,
                'jour' => Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd Do MMMM YYYY'),
                'heures' => $this->checkDate("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'jour_numerique' => Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->format('Y-m-d'),
                'fictif' => $this->checkFictif("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'time' => $this->checkTime("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'absence' => $this->checkFictifOrAbsence("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
            );
            if (Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "samedi") {
                if (count($m[$k][$i]['heures']) == 0) {
                    unset($m[$k][$i]);
                }
            }
        }

        $html = $this->renderView(
            'horaire/modal.html.twig',
            array(
                'user' => $user,
                'mois' => Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('MMMM'),
                'annee' => $annee,
                'logo_entreprise' => $logo_entreprise,
                'horaires' => $m
            )
        );

        return new PdfResponse(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            'file.pdf'
        );
    }

    private function totalSemaine(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, $user, &$rows, $collumn, $h_length, $hSemaine, &$hTotal, &$hSuppTotal, &$hSuppTotal50, $hCongesSemaine, &$hAbsenceTotal, &$conges, $hFerieSemaine, &$hFerieTotal, $chomage_partiel_semaine, &$chomage_partiel, $hSemainePrec, $nb_heure_abs_fictive, &$hAbsenceTotalNa, &$heure_normales_fictives,&$spreadsheet)
    {
        $rows+=2;


        $borders_row = [];
        
        $sheet->getStyle('A'.$rows.':'.$spreadsheet->getActiveSheet()->getHighestColumn().''.$rows)->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFFFFF00');
        $sheet->getStyle('A'.($rows+1).':'.$spreadsheet->getActiveSheet()->getHighestColumn().''.($rows+1))->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF00B0F0');
        $sheet->getStyle('A'.($rows+2).':'.$spreadsheet->getActiveSheet()->getHighestColumn().''.($rows+2))->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFFF3DCD');

        $sheet->getCellByColumnAndRow(1, $rows)->setValue('TOTAL');
        $sheet->getStyle('A'.$rows)->getFont()->setBold(true);
        $borders_row[] = $rows;
        $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hSemaine?$hSemaine:'');
        $hTotal = $hTotal + $hSemaine;
        $rows++;
        # Heure supp
        if ($hFerieSemaine == 0) {
            $hSuppSemaine = ($hSemaine + $hSemainePrec - $user->getHeureHebdo()) > 0 ? ($hSemaine + $hSemainePrec - $user->getHeureHebdo() > 8 ? 8 : $hSemaine + $hSemainePrec - $user->getHeureHebdo()) : 0;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('HEURES SUPPLEMENTAIRES à 25%');
            $borders_row[] = $rows;
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hSuppSemaine?$hSuppSemaine:'');
            $hSuppTotal = $hSuppTotal + $hSuppSemaine;
            $rows++;
            $hSuppSemaine50 = ($hSemaine + $hSemainePrec - $user->getHeureHebdo()) > 0 ? ($hSemaine + $hSemainePrec - $user->getHeureHebdo() - 8 > 0 ? $hSemaine + $hSemainePrec - $user->getHeureHebdo() - 8 : 0) : 0;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('HEURES SUPPLEMENTAIRES à 50%');
            $borders_row[] = $rows;
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hSuppSemaine50?$hSuppSemaine50:'');
            $hSuppTotal50 = $hSuppTotal50 + $hSuppSemaine50;
            $rows++;
        } else {
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('HEURES SUPPLEMENTAIRES à 25%');
            $borders_row[] = $rows;
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue('');
            $rows++;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('HEURES SUPPLEMENTAIRES à 50%');
            $borders_row[] = $rows;
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue('');
            $rows++;
        }
        # Chomage partiel
        if($chomage_partiel_semaine == 1) {
            $calcul = $user->getHeureHebdo() - $hSemaine - $hFerieSemaine - $hSemainePrec;
            $chomage_partiel_semaine = $calcul > 0 ? $calcul : 0;
            $chomage_partiel = $chomage_partiel + $chomage_partiel_semaine;
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('CHOMAGE PARTIEL');
            $borders_row[] = $rows;
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($chomage_partiel_semaine?$chomage_partiel_semaine:'');
        } else {
            $sheet->getCellByColumnAndRow(1, $rows)->setValue('CHOMAGE PARTIEL');
            $borders_row[] = $rows;
            $sheet->getCellByColumnAndRow($collumn, $rows)->setValue('');
        }
        $rows++;
        # ABSENCES Non Autorisées
        $nombre_heure_semaine_protata =  (($user->getHeureHebdo() / 5)*($h_length));
        $hAbsenceSemaine =  $hSemaine + $hSemainePrec - $nombre_heure_semaine_protata + $hCongesSemaine + $chomage_partiel_semaine + $hFerieSemaine < 0 ? $hSemaine + $hSemainePrec - $nombre_heure_semaine_protata + $hCongesSemaine + $chomage_partiel_semaine + $hFerieSemaine :  0;
        $sheet->getCellByColumnAndRow(1, $rows)->setValue('ABSENCES NON AUTORISÉES');
        $borders_row[] = $rows;
        $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hAbsenceSemaine?$hAbsenceSemaine:'');
        $hAbsenceTotalNa = $hAbsenceTotalNa + $hAbsenceSemaine;
        $rows++;
        # ABSENCES Autorisées
        $sheet->getCellByColumnAndRow(1, $rows)->setValue('ABSENCES AUTORISÉES');
        $borders_row[] = $rows;
        $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hAbsenceSemaine > 0 ? $nb_heure_abs_fictive : '');
        $hAbsenceTotal = $hAbsenceTotal + $nb_heure_abs_fictive;
        $rows++;
        # Heures normales
        $hn_feries = 0;
        if($hFerieSemaine > 0) {
            $hn_feries = $hSemaine + $hSemainePrec - $user->getHeureHebdo() > 0 ? $hSemaine + $hSemainePrec - $user->getHeureHebdo() : 0;
        }
        $heure_normales_fictives_semaine = $hAbsenceSemaine >= 0 ? abs($nb_heure_abs_fictive) + $hn_feries : 0;
        $sheet->getCellByColumnAndRow(1, $rows)->setValue('HEURES NORMALES');
        $borders_row[] = $rows;
        $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($heure_normales_fictives_semaine?$heure_normales_fictives_semaine:'');
        $heure_normales_fictives = $heure_normales_fictives + $heure_normales_fictives_semaine;
        $rows++;
        # Congés payés
        $sheet->getCellByColumnAndRow(1, $rows)->setValue('CONGÉS PAYÉS');
        $borders_row[] = $rows;
        $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hCongesSemaine?$hCongesSemaine:'');
        $conges = $conges + $hCongesSemaine;
        $rows++;
        # Fériés
        $sheet->getCellByColumnAndRow(1, $rows)->setValue('FÉRIÉS');
        $borders_row[] = $rows;
        $sheet->getCellByColumnAndRow($collumn, $rows)->setValue($hFerieSemaine?$hFerieSemaine:'');
        $hFerieTotal = $hFerieTotal + $hFerieSemaine;
        $rows = $rows + 3;

        foreach ($borders_row as $key => $value) {
            # code...
            $coordinate = 'A'.$value.':'.$spreadsheet->getActiveSheet()->getHighestColumn().''.$value;
            $sheet->getStyle($coordinate)->applyFromArray(
                [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ]
                ]
            ); 
            
            $sheet->getStyle('B'.$value.':'.$spreadsheet->getActiveSheet()->getHighestColumn().$rows)->getAlignment()->setHorizontal('center');
        }


    }


}
