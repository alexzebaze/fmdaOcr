<?php

namespace App\Repository;

use App\Entity\PartageNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PartageNote|null find($id, $lockMode = null, $lockVersion = null)
 * @method PartageNote|null findOneBy(array $criteria, array $orderBy = null)
 * @method PartageNote[]    findAll()
 * @method PartageNote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartageNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartageNote::class);
    }

    // /**
    //  * @return PartageNote[] Returns an array of PartageNote objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PartageNote
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
