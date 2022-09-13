<?php

namespace App\Repository;

use App\Entity\Previsionel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
/**
 * @method Previsionel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Previsionel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Previsionel[]    findAll()
 * @method Previsionel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrevisionelRepository extends ServiceEntityRepository
{
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Previsionel::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Previsionel[] Returns an array of Previsionel objects
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
    public function findOneBySomeField($value): ?Previsionel
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getPrevisionGroupByChantier(){

        $sql = "SELECT chantier_id, SUM(budget) as budget FROM previsionel GROUP BY chantier_id";

        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute();
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function getDureeCategorie($categorieId, $chantierId=null){

        $sql = "SELECT MIN(p.date_debut) as debut, MAX(p.date_fin) as fin FROM previsionel as p INNER JOIN lot ON p.lot_id = lot.id WHERE lot.previsionel_categorie_id = :categorieId ";

        $tabExec = ['categorieId'=>$categorieId];
        if(!is_null($chantierId)){
            $sql .= " AND p.chantier_id = :chantierId";
            $tabExec['chantierId'] = $chantierId;
        }

        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute($tabExec);
        $datas = $datas->fetch();
        $duree = $datas ? ["debut"=>$datas['debut'], "fin"=>$datas['fin']] : null;
        return $duree;
    }

    public function getLotByMonth($categorieId, $mois, $annee, $chantierId){

        $dateDebut = $annee.'-'.$mois.'-'.'01';
        $dateFin = $annee.'-'.$mois.'-'.cal_days_in_month(CAL_GREGORIAN, $mois, $annee);
        $sql = "SELECT lot.*, p.* FROM lot INNER JOIN previsionel as p ON p.lot_id = lot.id WHERE lot.previsionel_categorie_id = :categorieId AND p.chantier_id = :chantierId AND ( 
                (YEAR(p.date_debut) = :annee AND MONTH(p.date_debut) = :mois) OR
                (YEAR(p.date_fin) = :annee AND MONTH(p.date_fin) = :mois) OR
                ( p.date_debut <= :dateDebut AND p.date_fin  >= :dateFin )
            ) ORDER BY lot.lot";

        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['categorieId'=>$categorieId, 'chantierId'=>(int)$chantierId, 'annee'=>$annee, 'mois'=>$mois, 'dateDebut'=>$dateDebut, 'dateFin'=>$dateFin]);
        $datas = $datas->fetchAll();
        return $datas;
    }
}
