<?php

namespace App\Repository;

use App\Entity\Fournisseurs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Fournisseurs|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fournisseurs|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fournisseurs[]    findAll()
 * @method Fournisseurs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FournisseursRepository extends ServiceEntityRepository
{
    private $mysqli;
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Fournisseurs::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->entreprise_id = $session->get('entreprise_session_id');
        //$this->mysqli = new \mysqli("localhost", "fmda", "BV4BGHvb48TKi$20", "fmdafrxdxkapps");
    }

    // /**
    //  * @return Fournisseurs[] Returns an array of Fournisseurs objects
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
    public function findOneBySomeField($value): ?Fournisseurs
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findOneLikeName($nom){
        $sql = "
            SELECT id FROM fournisseurs as f WHERE f.entreprise_id = :entreprise_id AND LOWER(f.nom) LIKE :nom LIMIT 1";
        
        $fournisseur = $this->em->prepare($sql);
        $fournisseur->execute(['entreprise_id'=>$this->entreprise_id, 'nom' => strtolower('%'.$nom.'%')]);
        $fournisseur = $fournisseur->fetch();
        $qb = $this->createQueryBuilder('fournisseur')
            ->Where('fournisseur.id = :id')
            ->setParameter('id', $fournisseur['id']);
        $f = $qb->getQuery()->getOneOrNullResult();
        
        return $f;
    }    
    public function getWithEmailExist($email, $dossier){
        $sql = "SELECT id, nom FROM fournisseurs as f WHERE f.entreprise_id = :entreprise_id ";
            if($dossier == "facturation"){ 
                $sql .= " AND LOWER(f.email_facture_electronique) = :email ";
            }
            else{
                $sql .= " AND LOWER(f.email_bl) = :email ";
            }
            $sql .= " LIMIT 1 ";

        $fournisseur = $this->em->prepare($sql);
        $fournisseur->execute(['entreprise_id'=>1, 'email' => strtolower($email)]);
        return $fournisseur->fetch();
    }

    public function findByNameAlpn($fournisseurName){

        if(strtolower($fournisseurName) == "m.")
            return [];

        $fournisseurName = str_replace("m.", "", strtolower($fournisseurName));

        $sql = "SELECT id, nom, rotation FROM fournisseurs as f WHERE f.entreprise_id = :entreprise_id AND alphanum(nom) LIKE CONCAT('%', alphanum('$fournisseurName'), '%') ORDER BY f.nom ASC";
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        return $datas->fetchAll();
    }


    public function getByEntrepriseOdreByBl($entreprise_id){

        $sql = "SELECT f.id, f.nom, f.nom2, f.rotation FROM fournisseurs as f WHERE f.entreprise_id = :entreprise_id AND f.nom IS NOT NULL AND f.nom2 IS NOT NULL";

        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$entreprise_id]);
        return $datas->fetchAll();
    }       

    public function getByEntreprise($entreprise_id){

        $sql = "SELECT f.id, f.nom, f.nom2, f.rotation FROM fournisseurs as f WHERE f.entreprise_id = :entreprise_id";

        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$entreprise_id]);
        return $datas->fetchAll();
    }

    /* use to form, dont user hier getQuery at end */
    public function findByDateAchat($month, $year){

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('f')
            ->from('App\Entity\Fournisseurs', 'f')
            ->innerJoin('App\Entity\Achat', 'a', 'WITH', 'f.id = a.fournisseur')
            ->where('a.entreprise = :entreprise_id')
            ->andWhere('a.type = :type')
            ->andWhere('a.bl_validation IS NULL');
        if(!is_null($month)){
            $query = $query->andWhere('MONTH(a.facturedAt) = :month')
            ->setParameter('month', $month);
        }
        if(!is_null($year)){
            $query = $query->andWhere('YEAR(a.facturedAt) = :year')
            ->setParameter('year', $year);
        }
        $query = $query->setParameter('entreprise_id', $this->entreprise_id)
                        ->setParameter('type', 'bon_livraison')
                        ->orderBy('f.nom', 'ASC');

        return $query;
    }
    
    public function findFournisseurWithFavorite($userId, $entreprise){

        $sql = " SELECT f.id, f.nom, f.email, (SELECT count(fav.fournisseur_id) FROM fournisseurs_utilisateur fav WHERE fav.fournisseur_id = f.id AND fav.utilisateur_id = $userId LIMIT 1) as favoris FROM fournisseurs f WHERE f.entreprise_id = :entreprise";
       
        $datas = $this->em->prepare($sql);
        $tabExec = ['entreprise'=>$entreprise];
        
        $datas->execute($tabExec);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function getByTabFournisseur($tabId)
    {
        $in = '(' . implode(',', $tabId) .')';
        $sql = "
            SELECT u.id, u.nom FROM fournisseurs as u WHERE u.id IN $in AND u.entreprise_id = :entreprise";
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();

        return $datas;
    }    

    public function getByTabFournisseurEntity($tabId)
    {
        $req = $this->createQueryBuilder('f')
            ->where('f.id IN (:in)')
            ->andWhere('f.entreprise = :entreprise')
            ->setParameter('in', $tabId)
            ->setParameter('entreprise', $this->entreprise_id)
            ->getQuery()
            ->getResult();

        return $req;
    }


}
