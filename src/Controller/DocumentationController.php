<?php

namespace App\Controller;

use App\Services\GetTableDataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DocumentationController extends AbstractController
{
    #[Route('/documentation/show', name: 'showDocumentation_url', methods: ['GET'])]
    public function showDocumentation(EntityManagerInterface $entityManager, GetTableDataService $getTableDataService): Response
    {
        $id = 12;
        $columns = $getTableDataService->getColumnsByTableId($id);
        $tableName = $getTableDataService->getTableNameByTableId($id);
        $data = $getTableDataService->getTableDataByTableId($entityManager, $id);

        return $this->render('documentation_content.html.twig', [
            'list' => $id,
            'columns' => $columns,
            'tableName' => $tableName,
            'table_id' => $id,
            'data' => $data,
        ]);
    }
}
