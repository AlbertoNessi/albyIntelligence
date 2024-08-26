<?php

namespace App\Command;

use Elasticsearch\ClientBuilder;
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
    private $client;

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
        if ($this->client->indices()->exists(['index' => 'customers'])) {
            $this->client->indices()->delete(['index' => 'customers']);
        }

        if ($this->client->indices()->exists(['index' => 'order'])) {
            $this->client->indices()->delete(['index' => 'order']);
        }

        if ($this->client->indices()->exists(['index' => 'products'])) {
            $this->client->indices()->delete(['index' => 'products']);
        }

        $output->writeln('<info>Deleted existing indexes</info>');

        // Fetch and prepare Customer documents
        $customers = $this->entityManager->getRepository(CRM_Customers::class)->findAll();

        $output->writeln('');
        $output->writeln(count($customers) . " customers found...");

        foreach ($customers as $customer) {
            $content = implode("', '", [
                $customer->getCodiceAnagrafica(),
                $customer->getDescrizioneAnagrafica(),
                $customer->getIndirizzoMail(),
                $customer->getLocalita(),
                $customer->getProvincia(),
                $customer->getCodiceNazione(),
                $customer->getBanca(),
                $customer->getCodiceFiscale(),
                $customer->getIban(),
                $customer->getTelefono(),
            ]);

            // Process the concatenated content
            $entities = $this->nlpProcessorService->processText($content);

            $orders = $this->entityManager->getRepository(ORD_Orders::class)->findBy(['customer_code' => $customer->getCodiceAnagrafica()]);

            $orderDocs = [];
            $orderRowsDocs = [];
            foreach ($orders as $order) {
                $orderDocs[] = [
                    'orderNumber' => $order->getOrderNumber(),
                    'order_date' => $order->getDataOrdine()->format('d-m-Y'),
                    'order_year' => $order->getOrderYear(),
                    'customer_code' => $order->getCustomerCode(),
                    'customer_description' => $order->getCustomerDescription(),
                    'delivery_code' => $order->getDeliveryCode(),
                ];

                $rows = $this->entityManager->getRepository(ORD_OrderRows::class)->findBy(['id_ordine' => $order]);
                if ($rows) {
                    foreach ($rows as $row) {
                        $orderRowsDocs[] = [
                            'orderNumber' => $order->getOrderNumber(),
                            'itemCode' => $row->getCodiceArticolo(),
                            'itemDescription' => $row->getDescrizioneArticolo(),
                            'quantity' => $row->getQuantitaVendita(),
                            'listPrice' => $row->getPrezzoListino(),
                            'calculatedPrice' => $row->getPrezzoCalcolato(),
                            'valore_riga' => $row->getValoreRiga(),
                            'discount1' => $row->getSconto1(),
                            'discount2' => $row->getSconto2(),
                            'deliveryDate' => $row->getDataConsegna() ? $row->getDataConsegna()->format('d-m-Y') : "",
                            'paymentCode' => $row->getCodicePagamento(),
                            'paymentDescription' => $row->getDescrizionePagamento(),
                            'VatCode' => $row->getCodiceIva(),
                        ];
                    }
                }
            }

            $contacts = $this->entityManager->getRepository(CRM_Contacts::class)->findBy(['codice_anagrafica' => $customer->getCodiceAnagrafica()]);
            $contactsDocs = [];
            if ($contacts) {
                foreach ($contacts as $contact) {
                    $contactsDocs[] = [
                        'codice_destinazione' => $contact->getCodiceDestinazione(),
                        'nome' => $contact->getNome(),
                        'cognome' => $contact->getCognome(),
                        'cellulare' => $contact->getCellulare(),
                        'mail' => $contact->getMail(),
                        'codice_agente' => $contact->getCodiceAgente(),
                        'role' => $contact->getRoleId() ? $contact->getRoleId()->getDescription() : ""
                    ];
                }
            }

            $deliveries = $this->entityManager->getRepository(CRM_Deliveries::class)->findBy(['codice_anagrafica' => $customer->getCodiceAnagrafica()]);
            $deliveryDocs = [];
            if ($deliveries) {
                foreach ($deliveries as $delivery) {
                    $deliveryDocs[] = [
                        'codice_destinazione' => $delivery->getCodiceDestinazione(),
                        'descrizione_destinazione' => $delivery->getDescrizioneDestinazione(),
                        'indirizzo' => $delivery->getIndirizzo(),
                        'localita' => $delivery->getLocalita(),
                        'cap' => $delivery->getCap(),
                        'provincia' => $delivery->getProvincia(),
                        'codice_nazione' => $delivery->getCodiceNazione(),
                        'telefono' => $delivery->getTelefono(),
                        'mail' => $delivery->getMail(),
                        'codice_agente' => $delivery->getCodiceAgente(),
                        'note' => $delivery->getNote(),
                    ];
                }
            }

            $customerKey = $customer->getTipologiaAnagrafica()->getId() . "-" . $customer->getCodiceAnagrafica() . "-" . $customer->getCompany();
            $customerProfiles = $this->entityManager->getRepository(CRM_CustomerProfile::class)->findBy(['customer_key' => $customerKey]);
            $customerProfileDocs = [];
            if ($customerProfiles) {
                foreach ($customerProfiles as $customerProfile) {
                    $customerProfileDocs[] = [
                        'fatturato_annuo' => $customerProfile->getFatturatoAnnuo(),
                        'numero_dipendenti' => $customerProfile->getNumeroDipendenti(),
                        'principali_attivita' => $customerProfile->getPrincipaliAttivita(),
                        'gamma_prodotti' => $customerProfile->getGammaProdotti(),
                        'codice_listino' => $customerProfile->getCodiceListino(),
                    ];
                }
            }

            $tasks = $this->entityManager->getRepository(CRM_Tasks::class)->findBy(['customer_key' => $customerKey]);
            $taskDocs = [];
            if ($tasks) {
                foreach ($tasks as $task) {
                    $taskDocs[] = [
                        'task_type' => $task->getIdTipoTask() ? $task->getIdTipoTask()->getDescription() : "",
                        'title' => $task->getTitle(),
                        'description' => $task->getDescription(),
                        'priority' => $task->getPriority(),
                        'date_start' => $task->getDateStart()->format('Y-m-d'),
                        'date_end' => $task->getDateEnd()->format('Y-m-d'),
                        'status' => $task->getStatus(),
                        'all_day' => $task->getAllDay(),
                        'codice_agente' => $task->getCodiceAgente(),

                    ];
                }
            }

            $documents[] = [
                'index' => 'customers',
                'body' => [
                    'customer_code' => $customer->getCodiceAnagrafica(),
                    'customer_description' => $customer->getDescrizioneAnagrafica(),
                    'email' => $customer->getIndirizzoMail(),
                    'city' => $customer->getLocalita(),
                    'province' => $customer->getProvincia(),
                    'country' => $customer->getCodiceNazione(),
                    'bank' => $customer->getBanca(),
                    'tax_code' => $customer->getCodiceFiscale(),
                    'iban' => $customer->getIban(),
                    'phone' => $customer->getTelefono(),
                    'orders' => $orderDocs,
                    'orderRows' => $orderRowsDocs,
                    'contacts' => $contactsDocs,
                    'deliveries' => $deliveryDocs,
                    'profile' => $customerProfileDocs,
                    'tasks' => $taskDocs,
                    'entities' => $entities,
                ]
            ];
        }

        // Fetch and prepare Product documents
        $output->writeln('Products...');
        $products = $this->entityManager->getRepository(PRD_Products::class)->findAll();
        $output->writeln(count($products) . " products found...");

        foreach ($products as $product) {
            $content = implode("', '", [
                'product_code' => $product->getProductCode(),
                'product_name' => $product->getProductName()
            ]);

            $entities = $this->nlpProcessorService->processText($content);
            $documents[] = [
                'index' => 'products',
                'body' => [
                    'product_code' => $product->getProductCode(),
                    'product_name' => $product->getProductName(),
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
