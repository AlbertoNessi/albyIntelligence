<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard_url')]
    public function dashboard(): Response
    {
        return $this->render('main/dashboard.html.twig', [
            'table_id' => '0', 
            'list' => '', 
            'tableName' => 'Home'
        ]);
    }
}
