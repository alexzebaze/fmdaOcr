<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{

    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Article::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Article[] Returns an array of Article objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getByTabArticleEntity($tabId)
    {
        $req = $this->createQueryBuilder('a')
            ->where('a.id IN (:in)')
            ->andWhere('a.entreprise = :entreprise')
            ->setParameter('in', $tabId)
            ->setParameter('entreprise', $this->entreprise_id)
            ->getQuery()
            ->getResult();

        return $req;
    }

    public function filterByLibelleAndCode2($valFilter = ""){
        
        $sql = "SELECT *, (SELECT  FROM normenclature n INNER JOIN  WHERE n.article_normenclature_id  = a.id) AS list_article_libelle  FROM article a WHERE entreprise_id = :entreprise_id AND (LOWER(a.libelle) LIKE :libelle OR LOWER(a.code) LIKE :libelle) ORDER BY a.libelle ASC ";
        
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id, 'libelle' => strtolower('%'.$valFilter.'%')]);
    
        $datas = $datas->fetchAll();

        return $datas;
    }

    public function filterByLibelleAndCode($valFilter = ""){

        $req = $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.libelle) LIKE :libelle')
            ->orWhere('LOWER(p.code) LIKE :libelle')
            ->setParameter('libelle', strtolower('%'.$valFilter.'%'))
            ->andWhere('p.entreprise = :entreprise_id')
            ->setParameter('entreprise_id', $this->entreprise_id)
            ->getQuery()
            ->getResult();

        return $req;
    }

    public function updateSommeil($tabArticle){
        
        $in = '(' . implode(',', $tabArticle) .')';
        $sql = "UPDATE article set sommeil = 1 WHERE article.id IN $in";

        $datas = $this->em->prepare($sql);
        $datas->execute();

        return 1;
    }

    public function getByArticle($articleId){
        
        $sql = "SELECT a.id FROM article a INNER JOIN normenclature n ON a.id = n.article_normenclature_id WHERE n.article_reference_id = :articleId AND a.entreprise_id = :entreprise_id AND a.type = 2";
        
        $datas = $this->getEntityManager()->getConnection()->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id, 'articleId' => $articleId]);
    
        $datas = $datas->fetchAll();

        $articleArr = [];
        foreach ($datas as $key => $value) {
            $req = $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $value['id'])
            ->getQuery()
            ->getSingleResult();

            $articleArr[] = $req;
        }

        return $articleArr;
    }
}
