<?php

namespace App\EventListener;

use App\Entity\UserActivity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\EntrepriseRepository;
use App\Repository\MenuRepository;

class MenuListener {

    private $em;
    private $security;
    private $container;
    private $session;
    private $entrepriseRepository;
    private $menuRepository;
    private $router;
    private $requestStack;

    public function __construct(SessionInterface $session, EntityManagerInterface $em, Security $security, ContainerInterface $container, EntrepriseRepository $entrepriseRepository, MenuRepository $menuRepository, RequestStack $requestStack, RouterInterface $router) {
        $this->em = $em;
        $this->security = $security;
        $this->container = $container;
        $this->session = $session;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->entrepriseRepository = $entrepriseRepository;
        $this->menuRepository = $menuRepository;
    }

    public function onKernelController(FilterControllerEvent  $event) {

        // Check that the current request is a "MASTER_REQUEST"
        // Ignore any sub-request
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        // Check token authentication availability
        
        if ($this->security->getToken()) {
            $user = $this->security->getToken()->getUser();
            if($user instanceof \App\Entity\Admin){
                //$this->requestStack->getCurrentRequest()->getRequestUri()
                //$hoteUrl = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
                //$this->requestStack->getCurrentRequest()->getUri();
                
                $allMenus = $this->menuRepository->findAll();
                $tabLink = [];
                foreach ($allMenus as $value) {
                    $tabLink[] = $value->getLink();
                }
                $currentUrl = $this->requestStack->getCurrentRequest()->getRequestUri();
                $currentUrl = ltrim($currentUrl, "/");
                
                if(in_array($currentUrl, $tabLink) && $currentUrl != ""){
                    $menuRequest = $this->menuRepository->findOneBy(['link'=>$currentUrl]); 
                    $entrepriseAuthorized = $menuRequest->getEntreprises();

                    $entrepriseConnect = $this->session->get('entreprise_session_id');
                    
                    if(!is_null($entrepriseConnect)){
                        $found = false;
                        foreach ($entrepriseAuthorized as $value) {
                            if($value->getId() == $entrepriseConnect){
                                return;
                            }
                        }   
                        printf("Vous n'etes pas autorisé à acceder à cette page");
                        die();
                        $url = $this->router->generate("page_503");
                        $response = new RedirectResponse($url);
                        $event->setResponse($response);
                        //return new RedirectResponse('page_503');
                    }

                }


            }
        }
    }

}
