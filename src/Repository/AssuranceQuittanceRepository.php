<?php

namespace App\Repository;

use App\Entity\AssuranceQuittance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AssuranceQuittance|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssuranceQuittance|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssuranceQuittance[]    findAll()
 * @method AssuranceQuittance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssuranceQuittanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssuranceQuittance::class);
    }

    // /**
    //  * @return AssuranceQuittance[] Returns an array of AssuranceQuittance objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AssuranceQuittance
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
