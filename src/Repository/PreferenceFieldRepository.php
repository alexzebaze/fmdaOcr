<?php

namespace App\Repository;

use App\Entity\PreferenceField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PreferenceField|null find($id, $lockMode = null, $lockVersion = null)
 * @method PreferenceField|null findOneBy(array $criteria, array $orderBy = null)
 * @method PreferenceField[]    findAll()
 * @method PreferenceField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreferenceFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreferenceField::class);
    }

    // /**
    //  * @return PreferenceField[] Returns an array of PreferenceField objects
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
    public function findOneBySomeField($value): ?PreferenceField
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
