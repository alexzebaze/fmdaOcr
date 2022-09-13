<?php

namespace App\Repository;

use App\Entity\FournisseurCompteRendu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method FournisseurCompteRendu|null find($id, $lockMode = null, $lockVersion = null)
 * @method FournisseurCompteRendu|null findOneBy(array $criteria, array $orderBy = null)
 * @method FournisseurCompteRendu[]    findAll()
 * @method FournisseurCompteRendu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FournisseurCompteRenduRepository extends ServiceEntityRepository
{
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, FournisseurCompteRendu::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return FournisseurCompteRendu[] Returns an array of FournisseurCompteRendu objects
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
    public function findOneBySomeField($value): ?FournisseurCompteRendu
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */


    public function getByTabFournisseurAndChantier($chantierId, $tabFourId){

        $req = $this->createQueryBuilder('f')
            ->join('f.compteRendu', 'cr')
            ->where('f.fournisseur IN (:in)')
            ->andWhere('cr.chantier = :chantierId')
            ->setParameter('in', $tabFourId)
            ->setParameter('chantierId', $chantierId)
            ->getQuery()
            ->getResult();

        return $req;
    }
}
