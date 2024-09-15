<?php

namespace App\Controller;

use App\Services\EntityFactoryService;
use App\Services\EntityPersistenceService;
use App\Services\GetTableDataService;
use App\Services\RequestHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        try {
            $newRow = $entityFactoryService->createEntity($entityClass, $parameters);
            $entityPersistenceService->saveEntity($newRow);

            return new Response("Success!");
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete_row', name: 'delete_row_url', methods: ['POST'])]
    public function deleteRow(GetTableDataService $getTableDataService, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Retrieve the ID from the request
        $id = $request->request->get('id');
        $tableId = $request->request->get('tableId');

        if (!$id) {
            return new JsonResponse(['error' => 'Missing ID'], 400);
        }

        $entityName = $getTableDataService->getEntityNameByTableId($tableId);
        if (!$entityName) {
            return new JsonResponse(['error' => 'Invalid entity'], 400);
        }

        // Find the entity by ID
        $repository = $entityManager->getRepository($entityName);
        $entity = $repository->find($id);

        if (!$entity) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }

        // Remove the entity from the database
        $entityManager->remove($entity);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Entity deleted successfully']);
    }

}
