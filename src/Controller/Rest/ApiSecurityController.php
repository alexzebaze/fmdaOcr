<?php

namespace App\Controller\Rest;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations\Post;
use Swagger\Annotations as SWG;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Service\GlobalService;
use Symfony\Component\Mime\Address;
use App\Entity\Entreprise;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class ApiSecurityController extends AbstractController
{
    private $em;
    private $global_s;
    private $session;

    public function __construct(
        GlobalService $global_s,
        EntityManagerInterface $em,
        SessionInterface $session
    )
    {
        $this->em = $em;
        $this->global_s = $global_s;
        $this->session = $session;
    }

     /*
     *
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         type="string",
     *     ),
     */
    /**
     * Reset password
     * @SWG\Post(
     *     tags={"password_forgot"},
     *     description="Mot de passe oublié",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         type="string",
     *         required=true,
     *         @SWG\Schema(
     *              @SWG\Property(property="email", type="string"),
     *         )
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Password changed",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Invalid JSON",
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Erreur",
     *     ),
     * )
     * @Route("/password/forgot", methods={"POST"})
     * @param Request $request
     * @param MailerInterface $mailer
     * @return JsonResponse
     */
    public function forgotPassword(Request $request, MailerInterface $mailer):JsonResponse
    {

        $error = null; $customMailer = null;

        $data = json_decode($request->getContent(), true);

        if(!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
            return new JsonResponse(array('error' => "Veuillez envoyer un email valide s'il vous plaît"), Response::HTTP_BAD_REQUEST);
        }
        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['email' => $data['email']]);
        if(!$user){
            return new JsonResponse(array('error' => "Cet utilisateur n'existe pas"), Response::HTTP_BAD_REQUEST);
        }

        $entreprise = $user->getEntreprise();
        try{
            $customMailer = $this->global_s->initEmailTransport($entreprise->getId());
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if(!is_null($customMailer) && is_null($error)){

            $password = $this->randomPassword();
            $user->setPassword($password);

            $this->em->persist($user);
            $this->em->flush();

            $sender_email = !is_null($entreprise->getSenderMail()) ? $entreprise->getSenderMail() : 'gestion@fmda.fr';
            $sender_name = !is_null($entreprise->getSenderName()) ? $entreprise->getSenderName() : $entreprise->getName();
                
            $email = (new Email())
                ->from(new Address($sender_email, $sender_name))
                ->to($data['email'])
                ->subject('Mot de passe oublié')
                ->html('Bonjour, vous avez oublié votre mot de passe et votre nouveau mot de passe est : '.$password);

            try {
                $send = $customMailer->send($email);
                return new JsonResponse(array('status' => 200, 'response'=>"Le nouveau mot de passe a été envoyé avec succès"), Response::HTTP_OK);

            } catch (\Exception $ex) { 
                $error = $ex->getMessage(); 
                return new JsonResponse(array('error' => $error), Response::HTTP_BAD_REQUEST);
            }
        }
        else{
            if(!is_null($error))
                return new JsonResponse(array('error' => $error), Response::HTTP_BAD_REQUEST);
            else
                return new JsonResponse(array('error' => "Veuillez configurer les informations d'envoie de mail de cette entreprise"), Response::HTTP_BAD_REQUEST);
        }
    }

    
    public function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

}