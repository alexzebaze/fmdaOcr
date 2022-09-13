<?php

namespace App\Repository;

use App\Entity\Entdocu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Entdocu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entdocu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entdocu[]    findAll()
 * @method Entdocu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntdocuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Entdocu::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Entdocu[] Returns an array of Entdocu objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Entdocu
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findByTabDocu($tabDevis){

        $req = $this->createQueryBuilder('d')
            ->where('d.id IN (:in)')
            ->setParameter('in', $tabDevis)
            ->getQuery()
            ->getResult();

        return $req;
    }
}
