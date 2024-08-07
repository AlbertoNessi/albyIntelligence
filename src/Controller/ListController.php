<?php

namespace App\Controller;

use App\Entity\Contacts;
use App\Entity\Emails;
use App\Entity\Events;
use App\Entity\Messages;
use App\Entity\Notes;
use App\Services\EntityFactoryService;
use App\Services\EntityPersistenceService;
use App\Services\GetTableDataService;
use App\Services\RequestHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

        return $this->render('list/list.html.twig', [
            'list' => $id,
            'columns' => $columns,
            'tableName' => $tableName,
            'table_id' => $id,
            'data' => $data,
        ]);
    }

    #[Route('/add_new_row', name: 'addNewRow_url')]
    public function addNewRow(
        RequestHandlerService $requestHandlerService,
        EntityFactoryService $entityFactoryService,
        EntityPersistenceService $entityPersistenceService,
        GetTableDataService $getTableDataService,
        Request $request
    ): Response
    {
        $parameters = $requestHandlerService->getParametersFromRequest($request);
        $entityClass = $getTableDataService->getEntityNameByTableId($parameters['table_id']);
        /*$entityClass = $parameters['entityClass'];
        unset($parameters['entityClass']); // Remove entityClass from parameters*/

        try {
            $newRow = $entityFactoryService->createEntity($entityClass, $parameters);
            $entityPersistenceService->saveEntity($newRow);

            return new Response("Success!");
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
