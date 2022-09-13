<?php

namespace App\Repository;

use App\Entity\DocuNormenclature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocuNormenclature|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocuNormenclature|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocuNormenclature[]    findAll()
 * @method DocuNormenclature[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocuNormenclatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocuNormenclature::class);
    }

    // /**
    //  * @return DocuNormenclature[] Returns an array of DocuNormenclature objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DocuNormenclature
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
