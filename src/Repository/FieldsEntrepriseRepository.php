<?php

namespace App\Repository;

use App\Entity\FieldsEntreprise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FieldsEntreprise|null find($id, $lockMode = null, $lockVersion = null)
 * @method FieldsEntreprise|null findOneBy(array $criteria, array $orderBy = null)
 * @method FieldsEntreprise[]    findAll()
 * @method FieldsEntreprise[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FieldsEntrepriseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FieldsEntreprise::class);
    }

    // /**
    //  * @return FieldsEntreprise[] Returns an array of FieldsEntreprise objects
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
    public function findOneBySomeField($value): ?FieldsEntreprise
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
