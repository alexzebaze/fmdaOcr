<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Location|null find($id, $lockMode = null, $lockVersion = null)
 * @method Location|null findOneBy(array $criteria, array $orderBy = null)
 * @method Location[]    findAll()
 * @method Location[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationRepository extends ServiceEntityRepository
{

    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Location::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }


    // /**
    //  * @return Location[] Returns an array of Location objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Location
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function countMontantLocation($bienId){
        $sql = "
            SELECT SUM(lc.loyer_hc) as loyer_hc, SUM(lc.loyer_charge) as loyer_charge FROM location lc WHERE entreprise_id = :entreprise_id ";

        if(!is_null($bienId)){
            $sql .= " AND lc.chantier_id = $bienId ";
        }
        $datas = $this->em->prepare($sql);

        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetch();
        return $datas;
    }

    public function getReleveLocation($locationId){
        $sql = "
            SELECT t.*
            FROM ( SELECT location_id, MAX(date_releve) as lastDate
                  FROM releve_location rl WHERE rl.entreprise_id = :entreprise_id AND rl.location_id = :location_id
                  GROUP BY rl.location_id ) r
            INNER JOIN releve_location t
            ON (t.location_id = r.location_id AND t.date_releve = r.lastDate) WHERE t.entreprise_id = :entreprise_id AND t.location_id = :location_id";

        $datas = $this->em->prepare($sql);

        $datas->execute(['entreprise_id'=>$this->entreprise_id, 'location_id'=>$locationId]);
        $datas = $datas->fetchAll();
        
        return $datas;
    }    

    public function getLocationWithReleve(){
        $sql = "
            SELECT l.*, 
            (SELECT r.* FROM releve_location r WHERE r.location_id = l.id AND r.releve = 1 AND entreprise_id = :entreprise_id ORDER BY r.id DESC LIMIT 1) as eau, 
            (SELECT r.* FROM releve_location r WHERE r.location_id = l.id AND r.releve = 2 AND entreprise_id = :entreprise_id ORDER BY r.id DESC LIMIT 1) as electricite,
            (SELECT r.* FROM releve_location r WHERE r.location_id = l.id AND r.releve = 3 AND entreprise_id = :entreprise_id ORDER BY r.id DESC LIMIT 1) as gaz FROM location l WHERE entreprise_id = :entreprise_id";

        $datas = $this->em->prepare($sql);

        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();
        return $datas;
    }


    public function findByParams($bienId=null)
    {

        $req = $this->createQueryBuilder('l');
        if(!is_null($bienId)){
            $req = $req->join('l.bien', 'c')
            ->addSelect('c')
            ->andWhere('c.chantierId = :bienId')
            ->setParameter('bienId', $bienId);
        }
        
        $req = $req->andWhere('l.entreprise = :entreprise_id')
        ->setParameter('entreprise_id', $this->entreprise_id)
        ->getQuery()
        ->getResult();

        return $req;
    }

    public function getByTabLocation($field, $tabLocation){

        $in = '('.$tabLocation.')';
        $sql = "SELECT lc.id as location_id, u.id as client_id, u.email, u.telone FROM location as lc INNER JOIN client u ON lc.locataire_id = u.id WHERE lc.id IN $in AND lc.entreprise_id = :entreprise ";

        if($field == "locataire_email"){
            $sql .= " AND u.email IS NOT NULL ";
        }
        else if($field == "locataire_sms"){
            $sql .= " AND u.telone IS NOT NULL ";
        }
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();
          
        return $datas;
    }

    public function insertNotificationLocataire($query){

        $query = rtrim($query, ",");
        $sql = 'INSERT INTO locataire_notification (location_id, client_id, message, type, entreprise_id, piece_jointe, date_send) VALUES '.$query;
        
        $notification = $this->em->prepare($sql);
        $notification->execute();
        return 1;
    }
}
