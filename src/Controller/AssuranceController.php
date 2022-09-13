<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AssuranceController extends AbstractController
{
    /**
     * @Route("/assurance", name="assurance")
     */
    public function index()
    {
        return $this->render('assurance/index.html.twig', [
            'controller_name' => 'AssuranceController',
        ]);
    }
}
