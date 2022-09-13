<?php

namespace App\Repository;

use App\Entity\Fields;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * @method Fields|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fields|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fields[]    findAll()
 * @method Fields[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FieldsRepository extends ServiceEntityRepository
{   
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Fields::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Fields[] Returns an array of Fields objects
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
    public function findOneBySomeField($value): ?Fields
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findByTabId($tabId)
    {
        $req = $this->createQueryBuilder('f')
            ->where('f.id IN (:in)')
            ->setParameter('in', $tabId)
            ->getQuery()
            ->getResult();

        return $req;
    }
}
