<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class AIElasticSearchService
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['http://elasticsearch:9200'])
            ->build();
    }

    /**
     * Executes a search query against Elasticsearch.
     *
     * @param array  $indicesAndFields Associative array where keys are index names and values are arrays of fields to search.
     * @param string $query            The search query string.
     * @param string $queryType        The type of Elasticsearch query ('multi_match' or 'match_all').
     *
     * @return array The search hits from Elasticsearch.
     */
    public function search(array $indicesAndFields, string $query, string $queryType = 'multi_match'): array
    {
        $indexNames = array_keys($indicesAndFields);
        $fields = array_values($indicesAndFields);

        if ($queryType === 'match_all') {
            $params = [
                'index' => implode(',', $indexNames),
                'body'  => [
                    'query' => [
                        'match_all' => (object)[]
                    ]
                ]
            ];
        } elseif ($queryType === 'multi_match') {
            $params = [
                'index' => implode(',', $indexNames),
                'body'  => [
                    'query' => [
                        'multi_match' => [
                            'query'  => $query,
                            'fields' => array_merge(...$fields),
                            'type'   => 'best_fields',
                        ]
                    ]
                ]
            ];
        } else {
            throw new \InvalidArgumentException("Unsupported query type: {$queryType}");
        }

        try {
            $response = $this->client->search($params);

            return [
                'code' => 'OK',
                'message' => $response['hits']['hits'] ?? []
            ];
        } catch (ClientResponseException|ServerResponseException $e) {
            return [
                'code' => 'ERROR',
                'message' => 'Si è verificato un errore: ' . $e->getMessage() . " on line: " . $e->getLine()
            ];
        }
    }
}
