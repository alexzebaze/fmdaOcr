<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    private $mysqli;
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Client::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->entreprise_id = $session->get('entreprise_session_id');
        //$this->mysqli = new \mysqli("localhost", "fmda", "BV4BGHvb48TKi$20", "fmdafrxdxkapps");
    }

    // /**
    //  * @return Client[] Returns an array of Client objects
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
    public function findOneBySomeField($value): ?Client
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
            SELECT id FROM client as f WHERE f.entreprise_id = :entreprise_id LOWER(f.nom) LIKE :nom ORDER BY f.nom ASC LIMIT 1";
        
        $fournisseur = $this->em->prepare($sql);
        $fournisseur->execute(['entreprise_id'=>$this->entreprise_id, 'nom' => strtolower('%'.$nom.'%')]);
        $fournisseur = $fournisseur->fetch();
        $qb = $this->createQueryBuilder('fournisseur')
            ->Where('fournisseur.id = :id')
            ->setParameter('id', $fournisseur['id']);
        $f = $qb->getQuery()->getOneOrNullResult();
        
        return $f;
    }

    public function findByNameAlpn($clientName){

        if(strtolower($clientName) == "m.")
            return [];

        $clientName = str_replace("m.", "", strtolower($clientName));
        
        $sql = "SELECT id, nom FROM client as f WHERE f.entreprise_id = :entreprise_id AND alphanum(nom) LIKE CONCAT('%', alphanum('$clientName'), '%')";
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        return $datas->fetchAll();
    }

    public function getByTabClient($field, $tabClient){

        $in = '('.$tabClient.')';
        $sql = "SELECT u.id, u.email, u.telone FROM client as u WHERE u.id IN $in AND u.entreprise_id = :entreprise ";

        if($field == "locataire_email"){
            $sql .= " AND u.email IS NOT NULL ";
        }
        else if($field == "locataire_sms"){
            $sql .= " AND u.telone IS NOT NULL ";
        }
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();
          
        return $datas;
    }

    public function getByTabClient2($tabClient){

       $req = $this->createQueryBuilder('c')
            ->where('c.id IN (:in)')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('in', $tabClient)
            ->setParameter('entreprise', $this->entreprise_id)
            ->getQuery()
            ->getResult();

        return $req;
    }


    public function hideClient($tabClient){
        
        $in = '(' . implode(',', $tabClient) .')';

        $sql = "UPDATE client  SET display = 0 WHERE client.id IN $in";

        $datas = $this->em->prepare($sql);
        $datas->execute();

        return 1;
    }
}
