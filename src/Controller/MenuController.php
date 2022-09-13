<?php

namespace App\Controller;

use Doctrine\ORM\EntityRepository;
use App\Entity\Menu;
use App\Entity\Entreprise;
use App\Form\MenuType;
use App\Repository\EntrepriseRepository;
use App\Repository\MenuRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Service\MenuService;

/**
 * @Route("/menu")
 */
class MenuController extends AbstractController
{
    private $menuRepository;
    private $entrepriseRepository;
    private $session;
    private $menu_s;

    public function __construct(MenuRepository $menuRepository, EntrepriseRepository $entrepriseRepository, SessionInterface $session, MenuService $menu_s){
        $this->entrepriseRepository = $entrepriseRepository;
        $this->menuRepository = $menuRepository;
        $this->session = $session;
        $this->menu_s = $menu_s;
    }

    /**
     * @Route("/", name="menu_index", methods={"GET", "POST"})
     */
    public function index(Request $request, MenuRepository $menuRepository): Response
    {
        $entrepriseId = null;
        if ($request->isMethod('post')) {
            $entrepriseId = (!(int)$request->request->get('entreprise')) ? null : (int)$request->request->get('entreprise');
        }
        $menu = $menuRepository->getMenuFirstNiveau();

        $menusArr = $this->menu_s->orderMenu($menu);
        return $this->render('menu/index.html.twig', [
            'menus' => $menusArr,
            'entreprises' => $this->entrepriseRepository->findAll(),
            'entreprise_id'=>$entrepriseId
        ]);
    }    

    /**
     * @Route("/toggle", name="menu_toggle_entrepise", methods={"GET"})
     */
    public function toggle(Request $request)
    {
        $menuId = $request->query->get('menu_id');
        $is_selected = $request->query->get('is_selected');

        //$entrepriseId = !is_null($this->session->get('entreprise_session_id')) ? $this->session->get('entreprise_session_id') : $request->query->get('entreprise_id');
        
        $entrepriseId =  $request->query->get('entreprise_id');
        if($entrepriseId){  
            $entityManager = $this->getDoctrine()->getManager();
            $entreprise = $this->entrepriseRepository->find($entrepriseId);
            $menu = $this->menuRepository->find($menuId);

            if((int)$is_selected == 1){
                $menu->addEntreprise($entreprise);
            }
            else{
                $menu->removeEntreprise($entreprise);
            }
            $entityManager->flush();
        }
        return new Response(json_encode(array('status'=>200)));
    }    

    /**
     * @Route("/sortable", name="menu_sortable", methods={"GET"})
     */
    public function sortable(Request $request)
    {

        $orders = explode(',', $request->query->get('menu_sort'));
        $em = $this->getDoctrine()->getManager();
        $menus = $this->menuRepository->getMenuFirstNiveau();
        foreach ($menus as $value) {
            $index = array_search($value->getId(), $orders);
            if($index || $index == 0){
                $value->setRang($index);
            }
            $em->flush();  
        }
        
        $em->flush();  
        return new Response(json_encode(array('status'=>200)));
    }

    /**
     * @Route("/new", name="menu_new", methods={"GET","POST"})
     */
    public function new(Request $request, MenuRepository $menuRepository): Response
    {
        $menu = new Menu();
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $lastInsertRang = $menuRepository->getLastRang($form['parent']->getData());
            $menu->setRang(++$lastInsertRang);

            $hoteUrl = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
            $link = $form['link']->getData();
            $link = trim(str_replace($hoteUrl, "", $link));

            $menu->setLink($link);

            foreach ($menu->getEntreprises() as $value) {
                $menu->removeEntreprise($value);
            }

            $entreprises = $request->request->get('entreprise');
            if($entreprises){
                foreach ($entreprises as $value) {
                    $entrepriseItem = $this->entrepriseRepository->find($value);
                    $menu->addEntreprise($entrepriseItem);
                }
            }

            $entityManager->persist($menu);
            $entityManager->flush();

            return $this->redirectToRoute('menu_index');
        }

        return $this->render('menu/new.html.twig', [
            'menu' => $menu,
            'form' => $form->createView(),
            'menusEntrepise'=> $menu->getEntreprises(),
            'entreprises'=> $this->entrepriseRepository->findAll(),
        ]);
    }

    /**
     * @Route("/load-xhr", name="menu_load_xhr")
     */
    public function loadXhr(Request $request, MenuRepository $menuRepository){
        $entrepriseId = $this->session->get('entreprise_session_id');

        $datas = ['status'=>500, "message"=>"aucune entreprise fournie"];
        if($entrepriseId){
            $entreprise = $this->entrepriseRepository->find($entrepriseId);

            $menus = $entreprise->getMenus();
            $menusArr = [];
            if(!is_null($entreprise)){
                
                foreach ($menus as $value) {
                    if(is_null($value->getParent()))
                        $menusArr[] = $value;
                }
                $menusArr = $this->menu_s->orderMenu($menusArr);

                $datas = ['status'=>200, "message"=>""];
                $datas['datas'] = $this->renderView('menu/menu_left.html.twig', [
                    'menusGroup' => $menusArr,
                    'menusEntrepise' => $menus
                ]);
            }
        }

        $response = new Response(json_encode($datas));
        return $response;   
    }

    public function orderMenu($tabMenu){
        $result = []; $diffSort = []; $tabDiff = [];
        foreach ($tabMenu as $value) {
            $tabDiff[$value->getId()] = $value->getRang();
        }
        asort($tabDiff);
        
        foreach ($tabDiff as $key => $value) {
            $diffSort[] = $key;
        }

        foreach ($tabMenu as $value) {
            $index = array_search($value->getId(), $diffSort);
            $result[$index] = $value;
        }

        ksort($result);
        return $result;
    }

    public function sortToAmount($target, $devis){
        $result = []; $diffSort = []; $tabDiff = [];
        foreach ($devis as $value) {
            $tabDiff[$value->getId()] = abs($value->getPrixht() - $target);
        }
        asort($tabDiff);

        foreach ($tabDiff as $key => $value) {
            $diffSort[] = $key;
        }

        foreach ($devis as $value) {
            $index = array_search($value->getId(), $diffSort);
            $result[$index] = $value;
        }

        ksort($result);
        return $result;
    }

    /**
     * @Route("/{id}", name="menu_show", methods={"GET"})
     */
    public function show(Menu $menu): Response
    {
        return $this->render('menu/show.html.twig', [
            'menu' => $menu,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="menu_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Menu $menu, MenuRepository $menuRepository): Response
    {
        $form = $this->createForm(MenuType::class, $menu, array('menu' => $menu));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $hoteUrl = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
            $link = $form['link']->getData();
            $link = trim(str_replace($hoteUrl, "", $link));
            $menu->setLink($link);

            foreach ($menu->getEntreprises() as $value) {
                $menu->removeEntreprise($value);
            }

            $entreprises = $request->request->get('entreprise');
            if($entreprises){
                foreach ($entreprises as $value) {
                    $entrepriseItem = $this->entrepriseRepository->find($value);
                    $menu->addEntreprise($entrepriseItem);
                }
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('menu_index');
        }

        return $this->render('menu/edit.html.twig', [
            'menu' => $menu,
            'form' => $form->createView(),
            'entreprises'=> $this->entrepriseRepository->findAll(),
            'menusEntrepise'=> $menu->getEntreprises()
        ]);
    }

    /**
     * @Route("/{id}", name="menu_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Menu $menu): Response
    {
        if ($this->isCsrfTokenValid('delete'.$menu->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($menu);
            $entityManager->flush();
        }

        return $this->redirectToRoute('menu_index');
    }
}
