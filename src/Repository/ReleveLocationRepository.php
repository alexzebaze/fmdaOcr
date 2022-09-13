<?php

namespace App\Repository;

use App\Entity\ReleveLocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ReleveLocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReleveLocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReleveLocation[]    findAll()
 * @method ReleveLocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReleveLocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReleveLocation::class);
    }

    // /**
    //  * @return ReleveLocation[] Returns an array of ReleveLocation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ReleveLocation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
