<?php

namespace App\Repository;

use App\Entity\Normenclature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Normenclature|null find($id, $lockMode = null, $lockVersion = null)
 * @method Normenclature|null findOneBy(array $criteria, array $orderBy = null)
 * @method Normenclature[]    findAll()
 * @method Normenclature[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NormenclatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Normenclature::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Normenclature[] Returns an array of Normenclature objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Normenclature
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getByArticleRefAndArticleNorm($articleId){

        $req = $this->createQueryBuilder('n')
            ->join('n.articleNormenclature', 'an')
            ->join('n.article_reference', 'aref')
            ->andWhere('an.id = :articleId OR aref.id = :articleId')
            ->setParameter('articleId', $articleId)
            ->getQuery()
            ->getResult();

        return $req;
    }   
}
