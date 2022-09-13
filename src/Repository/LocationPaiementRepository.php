<?php

namespace App\Repository;

use App\Entity\LocationPaiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LocationPaiement|null find($id, $lockMode = null, $lockVersion = null)
 * @method LocationPaiement|null findOneBy(array $criteria, array $orderBy = null)
 * @method LocationPaiement[]    findAll()
 * @method LocationPaiement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationPaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocationPaiement::class);
    }

    // /**
    //  * @return LocationPaiement[] Returns an array of LocationPaiement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LocationPaiement
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
