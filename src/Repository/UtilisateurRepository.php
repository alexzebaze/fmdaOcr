<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Utilisateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Utilisateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Utilisateur[]    findAll()
 * @method Utilisateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Utilisateur::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Utilisateur[] Returns an array of Utilisateur objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Utilisateur
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function getOneLikeName($nom){
        $sql = "
            SELECT uid FROM utilisateur as u WHERE u.entreprise_id = :entreprise_id AND ( CONCAT(LOWER(u.lastname), ' ', LOWER(u.firstname)) LIKE :nom OR CONCAT(LOWER(u.firstname), ' ', LOWER(u.lastname)) LIKE :nom ) LIMIT 1";
        
        $utilisateur = $this->em->prepare($sql);
        $utilisateur->execute(['entreprise_id'=>$this->entreprise_id, 'nom' => strtolower('%'.$nom.'%')]);
        $utilisateur = $utilisateur->fetch();

        if($utilisateur){
            $qb = $this->createQueryBuilder('utilisateur')
                ->Where('utilisateur.uid = :id')
                ->setParameter('id', $utilisateur['uid']);
            $u = $qb->getQuery()->getOneOrNullResult();
        
            return $u;
        }
        return null;
    }

    /* ouvrier */
    public function getUserByEntreprise($isActive = null, $entrepriseId = null){
        
        if(is_null($entrepriseId)){
            $entrepriseId = $this->entreprise_id;
        }

        $sql = "
            SELECT uid, image, firstname, lastname, email, poste FROM utilisateur as u WHERE u.entreprise_id = :entreprise_id AND (u.sous_traitant = :st OR u.sous_traitant IS NULL) ";
        $tabExec = ['st'=>0, 'entreprise_id' => $entrepriseId];
        if(!is_null($isActive)){
            $sql .= " AND u.etat = :etat";
            $tabExec['etat'] = 1;
        }
        
        $utilisateurs = $this->em->prepare($sql);
        $utilisateurs->execute($tabExec);
        $utilisateurs = $utilisateurs->fetchAll();
        return $utilisateurs;
    }
    /* all user */
    public function getAllUserByEntreprise(){
        $sql = "
            SELECT uid, image FROM utilisateur as u WHERE u.entreprise_id = :entreprise_id";
        
        $utilisateurs = $this->em->prepare($sql);
        $utilisateurs->execute(['entreprise_id' => $this->entreprise_id]);
        $utilisateurs = $utilisateurs->fetchAll();
        return $utilisateurs;
    }

    public function getUserHoraire($entreprise_id, $mois, $annee)
    {
        $date_debut = $annee."-".$mois."-01";
        $today = (new \DateTime())->format('Y-m-d');
        $req = $this->createQueryBuilder('u')
        ->where('u.sous_traitant = :etat or u.sous_traitant is null')
        ->andWhere('u.entreprise = :entreprise')
        ->andWhere('u.date_entree >= :date_debut and u.date_sortie is null')
        ->setParameter('etat', false)
        ->setParameter('entreprise', $entreprise_id)
        ->setParameter('date_debut', $date_debut)
        ->getQuery()
        ->getResult();
          
        return $req;
    }

    public function getByTabUser($entreprise_id, $tabUser)
    {

        $in = '('.$tabUser.')';
        $sql = "
            SELECT u.uid as id FROM utilisateur as u WHERE u.uid IN $in AND u.etat = :etat AND u.entreprise_id = :entreprise";
        $datas = $this->em->prepare($sql);
        $datas->execute(['etat'=>1, 'entreprise'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();

        $datasArr = [];
        foreach ($datas as $value) {
            $qb = $this->createQueryBuilder('utilisateur')
            ->Where('utilisateur.uid = :id')
            ->setParameter('id', $value['id']);
            $datasArr[] = $qb->getQuery()->getOneOrNullResult();
        }
          
        return $datasArr;
    }
}
