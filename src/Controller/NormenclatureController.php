<?php

namespace App\Controller;

use App\Entity\Normenclature;
use App\Entity\Article;
use App\Form\NormenclatureType;
use App\Repository\NormenclatureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/normenclature")
 */
class NormenclatureController extends AbstractController
{
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }


    /**
     * @Route("/", name="normenclature_index", methods={"GET"})
     */
    public function index(NormenclatureRepository $normenclatureRepository): Response
    {
        return $this->render('normenclature/index.html.twig', [
            'normenclatures' => $normenclatureRepository->findAll(),
        ]);
    }

    /**
     * @Route("/normenclature-by-article", name="get_normenclature_by_article", methods={"GET"})
     */
    public function getNormenclatureByArticle(Request $request, NormenclatureRepository $normenclatureRepository): Response
    {
        $article = $request->query->get('article_id');
        $normenclatures = $normenclatureRepository->findBy(['articleNormenclature'=>$article]);

        $datas = ['status'=>200, "message"=>""];
        $datas['preview'] = $this->renderView('article/normenclature_content.html.twig', [
                'normenclatures' => $normenclatures,
            ]);
        
        $response = new Response(json_encode($datas));
        return $response;
    }

    public function recalculNormenclatureData($normenclature, $field){

        if(!is_null($normenclature->getPvUnitHt()) && $normenclature->getPvUnitHt() != 0 && $field = "pvUnitHt" ){
            $pourcentageMarge = ($normenclature->getArticleReference()->getMargeBrut()/$normenclature->getPvUnitHt())*100;
            $pourcentageMarge =  round($pourcentageMarge, 2);
            $normenclature->setPourcentageMarge($pourcentageMarge);
        }
        
        if($field = "pourcentageMarge" && $normenclature->getPourcentageMarge() != 0){
            $ttpvht = ($normenclature->getArticleReference()->getMargeBrut()*100)/$normenclature->getPourcentageMarge();
            $normenclature->setPvUnitHt(round($ttpvht, 2));
        } 

        return $normenclature;
    }

    /**
     * @Route("/update-field", name="normenclature_update_field")
     */
    public function updateField(Request $request)
    {   
        $em = $this->getDoctrine()->getManager();
        $normenclatureId = $request->query->get('normenclature_id');
        $val = $request->query->get('val');
        $field = $request->query->get('field');

        $normenclature = $this->getDoctrine()->getRepository(Normenclature::class)->find($normenclatureId);

        switch ($field) {
            case 'qte':
                $normenclature->setQte(round((float)$val, 1));
                break;
            case 'prixAchat':
                $normenclature->setPrixAchat((float)$val);
                break;
            case 'pvUnitHt':
                $normenclature->setPvUnitHt((float)$val);
                break;
            case 'pourcentageMarge':
                $normenclature->setPourcentageMarge((float)$val);
                break;
            default:
                break;
        }

        //$normenclature = $this->recalculNormenclatureData($normenclature, $field);

        $em->flush();

        $normenclatures = $this->getDoctrine()->getRepository(Normenclature::class)->findBy(['articleNormenclature'=>$normenclature->getArticleNormenclature()->getId()], ['id'=>'DESC']);
        $prixRevient = 0; $ttpvhtSum = 0; $ttmargeSum = 0;
        $normenclatureDivers = null;
        $i = 0;
        foreach ($normenclatures as $value) {
            if(!is_null($value->getArticleReference())){
                $ttUnitPa = $value->getQte()*$value->getArticleReference()->getPrixAchat();
                $ttpvht = $value->getQte()*$value->getArticleReference()->getPrixVenteHt();
                $ttmarge = $value->getQte()*$value->getArticleReference()->getMargeBrut();

                $ttpvhtSum += $ttpvht; 
                $prixRevient += $ttUnitPa; 
                $ttmargeSum +=  $ttmarge; 
            
                if(strtolower($value->getLibelle()) == 'divers'){
                    $normenclatureDivers = $value;
                    array_splice($normenclatures, $i, 1);
                } 
            }
            $i++;
        }


        if(!is_null($normenclatureDivers))
            $normenclatures[] = $normenclatureDivers;
        
        $datas = ['status'=>200, "message"=>""];
        $datas['content'] = $this->renderView('article/normenclature_list.html.twig', [
            'normenclatures'=>$normenclatures,
            'recapitulatifs'=>[
                'prixRevient'=>$prixRevient,
                'pvHt'=>$ttpvhtSum,
                'marge'=>$ttmargeSum
            ]

        ]);
        $article = $normenclature->getArticleNormenclature();
        $article->setPrixAchat(round($prixRevient, 2));
        $article->setPrixVenteHt(round($ttpvhtSum, 2));
        $margeBrut = $article->getPrixVenteHt() - $article->getPrixAchat();
        $article->setMargeBrut(round($margeBrut, 2));
        if(!is_null($article->getPrixVenteHt()) && $article->getPrixVenteHt() != 0){
            $pourcentageMarge = ($article->getMargeBrut()/$article->getPrixVenteHt())*100;
            $article->setPourcentageMarge(round($pourcentageMarge, 2));
        }
        else{
            $article->setPourcentageMarge(0);
        }
        
        $tva = 0;
        if(!is_null($article->getTauxTva())){
            $tva = $article->getTauxTva()->getValeur();
        }
        $tva = (float)("1.".(string)$tva);

        $prix_vente_ttc = $article->getPrixVenteHt()*$tva;
        $article->setPrixVenteTtc($prix_vente_ttc);

        $em->flush();

        $datas['article'] = [
            'prix_achat'=> $article->getPrixAchat(),
            'prix_vente_ht'=> $article->getPrixVenteHt(),
            'marge_brute'=> $article->getMargeBrut(),
            'pourcentage_marge'=> $article->getPourcentageMarge(),
            'prix_vente_ttc'=> $article->getPrixVenteTtc(),
        ];
        $response = new Response(json_encode($datas));
        return $response;

        //return new Response(json_encode(['status'=>200, "success request"]));
    }

    /**
     * @Route("/new/{article_id}", name="normenclature_new", methods={"GET","POST"})
     */
    public function new(Request $request, $article_id): Response
    {    
        // article_id == articleNormenclature

        $em = $this->getDoctrine()->getManager();

        $Tabarticle = $request->request->get('articles');
        $Tabarticle = $Tabarticle ?? [];
        $articles = $this->getDoctrine()->getRepository(Article::class)->getByTabArticleEntity($Tabarticle);  

        $articleFiche = $this->getDoctrine()->getRepository(Article::class)->find($article_id);  

        $articleNormenclatures = $this->getDoctrine()->getRepository(Normenclature::class)->findBy(['articleNormenclature'=>$article_id]);  
        if(count($articleNormenclatures) == 0){
            $this->generateNormenclatureDivers($articleFiche);
        }

        $code = (string)$articleFiche->getId();
        for ($i=strlen($code); $i < 5 ; $i++) { 
            $code = '0'.$code;
        }

        foreach ($articles as $article) {
            
            $normenclature = new Normenclature();
            $normenclature->setArticleId($code);
            $normenclature->setArticleNormenclature($articleFiche);
            $normenclature->setArticleReference($article);
            $normenclature->setCodeArticle($article->getCode());
            $normenclature->setLibelle($article->getLibelle());
            $normenclature->setPrixVenteTtc($article->getPrixVenteTtc());
            $normenclature->setPvUnitHt($article->getPrixVenteHt());
            $normenclature->setUnite($article->getUniteMesure());
            $normenclature->setEntreprise($article->getEntreprise());
            if(strtolower($article->getUniteMesure()) == "u")
                $normenclature->setQte(1);

            $em->persist($normenclature);
        }

        $articleFiche->setType(2);
        $em->flush();

        return $this->redirectToRoute('article_edit', ['id'=>$article_id]);
    }


    public function generateNormenclatureDivers($articleNormenclature){

        $em = $this->getDoctrine()->getManager();
        $articleDivers = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['libelle'=>'DIVERS', 'type'=>1]);
        $code = (string)$articleNormenclature->getId();
        for ($i=strlen($code); $i < 5 ; $i++) { 
            $code = '0'.$code;
        }

        $normenclature = new Normenclature();
        $normenclature->setArticleId($code);
        $normenclature->setArticleNormenclature($articleNormenclature);
        $normenclature->setArticleReference($articleDivers);
        $normenclature->setCodeArticle($articleDivers->getCode());
        $normenclature->setLibelle($articleDivers->getLibelle());
        $normenclature->setPrixVenteTtc($articleDivers->getPrixVenteTtc());
        $normenclature->setUnite($articleDivers->getUniteMesure());
        $normenclature->setEntreprise($articleNormenclature->getEntreprise());

        if(strtolower($articleDivers->getUniteMesure()) == "u"){
            $normenclature->setQte(1);
        }
        $em->persist($normenclature);
        $em->flush();

        return 1;
    }

    /**
     * @Route("/{id}/edit", name="normenclature_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Normenclature $normenclature): Response
    {
        $form = $this->createForm(NormenclatureType::class, $normenclature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('article_edit', ['id'=>$normenclature->getArticle()->getId()]);
        }

        return $this->render('normenclature/edit.html.twig', [
            'normenclature' => $normenclature,
            'form' => $form->createView(),
        ]);
    }

    public function saveInfoArticle($form, $request, $normenclature){
        $entityManager = $this->getDoctrine()->getManager();

        $Tabfournisseurs = $request->request->get('fournisseurs');
        $Tabfournisseurs = $Tabfournisseurs ?? [];

        $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->getByTabFournisseurEntity($Tabfournisseurs);  
        $normenclature->fournisseursToRemove($Tabfournisseurs);         
        foreach ($fournisseurs as $value) {
            $normenclature->addFournisseur($value);
        }

        /** @var UploadedFile $logo */
        $uploadedFile = $form['image']->getData();
        if ($uploadedFile){
            $logo = $this->global_s->saveImage($uploadedFile, '/public/uploads/article/', "article_".$normenclature->getCode());
            $normenclature->setImage($logo);
        }

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $normenclature->setEntreprise($entreprise);

        $entityManager->persist($normenclature);
        $entityManager->flush();
    }

    /**
     * @Route("/delete/{id}", name="normenclature_delete", methods={"DELETE"})
     */
    public function delete(Request $request, $id): Response
    {
        $normenclature = $this->getDoctrine()->getRepository(Normenclature::class)->find($id);
        $article_id = $normenclature->getArticleNormenclature()->getId();
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($normenclature);
        $entityManager->flush();
        

        return $this->redirectToRoute('article_edit', ['id'=>$article_id]);
    }
}
