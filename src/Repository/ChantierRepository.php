<?php

namespace App\Repository;

use App\Entity\Chantier;
use App\Entity\Utilisateur;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Chantier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chantier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chantier[]    findAll()
 * @method Chantier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChantierRepository extends ServiceEntityRepository
{   
    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Chantier::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Chantier[] Returns an array of Chantier objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Chantier
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findOneLikeName($nom){

         $sql = "
            SELECT chantier_id, nameentreprise FROM chantier as c WHERE c.entreprise_id = :entreprise_id AND LOWER(c.nameentreprise) LIKE :nom LIMIT 1";
        
        $chantier = $this->em->prepare($sql);
        $chantier->execute(['entreprise_id'=>$this->entreprise_id, 'nom' => strtolower('%'.$nom.'%')]);
        $chantier = $chantier->fetch();
        $qb = $this->createQueryBuilder('chantier')
            ->Where('chantier.chantierId = :id')
            ->setParameter('id', $chantier['chantier_id']);
        $champs = $qb->getQuery()->getOneOrNullResult();
        
        return $champs;
    }

    public function getByActif($entreprise){

        $sql = "SELECT chantier_id, nameentreprise, status FROM chantier WHERE entreprise_id = :entreprise_id AND status = :status AND ( ignore_ia IS NULL OR ignore_ia = :ignoreIa)";
        
        $chantier = $this->em->prepare($sql);
        $chantier->execute(['entreprise_id'=>$entreprise, 'status'=>1, 'ignoreIa'=>0]);
        
        return $chantier->fetchAll();
    }

    public function findByNameAlpn($chantierName){

        $sql = "SELECT chantier_id as id, nameentreprise as nom FROM chantier as c WHERE c.entreprise_id = :entreprise_id AND alphanum(nameentreprise) LIKE CONCAT('%', alphanum('$chantierName'), '%')";
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        return $datas->fetchAll();
    }

    public function getAllChantier(){
        $sql = "SELECT CAST(c.chantier_id AS SIGNED INTEGER) as id, c.nameentreprise as nom FROM chantier c WHERE c.entreprise_id = :entreprise_id AND c.status = 1 ORDER BY nameentreprise ASC";
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        return $datas->fetchAll();
    }


    public function getChantierByHoraireParam($mois, $annee, $is_devis_rattach){

        //["Devis rattachés"=>1, "Aucun devis rattachés"=>2]

        $today = (new \DateTime())->format('Y-m-d');
        $sql = "";
        $where = " WHERE 1 ";

        $devisJoin = "";
        if(!is_null($is_devis_rattach)){
            
            if($is_devis_rattach == 1 ){
                $devisJoin = " INNER join vente as v ON h.devis_id = v.id ";
                $where .= " AND h.devis_id IS NOT NULL ";
            }
            else if($is_devis_rattach == 2 ){
                $devisJoin = " LEFT join vente as v ON (v.id = h.devis_id OR v.id IS NULL) ";
                $where .= " AND h.devis_id IS NULL ";
            }
        }


        if(!is_null($mois))
            $where .= " AND MONTH(h.datestart) = :mois ";
        if(!is_null($annee))
            $where .= " AND YEAR(h.datestart) = :annee ";

        $sql = "SELECT  DISTINCT c.chantier_id FROM chantier as c INNER JOIN horaire h ON c.chantier_id = h.chantierid $devisJoin ";
        $where .= "  AND h.datestart <= :today AND c.entreprise_id = :entreprise ";
        $sql .= $where;

        $datas = $this->em->prepare($sql);
        $tabExec = ['entreprise'=>$this->entreprise_id, 'today'=>$today];
        

        if(!is_null($mois))
            $tabExec['mois'] = $mois;
        if(!is_null($annee))
            $tabExec['annee'] = $annee;
        

        $datas->execute($tabExec);
        $datas = $datas->fetchAll();

        return $datas;
    }

    public function getChantierByVenteType(){
        $sql = "SELECT c.chantier_id as id FROM chantier c INNER JOIN logement l ON l.chantier_id = c.chantier_id WHERE c.entreprise_id = :entreprise_id AND c.status = 1 AND l.type = :type ORDER BY c.nameentreprise ASC";

        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id, 'type'=>'vente']);
        $datas = $datas->fetchAll();

        $datasArr = [];
        foreach ($datas as $value) {
            $qb = $this->createQueryBuilder('chantier')
                ->Where('chantier.chantierId = :id')
                ->setParameter('id', $value['id']);
            $datasArr[] = $qb->getQuery()->getOneOrNullResult();                
        }
        return $datasArr;
    }

    public function getChantierIdsWhereNotInOrder(array $order)
    {
        if(count($order) == 0){
            $list =  $this->createQueryBuilder('c')
                ->select('c.chantierId')
                ->orderBy("c.chantierId", 'DESC')
                ->getQuery()
                ->getResult()
            ;
        }
        else{
            $list =  $this->createQueryBuilder('c')
                ->select('c.chantierId')
                ->where('c.chantierId NOT IN(:order)')
                ->orderBy("c.chantierId", 'DESC')
                ->setParameter('order', array_values($order))
                ->getQuery()
                ->getResult()
            ;
        }
        

        $listArray = [];
        foreach ($list as $value) {
            $listArray[] = $value['chantierId'];
        }

        return $listArray;
    }

    public function getAttestations(){
        $sql = "SELECT t.chantier_id, t.*
            FROM ( SELECT chantier_id, MAX(create_at) as lastDate
                  FROM assurance_attestation v  WHERE v.entreprise_id = :entreprise_id
                  GROUP BY v.chantier_id ) r
            INNER JOIN assurance_attestation t
            ON (t.chantier_id = r.chantier_id AND t.create_at = r.lastDate) WHERE t.entreprise_id = :entreprise_id";

        $datas = $this->em->prepare($sql);

        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();
        return $datas;
    }  

    public function getContrats(){
        $sql = "SELECT t.chantier_id, t.*
            FROM ( SELECT chantier_id, MAX(create_at) as lastDate
                  FROM assurance_contrat v  WHERE v.entreprise_id = :entreprise_id
                  GROUP BY v.chantier_id ) r
            INNER JOIN assurance_contrat t
            ON (t.chantier_id = r.chantier_id AND t.create_at = r.lastDate) WHERE t.entreprise_id = :entreprise_id";

        $datas = $this->em->prepare($sql);

        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();
        return $datas;
    }  

    public function getQuittances(){
        $sql = "SELECT t.chantier_id, t.*
            FROM ( SELECT chantier_id, MAX(create_at) as lastDate
                  FROM assurance_quittance v  WHERE v.entreprise_id = :entreprise_id
                  GROUP BY v.chantier_id ) r
            INNER JOIN assurance_quittance t
            ON (t.chantier_id = r.chantier_id AND t.create_at = r.lastDate) WHERE t.entreprise_id = :entreprise_id";

        $datas = $this->em->prepare($sql);

        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();
        return $datas;
    }  
}
