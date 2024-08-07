<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Config\DoctrineConfig;

class ChatGPTService
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param array $messages
     * @param bool $jsonMode
     * @return array
     * @throws GuzzleException
     */
    public function sendRequest(DoctrineConfig $doctrine, array $messages, bool $jsonMode = true): array
    {
        $apiKey = $doctrine->dbal()
            ->connection('default')
            ->password(env('OPENAI_API_SEC_KEY'))
        ;

        $apiUrl = 'https://api.openai.com/v1/chat/completions';

        $requestData = [
            'messages' => $messages,
            'model' => 'gpt-4',
            'temperature' => 0.2,
        ];

        if ($jsonMode) {
            $requestData['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->client->post($apiUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'json' => $requestData,
        ]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
