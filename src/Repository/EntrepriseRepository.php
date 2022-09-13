<?php

namespace App\Repository;

use App\Entity\Entreprise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Entreprise|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entreprise|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entreprise[]    findAll()
 * @method Entreprise[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntrepriseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entreprise::class);
        $this->em = $this->getEntityManager()->getConnection();
    }

    // /**
    //  * @return Entreprise[] Returns an array of Entreprise objects
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
    public function findOneBySomeField($value): ?Entreprise
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findOneLikeName($nom){
         $sql = "
            SELECT id FROM entreprise as e WHERE LOWER(e.name) LIKE :nom LIMIT 1";
        
        $entreprise = $this->em->prepare($sql);
        $entreprise->execute(['nom' => strtolower('%'.$nom.'%')]);
        $entreprise = $entreprise->fetch();
        $qb = $this->createQueryBuilder('entreprise')
            ->Where('entreprise.id = :id')
            ->setParameter('id', $entreprise['id']);
        $entrp = $qb->getQuery()->getOneOrNullResult();
        
        return $entrp;
    }
}
