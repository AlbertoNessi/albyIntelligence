<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListController extends AbstractController
{
    #[Route('/list/{id}', name: 'list_url')]
    public function list(int $id): Response
    {
        return $this->render('list/list.html.twig', [
            'list' => $id,
        ]);
    }
}
