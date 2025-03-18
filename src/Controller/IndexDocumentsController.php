<?php

namespace App\Controller;

use App\Entity\Contacts;
use App\Entity\Documentation;
use App\Entity\Emails;
use App\Entity\Events;
use App\Entity\LastIndexUpdate;
use App\Entity\Locations;
use App\Entity\Messages;
use App\Entity\Notes;
use App\Entity\Reminders;
use App\Entity\Tasks;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Services\SemanticIndexService;
use App\Services\AIElasticSearchService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class IndexDocumentsController extends AbstractController
{
    private AIElasticSearchService $elasticSearchService;
    private SemanticIndexService $semanticIndexService;

    public function __construct(AIElasticSearchService $elasticSearchService, SemanticIndexService $semanticIndexService) {
        $this->elasticSearchService = $elasticSearchService;
        $this->semanticIndexService = $semanticIndexService;
    }

    /**
     * @throws AuthenticationException
     * @throws Exception
     */
    #[Route('/indexDocuments', name: 'indexDocuments_url')]
    public function indexDocuments(SemanticIndexService $semanticIndexService, LoggerInterface $logger, Request $request): JsonResponse|int
    {
        ini_set('max_execution_time', 240);

        try {
            // Validate CSRF token
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('update_index', $token)) {
                return new JsonResponse([
                    'message' => 'Invalid CSRF token.'
                ], Response::HTTP_FORBIDDEN);
            }

            $client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();

            $indices = [
                'contacts',
                'emails',
                'events',
                'messages',
                'notes',
                'reminders',
                'tasks',
                'locations',
                'documentation'
            ];

            // Delete existing indices
            foreach ($indices as $index) {
                try {
                    if ($semanticIndexService->checkIndexExistence($index)) {
                        $response = $semanticIndexService->deleteExistingIndex($index);

                        if ($response['acknowledged'] !== true) {
                            $logger->error("Error during index delete");

                            throw new \RuntimeException("Error on line: " . __LINE__, 500);
                        }
                    }
                } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
                    $logger->error("LINE: " . $e->getLine() . " - MESSAGE: " . $e->getMessage() . " - TRACE: " . $e->getTraceAsString() . " - FILE: " . $e->getFile());

                    return new JsonResponse(['message' => "Si è verificato un errore"], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $documents = [];

            $semanticIndexService->processEntities(
                Contacts::class,
                'contacts',
                ['name', 'surname', 'email', 'phone'],
                ['name', 'surname', 'email', 'phone'],
                $documents,
            );

            $semanticIndexService->processEntities(
                Emails::class,
                'emails',
                ['sender', 'receiver', 'subject', 'message'],
                ['sender', 'receiver', 'subject', 'message'],
                $documents
            );

            $semanticIndexService->processEntities(
                Events::class,
                'events',
                ['title', 'subtitle', 'note'],
                ['title', 'subtitle', 'note'],
                $documents
            );

            $semanticIndexService->processEntities(
                Messages::class,
                'messages',
                ['sender', 'message', 'receiver'],
                ['sender', 'message', 'receiver'],
                $documents
            );

            $semanticIndexService->processEntities(
                Notes::class,
                'notes',
                ['note'],
                ['note'],
                $documents
            );

            $semanticIndexService->processEntities(
                Reminders::class,
                'reminders',
                ['dueDate', 'priority', 'task'],
                ['dueDate', 'priority', 'task'],
                $documents
            );

            $semanticIndexService->processEntities(
                Tasks::class,
                'tasks',
                ['name', 'dueDate', 'priority', 'status'],
                ['name', 'dueDate', 'priority', 'status'],
                $documents
            );

            $semanticIndexService->processEntities(
                Locations::class,
                'locations',
                ['name', 'address', 'city', 'province', 'region'],
                ['name', 'address', 'city', 'province', 'region'],
                $documents
            );

            $semanticIndexService->processEntities(
                Documentation::class,
                'documentation',
                ['title', 'content', 'section', 'type'],
                ['title', 'content', 'section', 'type'],
                $documents
            );

            $logger->info('Indexing documents into Elasticsearch using Bulk API...');

            // Index documents into Elasticsearch using Bulk API
            if (!empty($documents)) {
                $bulkParams = ['body' => []];

                foreach ($documents as $document) {
                    $bulkParams['body'][] = [
                        'index' => [
                            '_index' => $document['index']
                        ]
                    ];
                    $bulkParams['body'][] = $document['body'];
                }

                try {
                    $response = $client->bulk($bulkParams);

                    if ($response['errors']) {
                        foreach ($response['items'] as $item) {
                            if (isset($item['index']['error'])) {
                                $error = $item['index']['error'];

                                $logger->error('ERROR - Error indexing document - Error type: ' . $error['type'] . " - Error reason: " .  $error['reason']);
                            }
                        }
                        $logger->error('ERROR - Some documents failed to index');
                    } else {
                        $logger->info('SUCCESS - All documents indexed successfully using Bulk API!');
                    }

                    // Refresh indices to make documents searchable immediately
                    foreach ($indices as $index) {
                        if ($semanticIndexService->checkIndexExistence($index)) {
                            $client->indices()->refresh(['index' => $index]);
                        }
                    }
                } catch (\Exception $e) {
                    $logger->error("LINE: " . $e->getLine() . " - MESSAGE: " . $e->getMessage() . " - TRACE: " . $e->getTraceAsString() . " - FILE: " . $e->getFile());

                    return new JsonResponse(['message' => "Si è verificato un errore"], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                $logger->error('ERROR - No documents to index');
            }

            $logger->info('Done!');

            $response = [
                'message' => 'Done!'
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $exception) {
            $response = [
                'message' => 'Si è verificato un errore: ' . $exception->getMessage() . " on line: " . $exception->getLine() . " trace: " . $exception->getTraceAsString(),
            ];

            return new JsonResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Temporary test route
    #[Route('/test-elasticsearch', name: 'test_elasticsearch')]
    public function testElasticsearch(): JsonResponse
    {
        try {
            $client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();
            $info = $client->info();
            return new JsonResponse($info);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/log_index_update', name: 'logIndexUpdate_url', methods: ['POST'])]
    public function logIndexUpdate(
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        try {
            $submittedToken = $request->request->get('_token');
            $csrfToken = new CsrfToken('log_index_update', $submittedToken);

            if (!$csrfTokenManager->isTokenValid($csrfToken)) {
                return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
            }

            $existingLogs = $entityManager->getRepository(LastIndexUpdate::class)->findAll();
            if($existingLogs) {
                foreach ($existingLogs as $log) {
                    $log->setIsLast(false);
                    $entityManager->persist($log);
                }
                $entityManager->flush();
            }

            $lastIndexUpdate = new LastIndexUpdate();
            $lastIndexUpdate->setUpdatedAt(new \DateTimeImmutable());
            $lastIndexUpdate->setIsLast(true);

            $entityManager->persist($lastIndexUpdate);
            $entityManager->flush();

            return new JsonResponse([
                'code' => 'OK',
                'message' => 'Index update logged successfully',
            ], 200);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'code' => 'KO',
                'error' => "#" . $exception->getLine() . " - " . $exception->getMessage()
            ], 500);
        }
    }

    #[Route('/get_last_updated_time', name: 'getLastUpdatedTime_url', methods: ['GET'])]
    public function getLastUpdatedTime(EntityManagerInterface $entityManager): JsonResponse
    {
        $repository = $entityManager->getRepository(LastIndexUpdate::class);

        $lastUpdate = $repository->findOneBy(['isLast' => true]);

        if ($lastUpdate) {
            $updatedAt = $lastUpdate->getUpdatedAt();

            $formattedDate = $updatedAt->format('d-m-Y H:i:s');

            return new JsonResponse(['updatedAt' => $formattedDate]);
        } else {
            return new JsonResponse(['updatedAt' => null]);
        }
    }

    #[Route('/semantic_index/content', name: 'showSemanticIndexContent_url', methods: ['GET'])]
    public function showSemanticIndexContent(): Response
    {
        $indices = $this->semanticIndexService->getIndices();
        $documents = $this->elasticSearchService->getDocuments($indices);

        return $this->render('current_semantic_index_content.html.twig', [
            'indices' => $indices,
            'documents' => $documents,
            'table_id' => '',
            'tableName' => 'Mostra indice',
            'list' => ''
        ]);
    }
}
