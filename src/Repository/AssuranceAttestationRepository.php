<?php

namespace App\Repository;

use App\Entity\AssuranceAttestation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AssuranceAttestation|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssuranceAttestation|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssuranceAttestation[]    findAll()
 * @method AssuranceAttestation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssuranceAttestationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssuranceAttestation::class);
    }

    // /**
    //  * @return AssuranceAttestation[] Returns an array of AssuranceAttestation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AssuranceAttestation
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
