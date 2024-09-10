<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class AIElasticSearchService
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['http://elasticsearch:9200'])
            ->build();
    }

    public function search(array $indicesAndFields, string $query): array
    {
        $indexNames = array_keys($indicesAndFields);
        $fields = array_values($indicesAndFields);

        $params = [
            'index' => implode(',', $indexNames),
            'body'  => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => array_merge(...$fields),
                        'type' => 'best_fields',
                    ]
                ]
            ]
        ];

        $response = $this->client->search($params);

        // Return the search hits
        return $response['hits']['hits'] ?? [];
    }
}
