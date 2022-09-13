<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Menu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Menu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Menu[]    findAll()
 * @method Menu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MenuRepository extends ServiceEntityRepository
{
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Menu::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Menu[] Returns an array of Menu objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Menu
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getLastRang($hasParent = null){

        $tabExec = [];
        if(!is_null($hasParent)){
            $sql = "SELECT rang FROM menu as m WHERE m.parent_id = :parent ORDER BY m.rang DESC LIMIT 1";
            $tabExec['parent'] = $hasParent->getId();
        }
        else{
            $sql = "SELECT rang FROM menu as m WHERE m.parent_id IS NULL ORDER BY m.rang DESC LIMIT 1";
        }

        $rang = $this->em->prepare($sql);
        $rang->execute($tabExec);
        $rang = $rang->fetch();
        return (!$rang ) ? 0 : $rang['rang'];
    }

    public function getMenuFirstNiveau(){
        $req = $this->createQueryBuilder('m')
            ->andWhere('m.parent IS NULL')
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $req;
    }
}
