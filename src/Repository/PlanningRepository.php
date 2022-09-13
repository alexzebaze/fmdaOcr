<?php

namespace App\Repository;

use App\Entity\Planning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Planning|null find($id, $lockMode = null, $lockVersion = null)
 * @method Planning|null findOneBy(array $criteria, array $orderBy = null)
 * @method Planning[]    findAll()
 * @method Planning[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlanningRepository extends ServiceEntityRepository
{
    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Planning::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Planning[] Returns an array of Planning objects
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
    public function findOneBySomeField($value): ?Planning
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getPlanningByUser($dateStart, $dateEnd, $userId, $categorieId){

        $sql = "SELECT p.id, p.tache, p.debut, p.dateFin, pc.color as color, pc.id as categorieId FROM planning as p INNER JOIN planning_utilisateur as pu ON pu.planning_id = p.id INNER JOIN planning_category as pc ON pc.id = p.planning_categorie_id WHERE pu.utilisateur_id = :userId AND YEAR(p.debut) >= :dateStart AND YEAR(p.debut) <= :dateEnd AND pc.archive = :archive AND p.debut  IS NOT NULL AND p.dateFin IS NOT NULL";


        $tabExec = ['dateStart'=>$dateStart, 'dateEnd'=>$dateEnd, 'userId'=>$userId, 'archive'=>0];
        if(!is_null($categorieId)){
            $sql .= " AND p.planning_categorie_id = :categorieId ";
            $tabExec['categorieId'] = $categorieId;
        }
        $datas = $this->em->prepare($sql);
        $datas->execute($tabExec);

        $plannings = $datas->fetchAll();

        return $plannings;
    }

    public function getPlanningByUserAndPlanning($userId, $planningId, $dateStart = null){

        $sql = "SELECT p.id FROM planning as p INNER JOIN planning_utilisateur as pu ON pu.planning_id = p.id INNER JOIN planning_category as pc ON pc.id = p.planning_categorie_id WHERE pu.utilisateur_id = :userId AND p.planning_categorie_id = :planningId AND pc.archive = :archive AND p.debut >= :debut order by p.debut ASC";

        $datas = $this->em->prepare($sql);
        $datas->execute(['planningId'=>$planningId, 'userId'=>$userId, 'debut'=>$dateStart, 'archive'=>0]);
        $plannings = $datas->fetchAll();

        $planningsArr = [];
        foreach ($plannings as $value) {
             $qb = $this->createQueryBuilder('planning')
                ->Where('planning.id = :id')
                ->setParameter('id', $value['id']);
            $p = $qb->getQuery()->getOneOrNullResult();
            $planningsArr[] = $p;
        }
       
        return $planningsArr;
    }

    public function getAllPlanningByUser($userId, $dateStart = null){
        $sql = "SELECT p.id FROM planning as p INNER JOIN planning_utilisateur as pu ON pu.planning_id = p.id WHERE pu.utilisateur_id = :userId AND p.debut >= :debut order by p.debut ASC";

        $datas = $this->em->prepare($sql);
        $datas->execute(['userId'=>$userId, 'debut'=>$dateStart]);
        $plannings = $datas->fetchAll();
        $planningsArr = [];
        foreach ($plannings as $value) {
             $qb = $this->createQueryBuilder('planning')
                ->Where('planning.id = :id')
                ->setParameter('id', $value['id']);
            $p = $qb->getQuery()->getOneOrNullResult();
            $planningsArr[] = $p;
        }
       
        return $planningsArr;
    }

    public function getAllPlanningByTabUserByCategorie($categorieId, $tabUser, $dateStart= null, $datefin = null){

        $in = '(' . implode(',', $tabUser) .')';
        $sql = "SELECT DISTINCT p.id FROM planning as p INNER JOIN planning_utilisateur as pu ON pu.planning_id = p.id INNER JOIN planning_category as pc ON pc.id = p.planning_categorie_id  WHERE pu.utilisateur_id IN $in AND p.planning_categorie_id = :categorieId AND pc.archive = :archive ";

        $tabExec = ['categorieId'=>$categorieId, 'archive'=>0];

        if(!is_null($datefin)){
            $sql .= " AND p.datefin >= :datefin ";
            $tabExec['datefin'] = $datefin;
        }
        else{
            if(!is_null($dateStart)){
                $sql .= " AND p.debut >= :dateStart ";
                $tabExec['dateStart'] = $dateStart;
            }
        }

        $sql .= " order by p.debut ASC";

        $datas = $this->em->prepare($sql);
        $datas->execute($tabExec);
        $plannings = $datas->fetchAll();
        $planningsArr = [];
        foreach ($plannings as $value) {
             $qb = $this->createQueryBuilder('planning')
                ->Where('planning.id = :id')
                ->setParameter('id', $value['id']);
            $p = $qb->getQuery()->getOneOrNullResult();
            $planningsArr[] = $p;
        }
       
        return $planningsArr;
    }

    public function getAllPlanningByCategorieFromDate($categorieId, $dateStart, $dateFin){
        $req = $this->createQueryBuilder('p')
            ->join('p.planning_categorie', 'c')
            ->addSelect('c')
            ->andWhere('c.id = :categorieId')
            ->andWhere('c.archive = :archive')
            ->setParameter('categorieId', $categorieId)
            ->setParameter('archive', 0);
            
            if(!is_null($dateFin)){
                $req = $req->andWhere('p.datefin >= :dateFin')
                        ->setParameter('dateFin', $dateFin);
            }
            else{
                $req = $req->andWhere('p.debut >= :val')
                ->setParameter('val', $dateStart);
            }

            $req = $req->orderBy('p.debut', 'ASC')
            ->getQuery()
            ->getResult();

        return $req;
    }
}
