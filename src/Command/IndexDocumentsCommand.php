<?php

namespace App\Command;

use App\Entity\Contacts;
use App\Entity\Emails;
use App\Entity\Events;
use App\Entity\Messages;
use App\Entity\Notes;
use Elastic\Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\NLPProcessor;

#[AsCommand(
    name: 'ai:gpt:indexDocuments',
    description: 'Indexes data into Elasticsearch.'
)]
class IndexDocumentsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private NLPProcessor $nlpProcessorService;
    private \Elastic\Elasticsearch\Client $client;

    public function __construct(EntityManagerInterface $entityManager, NLPProcessor $nlpProcessorService)
    {
        $this->entityManager = $entityManager;
        $this->nlpProcessorService = $nlpProcessorService;
        $this->client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documents = [];

        $output->writeln('<info>Fetching and preparing documents...</info>');

        // List of indices to delete and reindex
        $indices = ['contacts', 'emails', 'events', 'messages', 'notes'];

        // Delete existing indices
        foreach ($indices as $index) {
            if ($this->client->indices()->exists(['index' => $index])->asBool()) {
                $response = $this->client->indices()->delete(['index' => $index]);
                if ($response['acknowledged'] !== true) {
                    $output->writeln("<error>Failed to delete index: {$index}</error>");
                    return Command::FAILURE;
                }
                $output->writeln("<info>Deleted index: {$index}</info>");
            }
        }

        // Process each entity type and collect documents
        $this->processEntities(
            Contacts::class,
            'contacts',
            ['name', 'surname', 'email', 'phone'],
            ['name', 'surname', 'email', 'phone'],
            $documents,
            $output
        );

        $this->processEntities(
            Emails::class,
            'emails',
            ['sender', 'receiver', 'subject', 'message'],
            ['sender', 'receiver', 'subject', 'message'],
            $documents,
            $output
        );

        $this->processEntities(
            Events::class,
            'events',
            ['title', 'subtitle', 'note'],
            ['title', 'subtitle', 'note'],
            $documents,
            $output
        );

        $this->processEntities(
            Messages::class,
            'messages',
            ['sender', 'message', 'receiver'],
            ['sender', 'message', 'receiver'],
            $documents,
            $output
        );

        $this->processEntities(
            Notes::class,
            'notes',
            ['note'],
            ['note'],
            $documents,
            $output
        );

        $output->writeln('Indexing documents into Elasticsearch using Bulk API...');

        // Index documents into Elasticsearch using Bulk API
        if (!empty($documents)) {
            $bulkParams = ['body' => []];

            foreach ($documents as $document) {
                $bulkParams['body'][] = [
                    'index' => [
                        '_index' => $document['index'],
                        // '_id' => optional, if you want to set a specific ID
                    ]
                ];
                $bulkParams['body'][] = $document['body'];
            }

            try {
                $response = $this->client->bulk($bulkParams);

                if ($response['errors']) {
                    foreach ($response['items'] as $item) {
                        if (isset($item['index']['error'])) {
                            $error = $item['index']['error'];
                            $output->writeln("<error>Error indexing document in {$item['index']['_index']}: {$error['type']} - {$error['reason']}</error>");
                        }
                    }
                    $output->writeln("<error>Some documents failed to index. Please check the errors above.</error>");
                } else {
                    $output->writeln("<info>All documents indexed successfully using Bulk API.</info>");
                }

                // Refresh indices to make documents searchable immediately
                foreach ($indices as $index) {
                    $this->client->indices()->refresh(['index' => $index]);
                }
            } catch (\Exception $e) {
                $output->writeln("<error>Bulk indexing failed: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }
        } else {
            $output->writeln("<comment>No documents to index.</comment>");
        }

        $output->writeln('');
        $output->writeln('<info>Done!</info>');

        return Command::SUCCESS;
    }

    /**
     * Processes entities of a given type and appends documents to the provided array.
     *
     * @param string $entityClass The fully qualified class name of the entity.
     * @param string $indexName The Elasticsearch index name.
     * @param array $fields The fields to include in the document body.
     * @param array $contentFields The fields to concatenate for NLP processing.
     * @param array  &$documents Reference to the documents array to append to.
     * @param OutputInterface $output The console output interface for logging.
     *
     * @return void
     * @throws \JsonException
     */
    private function processEntities(
        string $entityClass,
        string $indexName,
        array $fields,
        array $contentFields,
        array &$documents,
        OutputInterface $output
    ): void {
        $entities = $this->entityManager->getRepository($entityClass)->findAll();
        $entityCount = count($entities);
        $output->writeln("{$entityCount} {$indexName} found...");

        foreach ($entities as $entity) {
            // Prepare content for NLP processing
            $contentParts = [];
            foreach ($contentFields as $field) {
                $getter = 'get' . ucfirst($field);
                if (method_exists($entity, $getter)) {
                    $value = $entity->$getter();
                    // Handle potential null values
                    $contentParts[] = $value ?? '';
                } else {
                    $output->writeln("<error>Method {$getter} does not exist in " . get_class($entity) . "</error>");
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
                    $body[$field] = $entity->$getter();
                } else {
                    $output->writeln("<error>Method {$getter} does not exist in " . get_class($entity) . "</error>");
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
}
