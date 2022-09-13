<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CrosController extends AbstractController
{
    /**
     * @Route("/cros", name="cros")
     */
    public function index()
    {
        return $this->render('cros/index.html.twig', [
            'controller_name' => 'CrosController',
        ]);
    }
}
