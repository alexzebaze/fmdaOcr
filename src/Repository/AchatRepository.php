<?php

namespace App\Repository;

use App\Entity\Achat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Achat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Achat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Achat[]    findAll()
 * @method Achat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AchatRepository extends ServiceEntityRepository
{
    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Achat::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Achat[] Returns an array of Achat objects
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
    public function findOneBySomeField($value): ?Achat
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getFactureGenerated(){
        $date = (new \DateTime());
        $req = $this->createQueryBuilder('a')
            ->andWhere('a.automatique = :automatique')
            ->setParameter('automatique', 1)
            ->andWhere('a.type = :type')
            ->setParameter('type', "facturation")
            ->andWhere('a.date_generate <= :today')
            ->setParameter('today', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        return $req;
    }

        public function getFactureBlvalid(){
        $req = $this->createQueryBuilder('a')
            ->andWhere('a.bl_validation IS NOT NULL')
            ->andWhere('a.type = :type')
            ->setParameter('type', "facturation")
            ->getQuery()
            ->getResult();

        return $req;
    }


    public function findByfacturedDate($mois, $annee, $type, $chantierId = null, $fournisseurId=null, $filter = null, $lotId=null, $statusId=null, $is_paid=null, $is_devis_client_rattach=null)
    {

        $req = $this->createQueryBuilder('a');
        if(!is_null($fournisseurId)){
            $req = $req->join('a.fournisseur', 'f')
            ->addSelect('f')
            ->andWhere('f.id = :fournisseurId')
            ->setParameter('fournisseurId', $fournisseurId);
        }
        if(!is_null($chantierId)){
            $req = $req->join('a.chantier', 'c')
            ->addSelect('c')
            ->andWhere('c.chantierId = :chantierId')
            ->setParameter('chantierId', $chantierId);
        }
        if(!is_null($is_devis_client_rattach)){
            if($is_devis_client_rattach == 1 )
               $req = $req->andWhere('a.devis IS NOT NULL');
            else if($is_devis_client_rattach == 2 )
                $req = $req->andWhere('a.devis IS NULL');
        }
        if(!is_null($lotId)){
            $req = $req->join('a.lot', 'l')
            ->addSelect('l')
            ->andWhere('l.id = :lotId')
            ->setParameter('lotId', $lotId);
        }
        else{
            $req = $req->leftjoin('a.lot', 'l')
            ->addSelect('l');
        }
        if(!is_null($mois)){
            $req = $req->andWhere('MONTH(a.facturedAt) = :month')
            ->setParameter('month', $mois);
        }
        if(!is_null($is_paid)){
            if($is_paid == 1){
                $req = $req->andWhere('a.reglement IS NOT NULL');
            }
            elseif($is_paid == 2){
                $req = $req->andWhere('a.reglement IS NULL');
            }
        }
        if(!is_null($annee)){
            $req = $req->andWhere('YEAR(a.facturedAt) = :year')
            ->setParameter('year', $annee);
        }
        if( !is_null($statusId ) && ( ($type == "devis_pro") ) ){
            $req = $req->andWhere('a.status = :statusId2 OR a.status = :statusId1')
            ->setParameter('statusId1', 15)
            ->setParameter('statusId2', 17);
        }
        if(is_null($filter))
            $filter = ['a.id', 'DESC'];

        $req = $req->andWhere('a.type = :type')
        ->andWhere('a.entreprise = :entreprise_id')
        ->setParameter('type', $type)
        ->setParameter('entreprise_id', $this->entreprise_id)
        ->orderBy($filter[0], $filter[1])
        ->getQuery()
        ->getResult();

        return $req;
    }

    public function findByfacturedDateCompar($mois, $annee, $type, $fournisseurId = null)
    {
        $req = $this->createQueryBuilder('a')
            ->join('a.fournisseur', 'f')
            ->addSelect('f');

        if($type == "bon_livraison"){
            if(!is_null($fournisseurId)){
                $req = $req->andWhere('f.id = :fournisseurId')
                    ->setParameter('fournisseurId', $fournisseurId);
            }
            $req = $req->andWhere('a.bl_validation IS NULL');
        }
        if($type == "facturation"){
            if(!is_null($fournisseurId)){
                $req = $req->andWhere('f.id = :fournisseurId')
                    ->setParameter('fournisseurId', $fournisseurId);
            }
            //$req = $req->andWhere('a.reglement IS NULL');
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
        ->orderBy('a.prixht', 'DESC')
        ->getQuery()
        ->getResult();

        return $req;
    }

    public function getAllBl($type){
        $sql = "SELECT rossum_document_id FROM achat as b WHERE b.entreprise_id = :entreprise_id AND b.rossum_document_id IS NOT NULL AND b.type = :type";
        
        $bl = $this->em->prepare($sql);
        $bl->execute(['type'=>$type, 'entreprise_id'=>$this->entreprise_id]);
        return $bl->fetchAll();
    }

    public function getAchatByChantier($tabChantier, $type, $tabFournisseur){

        $req = $this->createQueryBuilder('a')
            ->where('a.chantier IN (:in)')
            ->andwhere('a.fournisseur IN (:inFour)')
            ->andWhere('a.type = :type')
            ->andWhere('a.entreprise = :entreprise')
            ->setParameter('in', $tabChantier)
            ->setParameter('inFour', $tabFournisseur)
            ->setParameter('entreprise', $this->entreprise_id)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();

        return $req;
    }

    public function getBlByListId($tabBlId){

        $req = $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->andwhere('a.id IN (:tabBlId)')
            ->setParameter('type', 'bon_livraison')
            ->setParameter('tabBlId', $tabBlId)
            ->getQuery()
            ->getResult();

        return $req;
    }

    public function getLotVente($tabFacture, $tabFournisseurs){
        
        $in = '(' . implode(',', $tabFacture) .')';
        $inFour = '(' . implode(',', $tabFournisseurs) .')';
        $sql = "
            SELECT a.lot_id as id FROM achat as a WHERE a.id IN $in AND a.fournisseur_id IN $inFour";
        $datas = $this->em->prepare($sql);
        $datas->execute();
        $datas = $datas->fetchAll();

        return $datas;
    }

    public function findByChantierBetweenDate($dateInterval, $chantier_id, $type)
    {
        return $this->createQueryBuilder('achat')
            ->join('achat.chantier', 'ch')
            ->where('ch.chantierId = :chantier_id')
            ->andWhere('achat.facturedAt BETWEEN :debut AND :fin')
            ->andWhere('achat.type = :type')
            ->andWhere('achat.entreprise = :entreprise_id')
            ->setParameter('debut', $dateInterval[0])
            ->setParameter('fin', $dateInterval[1])
            ->setParameter('type', $type)
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->setParameter('chantier_id', $chantier_id)
            ->orderBy('achat.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countMontantByfacturedDate($mois, $annee, $type, $chantierId = null, $fournisseurId = null, $lotId=null, $statusId = null){
        $sql = "
            SELECT SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat";

        if(!is_null($chantierId) || !is_null($fournisseurId)){
            $where = " WHERE achat.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON achat.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($fournisseurId)){
                $sql .= " inner join fournisseurs as f ON achat.fournisseur_id = f.id";
                $where .= " AND f.id = :fournisseurId";
            }
            $sql .= " ".$where." AND achat.type = :type ";
        }
        else{
            $sql .= " WHERE achat.entreprise_id = :entreprise_id AND achat.type = :type ";
        }
        if(!is_null($lotId))
            $sql .= " AND achat.lot_id = :lotId ";

        if(!is_null($statusId))
            $sql .= " AND achat.status_id = :statusId ";

        if(!is_null($mois))
            $sql .= " AND MONTH(achat.factured_at) = :month ";
        if(!is_null($annee))
            $sql .= " AND YEAR(achat.factured_at) = :year ";

        $datas = $this->em->prepare($sql);
        $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];
        if(!is_null($lotId))
            $tabExec['lotId'] = $lotId;

        if(!is_null($statusId))
            $tabExec['statusId'] = $statusId;

        if(!is_null($mois))
            $tabExec['month'] = $mois;
        if(!is_null($annee))
            $tabExec['year'] = $annee;

        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($fournisseurId))
            $tabExec['fournisseurId'] = $fournisseurId;
        
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        return $datas;
    }

    public function sumMontantAchatValideByTypeByStatus($lotId, $chantier, $type){
        $sql = "
            SELECT SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat WHERE achat.type = :type AND achat.chantier_id = :chantier AND achat.lot_id = :lot AND achat.entreprise_id = :entreprise_id ";

        $tabExec = ['type'=>$type, 'chantier'=> $chantier, 'lot'=>$lotId, 'entreprise_id'=>$this->entreprise_id];
        if(  ($type == "devis_pro") ){
            $sql .= " AND (achat.status_id = :status OR achat.status_id = :status2)";
            $tabExec['status'] = 15;
            $tabExec['status2'] = 17;
        }

        $datas = $this->em->prepare($sql);
        $datas->execute($tabExec);
        $datas = $datas->fetch();

        return $datas;
    }

    public function sumMontantAchatValideByChantierAndType($chantier, $type, $status = null){
        $sql = "
            SELECT SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat WHERE achat.type = :type AND achat.chantier_id = :chantier AND achat.entreprise_id = :entreprise_id ";

        $tabExec = ['type'=>$type, 'chantier'=> $chantier, 'entreprise_id'=>$this->entreprise_id];
        if( !is_null($status ) && (($type == "devis_pro"))){
            $sql .= " AND (achat.status_id = :status OR achat.status_id = :status2)";
            $tabExec['status'] = 15;
            $tabExec['status2'] = 17;
        }
        $datas = $this->em->prepare($sql);
        $datas->execute($tabExec);
        $datas = $datas->fetch();

        return $datas;
    }

    public function countMontantByfacturedGroupDate($mois, $annee, $type, $chantierId = null, $fournisseurId = null, $code_compta = null, $paye = null, $lotId =null, $is_devis_client_rattach = null, $is_paid=null){
        $sql = "
            SELECT MONTH(achat.factured_at) as mois, SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat";

        if(!is_null($chantierId) || !is_null($fournisseurId) || !is_null($lotId)){
            $where = " WHERE achat.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON achat.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($fournisseurId)){
                $sql .= " inner join fournisseurs as f ON achat.fournisseur_id = f.id";
                $where .= " AND f.id = :fournisseurId";
            }
            if(!is_null($lotId)){
                $sql .= " inner join lot as l ON achat.lot_id = l.id ";
                $where .= " AND l.id = :lotId";
            }

            $sql .= " ".$where." AND achat.type = :type ";
        }
        else{
            $sql .= " WHERE achat.entreprise_id = :entreprise_id AND achat.type = :type ";
        }

        if(!is_null($is_devis_client_rattach)){
            if($is_devis_client_rattach == 1){
                $sql .= " AND achat.devis_id IS NOT NULL ";
            }
            else if($is_devis_client_rattach == 2){
                $sql .= " AND achat.devis_id IS NULL ";
            }
        }

        if(!is_null($mois))
            $sql .= " AND MONTH(achat.factured_at) = :month ";
        if(!is_null($annee))
            $sql .= " AND YEAR(achat.factured_at) = :year ";

        if(!is_null($code_compta)){
            $sql .= " AND achat.code_compta = :code_compta ";
        }
        
        if(!is_null($paye) && $paye){
            $sql .= " AND achat.bl_validation IS NOT NULL ";
        }
        else if(!is_null($paye) && !$paye){
            $sql .= " AND achat.bl_validation IS NULL ";
        }

        if(!is_null($is_paid)){
            if($is_paid == 1){
                $sql .= " AND achat.reglement_id is not null ";
            }
            elseif($is_paid == 2){
                $sql .= " AND achat.reglement_id is null ";
            }
        }

        $sql .=" GROUP BY MONTH(achat.factured_at)";
        $datas = $this->em->prepare($sql);
        $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];
        if(!is_null($mois))
            $tabExec['month'] = $mois;
        if(!is_null($annee))
            $tabExec['year'] = $annee;

        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($fournisseurId))
            $tabExec['fournisseurId'] = $fournisseurId;
        if(!is_null($lotId))
            $tabExec['lotId'] = $lotId;
        if(!is_null($code_compta)){
            $tabExec['code_compta'] = $code_compta;
        }
        
        $datas->execute($tabExec);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function countMontantByfacturedGroupDateList($mois, $annee, $type, $chantierId = null, $fournisseurId = null, $code_compta = null, $paye = null, $lotId =null, $is_devis_client_rattach = null){
        $sql = "
            SELECT achat.bl_validation, MONTH(achat.factured_at) as mois, achat.prixht as sum_ht, achat.prixttc as prixttc FROM achat";

        if(!is_null($chantierId) || !is_null($fournisseurId)){
            $where = " WHERE achat.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON achat.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($fournisseurId)){
                $sql .= " inner join fournisseurs as f ON achat.fournisseur_id = f.id";
                $where .= " AND f.id = :fournisseurId";
            }
            if(!is_null($lotId)){
                $sql .= " inner join lot as l ON achat.lot_id = l.id ";
                $where .= " AND l.id = :lotId";
            }

            $sql .= " ".$where." AND achat.type = :type ";
        }
        else{
            $sql .= " WHERE achat.entreprise_id = :entreprise_id AND achat.type = :type ";
        }

        if(!is_null($is_devis_client_rattach)){
            if($is_devis_client_rattach == 1){
                $sql .= " AND achat.devis_id IS NOT NULL ";
            }
            else if($is_devis_client_rattach == 2){
                $sql .= " AND achat.devis_id IS NULL ";
            }
        }

        if(!is_null($mois))
            $sql .= " AND MONTH(achat.factured_at) = :month ";
        if(!is_null($annee))
            $sql .= " AND YEAR(achat.factured_at) = :year ";

        if(!is_null($code_compta)){
            $sql .= " AND achat.code_compta = :code_compta ";
        }
        if(!is_null($paye)){
            $sql .= " AND achat.bl_validation IS NOT NULL ";
        }

        //$sql .=" GROUP BY MONTH(achat.factured_at)";
        $datas = $this->em->prepare($sql);
        $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];
        if(!is_null($mois))
            $tabExec['month'] = $mois;
        if(!is_null($annee))
            $tabExec['year'] = $annee;

        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($fournisseurId))
            $tabExec['fournisseurId'] = $fournisseurId;
        if(!is_null($code_compta)){
            $tabExec['code_compta'] = $code_compta;
        }
        
        $datas->execute($tabExec);
        $datas = $datas->fetchAll();


        $sumMontantPaye = ['prixttc' => 0, 'sum_ht'=>0];
        foreach ($datas as $value) {
            $sql = "SELECT COUNT(id) as nbr FROM achat WHERE id = :facture_id AND reglement_id IS NOT NULL ";
            $dt = $this->em->prepare($sql);
            $dt->execute(['facture_id'=>$value['bl_validation']]);
            $dt = $dt->fetch();

            if($dt['nbr'] > 0){
                $sumMontantPaye['prixttc'] =  $sumMontantPaye['prixttc'] + $value['prixttc'];
                $sumMontantPaye['sum_ht'] =  $sumMontantPaye['sum_ht'] + $value['sum_ht'];
            }

        }
        return $sumMontantPaye;
    }

    public function countMontantByfacturedPayeDate($mois, $annee, $type, $chantierId = null, $fournisseurId = null, $lotId = null, $is_paid=null){
        $sql = "
            SELECT SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat";

        if(!is_null($chantierId) || !is_null($fournisseurId) || !is_null($lotId)){
            $where = " WHERE achat.reglement_id IS NOT NULL AND achat.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON achat.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($fournisseurId)){
                $sql .= " inner join fournisseurs as f ON achat.fournisseur_id = f.id";
                $where .= " AND f.id = :fournisseurId";
            }
            if(!is_null($lotId)){
                $sql .= " inner join lot as l ON achat.lot_id = l.id";
                $where .= " AND l.id = :lotId";
            }
            $sql .= " ".$where." AND achat.type = :type ";
        }
        else{
            $sql .= " WHERE achat.entreprise_id = :entreprise_id AND achat.reglement_id IS NOT NULL AND achat.type = :type ";
        }

        if(!is_null($is_paid)){
            if($is_paid == 1){
                $sql .= " AND achat.reglement_id is not null ";
            }
            elseif($is_paid == 2){
                $sql .= " AND achat.reglement_id is null ";
            }
        }

        if(!is_null($mois))
            $sql .= " AND MONTH(achat.factured_at) = :month ";
        if(!is_null($annee))
            $sql .= " AND YEAR(achat.factured_at) = :year ";

        $datas = $this->em->prepare($sql);
        $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];

        if(!is_null($mois))
           $tabExec['month'] = $mois;
        if(!is_null($annee))
            $tabExec['year'] = $annee;

        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($fournisseurId))
            $tabExec['fournisseurId'] = $fournisseurId;
        if(!is_null($lotId))
            $tabExec['lotId'] = $lotId;
        
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        return $datas;
    }

    public function countMontantDue($mois, $annee, $type, $chantierId = null, $fournisseurId = null, $lotId = null, $is_paid=null){
        $sql = "
            SELECT SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat";

        $where = " WHERE achat.due_at IS NOT NULL ";
        if(!is_null($chantierId) || !is_null($fournisseurId) || !is_null($lotId)){
            $where .= " AND achat.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON achat.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($fournisseurId)){
                $sql .= " inner join fournisseurs as f ON achat.fournisseur_id = f.id";
                $where .= " AND f.id = :fournisseurId";
            }
            if(!is_null($lotId)){
                $sql .= " inner join lot as l ON achat.lot_id = l.id";
                $where .= " AND l.id = :lotId";
            }
            $sql .= " ".$where." AND achat.type = :type ";
        }
        else{
            $sql .= " ".$where." AND achat.entreprise_id = :entreprise_id AND achat.type = :type ";
        }

        if(!is_null($is_paid)){
            if($is_paid == 1){
                $sql .= " AND achat.reglement_id is not null ";
            }
            elseif($is_paid == 2){
                $sql .= " AND achat.reglement_id is null ";
            }
        }

        if(!is_null($mois))
            $sql .= " AND MONTH(achat.due_at) = :month ";
        if(!is_null($annee))
            $sql .= " AND YEAR(achat.due_at) = :year ";

        $datas = $this->em->prepare($sql);
        $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];

        if(!is_null($mois))
           $tabExec['month'] = $mois;
        if(!is_null($annee))
            $tabExec['year'] = $annee;

        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($fournisseurId))
            $tabExec['fournisseurId'] = $fournisseurId;
        if(!is_null($lotId))
            $tabExec['lotId'] = $lotId;
        
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        return $datas;
    }

    public function countMontantByfacturedDateInterval($type, $chantierId = null, $start = null, $end = null,  $fournisseurId = null){
        $sql = "
            SELECT SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat";

        if(!is_null($chantierId) || !is_null($fournisseurId)){
            $where = " WHERE achat.entreprise_id = :entreprise_id ";
            if(!is_null($chantierId)){
                $sql .= " inner join chantier as c ON achat.chantier_id = c.chantier_id";
                $where .= " AND c.chantier_id = :chantierId";
            }
            if(!is_null($fournisseurId)){
                $sql .= " inner join fournisseurs as f ON achat.fournisseur_id = f.id";
                $where .= " AND f.id = :fournisseurId";
            }
            $sql .= " ".$where." AND achat.type = :type ";
        }
        else
            $sql .= " WHERE achat.entreprise_id = :entreprise_id AND achat.type = :type ";

        if(!is_null($start)){
            $sql .= " AND achat.factured_at >= :start AND achat.factured_at <= :end ";
        }


        $datas = $this->em->prepare($sql);
        if(!is_null($start))
            $tabExec = ['type'=>$type, 'start'=>$start, 'end'=>$end, 'entreprise_id'=>$this->entreprise_id];
        else
            $tabExec = ['type'=>$type, 'entreprise_id'=>$this->entreprise_id];
        
        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($fournisseurId))
            $tabExec['fournisseurId'] = $fournisseurId;
        
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        return $datas;
    }

    /*public function findGoupSumByfacturedDate($mois, $annee, $type){
        $sql = "SELECT A.id, SUM(A.prixht) as prixht , SUM(A.prixttc) as prixttc FROM achat as A WHERE A.type = :type AND MONTH(A.factured_at) = :month AND YEAR(A.factured_at) = :year GROUP BY YEAR(A.factured_at), MONTH(A.factured_at)";
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
            SELECT b.chantier_id, b.fournisseur_id, b.document_file FROM achat as b WHERE b.id IN $in AND b.document_file IS NOT NULL AND b.type = :type";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type]);
        $datas = $datas->fetchAll();
        return $datas;
    }
    public function findByTabIdNotExportCompta($tabIdFc, $type){
        if(empty($tabIdFc))
            return [];
        
        $in = '(' . implode(',', $tabIdFc) .')';

        /*$req = $this->createQueryBuilder('a')
            ->where('a.id IN (:in)')
            ->andWhere('a.type = :type')
            ->andWhere('a.document_file IS NOT NULL')
            ->andWhere('a.export_compta IS NULL')
            ->setParameter('in', $in)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();*/

        $sql = "
            SELECT b.chantier_id, b.document_file, b.fournisseur_id FROM achat as b WHERE b.id IN $in AND b.document_file IS NOT NULL AND b.type = :type AND export_compta IS NULL";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type]);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function countFactureExport($tabIdBl, $type){
        if(empty($tabIdBl))
            return [];
        
        $in = '(' . implode(',', $tabIdBl) .')';
        $sql = "
            SELECT COUNT(b.id) as ct FROM achat as b WHERE b.id IN $in AND b.type = :type AND export_compta IS NOT NULL";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type]);
        $datas = $datas->fetch();
        return $datas['ct'];
    }

    public function countDevisByChantierAndStatus($chantierId){
        $sql = "
            SELECT COUNT(d.id) as ct FROM achat as d WHERE d.chantier_id = :chantierId AND d.type = :type AND (d.status_id = :status1 OR d.status_id = :status2)";
        $datas = $this->em->prepare($sql);
        $datas->execute(['chantierId'=>$chantierId, 'type'=>'devis_pro', 'status1'=>15, 'status2'=>17]);
        $datas = $datas->fetch();
        return $datas['ct'];
    }

    public function validFacture($tabAchatId, $type, $blListId){
        if(empty($tabAchatId))
            return [];
        
        $in = '(' . implode(',', $tabAchatId) .')';
        $sql = "UPDATE achat SET achat.bl_validation = :bl_validation WHERE achat.type = :type AND achat.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type, 'bl_validation'=>$blListId]);
        return 1;
    }
    public function updateFactureCompta($tabAchatId){
        if(empty($tabAchatId))
            return [];
        
        $in = '(' . implode(',', $tabAchatId) .')';
        $sql = "UPDATE achat SET achat.export_compta = :export_compta, achat.date_export_compta = :date_export_compta WHERE achat.type = :type AND achat.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>'facturation', 'export_compta'=> 1, 'date_export_compta'=> date('Y-m-d')]);
        return 1;
    }
    public function validBl($tabBlId, $type, $factureId){
        if(empty($tabBlId))
            return [];
        
        $in = '(' . implode(',', $tabBlId) .')';
        $sql = "UPDATE achat SET achat.bl_validation = :bl_validation WHERE achat.type = :type AND achat.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type, 'bl_validation'=>$factureId]);
        return 1;
    }    

    public function StopFactureDueDatePass($factureDueDate){

        $in = '(' . implode(',', $factureDueDate) .')';
        $sql = "UPDATE achat SET achat.automatique = :automatique WHERE achat.id IN $in";
        $datas = $this->em->prepare($sql);
        $datas->execute(['automatique'=> 0]);
        
        return 1;
    }

    public function countMontantByChantier($type, $chantierId = null){
        $sql = "
            SELECT SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat";

        if(!is_null($chantierId)){
            $sql .= " inner join chantier as c WHERE achat.entreprise_id = :entreprise_id AND achat.chantier_id = c.chantier_id AND c.chantier_id = :chantierId AND achat.type = :type";
        }
        else
            $sql .= " WHERE achat.entreprise_id = :entreprise_id AND achat.type = :type";

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
            SELECT SUM(achat.prixht) as ht FROM achat WHERE achat.entreprise_id = :entreprise_id AND achat.chantier_id = :chantier_id AND type = :type";
        $datas = $this->em->prepare($sql);
        $datas->execute(['chantier_id'=>$chantierId, 'type'=>$type, 'entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetch();
        return $datas['ht'];
    }

    public function findByFFTH($fournisseurId, $dateFactured, $prixht, $type){
        $sql = "
            SELECT achat.id, achat.document_file FROM achat WHERE achat.entreprise_id = :entreprise_id AND achat.fournisseur_id = :fournisseurId AND achat.factured_at = :dateFactured AND achat.prixht = :prixht AND achat.type = :type LIMIT 1";

        $datas = $this->em->prepare($sql);
        $datas->execute(['fournisseurId'=>$fournisseurId, 'dateFactured'=>$dateFactured->format('Y-m-d')." 00:00:00", 'prixht'=> $prixht, 'type'=>$type, 'entreprise_id'=>$this->entreprise_id]);
        return $datas->fetch();
    }

    public function findDoublon($type, $entreprise_id){
        $sql = "SELECT A.* FROM achat A INNER JOIN( SELECT h.prixht, h.factured_at, h.fournisseur_id
        FROM achat as h WHERE h.type = :type AND h.entreprise_id = :entreprise_id
         GROUP BY h.prixht, h.factured_at, h.fournisseur_id HAVING COUNT(h.id) > 1) B ON A.prixht = B.prixht AND A.factured_at = B.factured_at AND A.fournisseur_id =B.fournisseur_id";

        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type, 'entreprise_id'=>$entreprise_id]);
        return $datas->fetchAll();
    }

    public function findDoublonDocumentId($type){
        $sql = "SELECT A.* FROM achat A INNER JOIN( SELECT h.document_id
        FROM achat as h WHERE h.type = :type AND h.entreprise_id = :entreprise_id
         GROUP BY h.document_id HAVING COUNT(h.id) > 1) B ON A.document_id = B.document_id";

        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=>$type, 'entreprise_id'=>$this->entreprise_id]);
        return $datas->fetchAll();
    }
    
    public function attachDevis($listAchatId, $devisId){
        $in = '(' . implode(',', $listAchatId) .')';  
        $sql = "UPDATE achat SET devis_id = $devisId WHERE achat.id in $in";
        
        $sql = $this->em->prepare($sql);
        $sql->execute();
        return 1;
    }

    public function attachDevisPro($listAchatId, $devisId){
        $in = '(' . implode(',', $listAchatId) .')';  
        $sql = "UPDATE achat SET devis_pro_id = $devisId WHERE achat.id in $in";
        
        $sql = $this->em->prepare($sql);
        $sql->execute();
        return 1;
    }

    public function getSumHTByDevis($devisId, $chantierId, $type){
        $sql = "
            SELECT SUM(a.prixht) as prixht FROM achat as a WHERE a.devis_id = :devisId AND a.type = :type AND a.chantier_id = :chantierId";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=> $type, 'devisId'=>$devisId, 'chantierId'=>$chantierId]);
        $datas = $datas->fetch();
        $prixht = $datas ? $datas['prixht'] : 0;
        return $prixht;
    }

    public function countMontantByDevis($type, $devisId){
        $sql = "SELECT SUM(achat.prixht) as sum_ht, SUM(achat.prixttc) as prixttc FROM achat WHERE achat.devis_id = :devisId AND achat.type = :type";
        $datas = $this->em->prepare($sql);
        $datas->execute(['type'=> $type, 'devisId'=>$devisId]);
        $datas = $datas->fetch();
        return $datas;
    }

    public function getBlByCHantierFournDate($chantier, $fournisseur, $dateFact){
        $sql = "SELECT a.id FROM achat a WHERE a.chantier_id = :chantier AND a.fournisseur_id = :fournisseur AND a.type = :type AND date_format(a.factured_at, '%Y-%m-%d') = :dateFact LIMIT 1";

        $datas = $this->em->prepare($sql);
        $datas->execute(['chantier'=> $chantier, 'fournisseur'=>$fournisseur, 'dateFact'=>$dateFact, 'type'=>'bon_livraison']);
        $datas = $datas->fetch();
        return $datas;
    }

    public function getBlByCHantierFournDateMonth($type, $fournisseurId, $chantierId, $yearMonth){
        $sql = "SELECT a.id FROM achat a WHERE a.chantier_id = :chantierId AND a.fournisseur_id = :fournisseurId AND a.type = :type AND date_format(a.factured_at, '%Y-%m') = :yearMonth LIMIT 1";

        $datas = $this->em->prepare($sql);
        $datas->execute(['chantierId'=> $chantierId, 'fournisseurId'=>$fournisseurId, 'yearMonth'=>$yearMonth, 'type'=>'bon_livraison']);
        $datas = $datas->fetchAll();

        $datasArr = [];
        foreach ($datas as $key => $value) {
            $req = $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $value['id'])
            ->getQuery()
            ->getSingleResult();

            $datasArr[] = $req;
        }
        return $datasArr;
    }

}
