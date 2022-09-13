<?php

namespace App\Repository;

use App\Entity\Paie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Paie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Paie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Paie[]    findAll()
 * @method Paie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaieRepository extends ServiceEntityRepository
{   
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Paie::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Paie[] Returns an array of Paie objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Paie
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getAllPaie(){
        $sql = "SELECT rossum_document_id FROM paie WHERE paie.rossum_document_id IS NOT NULL AND paie.entreprise_id = :entreprise_id";
        
        $bl = $this->em->prepare($sql);
        $bl->execute(['entreprise_id'=>$this->entreprise_id]);
        return $bl->fetchAll();
    }

    public function getByUserAndDate($user_id, $date){
        $sql = "SELECT p.tx_horaire, p.cout_global, p.tx_moyen, p.document_file FROM paie as p WHERE p.utilisateur_id = :user_id AND p.entreprise_id = :entreprise_id ORDER BY p.id DESC LIMIT 1";
        
        $paie = $this->em->prepare($sql);
        $paie->execute(['user_id'=>$user_id, 'entreprise_id'=>$this->entreprise_id]);
        return $paie->fetch();
    }

    public function sumCoutGloble($mois, $annee, $utilisateur_id){
        $sql = "SELECT SUM(p.cout_global) as sum_cout_global FROM paie as p WHERE p.entreprise_id = :entreprise_id ";
        
        $date = "";
        if(!empty($mois) && !is_null($annee))
            $date = $mois." ".$annee;
        elseif(!empty($mois))
            $date = $mois;
        elseif(!empty($annee))
            $date = $annee;

        $tabExec = ['entreprise_id'=>$this->entreprise_id];

        if(!is_null($utilisateur_id)){
            $sql .= ' AND p.utilisateur_id = :utilisateur_id ';
            $tabExec['utilisateur_id'] = $utilisateur_id;
        }

        if(!empty($date)){
            $sql .= ' AND LOWER(p.date_paie) LIKE :date_paie ';
            $tabExec['date_paie'] = strtolower('%'.$date.'%');
        }

        $paie = $this->em->prepare($sql);
        $paie->execute($tabExec);
        return $paie->fetch();
    }

    public function getByDate($mois, $annee, $utilisateur_id = null){
        $date = "";
        if(!empty($mois) && !is_null($annee))
            $date = $mois." ".$annee;
        elseif(!empty($mois))
            $date = $mois;
        elseif(!empty($annee))
            $date = $annee;

        $req = $this->createQueryBuilder('p');
        if(!empty($date)){
            $req = $req->andWhere('LOWER(p.date_paie) LIKE :date_paie')
            ->setParameter('date_paie', strtolower('%'.$date.'%'));
        }
        if(!is_null($utilisateur_id)){
            $req = $req->andWhere('p.utilisateur = :utilisateur_id')
            ->setParameter('utilisateur_id', $utilisateur_id);
        }
        $req = $req->andWhere('p.entreprise = :entreprise_id')
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->getQuery()
            ->getResult();

        return $req;
    }

    public function getByDateAndUser($mois, $annee, $utilisateur_id){
        $date = "";
        if(!empty($mois) && !is_null($annee))
            $date = $mois." ".$annee;
        elseif(!empty($mois))
            $date = $mois;
        elseif(!empty($annee))
            $date = $annee;

        $req = $this->createQueryBuilder('p');
        if(!empty($date)){
            $req = $req->andWhere('LOWER(p.date_paie) LIKE :date_paie')
            ->setParameter('date_paie', strtolower('%'.$date.'%'));
        }
        $req = $req->andWhere('p.utilisateur = :utilisateur_id')
            ->setParameter('utilisateur_id', $utilisateur_id)
            ->getQuery()
            ->getResult();

        return $req;
    }

    public function getTxHoraireByUserAndDate($tabMonth, $user_id, $chantierId){
        $in = '('."'". implode("','", $tabMonth) ."'".')';
        $sql = "SELECT AVG(p.tx_horaire) as avg_tx_horaire FROM paie as p INNER JOIN horaire as h ON h.userid = p.utilisateur_id WHERE h.chantierid = :chantierId AND p.utilisateur_id = :user_id  AND LOWER(p.date_paie) IN $in AND p.tx_horaire is not null AND p.tx_horaire != 0 AND h.devis_id IS NOT NULL";
        
        $paie = $this->em->prepare($sql);
        $paie->execute(['user_id'=>$user_id, 'chantierId'=>$chantierId]);
        $paie = $paie->fetch();
        $avg_tx_horaire = $paie ? $paie['avg_tx_horaire'] : 0;
        return $avg_tx_horaire;
    }

    public function getTxHoraireByUserAndCurrentYear($annee, $user_id, $chantierId){
        $sql = "SELECT AVG(p.tx_horaire) as avg_tx_horaire FROM paie as p INNER JOIN horaire as h ON h.userid = p.utilisateur_id WHERE h.chantierid = :chantierId AND p.utilisateur_id = :user_id AND LOWER(p.date_paie) LIKE :annee AND p.tx_horaire is not null AND p.tx_horaire != 0";
                    
        $paie = $this->em->prepare($sql);
        $paie->execute(['user_id'=>$user_id, 'chantierId'=>$chantierId, 'annee'=>strtolower('%'.$annee.'%')]);
        $paie = $paie->fetch();
        $avg_tx_horaire = $paie ? $paie['avg_tx_horaire'] : 0;
        return $avg_tx_horaire;
    }

    public function getLastPaieWithTx($userId){
        $sql = "SELECT p.tx_moyen, p.date_paie FROM paie p WHERE p.tx_moyen > 0 AND p.utilisateur_id = :userId ORDER BY p.id DESC LIMIT 1";
        $paie = $this->em->prepare($sql);
        $paie->execute(['userId'=>$userId]);
        return $paie->fetch();
    }

    public function findByTabId($tabIdBl){
        if(empty($tabIdBl))
            return [];
        
        $in = '(' . implode(',', $tabIdBl) .')';
        $sql = "
            SELECT p.document_file, u.firstname, u.lastname, u.email FROM paie as p inner join utilisateur as u WHERE p.utilisateur_id = u.uid AND p.id IN $in AND p.document_file IS NOT NULL AND p.entreprise_id = :entreprise_id ";
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function findByOnlyTabId($tabIdBl){
        if(empty($tabIdBl))
            return [];
        
        $in = '(' . implode(',', $tabIdBl) .')';
        $sql = "
            SELECT p.document_file, u.firstname, u.lastname, u.email, p.date_paie as datePaie FROM paie as p inner join utilisateur as u WHERE p.utilisateur_id = u.uid AND p.id IN $in AND p.document_file IS NOT NULL";
        $datas = $this->em->prepare($sql);
        $datas->execute([]);
        $datas = $datas->fetchAll();
        return $datas;
    }

    /*public function getAVGTxByDevis($devisId, $chantierId){
        $sql = "
            SELECT AVG(p.tx_moyen) as tx_horaire FROM paie as p INNER JOIN horaire as h ON h.userid = p.utilisateur_id WHERE h.devis_id = :devis_id AND p.tx_horaire IS NOT NULL AND p.tx_horaire != 0 AND h.chantierid = :chantierId AND h.time is not null AND time != 0";
        $datas = $this->em->prepare($sql);
        $datas->execute(['devis_id'=>$devisId, 'chantierId'=>$chantierId]);
        $datas = $datas->fetch();
        $txHoraire = $datas ? $datas['tx_horaire'] : 0;
        return $txHoraire;
    }*/

    public function getAVGTxByDevis($devisId, $chantierId){
        $sql = "
            SELECT p.tx_horaire FROM paie as p INNER JOIN horaire as h ON h.userid = p.utilisateur_id WHERE h.devis_id = :devis_id AND p.tx_horaire IS NOT NULL AND p.tx_horaire != 0 AND h.chantierid = :chantierId AND h.time is not null AND time != 0 ORDER BY p.id DESC LIMIT 1";
        $datas = $this->em->prepare($sql);
        $datas->execute(['devis_id'=>$devisId, 'chantierId'=>$chantierId]);
        $datas = $datas->fetch();
        $txHoraire = $datas ? $datas['tx_horaire'] : 0;
        return $txHoraire;
    }
    
    public function calculCoutGlobalByDate($tabMonth, $user_id){
        if(empty($tabMonth))
            return 0;
        
        $in = '('."'". implode("','", $tabMonth) ."'".')';
        $sql = "SELECT SUM(p.cout_global) as cout FROM paie as p WHERE p.date_paie IN $in AND p.utilisateur_id = :user_id AND p.entreprise_id = :entreprise_id";
        $datas = $this->em->prepare($sql);
        $datas->execute(['user_id'=>$user_id, 'entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetch();
        return $datas['cout'];
    }

    public function getUserMonthWork($userId, $mois, $annee){
        if($mois<10){
            $mois = "0".$mois;
        }
        $anneeMois = $annee.'-'.$mois;
        $today = new \DateTime();
        $year = $today->format('Y');
        $month = $today->format('m');
        $sql = "SELECT  DISTINCT DATE_FORMAT(datestart, '%m') as mois FROM horaire WHERE horaire.userid = :userId ";

        if($anneeMois == ($year.'-'.$month))
            $sql .= " AND DATE_FORMAT(datestart, '%Y-%m') < :anneeMois";
        else
            $sql .= " AND DATE_FORMAT(datestart, '%Y-%m') <= :anneeMois";

        $sql .= " AND YEAR(datestart) = :year AND time is not null AND time != 0";

        $datas = $this->em->prepare($sql);
        $datas->execute(['userId'=>$userId, 'anneeMois'=>$anneeMois, 'year'=>$annee]);
        $datas = $datas->fetchAll();
        return $datas;
    }
    public function getTxMoyByTabMonth($tabDate, $userId){
        $in = '('."'". implode("','", $tabDate) ."'".')';
        $sql = "SELECT SUM(p.tx_horaire) as sum_tx FROM paie as p WHERE p.utilisateur_id = :userId AND LOWER(p.date_paie) IN $in AND p.tx_horaire is not null AND p.tx_horaire != 0";
        
        $paie = $this->em->prepare($sql);
        $paie->execute(['userId'=>$userId]);
        $paie = $paie->fetch();
        $sum_tx = $paie ? $paie['sum_tx'] : 0;
        return $sum_tx;
    }

    public function getUserTxYear($userId, $annee){
        $sql = "SELECT * FROM paie as p WHERE p.utilisateur_id = :userId AND p.tx_horaire > 0 AND LOWER(p.date_paie) LIKE :annee ";

        $paie = $this->em->prepare($sql);
        $paie->execute(['userId'=>$userId, 'annee'=>strtolower('%'.$annee.'%')]);
        $paie = $paie->fetchAll();
        return $paie;
    }

    public function getUserTxMoyenYear($userId){
        $sql = "SELECT * FROM paie as p WHERE p.utilisateur_id = :userId AND p.tx_moyen > 0 order by id DESC LIMIT 12 ";

        $paie = $this->em->prepare($sql);
        $paie->execute(['userId'=>$userId]);
        $paie = $paie->fetchAll();
        return $paie;
    }

    public function getUserAVGTxMoyenYear($userId){
        $sql = "SELECT AVG(p.tx_moyen) as avg FROM paie as p WHERE p.utilisateur_id = :userId AND p.tx_moyen > 0 order by id DESC LIMIT 12 ";

        $paie = $this->em->prepare($sql);
        $paie->execute(['userId'=>$userId]);
        $paie = $paie->fetch();
        return $paie;
    }

    public function recapUserTxMoyenYear($userId, $annee){
        $sql = "SELECT SUM(p.tx_horaire) as sum_tx_horaire, COUNT(p.id) as nb_fiche FROM paie as p WHERE p.utilisateur_id = :userId AND p.tx_horaire > 0 AND LOWER(p.date_paie) LIKE :annee";

        $paie = $this->em->prepare($sql);
        $paie->execute(['userId'=>$userId, 'annee'=>strtolower('%'.$annee.'%')]);
        $paie = $paie->fetch();

        return $paie;
    }

    public function recapUserTxMoyenYear2($userId, $date){
        $sql = "SELECT AVG(p.tx_moyen) as avg FROM paie as p WHERE p.utilisateur_id = :userId AND p.tx_moyen > 0 AND  YEAR(p.date_paie2) <= :year AND MONTH(p.date_paie2) <= :month order by id DESC LIMIT 12 ";

        $paie = $this->em->prepare($sql);
        $paie->execute(['userId'=>$userId, 'year'=> $date->format('Y'), 'month'=> $date->format('m')]);
        $paie = $paie->fetch();

        return $paie;
    }

    public function getCoutGlobalGroupDate($mois, $annee){
        $date = "";
        if(!empty($mois) && !is_null($annee))
            $date = $mois." ".$annee;
        elseif(!empty($mois))
            $date = $mois;
        elseif(!empty($annee))
            $date = $annee;

        $req = "SELECT p.date_paie as datePaie, SUM(p.cout_global) as cout_global FROM paie AS p WHERE p.entreprise_id = :entreprise_id ";

        $tabExec = ['entreprise_id'=>$this->entreprise_id];
        if(!empty($date)){
            $req .= " AND LOWER(p.date_paie) LIKE :date_paie ";
            $tabExec['date_paie'] = strtolower('%'.$date.'%');
        }
        $req .= " GROUP BY p.date_paie";
        $datas = $this->em->prepare($req);

        $datas->execute($tabExec);
        $datas = $datas->fetchAll();
        return $datas;
    }
}
