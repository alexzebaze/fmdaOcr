<?php

namespace App\Repository;

use App\Entity\IAZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method IAZone|null find($id, $lockMode = null, $lockVersion = null)
 * @method IAZone|null findOneBy(array $criteria, array $orderBy = null)
 * @method IAZone[]    findAll()
 * @method IAZone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IAZoneRepository extends ServiceEntityRepository
{
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, IAZone::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return IAZone[] Returns an array of IAZone objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?IAZone
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function deleteIAZoneByDocument($documentId){
        $sql = "DELETE FROM iazone WHERE document_id = $documentId";

        $datas = $this->em->prepare($sql);
        $datas->execute();

        return 1;
    }
}
