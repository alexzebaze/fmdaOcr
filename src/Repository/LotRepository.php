<?php

namespace App\Repository;

use App\Entity\Lot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Lot|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lot|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lot[]    findAll()
 * @method Lot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Lot::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Lot[] Returns an array of Lot objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Lot
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getLotsWithoutCateg(){

        $req = $this->createQueryBuilder('l')
            ->andWhere('l.previsionel_categorie IS NULL')
            ->andWhere('l.entreprise = :entreprise_id')
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->orderBy('l.lot', 'ASC')
            ->getQuery()
            ->getResult();

        return $req;
    }    
    public function getByTabId($tabId){
        $req = $this->createQueryBuilder('l')
            ->where('l.id IN (:in)')
            ->andWhere('l.entreprise = :entreprise')
            ->setParameter('in', $tabId)
            ->setParameter('entreprise', $this->entreprise_id)
            ->getQuery()
            ->getResult();

        return $req;
    }
}
