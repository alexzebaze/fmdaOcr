<?php

namespace App\Repository;

use App\Entity\Pret;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Pret|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pret|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pret[]    findAll()
 * @method Pret[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PretRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Pret::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Pret[] Returns an array of Pret objects
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
    public function findOneBySomeField($value): ?Pret
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function sumCapitalRestant(){
        $sql = "
            SELECT SUM(pret.capital_restant) as sum FROM pret WHERE  pret.entreprise_id = :entreprise_id ";

        $tabExec = ['entreprise_id'=>$this->entreprise_id];
        $datas = $this->em->prepare($sql);
        $datas->execute($tabExec);
        $datas = $datas->fetch();

        return $datas;
    }

    /* Emprunt */
    public function sumCapital(){
        $sql = "
            SELECT SUM(pret.capital) as sum FROM pret WHERE  pret.entreprise_id = :entreprise_id ";

        $tabExec = ['entreprise_id'=>$this->entreprise_id];
        $datas = $this->em->prepare($sql);
        $datas->execute($tabExec);
        $datas = $datas->fetch();

        return $datas;
    }
}
