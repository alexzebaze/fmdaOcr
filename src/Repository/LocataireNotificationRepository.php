<?php

namespace App\Repository;

use App\Entity\LocataireNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method LocataireNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method LocataireNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method LocataireNotification[]    findAll()
 * @method LocataireNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocataireNotificationRepository extends ServiceEntityRepository
{
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, LocataireNotification::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }


    public function findNotificationByParam($mois, $annee, $clientId, $fournisseurId=null, $type_receiver=null){

        $req = $this->createQueryBuilder('n');
        if(!is_null($clientId)){
            $req = $req->join('n.client', 'c')
            ->addSelect('c')
            ->andWhere('c.id = :clientId')
            ->setParameter('clientId', $clientId);
        }
        if(!is_null($fournisseurId)){
            $req = $req->join('n.client', 'f')
            ->addSelect('f')
            ->andWhere('f.id = :fournisseurId')
            ->setParameter('fournisseurId', $fournisseurId);
        }
        
        if(!is_null($mois)){
            $req = $req->andWhere('MONTH(n.date_send) = :month')
            ->setParameter('month', $mois);
        }
        if(!is_null($annee)){
            $req = $req->andWhere('YEAR(n.date_send) = :year')
            ->setParameter('year', $annee);
        }
        if(!is_null($type_receiver)){
            $req = $req->andWhere('n.receiver = :type_receiver')
            ->setParameter('type_receiver', $type_receiver);
        }
        

        $req = $req->andWhere('n.entreprise = :entreprise_id')
        ->setParameter('entreprise_id', $this->entreprise_id)
        ->orderBy('n.date_send', 'DESC')
        ->getQuery()
        ->getResult();

        return $req;
    }

    // /**
    //  * @return LocataireNotification[] Returns an array of LocataireNotification objects
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
    public function findOneBySomeField($value): ?LocataireNotification
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
