<?php

namespace App\Controller;

use App\Services\AIElasticSearchService;
use App\Services\AIPromptResponseService;
use App\Services\ChatGPTService;
use App\Services\RequestHandlerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SearchController extends AbstractController
{
    #[Route('/search_in_semantic_index', name: 'searchInsideSemanticIndex_url', methods: ['POST'])]
    public function searchInsideSemanticIndex(
        Request $request,
        ChatGPTService $chatGPTService,
        RequestHandlerService $requestHandlerService,
        AIElasticSearchService $elasticSearchService,
        AIPromptResponseService  $aiPromptResponseService
    ): JsonResponse {
        try {
            $parameters = $requestHandlerService->getParametersFromRequest($request);

            $indicesAndFields = [
                'contacts' => [
                    'name',
                    'surname',
                    'email',
                    'phone',
                    'entities.text'
                ],
                'emails' => [
                    'sender',
                    'receiver',
                    'subject',
                    'message',
                    'entities',
                    'entities.text'
                ],
                'events' => [
                    'title',
                    'subtitle',
                    'note'
                ],
                'messages' => [
                    'sender',
                    'message',
                    'receiver'
                ],
                'notes' => [
                    'note',
                    'receiver'
                ],
            ];

            // Determine the type of query based on user message
            $queryType = 'multi_match';

            if (preg_match('/show all/i', $parameters['message'])) {
                $queryType = 'match_all';
            }

            $results = $elasticSearchService->search($indicesAndFields, $parameters['message'], $queryType);

            if ($results['code'] === 'ERROR') {
                return new JsonResponse(['error' => "Nessun dato disponibile"]);
            }

            // Prepare the dataText string
            $dataText = '';
            foreach ($results['message'] as $result) {
                if (isset($result['_source'])) {
                    $dataText .= "Document Data:\n";
                    foreach ($result['_source'] as $field => $value) {
                        if (is_array($value)) {
                            $dataText .= ucfirst(str_replace('_', ' ', $field)) . ":\n";
                            foreach ($value as $subValue) {
                                if (is_array($subValue)) {
                                    foreach ($subValue as $subFieldValue) {
                                        $dataText .= "  - " . $subFieldValue . "\n";
                                    }
                                } else {
                                    $dataText .= "  - " . $subValue . "\n";
                                }
                            }
                        } else {
                            $dataText .= ucfirst(str_replace('_', ' ', $field)) . ": " . $value . "\n";
                        }
                    }
                    $dataText .= "\n";
                } else {
                    $dataText .= "No source data available for this document.\n";
                }
            }

            $prompt = $aiPromptResponseService->generateAIPromptResponse($parameters['message'], $dataText);
            $response = $chatGPTService->sendRequest($prompt);

            return new JsonResponse($response);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (TransportExceptionInterface $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
