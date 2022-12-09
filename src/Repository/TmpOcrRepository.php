<?php

namespace App\Repository;

use App\Entity\TmpOcr;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method TmpOcr|null find($id, $lockMode = null, $lockVersion = null)
 * @method TmpOcr|null findOneBy(array $criteria, array $orderBy = null)
 * @method TmpOcr[]    findAll()
 * @method TmpOcr[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TmpOcrRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, TmpOcr::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return TmpOcr[] Returns an array of TmpOcr objects
    //  */
    /*
    public function findByExampleTmpOcr($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleTmpOcr = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeTmpOcr($value): ?TmpOcr
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleTmpOcr = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function removesAll($dossier, $filename){
        $sql = "DELETE FROM tmp_ocr WHERE dossier = :dossier AND filename = :filename";

        $data = $this->em->prepare($sql);
        $data->execute(['dossier'=>$dossier, 'filename'=>$filename]);

        return $data;
    }

    public function getNewTextByPostion($left, $top, $endWidthPosition, $endHeightPosition, $dossier, $filename, $blocktype = "WORD"){
        $sql = "SELECT id, description FROM  tmp_ocr WHERE dossier = :dossier AND filename = :filename AND position_left >= :left AND position_top >= :top AND blocktype = :blocktype GROUP BY id HAVING SUM(size_width+position_left) <= :endWidthPosition AND SUM(size_height+position_top)  <= :endHeightPosition ";

        $datas = $this->em->prepare($sql);

        $datas->execute(['left'=>$left, 'top'=>$top, 'endWidthPosition'=>$endWidthPosition, 'endHeightPosition'=>$endHeightPosition, 'dossier'=>$dossier, 'filename'=>$filename, 'blocktype'=>$blocktype]);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function getByParam($dossier, $filename, $order, $blocktype, $limit = 50, $entreprise_id = null){
        if(is_null($entreprise_id)){
            $entreprise_id = $this->entreprise_id;
        }

        $sql = "SELECT id, position_left, position_top, size_width, size_height, dossier, entreprise_id FROM tmp_ocr WHERE entreprise_id = :entreprise AND dossier = :dossier AND filename = :filename AND blocktype = :blocktype ORDER BY id $order LIMIT $limit";

        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$filename, 'entreprise'=>$entreprise_id, 'blocktype'=>$blocktype]);

        $datas = $datas->fetchAll();
        return $datas;
    }

    public function getsummaryFields($dossier, $lastOcrFile, $entreprise){
        $sql = "SELECT total_ttc_list, total_ht_list FROM tmp_ocr  WHERE entreprise_id = :entreprise AND dossier = :dossier AND filename = :filename AND (total_ht_list IS NOT NULL OR total_ttc_list IS NOT NULL) LIMIT 1";

        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$lastOcrFile, 'entreprise'=>$entreprise]);

        $datas = $datas->fetch();
        return $datas;
    }

    public function findLikeText($dossier, $lastOcrFile, $entrepriseId, $text){
        $sql = "SELECT COUNT(*) as countText FROM tmp_ocr  WHERE entreprise_id = :entreprise AND dossier = :dossier AND filename = :filename AND LOWER(name) LIKE :text LIMIT 1";

        $datas = $this->em->prepare($sql);
        $datas->execute(['dossier'=>$dossier, 'filename'=>$lastOcrFile, 'entreprise'=>$entrepriseId, 'text' => strtolower('%'.$text.'%')]);

        $datas = $datas->fetch();
        return $datas;
    }
}
