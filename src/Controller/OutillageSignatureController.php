<?php

namespace App\Controller;

use App\Controller\Traits\CommunTrait;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Repository\SignatureOutillageRepository;
use App\Repository\UtilisateurRepository;
use App\Entity\SignatureOutillage;
use App\Entity\Utilisateur;
use App\Service\GlobalService;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @Route("/outillage-signature", name="outillage_signature_")
 */
class OutillageSignatureController extends Controller
{
    use CommunTrait;
    private $global_s;
    private $session;
    private $utilisateurRepository;
    private $signatureOutillageRepository;

    public function __construct(SignatureOutillageRepository $signatureOutillageRepository, UtilisateurRepository $utilisateurRepository, GlobalService $global_s, SessionInterface $session){
        $this->utilisateurRepository = $utilisateurRepository;
        $this->signatureOutillageRepository = $signatureOutillageRepository;
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/upload/{userId}", name="upload", methods={"POST"})
     */
    public function uploadsOutillageSigne(Request $request, $userId): Response
    {
        $utilisateur = $this->utilisateurRepository->find($userId);
        $uploadedFile = $request->files->get('document_signe');

        $signature = new SignatureOutillage();
        if ($uploadedFile){
            $dir = $this->get('kernel')->getProjectDir() . "/public/uploads/image_outillage/signature/";
            try {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            } catch (FileException $e) {}

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugify($originalFilename);
            $extension = $uploadedFile->guessExtension();
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
            $uploadedFile->move($dir, $newFilename);

            $signature->setDocument($newFilename);
            $signature->setDateSignature(new \DateTime());
            $signature->setUtilisateur($utilisateur);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($signature);
            $entityManager->flush();

            $this->addFlash('success', "Document enregistré");
        }

        return $this->redirectToRoute('outillage_utilisateur_select', ['id'=>$userId]);

    }


    /**
     * @Route("/delete-signature/{signature_id}", name="delete_signature", methods={"GET"})
    */
    public function deleteSignature(Request $request, $signature_id)
    {
        $signature = $this->signatureOutillageRepository->find($signature_id);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($signature);
        $entityManager->flush();

        $this->addFlash('success', "Document signé supprimé");
        return $this->redirectToRoute('outillage_utilisateur_select');
    }

    /**
     * @Route("/list-signature", name="list_signature", methods={"GET"})
    */
    public function getByListSignature(Request $request)
    {
        $user_id = explode(',', $request->query->get('user_id'));
        $users = $this->utilisateurRepository->findOneBy(['uid'=>$user_id]);
        $signatures = $users->getSignatures();

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('outillage/modal_signature.html.twig', [
            'signatures' => $signatures,
        ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    /**
     * @Route("/", name="list_by_list_id", methods={"GET"})
     */
    public function getByListId(Request $request)
    {
        $tabBlId = explode(',', $request->query->get('bl_list'));
        $bon_livraisons = $this->achatRepository->getBlByListId($tabBlId);

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('comparateur/modal_bl.html.twig', [
                'bon_livraisons' => $bon_livraisons,
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

}
