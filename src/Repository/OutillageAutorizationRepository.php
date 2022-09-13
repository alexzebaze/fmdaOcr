<?php

namespace App\Repository;

use App\Entity\OutillageAutorization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method OutillageAutorization|null find($id, $lockMode = null, $lockVersion = null)
 * @method OutillageAutorization|null findOneBy(array $criteria, array $orderBy = null)
 * @method OutillageAutorization[]    findAll()
 * @method OutillageAutorization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OutillageAutorizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, OutillageAutorization::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return OutillageAutorization[] Returns an array of OutillageAutorization objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OutillageAutorization
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

        public function getAuthGroupByUserAndDateDebut(){
            $req = "SELECT o.id FROM outillage_autorization AS o JOIN outillage ON o.outillage_id = outillage.id WHERE outillage.entreprise_id = :entreprise_id ORDER BY o.id DESC";

            $tabExec = ['entreprise_id'=>$this->entreprise_id];
            $datas = $this->em->prepare($req);

            $datas->execute($tabExec);
            $datas = $datas->fetchAll();
            $datasArr = [];
            foreach ($datas as $value) {
                $qb = $this->createQueryBuilder('outil_auth')
                    ->Where('outil_auth.id = :id')
                    ->setParameter('id', $value['id']);
                $datasArr[] = $qb->getQuery()->getOneOrNullResult();                
            }
            return $datasArr;
        }
}
