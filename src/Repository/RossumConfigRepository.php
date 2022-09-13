<?php

namespace App\Repository;

use App\Entity\RossumConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RossumConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method RossumConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method RossumConfig[]    findAll()
 * @method RossumConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RossumConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RossumConfig::class);
    }

    // /**
    //  * @return RossumConfig[] Returns an array of RossumConfig objects
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
    public function findOneBySomeField($value): ?RossumConfig
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
