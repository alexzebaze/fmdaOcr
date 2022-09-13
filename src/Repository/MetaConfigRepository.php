<?php

namespace App\Repository;

use App\Entity\MetaConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MetaConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method MetaConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method MetaConfig[]    findAll()
 * @method MetaConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MetaConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetaConfig::class);
    }

    // /**
    //  * @return MetaConfig[] Returns an array of MetaConfig objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MetaConfig
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
