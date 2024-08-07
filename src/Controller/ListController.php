<?php

namespace App\Controller;

use App\Services\GetTableDataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListController extends AbstractController
{
    #[Route('/list/{id}', name: 'list_url')]
    public function list(EntityManagerInterface $entityManager, int $id, GetTableDataService $getTableDataService): Response
    {
        $columns = $getTableDataService->getColumnsByTableId($id);
        $tableName = $getTableDataService->getTableNameByTableId($id);
        $data = $getTableDataService->getTableDataByTableId($entityManager, $id);
        $entityName = $getTableDataService->getEntityNameByTableId($id);

        return $this->render('list/list.html.twig', [
            'list' => $id,
            'columns' => $columns,
            'tableName' => $tableName,
            'table_id' => $id,
            'data' => $data,
            'entityName' => $entityName
        ]);
    }

    #[Route('/list/add_new_row', name: 'addNewRow_url')]
    public function addNewRow(EntityManagerInterface $entityManager)
    {

        /*$newRow = new $entityName();*/


    }
}
