<?php

namespace App\Repository;

use App\Entity\ConfigImapEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConfigImapEmail|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigImapEmail|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigImapEmail[]    findAll()
 * @method ConfigImapEmail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigImapEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigImapEmail::class);
    }

    // /**
    //  * @return ConfigImapEmail[] Returns an array of ConfigImapEmail objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ConfigImapEmail
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
