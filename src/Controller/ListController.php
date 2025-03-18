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

    #[Route('/add_new_row', name: 'addNewRow_url', methods: ['POST'])]
    public function addNewRow(
        RequestHandlerService $requestHandlerService,
        EntityFactoryService $entityFactoryService,
        EntityPersistenceService $entityPersistenceService,
        GetTableDataService $getTableDataService,
        Request $request
    ): JsonResponse
    {
        $parameters = $requestHandlerService->getParametersFromRequest($request);
        $tableId = $parameters['table_id'] ?? null;

        if (!$tableId) {
            return new JsonResponse(['success' => false, 'error' => 'Missing table ID'], Response::HTTP_BAD_REQUEST);
        }

        $entityClass = $getTableDataService->getEntityNameByTableId($tableId);

        if (!$entityClass) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid table ID'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $newRow = $entityFactoryService->createEntity($entityClass, $parameters);
            $entityPersistenceService->saveEntity($newRow);

            // Prepare the response data
            $responseData = [];
            foreach ($parameters as $key => $value) {
                $responseData[$key] = $value;
            }
            $responseData['id'] = $newRow->getId(); // Ensure your entity has a getId() method

            return new JsonResponse(['success' => true, 'data' => $responseData], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/update_row/{id}', name: 'update_row_url', methods: ['POST'])]
    public function updateRow(
        int $id,
        RequestHandlerService $requestHandlerService,
        GetTableDataService $getTableDataService,
        EntityManagerInterface $entityManager,
        EntityPersistenceService $entityPersistenceService,
        Request $request
    ): JsonResponse
    {
        // Retrieve parameters from the request
        $parameters = $requestHandlerService->getParametersFromRequest($request);
        $tableId = $parameters['table_id'] ?? null;

        if (!$tableId) {
            return new JsonResponse(['success' => false, 'error' => 'Missing table ID'], Response::HTTP_BAD_REQUEST);
        }

        // Get the entity class based on table ID
        $entityClass = $getTableDataService->getEntityNameByTableId($tableId);

        if (!$entityClass) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid table ID'], Response::HTTP_BAD_REQUEST);
        }

        // Find the entity by ID
        $repository = $entityManager->getRepository($entityClass);
        $entity = $repository->find($id);

        if (!$entity) {
            return new JsonResponse(['success' => false, 'error' => 'Entity not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            // Update entity properties with new data
            foreach ($parameters as $field => $value) {
                if ($field === 'id' || $field === 'table_id') {
                    continue; // Skip updating the ID and table_id fields
                }

                // Assuming your entity has setter methods like setFieldName()
                $setter = 'set' . ucfirst($field);
                if (method_exists($entity, $setter)) {
                    $entity->$setter($value);
                }
            }

            // Persist the updated entity
            $entityPersistenceService->saveEntity($entity);

            // Prepare the response data
            $responseData = [];
            foreach ($parameters as $key => $value) {
                $responseData[$key] = $value;
            }
            $responseData['id'] = $entity->getId(); // Ensure your entity has a getId() method

            return new JsonResponse(['success' => true, 'data' => $responseData], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete_row', name: 'delete_row_url', methods: ['POST'])]
    public function deleteRow(GetTableDataService $getTableDataService, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Retrieve the ID and tableId from the request
        $id = $request->request->get('id');
        $tableId = $request->request->get('tableId');

        if (!$id) {
            return new JsonResponse(['success' => false, 'error' => 'Missing ID'], Response::HTTP_BAD_REQUEST);
        }

        $entityName = $getTableDataService->getEntityNameByTableId($tableId);
        if (!$entityName) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid entity'], Response::HTTP_BAD_REQUEST);
        }

        // Find the entity by ID
        $repository = $entityManager->getRepository($entityName);
        $entity = $repository->find($id);

        if (!$entity) {
            return new JsonResponse(['success' => false, 'error' => 'Entity not found'], Response::HTTP_NOT_FOUND);
        }

        // Remove the entity from the database
        $entityManager->remove($entity);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Entity deleted successfully']);
    }
}