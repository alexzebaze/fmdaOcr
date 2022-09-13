<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\Admin;
use App\Repository\AdminRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use App\Service\GlobalService;

class SecurityController extends AbstractController
{
    private $adminRepository;
    private $global_s;

    public function __construct(AdminRepository $adminRepository, GlobalService $global_s){
        $this->adminRepository = $adminRepository;
        $this->global_s = $global_s;
    }

    /**
     * @Route("/login", name="security_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logout()
    {
        return $this->redirectToRoute('security_login');
    }


    /**
     * @Route("/resetting/request", name="resetting_request", methods={"GET", "POST"})
     */
    public function resettingRequest(Request $request, MailerInterface $mailer){

        $email = "";
        if ($request->isMethod('post')) {
            $email = $request->request->get("email");
            $userExist = $this->adminRepository->findOneBy(['email'=>$email]);
            if(is_null($userExist)){

                $this->addFlash('error', "Aucun utilisateur n'existe avec cet email");

                return $this->render('security/resetting_request.html.twig', ['email' => $email]);
            }
            else{
                $entityManager = $this->getDoctrine()->getManager();

                $entreprise = $userExist->getEntreprise();

                $sender_email = "gestion@fmda.fr";
                $sender_name = "FMDA";

                if($entreprise){
                    $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
                    $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();
                }

                $token = $this->global_s->generateRandomString();
                $url = $this->generateUrl('resetting_reset', ['token'=>$token], UrlGeneratorInterface::ABSOLUTE_URL);
                $message = "Cliquer sur le lien suivant pour reinitialiser votre mot de passe ".$url;

                $userExist->setResetting($token);
                $entityManager->flush();

                $mail = (new Email())
                ->from(new Address($sender_email, $sender_name))
                ->to($email)
                ->subject('Reset Password')
                ->html($message);
                $mailer->send($mail);

                $this->addFlash('info', "Un email a été à l'adresse ".$email.". Il contient un lien sur lequel  vous devez cliquer pour reinitialiser votre mot de passe");
            }
        }

        return $this->render('security/resetting_request.html.twig', ['email' => $email]);
    }

    /**
     * @Route("/resetting/reset/{token}", name="resetting_reset", methods={"GET", "POST"})
     */
    public function resettingReset(UserPasswordEncoderInterface $passwordEncoder, Request $request, $token){

        $userExist = $this->adminRepository->findOneBy(['resetting'=>$token]);
        if(is_null($userExist)){
            $this->addFlash('error', "Le mot de passe a deja été reinitialisé avec ce token. Veuillez faire une nouvelle demande de reinitialisation de mot de passe");

            return $this->render('security/resetting_reset.html.twig', []);
        }
                
        if ($request->isMethod('post')) {
            $pass1 = $request->request->get("pass1");
            $pass2 = $request->request->get("pass2");
            if($pass1 != $pass2){
                $this->addFlash('error', "Les deux mots de passe doivent etre identique");
                return $this->render('security/resetting_reset.html.twig', []);
            }
            else{
                $userExist->setResetting(null);
                $password = $passwordEncoder->encodePassword($userExist, $pass1);
                $userExist->setPassword($password);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();

                $this->addFlash('success', "Mot de passe reinitialisé avec success. Veuillez vous authentifier");
                return $this->redirectToRoute('security_login');
                
            }

        }

        return $this->render('security/resetting_reset.html.twig', []);
    }
}
