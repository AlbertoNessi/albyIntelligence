<?php

namespace App\Services;

use Exception;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AlbyIntelligenceAssistantAPIService
{
    private string $apiKey;
    private string $createThreadUrl;
    private string $createAssistantUrl;
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(string $apiKey, string $createThreadUrl, string $createAssistantUrl, HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->apiKey = $apiKey;
        $this->createThreadUrl = $createThreadUrl;
        $this->createAssistantUrl = $createAssistantUrl;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws Exception
     */
    public function createAssistant(): array
    {
        try {
            $response = $this->client->request('POST', $this->createAssistantUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'OpenAI-Beta' => 'assistants=v2'
                ],
                'json' => [
                    'instructions' => 'You are a helpful assistant that helps the user navigate a web app.',
                    'name' => 'Web app assistant',
                    'tools' => [
                        [
                            'type' => 'code_interpreter'
                        ]
                    ],
                    'model' => 'gpt-4o-mini'
                ],
            ]);
        } catch (Exception $exception) {
            $this->logger->error('Error creating assistant: ' . $exception->getMessage());
            throw new Exception($exception->getMessage());
        }

        $content = $response->getContent();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws Exception
     */
    public function createThread(): array
    {
        try {
            $response = $this->client->request('POST', $this->createThreadUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'OpenAI-Beta' => 'assistants=v2'
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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws Exception
     */
    public function addMessageToThread(string $threadId, array $messages): array
    {
        $url = 'https://api.openai.com/v1/threads/' . $threadId . '/messages';

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'OpenAI-Beta' => 'assistants=v2'
                ],
                'json' => $messages,
            ]);

            $content = $response->getContent();
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $exception) {
            $errorContent = $response->getContent(false);
            $this->logger->error('Error adding message to thread: ' . $exception->getMessage() . ' - Response: ' . $errorContent);

            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function runAssistant(string $assistantId, string $threadId): array
    {
        $url = 'https://api.openai.com/v1/threads/' . $threadId . '/runs';

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'OpenAI-Beta' => 'assistants=v2'
            ],
            'json' => [
                "assistant_id" => $assistantId
            ],
        ]);

        $content = $response->getContent();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
