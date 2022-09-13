<?php

namespace App\Repository;

use App\Entity\EmailDocumentPreview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method EmailDocumentPreview|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailDocumentPreview|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailDocumentPreview[]    findAll()
 * @method EmailDocumentPreview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailDocumentPreviewRepository extends ServiceEntityRepository
{
    private $security;
    private $session;

    public function __construct(ManagerRegistry $registry, Security $security, SessionInterface $session)
    {
        parent::__construct($registry, EmailDocumentPreview::class);
        $this->session = $session;
        $this->em = $this->getEntityManager()->getConnection();
        $this->entreprise_id = $session->get('entreprise_session_id');
    }

    // /**
    //  * @return EmailDocumentPreview[] Returns an array of EmailDocumentPreview objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EmailDocumentPreview
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function countByDossier($dossier){
        $sql = "SELECT count(id) as nb FROM email_document_preview WHERE entreprise_id = :entreprise_id AND dossier = :dossier";
        $data = $this->em->prepare($sql);
        $data->execute(['entreprise_id'=>$this->entreprise_id, 'dossier'=>$dossier]);
        $data = $data->fetch();

        return $data;
    }

    public function countGroupByDossier(){
        $sql = "SELECT count(id) as nb, dossier FROM email_document_preview WHERE entreprise_id = :entreprise_id GROUP BY dossier";
        $datas = $this->em->prepare($sql);
        $datas->execute(['entreprise_id'=>$this->entreprise_id]);
        $datas = $datas->fetchAll();

        return $datas;
    }
}
