<?php

namespace App\Controller;

use App\Entity\CalendarEvents;
use App\Entity\Contacts;
use App\Entity\Emails;
use App\Entity\Events;
use App\Entity\Locations;
use App\Entity\Messages;
use App\Entity\Notes;
use App\Entity\Notifications;
use App\Entity\Reminders;
use App\Entity\SearchHistory;
use App\Entity\Tasks;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Services\SemanticIndexService;

class IndexDocumentsController extends AbstractController
{
    /**
     * @throws AuthenticationException
     * @throws Exception
     */
    #[Route('/indexDocuments', name: 'indexDocuments_url')]
    public function indexDocuments(SemanticIndexService $semanticIndexService, LoggerInterface $logger): JsonResponse|int
    {
        ini_set('max_execution_time', 120);
        $client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();

        $documents = [];

        // List of indices to delete and reindex
        $indices = [
            'contacts',
            'emails',
            'events',
            'messages',
            'notes',
            'reminders',
            'tasks',
            'locations',
        ];

        // Delete existing indices
        foreach ($indices as $index) {
            try {
                if ($semanticIndexService->checkIndexExistance($index)) {
                    $response = $semanticIndexService->deleteExistingIndex($index);

                    if ($response['acknowledged'] !== true) {
                        $logger->error("Error during index delete");

                            throw new \RuntimeException("Error on line: " . __LINE__, 500);
                    }
                }
            } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
                return new JsonResponse("Error " . $e->getMessage() . " on line: " . $e->getLine(), 500);
            }
        }

        try {
            $semanticIndexService->processEntities(
                Contacts::class,
                'contacts',
                ['name', 'surname', 'email', 'phone'],
                ['name', 'surname', 'email', 'phone'],
                $documents,
            );

            // Process each entity type and collect documents
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
                CalendarEvents::class,
                'calendar_events',
                ['title', 'description', 'eventDate'],
                ['title', 'description', 'eventDate'],
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
                Notifications::class,
                'notifications',
                ['message', 'flagRead', 'action'],
                ['message', 'flagRead', 'action'],
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
                SearchHistory::class,
                'search_history',
                ['query', 'searchedAt', 'city', 'province', 'region'],
                ['name', 'address', 'city', 'province', 'region'],
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

                                $logger->error('ERROR - Error indexing document in ' . $error . ": " . $error['type'] . " - " .  $error['reason']);
                            }
                        }
                        $logger->error('ERROR - Some documents failed to index');
                    } else {
                        $logger->info('SUCCESS - All documents indexed successfully using Bulk API!');
                    }

                    // Refresh indices to make documents searchable immediately
                    foreach ($indices as $index) {
                        $client->indices()->refresh(['index' => $index]);
                    }
                } catch (\Exception $e) {
                    $logger->error('ERROR - Bulk indexing failed: ' . $e->getMessage());

                    return Command::FAILURE;
                }
            } else {
                $logger->error('ERROR - No documents to index');
            }

            $logger->info('Done!');

            $response = [
                'code' => 'OK',
                'message' => 'Done!'
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $exception) {
            $response = [
                'code' => 'ERROR',
                'message' => 'Si è verificato un errore: ' . $exception->getMessage() . " on line: " . $exception->getLine()
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        }
    }
}
