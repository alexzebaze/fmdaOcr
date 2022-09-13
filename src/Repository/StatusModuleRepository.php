<?php

namespace App\Repository;

use App\Entity\StatusModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method StatusModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatusModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatusModule[]    findAll()
 * @method StatusModule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, StatusModule::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return StatusModule[] Returns an array of StatusModule objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StatusModule
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getStatusByModule($module){
        $sql = "SELECT CAST(s.id AS SIGNED INTEGER) as id, s.name as nom, s.color FROM status s INNER JOIN status_module sm ON sm.status_id = s.id INNER JOIN module m ON sm.module_id = m.id  WHERE s.entreprise_id = :entreprise_id AND m.cle = :module";
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id, 'module'=>$module]);
        return $datas->fetchAll();
    }
}
