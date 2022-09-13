<?php

namespace App\Repository;

use App\Entity\CtPlateau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CtPlateau|null find($id, $lockMode = null, $lockVersion = null)
 * @method CtPlateau|null findOneBy(array $criteria, array $orderBy = null)
 * @method CtPlateau[]    findAll()
 * @method CtPlateau[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CtPlateauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CtPlateau::class);
    }

    // /**
    //  * @return CtPlateau[] Returns an array of CtPlateau objects
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
    public function findOneBySomeField($value): ?CtPlateau
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
