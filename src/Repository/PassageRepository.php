<?php

namespace App\Repository;

use App\Entity\Passage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Passage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Passage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Passage[]    findAll()
 * @method Passage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PassageRepository extends ServiceEntityRepository
{
    private $security;
    private $session;
    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, Passage::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->security = $security;
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Passage[] Returns an array of Passage objects
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
    public function findOneBySomeField($value): ?Passage
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

public function findByParam($day = null, $mois = null, $annee = null, $utilisateurId = null, $fournisseurId = null, $is_bl = null){

        $req = $this->createQueryBuilder('p');
        if(!is_null($utilisateurId)){
            $req = $req->join('p.utilisateur', 'u')
            ->addSelect('u')
            ->andWhere('u.uid = :utilisateurId')
            ->setParameter('utilisateurId', $utilisateurId);
        }
        if(!is_null($fournisseurId)){
            $req = $req->join('p.fournisseur', 'f')
            ->addSelect('f')
            ->andWhere('f.id = :fournisseurId')
            ->setParameter('fournisseurId', $fournisseurId);
        }

        if(!is_null($day)){
            $req = $req->andWhere('DAY(p.date_detection) = :day')
            ->setParameter('day', $day);
        }
        if(!is_null($mois)){
            $req = $req->andWhere('MONTH(p.date_detection) = :month')
            ->setParameter('month', $mois);
        }
        if(!is_null($annee)){
            $req = $req->andWhere('YEAR(p.date_detection) = :year')
            ->setParameter('year', $annee);
        }
        if(!is_null($is_bl)){
            if($is_bl == 1)
                $req = $req->andWhere('p.bon_livraison IS NOT NULL');
            else if($is_bl == 2)
                $req = $req->andWhere('p.bon_livraison IS NULL ');
        }

        $req = $req->andWhere('p.entreprise = :entreprise_id')
        ->setParameter('entreprise_id', $this->entreprise_id)
        ->orderBy('p.date_detection', 'DESC')
        ->getQuery()
        ->getResult();

        return $req;
    }

    public function countGroupByOuvrier($day = null, $mois = null, $annee = null, $fournisseurId = null, $is_bl = null){
        $req = $this->createQueryBuilder('p')
        ->join('p.utilisateur', 'u')
        ->addSelect('count(p.id)');

        if(!is_null($fournisseurId)){
            $req = $req->join('p.fournisseur', 'f')
            ->addSelect('f')
            ->andWhere('f.id = :fournisseurId')
            ->setParameter('fournisseurId', $fournisseurId);
        }

        if(!is_null($day)){
            $req = $req->andWhere('DAY(p.date_detection) = :day')
            ->setParameter('day', $day);
        }
        if(!is_null($mois)){
            $req = $req->andWhere('MONTH(p.date_detection) = :month')
            ->setParameter('month', $mois);
        }
        if(!is_null($annee)){
            $req = $req->andWhere('YEAR(p.date_detection) = :year')
            ->setParameter('year', $annee);
        }
        if(!is_null($is_bl)){
            if($is_bl == 1)
                $req = $req->andWhere('p.bon_livraison IS NOT NULL');
            else if($is_bl == 2)
                $req = $req->andWhere('p.bon_livraison IS NULL ');
        }

        $req = $req->andWhere('p.entreprise = :entreprise_id')
        ->setParameter('entreprise_id', $this->entreprise_id)
        ->orderBy('p.date_detection', 'DESC')
        ->groupBy('u')
        ->getQuery()
        ->getResult();

        return $req;
    }


    public function isPassage($chantierId, $fournisseurId, $dateDetection, $entreprise = null){
        $sql = "SELECT p.id FROM passage p WHERE (p.chantier_id = :chantierId AND p.fournisseur_id = :fournisseurId AND date_format(p.date_detection, '%Y-%m-%d') = :date_detection AND p.entreprise_id = :entrepriseId) AND p.bon_livraison_id IS NULL LIMIT 1";  
        
        if(is_null($entreprise))
            $entreprise = $this->entreprise_id;

        $datas = $this->em->prepare($sql); 
        $datas->execute(['chantierId'=>$chantierId, 'fournisseurId'=>$fournisseurId, 'date_detection' => $dateDetection, 'entrepriseId'=>$entreprise]);

        $datas = $datas->fetch();

        return $datas;
    }

    public function getPassagesToAttach($chantierId, $fournisseurId, $dateDetection){
        $sql = "SELECT p.id FROM passage p WHERE (p.chantier_id = :chantierId AND p.fournisseur_id = :fournisseurId AND p.entreprise_id = :entrepriseId) AND p.bon_livraison_id IS NULL ";  
        
        $datas = $this->em->prepare($sql); 
        $datas->execute(['chantierId'=>$chantierId, 'fournisseurId'=>$fournisseurId, 'entrepriseId'=>$this->entreprise_id]);

        $datas = $datas->fetchAll();

        $datasArr = [];
        foreach ($datas as $value) {
            $qb = $this->createQueryBuilder('passage')
                ->Where('passage.id = :id')
                ->setParameter('id', $value['id']);
            $datasArr[] = $qb->getQuery()->getOneOrNullResult();                
        }
        return $datasArr;
    }

    public function deletePassage($tabPassage){
        
        $in = '(' . implode(',', $tabPassage) .')';
        $sql = "DELETE FROM passage WHERE passage.id IN $in";

        $datas = $this->em->prepare($sql);
        $datas->execute();

        return 1;
    }
}   






