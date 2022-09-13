<?php

namespace App\Repository;

use App\Entity\Horaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HoraireRepository extends ServiceEntityRepository
{
    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Horaire::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    public function findByParams($chantierId, $ouvrierId, $devisId = null, $mois=null, $annee=null, $is_devis_rattach = null, $fonction = null){

        //["Devis rattachés"=>1, "Aucun devis rattachés"=>2]

        $today = (new \DateTime())->format('Y-m-d');
        $sql = "";
        $where = " WHERE 1 ";
        if(!is_null($chantierId)){
            $chantierJoin = " INNER join chantier as c ON h.chantierid = c.chantier_id ";
            $where .= " AND c.chantier_id = :chantierId ";
        }
        else{
            $chantierJoin = " LEFT join chantier as c ON (c.chantier_id = h.chantierid OR c.chantier_id IS NULL) ";
        }

        if(!is_null($ouvrierId)){
            $ouvrierjoin = " INNER join utilisateur as u ON h.userid = u.uid ";
            $where .= " AND u.uid = :ouvrierId ";
        }
        else
            $ouvrierjoin = " LEFT join utilisateur as u ON h.userid = u.uid ";


        if(!is_null($devisId) || (!is_null($is_devis_rattach) && $is_devis_rattach == 1 )){
            $devisJoin = " INNER join vente as v ON h.devis_id = v.id ";

            if(!is_null($devisId))
                $where .= " AND v.id = :devisId ";
            else
                $where .= "AND h.devis_id IS NOT NULL";
        }
        else{
            $devisJoin = " LEFT join vente as v ON (v.id = h.devis_id OR v.id IS NULL) ";

            if(!is_null($is_devis_rattach) && $is_devis_rattach == 2 ){
                $where .= " AND h.devis_id IS NULL ";
            }
        }


        if(!is_null($mois))
            $where .= " AND MONTH(h.datestart) = :mois ";
        if(!is_null($annee))
            $where .= " AND YEAR(h.datestart) = :annee ";
        if(!is_null($fonction))
            $where .= " AND h.fonction = :fonction ";

        $sql = "SELECT CONCAT(u.firstname, ' ', u.lastname) as firstname, u.image as image, u.uid as user_id, h.datestart, h.dateend, h.fonction, h.idsession, h.time, h.absence, h.fictif, c.nameentreprise, c.chantier_id as chantier_id, v.document_file, v.document_id, v.id as vente_id, u.date_entree, u.date_sortie FROM horaire as h $chantierJoin $devisJoin $ouvrierjoin ";
        $where .= " AND u.entreprise_id = :entreprise AND u.etat = :verif  AND date_format(h.datestart, '%Y-%m-%d') <= :today order By h.datestart DESC ";
        $sql .= $where;

        $datas = $this->em->prepare($sql);
        $tabExec = ['entreprise'=>$this->entreprise_id, 'verif'=>1, 'today'=>$today];
        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($ouvrierId))
            $tabExec['ouvrierId'] = $ouvrierId;
        if(!is_null($devisId))
            $tabExec['devisId'] = $devisId;
        if(!is_null($mois))
            $tabExec['mois'] = $mois;
        if(!is_null($annee))
            $tabExec['annee'] = $annee;
        
        if(!is_null($fonction))
            $tabExec['fonction'] = $fonction;
        

        $datas->execute($tabExec);
        $datas = $datas->fetchAll();
        //dd([$sql, $tabExec, $datas]);
        return $datas;
    }

    public function findHoraireFictiveGroupByUser($mois= null, $annee=null, $utilisateurId=null){
        $sql = "SELECT h.absence, h.datestart, h.userid, u.firstname, u.lastname, h.pause, h.time as heure, h.fictif as fictif FROM horaire h INNER JOIN utilisateur u ON h.userid = u.uid WHERE u.etat = 1 AND u.entreprise_id = :entreprise ";

        $tabExec = ['entreprise'=>$this->entreprise_id];
        if(!is_null($mois)){
            $sql .=" AND MONTH(h.datestart) = :mois ";
            $tabExec['mois'] = $mois;
        }
        if(!is_null($annee)){
            $sql .=" AND YEAR(h.datestart) = :annee ";
            $tabExec['annee'] = $annee;
        }
        if(!is_null($utilisateurId)){
            $sql .=" AND h.userid = :userid ";
            $tabExec['userid'] = $utilisateurId;
        }

        $datas = $this->em->prepare($sql);

        $datas->execute($tabExec);
        $datas = $datas->fetchAll();
        //dd([$sql, $tabExec, $datas]);
        return $datas;
    }

    public function getAllTacheByParams($chantierId, $ouvrierId, $devisId = null, $mois=null, $annee=null, $is_devis_rattach = null, $tofilter= null){

        //["Devis rattachés"=>1, "Aucun devis rattachés"=>2]

        $today = (new \DateTime())->format('Y-m-d');
        $sql = "";
        $where = " WHERE 1 ";
        if(!is_null($chantierId)){
            $chantierJoin = " INNER join chantier as c ON h.chantierid = c.chantier_id ";
            $where .= " AND c.chantier_id = :chantierId ";
        }
        else{
            $chantierJoin = " LEFT join chantier as c ON (c.chantier_id = h.chantierid OR c.chantier_id IS NULL) ";
        }

        if(!is_null($ouvrierId)){
            $ouvrierjoin = " INNER join utilisateur as u ON h.userid = u.uid ";
            $where .= " AND u.uid = :ouvrierId ";
        }
        else
            $ouvrierjoin = " LEFT join utilisateur as u ON h.userid = u.uid ";


        if(!is_null($devisId) || (!is_null($is_devis_rattach) && $is_devis_rattach == 1 )){
            $devisJoin = " INNER join vente as v ON h.devis_id = v.id ";

            if(!is_null($devisId))
                $where .= " AND v.id = :devisId ";
            else
                $where .= "AND h.devis_id IS NOT NULL";
        }
        else{
            $devisJoin = " LEFT join vente as v ON (v.id = h.devis_id OR v.id IS NULL) ";

            if(!is_null($is_devis_rattach) && $is_devis_rattach == 2 ){
                $where .= " AND h.devis_id IS NULL ";
            }
        }


        if(!is_null($mois))
            $where .= " AND MONTH(h.datestart) = :mois ";
        if(!is_null($annee))
            $where .= " AND YEAR(h.datestart) = :annee ";

        if(!is_null($tofilter)){
            $sql = "SELECT DISTINCT c.chantier_id FROM horaire as h $chantierJoin $devisJoin $ouvrierjoin ";
        }
        else{
            $sql = "SELECT DISTINCT h.fonction FROM horaire as h $chantierJoin $devisJoin $ouvrierjoin ";
        }
        $where .= " AND u.entreprise_id = :entreprise AND u.etat = :verif  AND h.datestart <= :today order By h.datestart DESC ";
        $sql .= $where;

        $datas = $this->em->prepare($sql);
        $tabExec = ['entreprise'=>$this->entreprise_id, 'verif'=>1, 'today'=>$today];
        if(!is_null($chantierId)){
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($ouvrierId))
            $tabExec['ouvrierId'] = $ouvrierId;
        if(!is_null($devisId))
            $tabExec['devisId'] = $devisId;
        if(!is_null($mois))
            $tabExec['mois'] = $mois;
        if(!is_null($annee))
            $tabExec['annee'] = $annee;
        

        $datas->execute($tabExec);
        $datas = $datas->fetchAll();

        return $datas;
    }

    public function findByParams2($chantierId, $ouvrierId)
    {
        $filter = ['h.idsession', 'DESC'];

        $req = $this->createQueryBuilder('h');
        if(!is_null($chantierId)){
            $req = $req->join('h.userid', 'c')
            ->addSelect('c')
            ->andWhere('c.chantierid = :chantierId')
            ->setParameter('chantierId', $chantierId);
        }
        if($ouvrierId){
            $req = $req->join('h.chantier', 'c')
            ->addSelect('c')
            ->andWhere('c.chantierid = :chantierId')
            ->setParameter('chantierId', $chantierId);
        }
        if($ouvrierId){
            $req = $req->andWhere('h.userid = :ouvrierId')
            ->setParameter('ouvrierId', $ouvrierId);
        }
        $req = $req->orderBy($filter[0], $filter[1])
        ->getQuery()
        ->getResult();

        return $req;
    }

    public function findHoraireByUserAndDate($annee, $mois, $user, $chantier = null)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('s')
            ->from('App\Entity\Horaire', 's')
            ->innerJoin('App\Entity\Utilisateur', 'u', 'WITH', 's.userid = u.uid')
            ->where('s.userid = :user_id')
            ->andWhere('u.entreprise = :entreprise_id')
            ->andWhere('YEAR(s.datestart) = :year')
            ->andWhere('MONTH(s.datestart) = :month')
            ->setParameter('user_id', $user->getUid())
            ->setParameter('year', $annee)
            ->setParameter('month', $mois)
            ->setParameter('entreprise_id', $this->entreprise_id);

        if (!empty($chantier)) {
            $query->andWhere('s.chantierid = :chantier')
                ->setParameter('chantier', $chantier);
        }

        return $horaires = $query
            ->getQuery()
            ->getResult();
    }

    public function findTotalHoraireByUserAndDate($annee, $mois, $user, $chantier = null)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('s')
            ->from('App\Entity\Horaire', 's')
            ->innerJoin('App\Entity\Utilisateur', 'u', 'WITH', 's.userid = u.uid')
            ->where('s.userid = :user_id')
            ->andWhere('u.entreprise = :entreprise_id')
            ->andWhere('YEAR(s.datestart) = :year')
            ->andWhere('MONTH(s.datestart) = :month')
            ->setParameter('user_id', $user)
            ->setParameter('year', $annee)
            ->setParameter('month', $mois)
            ->setParameter('entreprise_id', $this->entreprise_id);

        if (!empty($chantier)) {
            $query->andWhere('s.chantierid = :chantier')
                ->setParameter('chantier', $chantier);
        }

        $horaires = $query
            ->getQuery()
            ->getResult();
        $total = 0;
        foreach ($horaires as $horaire) {
            $total+= $horaire->getTime();
        }
        return $total;
    }


    public function getHoraireByChantier($chantierId) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('h.idsession, h.fonction, h.userid,  u.firstname, u.lastname,  h.time,  h.datestart')
            ->from('App\Entity\Horaire', 'h')
            ->innerJoin('App\Entity\Utilisateur', 'u', 'WITH', 'h.userid = u.uid')
            ->innerJoin('App\Entity\Chantier', 'c', 'WITH', 'h.chantierid = c.chantierId')
            ->andWhere('c.chantierId = :id')
            ->setParameter('id', $chantierId)
        ;
    }

    public function getHoraireByNada() {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('h.fonction, h.userid,  u.firstname, u.lastname,  h.time,  h.datestart')
            ->from('App\Entity\Horaire', 'h')
            ->innerJoin('App\Entity\Utilisateur', 'u', 'WITH', 'h.userid = u.uid')
            ->innerJoin('App\Entity\Chantier', 'c', 'WITH', 'h.chantierid = c.chantierId')
            ->where('u.entreprise = :entreprise_id')
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->orderBy('h.datestart', 'asc')
        ;
    }

    public function getHoraireByUser($id) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('h.fonction, h.userid,  u.firstname, u.lastname,  h.time,  h.datestart')
            ->from('App\Entity\Horaire', 'h')
            ->innerJoin('App\Entity\Utilisateur', 'u', 'WITH', 'h.userid = u.uid')
            ->innerJoin('App\Entity\Chantier', 'c', 'WITH', 'h.chantierid = c.chantierId')
            ->andWhere('u.uid = :id')
            ->setParameter('id', $id)
            ->orderBy('h.datestart', 'asc')
        ;
    }

    public function getHoraireByUserAndChantier($user_id, $chantier, $heure){
        /*if(empty($tabMatCollab))
            return [];
        
        $in = '(' . implode(',', $tabMatCollab) .')';*/
        $sql = "SELECT A.userid, A.datestart, B.t FROM horaire A INNER JOIN (SELECT h.userid, h.datestart, h.chantierid, SUM(h.time) as t FROM horaire as h WHERE h.userid = :user_id AND h.chantierid = :chantier GROUP BY h.chantierid HAVING SUM(h.time) = :heure) B ON A.userid = B.userid GROUP BY YEAR(A.datestart), MONTH(A.datestart)";
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['user_id'=>$user_id, 'chantier'=>$chantier, 'heure'=>$heure]);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function findHoraireUserByDateAndChantier($mois, $annee, $userId, $chantierId = null){

        $tabExec = [];
        
         $sql = "SELECT h.*, v.document_file, v.document_id, u.firstname, u.lastname, c.nameentreprise FROM horaire h INNER JOIN utilisateur u ON h.userid = u.uid INNER JOIN chantier c ON h.chantierid = c.chantier_id LEFT JOIN vente v ON h.devis_id = v.id WHERE 1 "; 

        if(!is_null($chantierId)){
            $sql .= " AND c.chantier_id = :chantierId ";
            $tabExec['chantierId'] = $chantierId;
        }
        if(!is_null($userId)){
            $sql .= " AND u.uid = :userId ";
            $tabExec['userId'] = $userId;
        }

        if(!is_null($mois)){
            $sql .= " AND MONTH(h.datestart) = :month ";
            $tabExec['month'] = $mois;
        }
        if(!is_null($annee)){
            $sql .= " AND YEAR(h.datestart) = :year ";
            $tabExec['year'] = $annee;
        }
        $sql .= " ORDER BY h.datestart DESC ";

        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute($tabExec);

        $datas = $datas->fetchAll();
        return $datas;
    }

    public function countTimeByDevis($devisId, $chantierId){
        $sql = "SELECT SUM(h.time) as nbHeure FROM horaire as h WHERE h.devis_id = :devisId AND h.chantierid = :chantierId";
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['devisId'=>$devisId, 'chantierId'=>$chantierId]);
        $datas = $datas->fetch();
        $nbHeure = $datas ? $datas['nbHeure'] : 0;
        return $nbHeure;
    }

    public function countUserChantierHoraireDevis($devisId, $chantierId){
        $sql = "SELECT COUNT(DISTINCT h.userid) as nbUser FROM horaire as h WHERE h.devis_id = :devisId AND h.chantierid = :chantierId AND userid IS NOT NULL";
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['devisId'=>$devisId, 'chantierId'=>$chantierId]);
        $datas = $datas->fetch();
        $nbHeure = $datas ? $datas['nbUser'] : 0;
        return $nbHeure;
    }

    public function getGeneraleHourFictif($user_id, $mois = null, $annee = null){
        $sql = "SELECT SUM(fictif) as hr FROM horaire WHERE fictif<>1 and userid = :user_id ";

        if(!is_null($mois)){
            $sql .= " and YEAR(datestart) = :annee AND MONTH(datestart) = :mois";
        }
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        if(is_null($mois))
            $datas->execute(['user_id'=>$user_id]);
        else
            $datas->execute(['user_id'=>$user_id, 'mois'=>$mois, 'annee'=>$annee]);
        $datas = $datas->fetch();
        return $datas['hr'];
    }

    public function getGeneraleTime($user_id, $mois = null, $annee = null){
        $sql = "SELECT SUM(time) as hr FROM horaire WHERE userid = :user_id ";

        $tabExec = ['user_id'=>$user_id];
        if($mois){
            $sql .= " and MONTH(datestart) = :mois ";
            $tabExec['mois'] = $mois;
        }
        if($annee){
            $sql .= " and YEAR(datestart) = :annee ";
            $tabExec['annee'] = $annee;
        }

        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        return $datas ? $datas['hr'] : $datas;
    }

    public function findChantierBydateHoraire($start, $end, $form, $user, $status){
        
        if($form){
            $sql = "SELECT c.nameentreprise, c.chantier_id, c.default_galerie_id, c.entreprise_id, c.address, c.image, c.cp, c.city, c.numero, c.status, B.time FROM chantier c INNER JOIN(SELECT b.chantier_id, SUM(h.time) as time FROM chantier b INNER JOIN horaire h ON h.chantierid = b.chantier_id WHERE entreprise_id = :entreprise_id AND status = :status AND h.datestart >= :start AND h.datestart <= :end  GROUP BY h.chantierid) B ON c.chantier_id = B.chantier_id WHERE c.entreprise_id = :entreprise_id AND c.status = :status ORDER BY c.nameentreprise ASC";
        }
        else{
            $sql = "SELECT c.nameentreprise, c.chantier_id, c.address, c.default_galerie_id, c.entreprise_id, c.image, c.cp, c.city, c.numero, c.status, B.time FROM chantier c LEFT JOIN(SELECT b.chantier_id, SUM(h.time) as time FROM chantier b INNER JOIN horaire h ON h.chantierid = b.chantier_id WHERE entreprise_id = :entreprise_id AND status = :status GROUP BY h.chantierid) B ON c.chantier_id = B.chantier_id WHERE entreprise_id = :entreprise_id AND status = :status ORDER BY c.nameentreprise ASC";
        }
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        if($form){
            $datas->execute(['status'=>$status, 'entreprise_id'=>$this->entreprise_id, 'end'=>$end->format('Y-m-d'), 'start'=>$start->format('Y-m-d')]);
        }
        else{
            $datas->execute(['status'=>$status, 'entreprise_id'=>$this->entreprise_id]);
        }

        $datas = $datas->fetchAll();

        return $datas;
    }

    public function filterChantierBydateHoraire($start, $end, $user, $valFilter = ""){
        
        $sql = "SELECT c.nameentreprise, c.chantier_id, c.address, c.default_galerie_id, c.entreprise_id, c.image, c.cp, c.city, c.numero, c.status, B.time FROM chantier c LEFT JOIN(SELECT b.chantier_id, SUM(h.time) as time FROM chantier b INNER JOIN horaire h ON h.chantierid = b.chantier_id WHERE entreprise_id = :entreprise_id GROUP BY h.chantierid) B ON c.chantier_id = B.chantier_id WHERE entreprise_id = :entreprise_id AND LOWER(c.nameentreprise) LIKE :nom ORDER BY c.nameentreprise ASC ";
        
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id, 'nom' => strtolower('%'.$valFilter.'%')]);
    
        $datas = $datas->fetchAll();

        return $datas;
    }

    public function getHoraireByChantierAndGroupFct($chantierId) {
        $sql = "SELECT * FROM horaire h WHERE h.chantierid = :chantierId GROUP BY h.fonction";

        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['chantierId'=>$chantierId]);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function getStartEndDate($fonction, $chantierId) {
        $sql = "SELECT MIN(h.datestart) as datestart, MAX(h.dateend) as dateend FROM horaire h  WHERE h.fonction = :fonction AND h.chantierid = :chantierId";

        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['fonction'=>$fonction, 'chantierId'=>$chantierId]);
        $datas = $datas->fetch();
        return $datas;
    }

    public function getUserHoraire($fonction, $chantierId) {
        $sql = "SELECT h.userid FROM horaire h WHERE h.fonction = :fonction AND h.chantierid = :chantierId GROUP BY h.userid ";

        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['fonction'=>$fonction, 'chantierId'=>$chantierId]);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function attachDevis($listHoraireId, $devisId){

        $in = '(' . implode(',', $listHoraireId) .')';
        $sql = "UPDATE horaire SET devis_id = $devisId WHERE horaire.idsession in $in";
        
        $sql = $this->em->prepare($sql);
        $sql->execute();
        return 1;
    }

    public function getHoraireDetailByDevis($devisId){
        $sql = "SELECT SUM(h.time) as heure, h.* FROM horaire as h WHERE h.devis_id = :devisId GROUP BY h.userid";
        $datas = $this->em->prepare($sql);
        $datas->execute(['devisId'=>$devisId]);
        return $datas->fetchAll();
    }
}