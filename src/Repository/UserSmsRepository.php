<?php

namespace App\Repository;

use App\Entity\UserSms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserSms|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSms|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSms[]    findAll()
 * @method UserSms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSmsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSms::class);
    }

    // /**
    //  * @return UserSms[] Returns an array of UserSms objects
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
    public function findOneBySomeField($value): ?UserSms
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
