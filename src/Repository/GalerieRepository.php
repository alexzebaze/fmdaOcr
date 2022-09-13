<?php

namespace App\Repository;

use App\Entity\Galerie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method Galerie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Galerie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Galerie[]    findAll()
 * @method Galerie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GalerieRepository extends ServiceEntityRepository
{   
    private $session;
    public function __construct(ManagerRegistry $registry, SessionInterface $session)
    {
        parent::__construct($registry, Galerie::class);
        $this->em = $this->getEntityManager()->getConnection();
        $this->session = $session;
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return Galerie[] Returns an array of Galerie objects
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
    public function findOneBySomeField($value): ?Galerie
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function insertPhoto($tabPhoto){

        $tabPhoto = rtrim($tabPhoto, ",");
        $sql = 'INSERT INTO galerie (chantier_id, nom, extension, type, entreprise_id, user_id, created_at) VALUES '.$tabPhoto;
        
        $gallery = $this->em->prepare($sql);
        $gallery->execute();
        return 1;
    }
    public function getChantierDesableHaveGallery($nameentreprise = null){
        $sql = "SELECT g.chantier_id from galerie as g INNER JOIN chantier ON g.chantier_id = chantier.chantier_id WHERE chantier.status = :status AND g.entreprise_id = :entrepriseId ";

        $tabExec = ['entrepriseId'=>$this->entreprise_id, 'status'=> 0];
        if($nameentreprise){
            $sql .= " AND LOWER(chantier.nameentreprise) LIKE :nameentreprise ";
            $tabExec['nameentreprise'] = $nameentreprise;
        }
        $sql .= " GROUP BY g.chantier_id";

        $datas = $this->em->prepare($sql);
        $datas->execute($tabExec);
    }

    public function getGalleryGroupByUser($entrepriseId = null){

        if(is_null($entrepriseId)){
            $entrepriseId = $this->entreprise_id;
        }
        
        $sql = "SELECT u.uid, u.firstname, u.lastname, (SELECT count(g.id) FROM galerie g WHERE u.uid = g.user_id AND MONTH(g.created_at) = :month) as nbr_gallery from utilisateur as u  WHERE  u.entreprise_id = :entrepriseId AND u.etat = :etat";

        $tabExec = ['entrepriseId'=>$entrepriseId, 'month'=>(new \DateTime())->format('m'), 'etat'=>1];
        $datas = $this->em->prepare($sql);
        $datas->execute($tabExec);
        $datas = $datas->fetchAll();
        return $datas;
    }

    public function findDefaultGallery(){
        
        $sql = "SELECT g.nom, c.default_galerie_id, c.chantier_id, g.created_at as created_at FROM galerie g INNER JOIN chantier c WHERE c.default_galerie_id is not null AND c.default_galerie_id = g.id AND c.entreprise_id = :entreprise";

        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();
        return $datas;
    }
}
