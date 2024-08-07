<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route('/', name: 'dashboard_url')]
    public function dashboard() : Response
    {
        return $this->render('main/dashboard.html.twig');
    }
}
