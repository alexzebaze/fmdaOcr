<?php

/*
 * Listens to security related events like log-ins, failed logins, etc,
 * and sends them to ThisData.
 *
 */

namespace App\EventListener;

use App\Entity\Admin;
use App\Entity\HistoriqueConnexion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\AuthenticationEvents;  
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;  
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;  
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;  
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;  
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;  
use Symfony\Component\HttpFoundation\RequestStack;

class SecuritySubscriber implements EventSubscriberInterface  
{

    private $em;
    private $tokenStorage;
    private $authenticationUtils;
    private $session;
    private $router;
    private $flashBag;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage, AuthenticationUtils $authenticationUtils, SessionInterface $session, UrlGeneratorInterface $router, FlashBagInterface $flashBag,  RequestStack $requestStack)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationUtils = $authenticationUtils;
        $this->session = $session;
        $this->router = $router;
        $this->flashBag = $flashBag;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        );
    }

    public function onAuthenticationFailure( AuthenticationFailureEvent $event )
    {
        // $username = $this->authenticationUtils->getLastUsername();
        // $existingUser = $this->em->getRepository(Admin::class)->findOneBy(['email' => $username]);

        // if($existingUser){
        //     if($existingUser instanceof \App\Entity\Admin){
        //         $request = $event->getRequest();
        //         $historique = new HistoriqueConnexion();
        //         $historique->setAdmin($existingUser);
        //         $historique->setDateConnexion(new \DateTime());
        //         $historique->setCommentaire("Echec tentative connexion");
        //         $historique->setIp($request->getClientIp());
        //         $this->em->persist($historique);
        //         $this->em->flush();
        //     }
        // }
    }

    public function onSecurityInteractiveLogin( InteractiveLoginEvent $event )
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if($user instanceof \App\Entity\Admin){
            $request = $event->getRequest();

            $historique = new HistoriqueConnexion();
            $historique->setAdmin($user);
            $historique->setDateConnexion(new \DateTime());
            $historique->setCommentaire("Connexion Réussite");
            $historique->setIp($request->getClientIp());
            $historique->setOnline(true);
            $this->em->persist($historique);
            $this->em->flush();
        }
    }
}