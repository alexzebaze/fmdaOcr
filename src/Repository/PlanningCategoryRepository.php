<?php

namespace App\Repository;

use App\Entity\PlanningCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method PlanningCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlanningCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlanningCategory[]    findAll()
 * @method PlanningCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlanningCategoryRepository extends ServiceEntityRepository
{
    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, PlanningCategory::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return PlanningCategory[] Returns an array of PlanningCategory objects
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
    public function findOneBySomeField($value): ?PlanningCategory
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function updateCollapse($collapse){

        if($collapse == "close"){
            $sql = 'UPDATE planning_category SET collapse = 1 WHERE planning_category.entreprise_id = :entreprise_id ';
        }
        else {  
            $sql = 'UPDATE planning_category SET collapse = 0 WHERE planning_category.entreprise_id = :entreprise_id ';
        }
        
        $categorie = $this->em->prepare($sql);
        $categorie->execute(['entreprise_id'=>$this->entreprise_id]);
        return 1;
    }
}
