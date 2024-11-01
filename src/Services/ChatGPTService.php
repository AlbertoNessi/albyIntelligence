<?php

namespace App\Services;

use Exception;
use JsonException;
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
     * @throws JsonException
     * @throws Exception
     */
    public function sendRequest(array $messages, bool $jsonMode = true): array
    {
        $requestData = [
            'model' => 'gpt-4o-mini',
            'messages' => $messages
        ];

        if ($jsonMode) {
            $requestData['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = $this->client->request('POST', $this->apiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => $requestData,
            ]);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        $content = $response->getContent();

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function extractResponseContent($response): string
    {
        return $response['choices'][0]['message']['content'];
    }
}
