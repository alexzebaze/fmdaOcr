<?php

namespace App\Repository;

use App\Entity\UserHoraireAbsent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserHoraireAbsent|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserHoraireAbsent|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserHoraireAbsent[]    findAll()
 * @method UserHoraireAbsent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserHoraireAbsentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserHoraireAbsent::class);
    }

    // /**
    //  * @return UserHoraireAbsent[] Returns an array of UserHoraireAbsent objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserHoraireAbsent
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
