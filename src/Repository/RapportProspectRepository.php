<?php

namespace App\Repository;

use App\Entity\RapportProspect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RapportProspect|null find($id, $lockMode = null, $lockVersion = null)
 * @method RapportProspect|null findOneBy(array $criteria, array $orderBy = null)
 * @method RapportProspect[]    findAll()
 * @method RapportProspect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RapportProspectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RapportProspect::class);
    }

    // /**
    //  * @return RapportProspect[] Returns an array of RapportProspect objects
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
    public function findOneBySomeField($value): ?RapportProspect
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
