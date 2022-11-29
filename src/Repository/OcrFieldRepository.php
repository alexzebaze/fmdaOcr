<?php

namespace App\Repository;

use App\Entity\OcrField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method OcrField|null find($id, $lockMode = null, $lockVersion = null)
 * @method OcrField|null findOneBy(array $criteria, array $orderBy = null)
 * @method OcrField[]    findAll()
 * @method OcrField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OcrFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, OcrField::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return OcrField[] Returns an array of OcrField objects
    //  */
    /*
    public function findByExampleOcrField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleOcrField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeOcrField($value): ?OcrField
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleOcrField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */


    public function searchPosition($left, $top, $dossier, $marge, $entreprise_id = null){
        if(is_null($entreprise_id)){
            $entreprise_id = $this->entreprise_id;
        }

        $sql = "SELECT id, document_id FROM  ocr_field WHERE dossier = :dossier AND entreprise_id = :entreprise AND (position_left <= :left_min AND position_left >= :left_max ) AND (position_top <= :top_min AND position_top >= :top_max) ORDER BY id DESC LIMIT 1";

        $datas = $this->em->prepare($sql);

        $datas->execute(['left_min'=>($left+$marge[0]), 'left_max'=>($left-$marge[0]), 'top_min'=>($top+$marge[1]), 'top_max'=>($top-$marge[1]), 'entreprise'=>$entreprise_id, 'dossier'=>$dossier]);
        $datas = $datas->fetch();
        return $datas;
    }

    public function deleteOcrFieldByDocument($documentId){
        $sql = "DELETE FROM ocr_field WHERE document_id = $documentId";

        $datas = $this->em->prepare($sql);
        $datas->execute();

        return 1;
    }

    public function getByNameAlpn($dossier, $entreprise_id, $filename, $searchName, $offset=1, $searchName2="", $limit = 30){

        if(strtolower($searchName) == "m.")
            return [];

        $searchName = str_replace("m. ", "", strtolower($searchName));
        $searchName = str_replace("m.", "", strtolower($searchName));
        $searchName = str_replace("'", "''", strtolower($searchName));
        $searchName2 = str_replace("'", "''", strtolower($searchName2));

        if($searchName2 != "" && !is_null($searchName2)){
            $sql = "SELECT name, entreprise_id, filename, dossier FROM (SELECT * FROM tmp_ocr WHERE id >= :offset LIMIT $limit) as A WHERE ( A.name LIKE CONCAT('%', '$searchName', '%') OR REPLACE(A.name, '.', ' ') LIKE CONCAT('%', '$searchName2', '%') ) AND A.filename = :filename and A.dossier = :dossier AND A.entreprise_id = :entreprise_id";
        }
        else{
            $sql = "SELECT name, entreprise_id, filename, dossier FROM (SELECT * FROM tmp_ocr WHERE id >= :offset LIMIT $limit) as A WHERE REPLACE(A.name, '.', ' ') LIKE CONCAT('%', '$searchName', '%') AND A.filename = :filename and A.dossier = :dossier AND A.entreprise_id = :entreprise_id";
        }

        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$filename, 'entreprise_id'=>$entreprise_id, 'offset'=>$offset]);
        return $datas->fetchAll();
    } 

    public function getByNameAlpnClient($dossier, $entreprise_id, $filename, $searchName, $offset=1, $searchName2="", $limit = 30){

        if(strtolower($searchName) == "m.")
            return [];

        $searchName = str_replace("m.", "", strtolower($searchName));
        $searchName = str_replace("m. ", "", strtolower($searchName));
        $searchName = str_replace("'", "''", strtolower($searchName));
        $searchName2 = str_replace("'", "''", strtolower($searchName2));

        $sql = "SELECT name, entreprise_id, filename, dossier FROM (SELECT * FROM tmp_ocr WHERE id >= :offset LIMIT $limit) as A WHERE A.name LIKE '$searchName%' AND A.filename = :filename and A.dossier = :dossier AND A.entreprise_id = :entreprise_id AND A.name <> 'DEMOLITION'";

        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$filename, 'entreprise_id'=>$entreprise_id, 'offset'=>$offset]);
        return $datas->fetchAll();
    } 

    public function getByNameAlpnUser($dossier, $entreprise_id, $filename, $searchName, $searchName2, $offset=1, $limit = 30){

        if(strtolower($searchName) == "m.")
            return [];

        $searchName = str_replace("m.", "", strtolower($searchName));
        $searchName2 = str_replace("m. ", "", strtolower($searchName2));

        $sql = "SELECT name, entreprise_id, filename, dossier FROM (SELECT * FROM tmp_ocr WHERE id >= :offset LIMIT $limit) as A WHERE (LOWER(A.name) LIKE '%$searchName%' OR LOWER(A.name) LIKE '%$searchName2%') AND A.filename = :filename and A.dossier = :dossier AND A.entreprise_id = :entreprise_id";

        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$filename, 'entreprise_id'=>$entreprise_id, 'offset'=>$offset]);
        return $datas->fetchAll();
    } 

    public function getByNameAndName2Alpn($dossier, $entreprise_id, $filename, $searchName, $offset=1, $searchName2="", $limit = 30){

        if(strtolower($searchName) == "m.")
            return [];

        $searchName = str_replace("m. ", "", strtolower($searchName));
        $searchName = str_replace("m.", "", strtolower($searchName));
        $searchName2 = str_replace("'", "''", strtolower($searchName2));

        $sql = "SELECT name, entreprise_id, filename, dossier FROM (SELECT * FROM tmp_ocr WHERE id >= :offset LIMIT $limit) as A WHERE REPLACE(A.name, '.', ' ') LIKE CONCAT('%', '$searchName', '%') AND REPLACE(A.name, '.', ' ') LIKE CONCAT('%', '$searchName2', '%') AND A.filename = :filename and A.dossier = :dossier AND A.entreprise_id = :entreprise_id";

        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$filename, 'entreprise_id'=>$entreprise_id, 'offset'=>$offset]);
        return $datas->fetchAll();
    }     

    // public function getByNameAlpnChantier($dossier, $entreprise_id, $filename, $searchName, $offset=1){

    //     if(strtolower($searchName) == "m.")
    //         return [];

    //     $searchName = str_replace("m.", "", strtolower($searchName));
    //     $searchName = str_replace("'", "", strtolower($searchName));

    //     $sql = "SELECT name, entreprise_id, filename, dossier FROM (SELECT * FROM tmp_ocr WHERE id >= :offset LIMIT 200) as A WHERE ( CONCAT('%', '$searchName', '%') RLIKE A.name OR A.name LIKE CONCAT('%', '$searchName', '%') ) AND A.filename = :filename and A.dossier = :dossier AND A.entreprise_id = :entreprise_id";

    //     $datas = $this->em->prepare($sql);
    //     $datas->execute(['dossier'=>$dossier, 'filename'=>$filename, 'entreprise_id'=>$this->entreprise_id, 'offset'=>$offset]);
    //     return $datas->fetchAll();
    // }      

    public function findFirstTmpOcrText($dossier, $entreprise_id, $filename, $offset=1, $limit=30){

        $sql = "SELECT name, entreprise_id, filename, dossier FROM (SELECT DISTINCT * FROM tmp_ocr WHERE id >= :offset GROUP BY name ORDER BY position_top ASC LIMIT $limit) as A WHERE A.filename = :filename and A.dossier = :dossier AND A.entreprise_id = :entreprise_id GROUP BY A.name ORDER BY position_top, position_left ASC ";

        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$filename, 'entreprise_id'=>$entreprise_id, 'offset'=>$offset]);
        return $datas->fetchAll();
    }    

    public function getFirstEltDocument($dossier, $entreprise_id, $filename){

        $sql = "SELECT id, name FROM tmp_ocr as A WHERE A.filename = :filename and A.dossier = :dossier AND A.entreprise_id = :entreprise_id LIMIT 1";
        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$filename, 'entreprise_id'=>$entreprise_id]);
        return $datas->fetch();
    }

}
