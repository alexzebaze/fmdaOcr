<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Fournisseurs;
use App\Entity\Entreprise;
use App\Entity\Tva;
use App\Entity\Lot;
use App\Entity\Normenclature;
use App\Entity\Docu;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GlobalService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\EntityRepository;
/**
 * @Route("/article")
 */
class ArticleController extends AbstractController
{
    private $global_s;
    private $session;

    public function __construct(GlobalService $global_s, SessionInterface $session){
        $this->global_s = $global_s;
        $this->session = $session;
    }

    /**
     * @Route("/", name="article_index", methods={"GET", "POST"})
     */
    public function index(ArticleRepository $articleRepository, Request $request, Session $session): Response
    {
        $form = $request->request->get('form', null);
        if ($form) {
            $lotId = (!(int)$form['lot']) ? null : (int)$form['lot'];
            $session->set('lot_art', $lotId);
        } else {
            $lotId = $session->get('lot_art', null);
        }

        $form = $this->createFormBuilder(array(
            'lot' => (!is_null($lotId)) ?  $this->getDoctrine()->getRepository(Lot::class)->find($lotId) : "",
        ))
        ->add('lot', EntityType::class, array(
            'class' => Lot::class,
            'query_builder' => function(EntityRepository $repository) { 
                return $repository->createQueryBuilder('l')
                ->where('l.entreprise = :entreprise_id')
                ->setParameter('entreprise_id', $this->session->get('entreprise_session_id'))
                ->orderBy('l.lot', 'ASC');
            },
            'required' => false,
            'label' => "Lot",
            'choice_label' => 'lot',
            'attr' => array(
                'class' => 'form-control'
            )
        ))->getForm();


        if($lotId){
            $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>1, 'lot'=>$lotId], ['libelle'=>'ASC']);

            $normenclatures = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>2, 'lot'=>$lotId], ['libelle'=>'ASC']);
        }
        else{
            $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>1], ['libelle'=>'ASC']);

            $normenclatures = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>2], ['libelle'=>'ASC']);
        }

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'normenclatures' => $normenclatures,
            'form' => $form->createView(),
            'typeArticle'=>$this->global_s->getTypeArticles()
        ]);
    }

    /**
     * @Route("/update-sommeil", name="article_update_sommeil", methods={"POST"})
     */
    public function updateSommeil(Request $request){
        $tabArticle = explode('-', $request->request->get('list-article-check'));

        $this->getDoctrine()->getRepository(Article::class)->updateSommeil($tabArticle);
        $this->addFlash('success', "Article mis en sommeil avec success");
        
        
        return $this->redirectToRoute('article_index');
    }

    /**
     * @Route("/new", name="article_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $article = new Article();   


        $tvaDefault = $this->getDoctrine()->getRepository(Tva::class)->findOneBy(['valeur'=>20]);
        if(!is_null($tvaDefault)){
            $article->setTauxTva($tvaDefault);
        }

        $lastArticle = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['code'=>'DESC']);
        if(!is_null($lastArticle) && !is_null($lastArticle->getCode())){
            $codeInt = (int)$lastArticle->getCode();
            $codeInt++;
            $code = (string)$codeInt;
            for ($i=strlen($code); $i < 5 ; $i++) { 
                $code = '0'.$code;
            }

            $article->setCode($code);
        }
        else{
            $article->setCode("00001");
        }
    

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager = $this->getDoctrine()->getManager();
            
            $this->saveInfoArticle($form, $request, $article);


            $Tabarticle = $request->request->get('articles');
            $Tabarticle = $Tabarticle ?? [];
            $articles = $this->getDoctrine()->getRepository(Article::class)->getByTabArticleEntity($Tabarticle);  

            $articleNormenclatures = $this->getDoctrine()->getRepository(Normenclature::class)->findBy(['articleNormenclature'=>$article->getId()]);  
            if(count($articleNormenclatures) == 0){
                $this->generateNormenclatureDivers($article);
            }

            $code = (string)$article->getId();
            for ($i=strlen($code); $i < 5 ; $i++) { 
                $code = '0'.$code;
            }

            foreach ($articles as $value) {
                
                $normenclature = new Normenclature();
                $normenclature->setArticleId($code);
                $normenclature->setArticleNormenclature($article);
                $normenclature->setArticleReference($value);
                $normenclature->setCodeArticle($value->getCode());
                $normenclature->setLibelle($value->getLibelle());
                $normenclature->setPrixVenteTtc($value->getPrixVenteTtc());
                $normenclature->setUnite($value->getUniteMesure());
                $normenclature->setEntreprise($value->getEntreprise());
                if(strtolower($value->getUniteMesure()) == "u")
                    $normenclature->setQte(1);

                $entityManager->persist($normenclature);
            }

            if(count($articles)){
                $article->setType(2);
            }

            $entityManager->flush();


            return $this->redirectToRoute('article_index');
        }


        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>1]);
        $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
            'fournisseurs'=>$fournisseurs,
            'articles'=>$articles,
            'normenclaturesArrIdRef'=>[]
        ]);
    }

    public function saveNormenclature($request, $article){

        $entityManager = $this->getDoctrine()->getManager();

        $Tabarticle = $request->request->get('articles');
        $Tabarticle = $Tabarticle ?? [];
        $articles = $this->getDoctrine()->getRepository(Article::class)->getByTabArticleEntity($Tabarticle); 

        $code = (string)$article->getId();
        for ($i=strlen($code); $i < 5 ; $i++) { 
            $code = '0'.$code;
        }

        foreach ($articles as $value) {
            $normenclature = new Normenclature();
            $normenclature->setArticleId($code);
            $normenclature->setArticleNormenclature($article);
            $normenclature->setArticleReference($value);
            $normenclature->setCodeArticle($value->getCode());
            $normenclature->setLibelle($value->getLibelle());
            $normenclature->setPrixVenteTtc($value->getPrixVenteTtc());
            $normenclature->setUnite($value->getUniteMesure());
            $normenclature->setEntreprise($value->getEntreprise());
            if(strtolower($value->getUniteMesure()) == "u")
                $normenclature->setQte(1);

            $entityManager->persist($normenclature);
        }

        $entityManager->flush();
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
     * @Route("/get-devis-normenclature-attach", name="article_get_devis_normenclature_attach")
     */
    public function getDevisNormenclatureAttach(Request $request): Response
    {    
        $article = $this->getDoctrine()->getRepository(Article::class)->find($request->query->get('articleId'));
        $docus = $this->getDoctrine()->getRepository(Docu::class)->getByArticleId($request->query->get('articleId'));
        $normenclatures = $this->getDoctrine()->getRepository(Normenclature::class)->getByArticleRefAndArticleNorm($request->query->get('articleId'));

        $datas = ['status'=>200, "message"=>""];

        $datas['content'] = $this->renderView('article/devis_normenclature_attach.html.twig', ['normenclatures'=>$normenclatures, 'docus'=>$docus, 'article'=>$article]);
        
        $response = new Response(json_encode($datas));
        return $response; 
    }

    /**
     * @Route("/load-fichearticle-normenclature-xhr", name="article_fichearticle_normenclature_xhr")
     */
    public function ficheArticleNormenclature(Request $request): Response
    {
        $docuId = $request->query->get('docu_id');
        $docu = $this->getDoctrine()->getRepository(Docu::class)->find($docuId);

        $normenclatures = [];
        $normenclaturesArrIdRef = [];
        if(!is_null($docu) && !is_null($docu->getArticle())){

            $normenclatures = $this->getDoctrine()->getRepository(Normenclature::class)->findBy(['articleNormenclature'=>$docu->getArticle()->getId()], ['id'=>'DESC']);
            foreach ($normenclatures as $value) {
                if(!is_null($value->getArticleReference())){
                    $normenclaturesArrIdRef[] = $value->getArticleReference()->getId();
                }
            }

        }

        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>1]);

        $datas = ['status'=>200, "message"=>""];
        $parrams= [
            'articles'=>$articles,
            'normenclatures'=>$normenclatures,
            'normenclaturesArrIdRef'=>$normenclaturesArrIdRef,
        ];

        $datas['content'] = $this->renderView('creation_devis_client/normenclature_list.html.twig', $parrams);
        
        $response = new Response(json_encode($datas));
        return $response;
    }


    /**
     * @Route("/new-xhr", name="article_new_xhr", methods={"POST"})
     */
    public function newXhr(Request $request): Response
    {   
        if($request->request->get("code")){
            $article = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['code'=>$request->request->get("code")]);
            if(is_null($article))
                $article = new Article(); 
        }
        else
            $article = new Article(); 

        $lastArticle = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['code'=>'DESC']);

        if(is_null($lastArticle)){
            $article->setCode("00001");
        }
        elseif(is_null($article->getCode())){
            $codeInt = (int)$lastArticle->getCode();
            $codeInt++;
            $code = (string)$codeInt;
            for ($i=strlen($code); $i < 5 ; $i++) { 
                $code = '0'.$code;
            }

            $article->setCode($code);   
        }
        
        if($request->request->get("libelle")){
            $article->setLibelle($request->request->get("libelle"));
        }
        if($request->request->get("type_article")){
            $article->setType($request->request->get("type_article"));
        }
        if($request->request->get("unite")){
            $article->setUniteMesure($request->request->get("unite"));
        }
        if($request->request->get("pvht")){
            $article->setPrixVenteHt($request->request->get("pvht"));
        }
        if($request->request->get("tva")){
            $tva = $this->getDoctrine()->getRepository(Tva::class)->find($request->request->get("tva"));
            $article->setTauxTva($tva);
        }
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $article->setEntreprise($entreprise);

        $entityManager = $this->getDoctrine()->getManager();


        $entityManager->persist($article);
        $entityManager->flush();

        if($request->request->get('articles')){
            $this->saveNormenclature($request, $article);

            $article->setType(2);
            $entityManager->flush();
        }

        $datas = [
            'libelle'=>$article->getLibelle(),
            'code'=>$article->getCode(),
            'article_id'=>$article->getId(),
            'type_article'=>$article->getType(),
            'unite'=>$article->getUniteMesure(),
            'tva'=>$article->getTauxTva(),
            'pvht'=>$article->getPrixVenteHt(),
        ];
        return new Response(json_encode(['status'=>200, 'datas'=>$datas]));

    }

    public function saveInfoArticle($form, $request, $article){
        $entityManager = $this->getDoctrine()->getManager();

        $Tabfournisseurs = $request->request->get('fournisseurs');
        $Tabfournisseurs = $Tabfournisseurs ?? [];

        $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->getByTabFournisseurEntity($Tabfournisseurs);  
        $article->fournisseursToRemove($Tabfournisseurs);         
        foreach ($fournisseurs as $value) {
            $article->addFournisseur($value);
        }

        /** @var UploadedFile $logo */
        $uploadedFile = $form['image']->getData();
        if ($uploadedFile){
            $logo = $this->global_s->saveImage($uploadedFile, '/public/uploads/article/', "article_".$article->getCode().'_'.uniqid());
            $article->setImage($logo);
        }

        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($this->session->get('entreprise_session_id'));
        $article->setEntreprise($entreprise);

        $entityManager->persist($article);
        $entityManager->flush();
    }

    /**
     * @Route("/edit/{id}", name="article_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Article $article): Response
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->saveInfoArticle($form, $request, $article);


            if($article->getType() == 1){
                $articleType2 = $this->getDoctrine()->getRepository(Article::class)->getByArticle($article->getId());
                foreach ($articleType2 as $artType2) {
                    $normenclatures = $this->getDoctrine()->getRepository(Normenclature::class)->findBy(['articleNormenclature'=>$artType2->getId()], ['id'=>'DESC']);

                    $ttprixRevient = 0; $ttpvhtSum = 0; $ttmargeSum = 0;
                    foreach ($normenclatures as $value) {
                        if(!is_null($value->getArticleReference())){
                            $ttUnitPa = $value->getQte()*$value->getArticleReference()->getPrixAchat();
                            $ttpvht = $value->getQte()*$value->getArticleReference()->getPrixVenteHt();
                            $ttmarge = $value->getQte()*$value->getArticleReference()->getMargeBrut();

                            $ttpvhtSum += $ttpvht; 
                            $ttprixRevient += $ttUnitPa; 
                            $ttmargeSum +=  $ttmarge;
                        }
                    }

                    $artType2->setPrixAchat($ttprixRevient);
                    $artType2->setPrixVenteHt($ttpvhtSum);

                    $margeBrut = $artType2->getPrixVenteHt() - $artType2->getPrixAchat();
                    $artType2->setMargeBrut($margeBrut);

                    if(!is_null($artType2->getPrixVenteHt()) && $artType2->getPrixVenteHt() != 0){
                        $pourcentageMarge = ($artType2->getMargeBrut()/$artType2->getPrixVenteHt())*100;
                        $artType2->setPourcentageMarge(round($pourcentageMarge, 2));
                    }
                    else{
                        $artType2->setPourcentageMarge(0);
                    }

                    $tva = 0;
                    if(!is_null($artType2->getTauxTva())){
                        $tva = $artType2->getTauxTva()->getValeur();
                    }
                    $tva = (float)("1.".(string)$tva);

                    $prix_vente_ttc = $artType2->getPrixVenteHt()*$tva;
                    $artType2->setPrixVenteTtc($prix_vente_ttc);

                    $em->flush();
                }
            }


            return $this->redirectToRoute('article_index');
        }
        
        $fournisseurs = $this->getDoctrine()->getRepository(Fournisseurs::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id')]);
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['entreprise'=>$this->session->get('entreprise_session_id'), 'type'=>1]);

        $ttprixRevient = 0; $ttpvhtSum = 0; $ttmargeSum = 0;
        
        $normenclatures = [];
        $normenclaturesArrIdRef = [];

        if($article->getType() == 2){
            $normenclatures = $this->getDoctrine()->getRepository(Normenclature::class)->findBy(['articleNormenclature'=>$article->getId()], ['id'=>'DESC']);

            $i = 0;
            $normenclatureDivers = null;
            foreach ($normenclatures as $value) {
                if(!is_null($value->getArticleReference())){
                    $ttUnitPa = $value->getQte()*$value->getArticleReference()->getPrixAchat();
                    $ttpvht = $value->getQte()*$value->getArticleReference()->getPrixVenteHt();
                    $ttmarge = $value->getQte()*$value->getArticleReference()->getMargeBrut();

                    $ttpvhtSum += $ttpvht; 
                    $ttprixRevient += $ttUnitPa; 
                    $ttmargeSum +=  $ttmarge;

                    if(strtolower($value->getLibelle()) == 'divers'){
                        $normenclatureDivers = $value;
                        array_splice($normenclatures, $i, 1);
                    } 

                    $normenclaturesArrIdRef[] = $value->getArticleReference()->getId();
                }
                $i++;
            }


            $article->setPrixAchat($ttprixRevient);
            $article->setPrixVenteHt($ttpvhtSum);

            $margeBrut = $article->getPrixVenteHt() - $article->getPrixAchat();
            $article->setMargeBrut($margeBrut);

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

            $form = $this->createForm(ArticleType::class, $article);

            if(!is_null($normenclatureDivers))
                $normenclatures[] = $normenclatureDivers;

        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
            'fournisseurs'=>$fournisseurs,
            'articles'=>$articles,
            'normenclatures'=>$normenclatures,
            'fournisseursArticle'=>$article->getFournisseurs(), 
            'normenclaturesArrIdRef'=>$normenclaturesArrIdRef,// tableau des normenclatures deja ajoutées
            'recapitulatifs'=>[
                'prixRevient'=>$ttprixRevient,
                'pvHt'=>$ttpvhtSum,
                'marge'=>$ttmargeSum
            ]
        ]);
    }

    /**
     * @Route("/dupliquer/{id}", name="article_duplique", methods={"GET","POST"})
     */
    public function dupliqueArticle($id){
        $em = $this->getDoctrine()->getManager();
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);

        $articleClone = new Article();
        $articleClone = clone $article;
        $articleClone->setCodeArticleFournisseur(null);

        $lastArticle = $this->getDoctrine()->getRepository(Article::class)->findOneBy(['entreprise'=>$this->session->get('entreprise_session_id')], ['id'=>'DESC']);
        if(!is_null($lastArticle) && !is_null($lastArticle->getCode())){
            $codeInt = (int)$lastArticle->getCode();
            $codeInt++;
            $code = (string)$codeInt;
            for ($i=strlen($code); $i < 5 ; $i++) { 
                $code = '0'.$code;
            }

            $articleClone->setCode($code);
        }
        
        $em->persist($articleClone);

        $em->flush();

        return $this->redirectToRoute('article_edit', ['id'=>$articleClone->getId()]);
    }


    /**
     * @Route("/detail/{id}", name="article_show", methods={"GET"})
     */
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * @Route("/{id}", name="article_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_index');
    }
}
