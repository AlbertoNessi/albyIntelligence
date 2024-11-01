<?php

namespace App\Controller;

use App\Services\AIElasticSearchService;
use App\Services\AIPromptResponseService;
use App\Services\ChatGPTService;
use App\Services\RequestHandlerService;
use App\Services\SemanticIndexService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SemanticSearchController extends AbstractController
{
    private ChatGPTService $chatGPTService;
    private RequestHandlerService $requestHandlerService;
    private AIElasticSearchService $elasticSearchService;
    private AIPromptResponseService $aiPromptResponseService;
    private LoggerInterface $logger;
    private semanticIndexService $semanticIndexService;

    public function __construct(
        ChatGPTService $chatGPTService,
        RequestHandlerService $requestHandlerService,
        AIElasticSearchService $elasticSearchService,
        AIPromptResponseService $aiPromptResponseService,
        LoggerInterface $logger,
        SemanticIndexService $semanticIndexService
    ) {
        $this->chatGPTService = $chatGPTService;
        $this->requestHandlerService = $requestHandlerService;
        $this->elasticSearchService = $elasticSearchService;
        $this->aiPromptResponseService = $aiPromptResponseService;
        $this->logger = $logger;
        $this->semanticIndexService = $semanticIndexService;
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

            // GENERATE REQUEST
            $generatedPromptForElasticSearch = $this->aiPromptResponseService->generatePromptForElasticSearch($message);
            $promptForElasticSearch = $this->chatGPTService->sendRequest($generatedPromptForElasticSearch, false);
            $extractedPromptForElasticSearch = $this->chatGPTService->extractResponseContent($promptForElasticSearch);
            $queryType = $this->determineQueryType($message);
            $indicesAndFields = $this->semanticIndexService->getIndicesAndFields();

            $results = $this->elasticSearchService->search($indicesAndFields, $extractedPromptForElasticSearch, $queryType);

            if ($results['code'] === 'ERROR') {
                return $this->json(['error' => "No data available"], Response::HTTP_NOT_FOUND);
            }

            // GENERATE RESPONSE
            $dataText = $this->formatResults($results);
            $prompt = $this->aiPromptResponseService->generateAIPromptResponse($message, $dataText);
            $response = $this->chatGPTService->sendRequest($prompt, false);
            $extractedResponse = $this->chatGPTService->extractResponseContent($response);

            return new JsonResponse([
                'code' => 'OK',
                'user_query' => $message,
                'message' => $extractedResponse
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'code' => 'KO',
                'message' => [
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile()
                ]
            ]);
        } catch (TransportExceptionInterface $e) {
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
