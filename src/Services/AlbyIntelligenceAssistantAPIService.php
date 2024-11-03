<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AlbyIntelligenceAssistantAPIService
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

    public function createThread(string $assistantId): array
    {
        $url = 'https://api.openai.com/v1/assistants/' . $assistantId . '/threads';

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [],
            ]);
        } catch (Exception $exception) {
            $this->logger->error('Error creating thread: ' . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }

        $content = $response->getContent();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function addMessageToThread(string $assistantId, string $threadId, array $messages): array
    {
        $url = 'https://api.openai.com/v1/assistants/' . $assistantId . '/threads/' . $threadId . '/messages';

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => ['messages' => $messages],
            ]);
        } catch (Exception $exception) {
            $this->logger->error('Error adding message to thread: ' . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }

        $content = $response->getContent();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function runAssistant(string $assistantId, string $threadId): array
    {
        $url = 'https://api.openai.com/v1/assistants/' . $assistantId . '/threads/' . $threadId . '/completions';

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [],
            ]);
        } catch (Exception $exception) {
            $this->logger->error('Error running assistant: ' . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }

        $content = $response->getContent();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
