<?php
namespace App\Service;

use Carbon\Carbon;
use App\Entity\User;
use App\Entity\Portrait;
use App\Entity\PortraitUser;
use App\Service\NotificationService;
use App\Repository\PortraitRepository; 
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PortraitUserRepository; 
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class PortraitService{

    private $em;
    private $portraitRepository;
    private $portraitUserRepository;
    private $router;
    private $container;
    
    public function __construct(EntityManagerInterface $em, PortraitRepository $portraitRepository, PortraitUserRepository $portraitUserRepository, NotificationService $notification_s, UrlGeneratorInterface $router, ContainerInterface $container){
        $this->em = $em;
        $this->portraitRepository = $portraitRepository;
        $this->portraitUserRepository = $portraitUserRepository;
        $this->router = $router;
        $this->container = $container;
        setlocale(LC_TIME, 'fr_FR');
        \Carbon\Carbon::setLocale('fr');
    }

    public function addCountToPortrait($user, $code){
        $portraits = $this->portraitRepository->findBy(['action'=>$code]);
        foreach ($portraits as $portrait) {
            $portraitUser = $this->portraitUserRepository->findOneBy(['portrait'=>$portrait,'user'=>$user]) ?? new PortraitUser();
            $portraitUser->setUser($user);
            $portraitUser->setPortrait($portrait);
            $portraitUser->addPoint();
            $this->em->persist($portraitUser);
            $this->em->flush();
            if(!$portraitUser->getShowed() && $portraitUser->getPoint()>=$portrait->getPoint()){
                /**
                 * @var NotificationService $notification_s 
                 */
                $notification_s = $this->container->get('App\Service\NotificationService');
                $notification_s->newPortrait(
                    $user,
                    $portrait,
                    $portraitUser,
                    $this->router->generate('badges_index', ['username'=>$user->getUsername()])
                );
            }
        }
    }


    public function getPortraitForUser(User $user){

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getId();
            },
        ];
        $normalizers = [new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];

        $serializer = new Serializer($normalizers, $encoders);

        $portraits = $this->portraitRepository->findBy(['active'=>true]);
        $portraitsArray = [];

        $base_portrait_url = $this->router->generate('home')."images/portraits/";

        foreach ($portraits as $portrait) {

            $portraitUser = $this->createPortraitUser($user, $portrait);
            $portraitsArray[] = [
                "id"=>$portrait->getId(),
                "title"=>$portrait->getTitle(),
                "description"=>$portrait->getDescription(),
                "point"=>$portrait->getPoint(),
                "point_two"=>$portrait->getPointTwo(),
                "point_three"=>$portrait->getPointThree(),
                "action"=>$portrait->getAction(),
                "type"=>$portrait->getType(),
                "avatar"=>$base_portrait_url."{$portrait->getAvatar()}",
                "avatar_mini"=>$base_portrait_url."{$portrait->getAvatarMini()}",
                "avatar_two"=>$base_portrait_url."{$portrait->getAvatarTwo()}",
                "avatar_two_mini"=>$base_portrait_url."{$portrait->getAvatarTwoMini()}",
                "avatar_three"=>$base_portrait_url."{$portrait->getAvatarThree()}",
                "avatar_three_mini"=>$base_portrait_url."{$portrait->getAvatarThreeMini()}",
                "self"=>[
                    "id"=>$portraitUser->getId(),
                    "point"=>$portraitUser->getPoint(),
                    "showed"=>$portraitUser->getShowed(),
                    "showed_at"=> $portraitUser->getShowedAt() ? $portraitUser->getShowedAt()->format('Y-m-d H:i:s') : null,
                    "showed_at_diff"=> $portraitUser->getShowedAt() ? (new Carbon($portraitUser->getShowedAt()))->diffForHumans() : null,
                    "displayed"=>$portraitUser->getDisplayed(),
                    "priority"=>$portraitUser->getPriority(),
                    "active"=>$user->getPortrait() && $user->getPortrait()->getId() == $portrait->getId() ? true : false,
                ]
            ];
        }

        $portraitsArrayTemp = $portraitsArray;
        $portraitsArray = [];
        foreach ($portraitsArrayTemp as $portrait) {
            if(is_null($portrait['self']['priority']))
                $portrait['self']['priority'] = count($portraitsArrayTemp);
            $portraitsArray[] = $portrait;
        }

        uasort($portraitsArray, function($portrait_a, $portrait_b) {
            if ($portrait_a['self']['priority'] == $portrait_b['self']['priority']) {
                return 0;
            }
            return ($portrait_a['self']['priority'] < $portrait_b['self']['priority']) ? -1 : 1;
        });

        $portraitsArrayTemp = $portraitsArray;
        $portraitsArrayA = [];
        $portraitsArrayB = [];
        foreach ($portraitsArrayTemp as $portrait) {
            if($portrait['self']['point'] >= $portrait['point'])
                $portraitsArrayA[] = $portrait;
            else
                $portraitsArrayB[] = $portrait;
        }

        $portraitsArray = array_merge($portraitsArrayA, $portraitsArrayB);

        return $portraitsArray;

    }

    public function getActivatedPortraitsForUser($user){
        $portraits = $this->getPortraitForUser($user);
        $portraitsArray = [];
        foreach ($portraits as $portrait) {
            if($portrait['self']['point'] >= $portrait['point'])
                $portraitsArray[] = $portrait;
        }
        return $portraitsArray;
    }


    public function createPortraitUser($user, $portrait){
        
        $portraitUser = $this->portraitUserRepository->findOneBy(['portrait'=>$portrait,'user'=>$user]);

        if(!$portraitUser){
            $portraitUser = new PortraitUser();

            $portraitUser->setUser($user);
            $portraitUser->setPortrait($portrait);
            $this->em->persist($portraitUser);

            $this->em->flush();
        }

        return $portraitUser;
    }

    public function countTotalPortraits(){
        return $this->portraitRepository->count([]);
    }

}
