<?php

namespace App\Repository;

use App\Entity\VehiculeKilometrage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VehiculeKilometrage|null find($id, $lockMode = null, $lockVersion = null)
 * @method VehiculeKilometrage|null findOneBy(array $criteria, array $orderBy = null)
 * @method VehiculeKilometrage[]    findAll()
 * @method VehiculeKilometrage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VehiculeKilometrageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehiculeKilometrage::class);
    }

    // /**
    //  * @return VehiculeKilometrage[] Returns an array of VehiculeKilometrage objects
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
    public function findOneBySomeField($value): ?VehiculeKilometrage
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
