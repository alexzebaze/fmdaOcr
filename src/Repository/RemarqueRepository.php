<?php

namespace App\Repository;

use App\Entity\Remarque;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Remarque|null find($id, $lockMode = null, $lockVersion = null)
 * @method Remarque|null findOneBy(array $criteria, array $orderBy = null)
 * @method Remarque[]    findAll()
 * @method Remarque[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RemarqueRepository extends ServiceEntityRepository
{
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Remarque::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Remarque[] Returns an array of Remarque objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Remarque
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getRemarqueByCompteRendu($compte_rendu_id)
    {
        return $this->createQueryBuilder('remarque')
            ->join('remarque.compte_rendu', 'cr')
            ->join('cr.chantier', 'ch')
            ->where('cr.id = :compte_rendu_id')
            ->andWhere('ch.entreprise = :entreprise_id')
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->setParameter('compte_rendu_id', $compte_rendu_id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getRemarqueById($remarqueId)
    {
        return $this->createQueryBuilder('remarque')
            ->join('remarque.compte_rendu', 'cr')
            ->join('cr.chantier', 'ch')
            ->where('remarque.id = :remarqueId')
            ->andWhere('ch.entreprise = :entreprise_id')
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->setParameter('remarqueId', $remarqueId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getFournisseurRemarqueByChantier($chantier_id){

        $sql = "SELECT r.remarque_id FROM remarque_fournisseurs r INNER JOIN remarque rm ON rm.id = r.remarque_id INNER JOIN compte_rendu c ON c.id = rm.compte_rendu_id  WHERE c.chantier_id = :chantier_id ";
        $datas = $this->em->prepare($sql);
        $datas->execute(['chantier_id'=>$chantier_id]);
        $datas = $datas->fetchAll();

        $tabRemarque = [];
        foreach ($datas as $value) {
            $tabRemarque[] = $value['remarque_id'];
        }
        return $tabRemarque;
    }

    public function dettachFournisseur($chantier_id, $tabFournisseur){

        $tabRemarque = $this->getFournisseurRemarqueByChantier($chantier_id);
        $in = '(' . implode(',', $tabFournisseur) .')';

        $sql = "DELETE FROM remarque_fournisseurs WHERE fournisseur_id IN $in ";
        if(count($tabRemarque)){
            $inRemarq = '(' . implode(',', $tabRemarque) .')';
            $sql .= " AND remarque_id IN $inRemarq";
        }

        $datas = $this->em->prepare($sql);
        $datas->execute();

        return $datas;
    }
}
