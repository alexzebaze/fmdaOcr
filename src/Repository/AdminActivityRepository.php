<?php

namespace App\Repository;

use App\Entity\AdminActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AdminActivity|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdminActivity|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdminActivity[]    findAll()
 * @method AdminActivity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminActivity::class);
    }

    // /**
    //  * @return AdminActivity[] Returns an array of AdminActivity objects
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
    public function findOneBySomeField($value): ?AdminActivity
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
