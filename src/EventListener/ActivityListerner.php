<?php

namespace App\EventListener;

use Carbon\Carbon;
use App\Entity\AdminActivity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityListerner {

    private $em;
    private $security;
    private $container;
    private $requestStack;

    public function __construct(EntityManagerInterface $em, Security $security, ContainerInterface $container, RequestStack $requestStack) {
        $this->em = $em;
        $this->security = $security;
        $this->container = $container;
        $this->requestStack = $requestStack;
    }

    public function onTerminate(ControllerEvent $event) {
        // Check that the current request is a "MASTER_REQUEST"
        // Ignore any sub-request
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }


        // Check token authentication availability
        if ($this->security->getToken()) {
            $user = $this->security->getToken()->getUser();

            if($user instanceof \App\Entity\Admin){
                $request = $event->getRequest();


                $ipAuthorize = explode(',', $user->getIpList());
                if(!in_array($request->getClientIp(), $ipAuthorize) && !$user->getAllIp() ){
                    printf("Votre adresse IP n'est pas dans la liste des adresses IP Autorisées");
                    die();
                }

                $user_activity = new AdminActivity();
                $user_activity->setAdmin($user);
                $user_activity->setCreateAt(new \Datetime());
                $user_activity->setIp($request->getClientIp());
                $user_activity->setMethod($request->getMethod());

                $currentUrl = $request->headers->get('host').$this->requestStack->getCurrentRequest()->getRequestUri();
                $user_activity->setLien($currentUrl);
                $this->em->persist($user_activity);
                $this->em->flush();
            } 
        }
    }

}
