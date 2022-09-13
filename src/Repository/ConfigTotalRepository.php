<?php

namespace App\Repository;

use App\Entity\ConfigTotal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConfigTotal|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigTotal|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigTotal[]    findAll()
 * @method ConfigTotal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigTotalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigTotal::class);
    }

    // /**
    //  * @return ConfigTotal[] Returns an array of ConfigTotal objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ConfigTotal
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
