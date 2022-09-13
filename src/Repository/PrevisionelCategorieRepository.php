<?php

namespace App\Repository;

use App\Entity\PrevisionelCategorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PrevisionelCategorie|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrevisionelCategorie|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrevisionelCategorie[]    findAll()
 * @method PrevisionelCategorie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrevisionelCategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrevisionelCategorie::class);
    }

    // /**
    //  * @return PrevisionelCategorie[] Returns an array of PrevisionelCategorie objects
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
    public function findOneBySomeField($value): ?PrevisionelCategorie
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
