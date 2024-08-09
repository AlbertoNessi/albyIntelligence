<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatGPTService
{
    private string $apiKey;
    private string $apiUrl;
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(string $apiKey, string $apiUrl, HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param array $messages
     * @param bool $jsonMode
     * @return array
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function sendRequest(array $messages, bool $jsonMode = true): array
    {
        $this->logger->info("Entering sendRequest method.");

        $requestData = [
            'messages' => $messages,
            'model' => 'gpt-4o-2024-08-06',
            'temperature' => 0.2,
        ];

        if ($jsonMode) {
            $requestData['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->client->request('POST', $this->apiUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'json' => $requestData,
        ]);

        $content = $response->getContent();

        $this->logger->info("Request sent to ChatGPT API.", ['requestData' => $requestData]);

        $this->logger->info("Response: " . $content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
