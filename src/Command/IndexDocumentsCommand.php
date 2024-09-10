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

        // Delete the existing indexes
        if ($this->client->indices()->exists(['index' => 'contacts'])->asBool()) {
            $this->client->indices()->delete(['index' => 'contacts']);
        }

        if ($this->client->indices()->exists(['index' => 'emails'])->asBool()) {
            $this->client->indices()->delete(['index' => 'emails']);
        }

        if ($this->client->indices()->exists(['index' => 'events'])->asBool()) {
            $this->client->indices()->delete(['index' => 'events']);
        }

        if ($this->client->indices()->exists(['index' => 'messages'])->asBool()) {
            $this->client->indices()->delete(['index' => 'messages']);
        }

        if ($this->client->indices()->exists(['index' => 'notes'])->asBool()) {
            $this->client->indices()->delete(['index' => 'notes']);
        }

        $output->writeln('<info>Deleted existing indexes</info>');

        // Fetch and prepare Contacts documents
        $contacts = $this->entityManager->getRepository(Contacts::class)->findAll();

        $output->writeln('');
        $output->writeln(count($contacts) . " contacts found...");

        foreach ($contacts as $contact) {
            $content = implode("', '", [
                $contact->getName(),
                $contact->getSurname(),
                $contact->getEmail(),
                $contact->getPhone()
            ]);

            // Process the concatenated content
            $entities = $this->nlpProcessorService->processText($content);

            $documents[] = [
                'index' => 'contacts',
                'body' => [
                    'name' => $contact->getName(),
                    'surname' => $contact->getSurname(),
                    'email' => $contact->getEmail(),
                    'phone' => $contact->getPhone(),
                    'entities' => $entities,
                ]
            ];
        }

        // EMAILS
        $output->writeln('Emails...');
        $emails = $this->entityManager->getRepository(Emails::class)->findAll();
        $output->writeln(count($emails) . " emails found...");

        foreach ($emails as $email) {
            $content = implode("', '", [
                'sender' => $email->getSender(),
                'receiver' => $email->getReceiver(),
                'subject' => $email->getSubject(),
                'message' => $email->getMessage(),
            ]);

            $entities = $this->nlpProcessorService->processText($content);
            $documents[] = [
                'index' => 'emails',
                'body' => [
                    'sender' => $email->getSender(),
                    'receiver' => $email->getReceiver(),
                    'subject' => $email->getSubject(),
                    'message' => $email->getMessage(),
                    'entities' => $entities,
                ]
            ];
        }

        // EVENTS
        $output->writeln('Events...');
        $events = $this->entityManager->getRepository(Events::class)->findAll();
        $output->writeln(count($events) . " events found...");

        foreach ($events as $event) {
            $content = implode("', '", [
                'title' => $event->getTitle(),
                'subtitle' => $event->getSubtitle(),
                'note' => $event->getNote(),
            ]);

            $entities = $this->nlpProcessorService->processText($content);
            $documents[] = [
                'index' => 'events',
                'body' => [
                    'title' => $event->getTitle(),
                    'subtitle' => $event->getSubtitle(),
                    'note' => $event->getNote(),
                    'entities' => $entities,
                ]
            ];
        }

        // MESSAGES
        $output->writeln('Messages...');
        $messages = $this->entityManager->getRepository(Messages::class)->findAll();
        $output->writeln(count($messages) . " messages found...");

        foreach ($messages as $message) {
            $content = implode("', '", [
                'sender' => $message->getSender(),
                'message' => $message->getMessage(),
                'receiver' => $message->getReceiver(),
            ]);

            $entities = $this->nlpProcessorService->processText($content);
            $documents[] = [
                'index' => 'messages',
                'body' => [
                    'sender' => $message->getSender(),
                    'message' => $message->getMessage(),
                    'receiver' => $message->getReceiver(),
                    'entities' => $entities,
                ]
            ];
        }

        // NOTES
        $output->writeln('Notes...');
        $notes = $this->entityManager->getRepository(Notes::class)->findAll();
        $output->writeln(count($notes) . " notes found...");

        foreach ($notes as $note) {
            $content = implode("', '", [
                'note' => $note->getNote(),
            ]);

            $entities = $this->nlpProcessorService->processText($content);
            $documents[] = [
                'index' => 'notes',
                'body' => [
                    'note' => $note->getNote(),
                    'entities' => $entities,
                ]
            ];
        }

        $output->writeln('Indexing documents into Elasticsearch...');

        // Index documents into Elasticsearch
        foreach ($documents as $document) {
            $params = [
                'index' => $document['index'],
                'body'  => $document['body']
            ];
            $this->client->index($params);
        }

        $output->writeln('');
        $output->writeln('<info>Done!</info>');

        return Command::SUCCESS;
    }
}
