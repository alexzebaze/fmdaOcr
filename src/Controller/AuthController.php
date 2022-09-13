<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Repository\UserRepository;
use App\Entity\Utilisateur;
use App\Entity\Entreprise;

class AuthController extends AbstractController
{
    /** @var UserRepository $userRepository */
    private $userRepository;

    /**
     * AuthController Constructor
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register new user
     * @param Request $request
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function register(Request $request)
    {
        $newUserData['email']    = $request->get('email');
        $newUserData['password'] = $request->get('password');

        $user = $this->userRepository->createNewUser($newUserData);

        return new Response(sprintf('User %s créé avec succès ', $user->getUsername()));
    }

    /**
     * Register new utilisateur
     * @param Request $request
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function registerCustomer(Request $request)
    {
        //$me = $this->getUser();
        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find(['id' => 1]);

        $user = new Utilisateur();
        $user->setEntreprise($entreprise);

        $user->setLastname($request->get('lastname'));
        $user->setFirstname($request->get('firstname'));
        $user->setEmail($request->get('email'));

        $user->setPassword($request->get('password'));
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new Response(sprintf('User %s créé avec succès ', $user->getUsername()));
    }

    /**
     * api route redirects
     * @return Response
     */
    public function api()
    {
        return new Response(sprintf("Connecté en tant que %s", $this->getUser()->getUsername()));
    }
}