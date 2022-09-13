<?php

namespace App\Repository;

use App\Entity\PlaningStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PlaningStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlaningStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlaningStatus[]    findAll()
 * @method PlaningStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlaningStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlaningStatus::class);
    }

    // /**
    //  * @return PlaningStatus[] Returns an array of PlaningStatus objects
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
    public function findOneBySomeField($value): ?PlaningStatus
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
