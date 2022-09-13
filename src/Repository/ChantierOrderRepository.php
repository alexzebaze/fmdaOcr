<?php

namespace App\Repository;

use App\Entity\ChantierOrder;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ChantierOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChantierOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChantierOrder[]    findAll()
 * @method ChantierOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChantierOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChantierOrder::class);
    }

    // /**
    //  * @return ChantierOrder[] Returns an array of ChantierOrder objects
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
    public function findOneBySomeField($value): ?ChantierOrder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getOrderIdsByUtilisateurEntity(Utilisateur $utilisateur)
    {
        $list =  $this->createQueryBuilder('c')
            ->join('c.utilisateur','u')
            ->addSelect('u')
            ->join('c.chantier','ch')
            ->addSelect('ch')
            ->andWhere('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->andWhere('ch.entreprise = :entreprise')
            ->setParameter('entreprise', $utilisateur->getEntreprise())
            ->orderBy('c.order_num', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $list;
    }

    public function getOrderIdsByUtilisateur(Utilisateur $utilisateur)
    {
        $list =  $this->createQueryBuilder('c')
            ->join('c.chantier','chantier')
            ->select('chantier.chantierId')
            ->andWhere('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('c.order_num', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        $listArray = [];
        foreach ($list as $value) {
            $listArray[] = $value['chantierId'];
        }

        return $listArray;
    }
}
