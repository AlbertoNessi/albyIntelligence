<?php

namespace App\Services;

use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
use JsonException;
use Psr\Log\LoggerInterface;
use Elastic\Elasticsearch\Client;

class SemanticIndexService
{
    private EntityManagerInterface $entityManager;
    private NLPProcessorService $nlpProcessorService;
    private Client $client;
    private LoggerInterface $logger;

    /**
     * @throws AuthenticationException
     */
    public function __construct(EntityManagerInterface $entityManager, NLPProcessorService $nlpProcessorService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->nlpProcessorService = $nlpProcessorService;
        $this->client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();
        $this->logger = $logger;
    }

    /**
     * @throws JsonException
     */
    public function processEntities(string $entityClass, string $indexName, array $fields, array $contentFields, array &$documents): void
    {
        $entities = $this->entityManager->getRepository($entityClass)->findAll();
        $entityCount = count($entities);

        $this->logger->info("$entityCount $indexName found...");

        // Prevent index creation if no entities are found
        if ($entityCount === 0) {
            $this->logger->error("No entities found for $indexName. Skipping index creation.");

            return;
        }

        foreach ($entities as $entity) {
            $contentParts = [];
            foreach ($contentFields as $field) {
                $getter = 'get' . ucfirst($field);
                if (method_exists($entity, $getter)) {
                    $value = $entity->$getter();

                    if ($value instanceof DateTimeInterface) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    $contentParts[] = $value ?? '';
                } else {
                    $this->logger->error("Method $getter does not exist in " . get_class($entity));

                    $contentParts[] = '';
                }
            }
            $content = implode(" ', ' ", $contentParts);

            // Process the concatenated content
            $processedEntities = $this->nlpProcessorService->processText($content);

            // Build the document body
            $body = [];
            foreach ($fields as $field) {
                $getter = 'get' . ucfirst($field);
                if (method_exists($entity, $getter)) {
                    $value = $entity->$getter();
                    // Convert DateTimeInterface to string if necessary
                    if ($value instanceof DateTimeInterface) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $body[$field] = $value;
                } else {
                    $this->logger->error("Method $getter does not exist in " . get_class($entity));

                    $body[$field] = null;
                }
            }
            $body['entities'] = $processedEntities;

            $documents[] = [
                'index' => $indexName,
                'body'  => $body
            ];
        }
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function deleteExistingIndex(string $indexName): Elasticsearch|Promise
    {
        return $this->client->indices()->delete(['index' => $indexName]);
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function checkIndexExistence(string $indexName): bool
    {
        return $this->client->indices()->exists(['index' => $indexName])->asBool();
    }

    /**
     * Returns the indices and fields used for the ElasticSearch query.
     *
     * @return array
     */
    public function getIndicesAndFields(): array
    {
        return [
            'contacts' => ['name', 'surname', 'email', 'phone', 'entities.text'],
            'emails' => ['sender', 'receiver', 'subject', 'message', 'entities', 'entities.text'],
            'events' => ['title', 'subtitle', 'note'],
            'messages' => ['sender', 'message', 'receiver'],
            'notes' => ['note', 'receiver'],
        ];
    }

    /**
     * Returns the indices used to show the semantic index.
     *
     * @return array
     */
    public function getIndices(): array
    {
        return [
            'contacts',
            'emails',
            'events',
            'messages',
            'notes'
        ];
    }
}
