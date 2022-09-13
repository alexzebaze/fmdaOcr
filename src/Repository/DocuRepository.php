<?php

namespace App\Repository;

use App\Entity\Docu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Docu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Docu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Docu[]    findAll()
 * @method Docu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocuRepository extends ServiceEntityRepository
{
    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Docu::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Docu[] Returns an array of Docu objects
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
    public function findOneBySomeField($value): ?Docu
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

     public function findByTabDocu($tabDocument){

        $req = $this->createQueryBuilder('d')
            ->where('d.id IN (:in)')
            ->setParameter('in', $tabDocument)
            ->getQuery()
            ->getResult();

        return $req;
    }     

    public function getByArticleId($articleId){

        $req = $this->createQueryBuilder('d')
            ->join('d.entdocu', 'e')
            ->where('d.article = :articleId')
            ->setParameter('articleId', $articleId)
            ->groupBy('e')
            ->getQuery()
            ->getResult();

        return $req;
    }
}
