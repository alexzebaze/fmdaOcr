<?php

namespace App\Repository;

use App\Entity\Vente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Vente|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vente|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vente[]    findAll()
 * @method Vente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VenteRepository extends ServiceEntityRepository
{
    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Vente::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Vente[] Returns an array of Vente objects
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
    public function findOneBySomeField($value): ?Vente
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findByVenteDate($mois, $annee, $type, $chantierId = null, $clientId=null, $filter = null)
    {
        if(is_null($filter))
            $filter = ['a.id', 'DESC'];

        $req = $this->createQueryBuilder('a');
        if(!is_null($clientId)){
            $req = $req->join('a.client', 'f')
            ->addSelect('f')
            ->andWhere('f.id = :clientId')
            ->setParameter('clientId', $clientId);
        }
        if(!is_null($chantierId)){
            $req = $req->join('a.chantier', 'c')
            ->addSelect('c')
            ->andWhere('c.chantierId = :chantierId')
            ->setParameter('chantierId', $chantierId);
        }
        if(!is_null($mois)){
            $req = $req->andWhere('MONTH(a.facturedAt) = :month')
            ->setParameter('month', $mois);
        }
        if(!is_null($annee)){
            $req = $req->andWhere('YEAR(a.facturedAt) = :year')
            ->setParameter('year', $annee);
        }
        $req = $req->andWhere('a.type = :type')
        ->andWhere('a.entreprise = :entreprise_id')
        ->setParameter('type', $type)
        ->setParameter('entreprise_id', $this->entreprise_id)
        ->orderBy($filter[0], $filter[1])
        ->getQuery()
        ->getResult();

        return $req;
    }

    public function findByVenteDateCompar($mois, $annee, $type, $clientId = null)
    {
        $req = $this->createQueryBuilder('a')
            ->join('a.client', 'f')
            ->addSelect('f');

        if($type == "bon_livraison"){
            if(!is_null($clientId)){
                $req = $req->andWhere('f.id = :clientId')
                    ->setParameter('clientId', $clientId);
            }
            $req = $req->andWhere('a.bl_validation IS NULL');
        }
        if($type == "facture"){
            if(!is_null($clientId)){
                $req = $req->andWhere('f.id = :clientId')
                    ->setParameter('clientId', $clientId);
            }
            $req = $req->andWhere('a.reglement IS NULL');
        }

        if($mois){
            $req = $req->andWhere('MONTH(a.facturedAt) = :month')
            ->setParameter('month', $mois);
        }
        if($annee){
            $req = $req->andWhere('YEAR(a.facturedAt) = :year')
            ->setParameter('year', $annee);
        }
        $req = $req->andWhere('a.type = :type')
        ->andWhere('a.entreprise = :entreprise_id')
        ->setParameter('type', $type)
        ->setParameter('entreprise_id', $this->entreprise_id)
        ->getQuery()
        ->getResult();

        return $req;
    }

    public function getAllBl($type){
        $sql = "SELECT rossum_document_id FROM vente as b WHERE b.entreprise_id = :entreprise_id AND b.rossum_document_id IS NOT NULL AND b.type = :type";
        
        $bl = $this->em->prepare($sql);
        $bl->execute(['type'=>$type, 'entreprise_id'=>$this->entreprise_id]);
        return $bl->fetchAll();
    }

    public function findByChantierBetweenDate($dateInterval, $chantier_id, $type)
    {
        return $this->createQueryBuilder('vente')
            ->join('vente.chantier', 'ch')
            ->where('ch.chantierId = :chantier_id')
            ->andWhere('vente.facturedAt BETWEEN :debut AND :fin')
            ->andWhere('vente.type = :type')
            ->andWhere('vente.entreprise = :entreprise_id')
            ->setParameter('debut', $dateInterval[0])
            ->setParameter('fin', $dateInterval[1])
            ->setParameter('type', $type)
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->setParameter('chantier_id', $chantier_id)
            ->orderBy('vente.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getDevisWithHeureAndTx($chantierId){

        $sql = "SELECT d.id, d.document_file, d.factured_at, d.id, H.nbHeure FROM vente as d LEFT JOIN (SELECT SUM(h.time) as nbHeure, h.devis_id, h.userid FROM horaire as h WHERE h.chantierid = :chantierId) H ON d.id = H.devis_id WHERE d.chantier_id = :chantierId AND d.type = :type";
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['chantierId'=>$chantierId, 'type'=>'devis_client']);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function countMontantByVenteDate($mois, $annee, $type, $chantierId = null, $clientId = null, $dateInterval=null){
        $sql = "
            SELECT SUM(vente.prixht) as sum_ht, SUM(vente.prixttc) as prixttc FROM vente";

        if(!is_null($chantierId) || !is_null($clientId)){
            $where = " WHERE vente.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON vente.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($clientId)){
                $sql .= " inner join client as f ON vente.client_id = f.id";
                $where .= " AND f.id = :clientId";
            }
            $sql .= " ".$where." AND vente.type = :type ";
        }
        else{ 
            $sql .= " WHERE vente.entreprise_id = :entreprise_id AND vente.type = :type ";
        }

        if(!is_null($dateInterval)){
            if(count($dateInterval)){
                $sql .= " AND vente.factured_at >= :start AND vente.factured_at <= :end ";
            }
            $datas = $this->em->prepare($sql);
            $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];

            if(count($dateInterval)){
                $tabExec['start'] = $dateInterval[0];
                $tabExec['end'] = $dateInterval[1];
            }
        }
        else{
            if($mois && !empty($mois))
                $sql .= " AND MONTH(vente.factured_at) = :month ";
            if(!is_null($annee))
                $sql .= " AND YEAR(vente.factured_at) = :year ";
     
            $datas = $this->em->prepare($sql);
            $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];
            if($mois && !empty($mois))
                $tabExec['month'] = $mois;
            if(!is_null($annee))
                $tabExec['year'] = $annee;
        }

        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($clientId))
            $tabExec['clientId'] = $clientId;
        
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        return $datas;
    }
    public function countMontantByVenteGroupDate($mois, $annee, $type, $chantierId = null, $clientId = null, $dateInterval=null){
        $sql = "
            SELECT vente.id, GROUP_CONCAT(vente.id) as list_id, MONTH(vente.factured_at) as mois, SUM(vente.prixht) as sum_ht, SUM(vente.prixttc) as prixttc, count(vente.id) as nbDevisMois FROM vente ";

        if(!is_null($chantierId) || !is_null($clientId)){
            $where = " WHERE vente.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON vente.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($clientId)){
                $sql .= " inner join client as f ON vente.client_id = f.id";
                $where .= " AND f.id = :clientId";
            }
            $sql .= " ".$where." AND vente.type = :type ";
        }
        else{ 
            $sql .= " WHERE vente.entreprise_id = :entreprise_id AND vente.type = :type ";
        }

        if(!is_null($dateInterval)){
            if(count($dateInterval)){
                $sql .= " AND vente.factured_at >= :start AND vente.factured_at <= :end ";
            }
            $sql .=" GROUP BY MONTH(vente.factured_at)";
            $datas = $this->em->prepare($sql);
            $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];

            if(count($dateInterval)){
                $tabExec['start'] = $dateInterval[0];
                $tabExec['end'] = $dateInterval[1];
            }
        }
        else{
            if($mois && !empty($mois))
                $sql .= " AND MONTH(vente.factured_at) = :month ";
            if(!is_null($annee))
                $sql .= " AND YEAR(vente.factured_at) = :year ";
        
            $sql .=" GROUP BY MONTH(vente.factured_at) ";
            $datas = $this->em->prepare($sql);
            $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];
            if($mois && !empty($mois))
                $tabExec['month'] = $mois;
            if(!is_null($annee))
                $tabExec['year'] = $annee;
        }
        

        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($clientId))
            $tabExec['clientId'] = $clientId;
        
        $datas->execute($tabExec);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function countMontantByVentePayeDate($mois, $annee, $type, $chantierId = null, $clientId = null){
        $sql = "
            SELECT SUM(vente.prixht) as sum_ht, SUM(vente.prixttc) as prixttc FROM vente";

        if(!is_null($chantierId) || !is_null($clientId)){
            $where = " WHERE vente.reglement_id IS NOT NULL AND vente.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON vente.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($clientId)){
                $sql .= " inner join client as f ON vente.client_id = f.id";
                $where .= " AND f.id = :clientId";
            }
            $sql .= " ".$where." AND vente.type = :type ";
        }
        else{
            $sql .= " WHERE vente.entreprise_id = :entreprise_id AND vente.reglement_id IS NOT NULL AND vente.type = :type ";
        }
        if(!is_null($mois)){
            $sql .= " AND MONTH(vente.factured_at) = :month ";
        }
        if(!is_null($annee)){
            $sql .= " AND YEAR(vente.factured_at) = :year ";
        }

        $datas = $this->em->prepare($sql);
        $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];
        if(!is_null($mois))
            $tabExec['month'] = $mois;
        if(!is_null($annee))
            $tabExec['year'] = $annee;

        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($clientId))
            $tabExec['clientId'] = $clientId;
        
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        return $datas;
    }
    public function countMontantByVenteDateInterval($type, $chantierId = null, $start = null, $end = null,  $clientId = null){
        $sql = "
            SELECT SUM(vente.prixht) as sum_ht, SUM(vente.prixttc) as prixttc FROM vente";

        if(!is_null($chantierId) || !is_null($clientId)){
            $where = " WHERE vente.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON vente.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($clientId)){
                $sql .= " inner join client as f ON vente.client_id = f.id";
                $where .= " AND f.id = :clientId";
            }
            $sql .= " ".$where." AND vente.type = :type ";
        }
        else
            $sql .= " WHERE vente.entreprise_id = :entreprise_id AND vente.type = :type ";

        if(!is_null($start)){
            $sql .= " AND vente.factured_at >= :start AND vente.factured_at <= :end ";
        }


        $datas = $this->em->prepare($sql);
        if(!is_null($start))
            $tabExec = ['type'=>$type, 'start'=>$start, 'end'=>$end, 'entreprise_id'=>$this->entreprise_id];
        else
            $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];
        
        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($clientId))
            $tabExec['clientId'] = $clientId;
        
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        return $datas;
    }

    /*public function findGoupSumByfacturedDate($mois, $annee, $type){
        $sql = "SELECT A.id, SUM(A.prixht) as prixht , SUM(A.prixttc) as prixttc FROM vente as A WHERE A.type = :type AND MONTH(A.factured_at) = :month AND YEAR(A.factured_at) = :year GROUP BY YEAR(A.factured_at), MONTH(A.factured_at)";
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['type'=>$type, 'month'=>$mois, 'year'=>$annee]);
        $datas = $datas->fetchAll();
        return $datas;
    }*/

    public function findByTabId($tabIdBl, $type){
        if(empty($tabIdBl))
            return [];
        
        $in = '(' . implode(',', $tabIdBl) .')';

        /*$req = $this->createQueryBuilder('a')
            ->where('a.id IN (:in)')
            ->andWhere('a.type = :type')
            ->andWhere('a.document_file IS NOT NULL')
            ->setParameter('in', $in)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();

        return $req;*/
             
        $sql = "
            SELECT b.chantier_id, b.client_id, b.document_file FROM vente as b WHERE b.id IN $in AND b.document_file IS NOT NULL AND b.type = :type";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type]);
        $datas = $datas->fetchAll();
        return $datas;
    }
    public function findByTabIdNotExportCompta($tabIdFc, $type, $receiver){
        if(empty($tabIdFc))
            return [];
        
        $in = '(' . implode(',', $tabIdFc) .')';

        if($receiver == "quittance"){
            $sql = "
            SELECT b.chantier_id, b.document_file, b.client_id FROM vente as b WHERE b.id IN $in AND b.document_file IS NOT NULL AND b.type = :type AND export_quittance IS NULL";
        }
        else{
            $sql = "
            SELECT b.chantier_id, b.document_file, b.client_id FROM vente as b WHERE b.id IN $in AND b.document_file IS NOT NULL AND b.type = :type AND export_compta IS NULL";
        }
        

        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type]);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function countVenteExport($tabIdBl, $type){
        if(empty($tabIdBl))
            return [];
        
        $in = '(' . implode(',', $tabIdBl) .')';
        $sql = "
            SELECT COUNT(b.id) as ct FROM vente as b WHERE b.id IN $in AND b.type = :type AND export_compta IS NOT NULL AND export_quittance IS NOT NULL";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type]);
        $datas = $datas->fetch();
        return $datas ? $datas['ct'] : 0;
    }

    public function validVente($tabVenteId, $type, $blListId){
        if(empty($tabVenteId))
            return [];
        
        $in = '(' . implode(',', $tabVenteId) .')';
        $sql = "UPDATE vente SET vente.bl_validation = :bl_validation WHERE vente.type = :type AND vente.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type, 'bl_validation'=>$blListId]);
        return 1;
    }
    public function updateVenteCompta($tabVenteId){
        if(empty($tabVenteId))
            return [];
        
        $in = '(' . implode(',', $tabVenteId) .')';
        $sql = "UPDATE vente SET vente.export_compta = :export_compta, vente.date_export_compta = :date_export_compta WHERE vente.type = :type AND vente.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>'facture', 'export_compta'=> 1, 'date_export_compta'=> date('Y-m-d H:i:s')]);
        return 1;
    }

    public function updateVenteQuittance($tabVenteId){
        if(empty($tabVenteId))
            return [];
        
        $in = '(' . implode(',', $tabVenteId) .')';
        $sql = "UPDATE vente SET vente.export_quittance = :export_quittance, vente.date_export_quittance = :date_export_quittance WHERE vente.type = :type AND vente.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>'facture', 'export_quittance'=> 1, 'date_export_quittance'=> date('Y-m-d H:i:s')]);
        return 1;
    }
    public function validBl($tabBlId, $type, $factureId=null){
        if(empty($tabBlId))
            return [];
        
        $in = '(' . implode(',', $tabBlId) .')';
        $sql = "UPDATE vente SET vente.bl_validation = :bl_validation WHERE vente.type = :type AND vente.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type, 'bl_validation'=>1]);
        return 1;
    }

    public function countMontantByChantier($type, $chantierId = null){
        $sql = "
            SELECT SUM(vente.prixht) as sum_ht, SUM(vente.prixttc) as prixttc FROM vente";

        if(!is_null($chantierId)){
            $sql .= " inner join chantier as c WHERE vente.entreprise_id = :entreprise_id AND vente.chantier_id = c.chantier_id AND c.chantier_id = :chantierId AND vente.type = :type";
        }
        else
            $sql .= " WHERE vente.entreprise_id = :entreprise_id AND vente.type = :type";

        $datas = $this->em->prepare($sql);
        if(!is_null($chantierId))
            $datas->execute(['chantierId'=>$chantierId, 'type'=>$type, 'entreprise_id'=>$this->entreprise_id]);
        else
            $datas->execute(['type'=>$type, 'entreprise_id'=>$this->entreprise_id]);

        $datas = $datas->fetch();
        return $datas;
    }

    public function countCoutGlobalByChantier($chantierId, $type){
        $sql = "
            SELECT SUM(vente.prixht) as ht FROM vente WHERE vente.entreprise_id = :entreprise_id AND vente.chantier_id = :chantier_id AND type = :type";
        $datas = $this->em->prepare($sql);
        $datas->execute(['chantier_id'=>$chantierId, 'type'=>$type, 'entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetch();
        return $datas['ht'];
    }

    public function findByFFTH($clientId, $dateFactured, $prixht, $type){
        $sql = "
            SELECT vente.id, vente.document_file FROM vente WHERE vente.entreprise_id = :entreprise_id AND vente.client_id = :clientId AND vente.factured_at = :dateFactured AND vente.prixht = :prixht AND vente.type = :type LIMIT 1";

        $datas = $this->em->prepare($sql);
        $datas->execute(['clientId'=>$clientId, 'dateFactured'=>$dateFactured->format('Y-m-d')." 00:00:00", 'prixht'=> $prixht, 'type'=>$type, 'entreprise_id'=>$this->entreprise_id]);
        return $datas->fetch();
    }

    public function findDoublon($type, $entreprise_id){
        $sql = "SELECT A.* FROM vente A INNER JOIN( SELECT h.prixht, h.factured_at, h.client_id
        FROM vente as h WHERE h.type = :type AND h.entreprise_id = :entreprise_id
         GROUP BY h.prixht, h.factured_at, h.client_id HAVING COUNT(h.id) > 1) B ON A.prixht = B.prixht AND A.factured_at = B.factured_at AND A.client_id =B.client_id";

        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type, 'entreprise_id'=>$entreprise_id]);
        return $datas->fetchAll();
    }

    public function coutDevisClientByChantier($chantierId){
        $sql = "
            SELECT COUNT(v.id) as ct FROM vente as v WHERE v.chantier_id = :chantierId AND v.type = :type";
        $data = $this->em->prepare($sql);
        $data->execute(['type'=>'devis_client', 'chantierId'=> $chantierId]);
        $data = $data->fetch();
        return $data ? $data['ct'] : 0;
    }

    public function coutFactureClientByChantier($chantierId){
        $sql = "
            SELECT COUNT(v.id) as ct FROM vente as v WHERE v.chantier_id = :chantierId AND v.type = :type";
        $data = $this->em->prepare($sql);
        $data->execute(['type'=>'facture', 'chantierId'=> $chantierId]);
        $data = $data->fetch();
        return $data ? $data['ct'] : 0;
    }

    public function attachDevis($listVenteId, $devisId){
        $in = '(' . implode(',', $listVenteId) .')';  
        $sql = "UPDATE vente SET devis_id = $devisId WHERE vente.id in $in";
        
        $sql = $this->em->prepare($sql);
        $sql->execute();
        return 1;
    }

    public function getSumMontantByDevis($devisId){

        $sql = "SELECT SUM(v.prixht) as sum_ht, SUM(v.prixttc) as sum_ttc FROM vente as v WHERE v.devis_id = :devisId AND v.type = :type";
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['devisId'=>$devisId, 'type'=>'facture']);
        $datas = $datas->fetch();

        $datas = $datas ? ['sum_ht'=>$datas['sum_ht'], 'sum_ttc'=>$datas['sum_ttc']] : ['sum_ht'=>0, 'sum_ttc'=>0];
        return $datas;
    }

    public function getVenteByChantier($tabChantier, $type){

        $req = $this->createQueryBuilder('v')
            ->where('v.chantier IN (:in)')
            ->andWhere('v.type = :type')
            ->andWhere('v.entreprise = :entreprise')
            ->setParameter('in', $tabChantier)
            ->setParameter('entreprise', $this->entreprise_id)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();

        return $req;
    }
    public function getLotVente($tabVente){
        $in = '(' . implode(',', $tabVente) .')';
        $sql = "
            SELECT v.lot_id as id FROM vente as v WHERE v.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute();
        $datas = $datas->fetchAll();

        return $datas;
    }

}
