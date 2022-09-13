<?php

namespace App\Repository;

use App\Entity\CompteRendu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method CompteRendu|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompteRendu|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompteRendu[]    findAll()
 * @method CompteRendu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompteRenduRepository extends ServiceEntityRepository
{
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, CompteRendu::class);
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return CompteRendu[] Returns an array of CompteRendu objects
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
    public function findOneBySomeField($value): ?CompteRendu
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function countRemarqueCompteRendu($compteRenduId){
        $sql = "SELECT count(r.id) as remarque_cloture FROM remarque r INNER JOIN status ON status.id = r.status_id WHERE status.name = :status AND r.compte_rendu_id = :compteRenduId";
        
        $data = $this->getEntityManager()->getConnection()->prepare($sql);
        $data->execute(['status'=>'cloture', 'compteRenduId'=>$compteRenduId]);
        $data = $data->fetch();
        return $data['remarque_cloture'];
    }   

    public function getCompteRenduByChantier($chantier)
    {
        return $this->createQueryBuilder('compte_rendu')
            ->join('compte_rendu.chantier', 'ch')
            ->where('ch.chantierId = :chantier')
            ->andWhere('ch.entreprise = :entreprise_id')
            ->setParameter('chantier', $chantier)
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->orderBy('compte_rendu.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

}
