<?php

namespace App\Controller;

use App\Services\AIElasticSearchService;
use App\Services\AIPromptResponseService;
use App\Services\ChatGPTService;
use App\Services\RequestHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SemanticSearchController extends AbstractController
{
    private ChatGPTService $chatGPTService;
    private RequestHandlerService $requestHandlerService;
    private AIElasticSearchService $elasticSearchService;
    private AIPromptResponseService $aiPromptResponseService;
    private LoggerInterface $logger;

    public function __construct(
        ChatGPTService $chatGPTService,
        RequestHandlerService $requestHandlerService,
        AIElasticSearchService $elasticSearchService,
        AIPromptResponseService $aiPromptResponseService,
        LoggerInterface $logger
    ) {
        $this->chatGPTService = $chatGPTService;
        $this->requestHandlerService = $requestHandlerService;
        $this->elasticSearchService = $elasticSearchService;
        $this->aiPromptResponseService = $aiPromptResponseService;
        $this->logger = $logger;
    }

    #[Route('/search_in_semantic_index', name: 'searchInsideSemanticIndex_url', methods: ['POST'])]
    public function searchInsideSemanticIndex(Request $request): JsonResponse
    {
        ini_set('max_execution_time', 240);

        try {
            // Extract parameters and ensure 'message' is valid.
            $parameters = $this->requestHandlerService->getParametersFromRequest($request);
            $message = $parameters['message'] ?? null;
            if (!is_string($message)) {
                return $this->json(['error' => 'Invalid message parameter'], Response::HTTP_BAD_REQUEST);
            }

            $indicesAndFields = $this->getIndicesAndFields();

            $queryType = $this->determineQueryType($message);

            $results = $this->elasticSearchService->search($indicesAndFields, $message, $queryType);

            if ($results['code'] === 'ERROR') {
                return $this->json(['error' => "No data available"], Response::HTTP_NOT_FOUND);
            }

            $dataText = $this->formatResults($results);

            /*dd($dataText);*/
            $prompt = $this->aiPromptResponseService->generateAIPromptResponse($message, $dataText);

            /*dd($prompt);*/
            $response = $this->chatGPTService->sendRequest($prompt);

            $this->logger->info("Dopo sendRequest");

            return new JsonResponse([
                'code' => 'OK',
                'message' => $response
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'code' => 'KO',
                'message' => [
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile()
                ]
            ]);
        }
    }

    /**
     * Returns the indices and fields used for the ElasticSearch query.
     *
     * @return array
     */
    private function getIndicesAndFields(): array
    {
        return [
            'contacts' => ['name', 'surname', 'email', 'phone', 'entities.text'],
            'emails' => ['sender', 'receiver', 'subject', 'message', 'entities', 'entities.text'],
            'events' => ['title', 'subtitle', 'note'],
            'messages' => ['sender', 'message', 'receiver'],
            'notes' => ['note', 'receiver'],
        ];
    }

    /**
     * Determines the query type based on the user's message.
     *
     * @param string $message
     * @return string
     */
    private function determineQueryType(string $message): string
    {
        return preg_match('/show all/i', $message) ? 'match_all' : 'multi_match';
    }

    /**
     * Formats the search results into a human-readable string.
     *
     * @param array $results
     * @return string
     */
    private function formatResults(array $results): string
    {
        $dataText = '';
        foreach ($results['message'] as $result) {
            if (isset($result['_source'])) {
                $dataText .= "Document Data:\n";
                foreach ($result['_source'] as $field => $value) {
                    $fieldName = ucfirst(str_replace('_', ' ', $field));
                    if (is_array($value)) {
                        $dataText .= "$fieldName:\n";
                        foreach ($value as $subValue) {
                            $dataText .= is_array($subValue)
                                ? $this->formatSubValues($subValue)
                                : "  - $subValue\n";
                        }
                    } else {
                        $dataText .= "$fieldName: $value\n";
                    }
                }
                $dataText .= "\n";
            } else {
                $dataText .= "No source data available for this document.\n";
            }
        }
        return $dataText;
    }

    /**
     * Helper method to format nested arrays within the results.
     *
     * @param array $subValues
     * @return string
     */
    private function formatSubValues(array $subValues): string
    {
        $formatted = '';
        foreach ($subValues as $value) {
            $formatted .= "    - $value\n";
        }
        return $formatted;
    }
}
