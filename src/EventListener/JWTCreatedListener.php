<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTCreatedListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }
        if ($user instanceof Utilisateur)

            $data['data'] = array(
                'id'        => $user->getUid(),
                'email'     => $user->getEmail(),
                'lastname'     => $user->getLastname(),
                'firstname'     => $user->getFirstname(),
                'roles'     => $user->getRoles(),
            );

        $event->setData($data);
    }       

}