<?php

namespace App\Repository;

use App\Entity\Fabricant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Fabricant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fabricant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fabricant[]    findAll()
 * @method Fabricant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FabricantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fabricant::class);
    }

    // /**
    //  * @return Fabricant[] Returns an array of Fabricant objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Fabricant
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
