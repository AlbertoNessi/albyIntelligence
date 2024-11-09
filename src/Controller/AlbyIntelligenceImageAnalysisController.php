<?php

namespace App\Controller;

use App\Services\AIPromptResponseService;
use App\Services\AlbyIntelligenceAssistantAPIService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Services\ChatGPTService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AlbyIntelligenceImageAnalysisController extends AbstractController
{
    private ChatGPTService $chatGPTService;
    private AlbyIntelligenceAssistantAPIService $assistantAPIService;
    private AIPromptResponseService $aiPromptResponseService;
    private Loggerinterface $logger;

    public function __construct(ChatGPTService $chatGPTService, AlbyIntelligenceAssistantAPIService $assistantAPIService, AIPromptResponseService $aiPromptResponseService, LoggerInterface $logger)
    {
        $this->chatGPTService = $chatGPTService;
        $this->assistantAPIService = $assistantAPIService;
        $this->aiPromptResponseService = $aiPromptResponseService;
        $this->logger = $logger;
    }

    #[Route('/analyze-image', name: 'analyzeImage_url', methods: ['POST'])]
    public function analyzeImage(Request $request): JsonResponse
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('update_index', $token)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid CSRF token.'
            ], Response::HTTP_FORBIDDEN);
        }

        $imageFile = $request->files->get('image');
        $prompt = $request->request->get('prompt', 'Describe the image');

        if (!$imageFile) {
            return new JsonResponse(['error' => 'No image provided'], 400);
        }

        $imageContent = file_get_contents($imageFile->getPathname());
        $base64Image = base64_encode($imageContent);
        $dataUrl = 'data:image/jpeg;base64,' . $base64Image;

        try {
            $messages = $this->aiPromptResponseService->generatePromptForImageAnalysis($prompt, $dataUrl);
            $response = $this->chatGPTService->sendImageRequest($messages);

            return new JsonResponse(['response' => $response], 200);
        } catch (Exception $e) {
            $this->logger->error('Error analyzing image: ' . $e->getMessage());
            return new JsonResponse(['error' => 'An error occurred while processing your request'], 500);
        }
    }

    /**
     * @throws \JsonException
     */
    #[Route('/start_conversation', name: 'startConversation_url', methods: ['POST'])]
    public function startConversation(Request $request): JsonResponse
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('start_conversation', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $prompt = $request->request->get('prompt', 'Describe the image');

        try {
            $assistant = $this->assistantAPIService->createAssistant();
            $threadData = $this->assistantAPIService->createThread();
            $threadId = $threadData['id'] ?? null;

            if (!$threadId) {
                return new JsonResponse(['error' => 'Failed to create a new thread.'], 500);
            }

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $imageContent = file_get_contents($imageFile->getPathname());
                $base64Image = base64_encode($imageContent);
                $dataUrl = 'data:image/jpeg;base64,' . $base64Image;

                $messages = [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $dataUrl
                            ],
                            "detail" => "low"
                        ]
                    ]
                ];

                $this->assistantAPIService->addMessageToThread($threadId, $messages);
            }

            $response = $this->assistantAPIService->runAssistant($assistant['id'], $threadId);

            return new JsonResponse(['response' => $response], 200);
        } catch (Exception|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->logger->error("LINE: " . $e->getLine() . " - MESSAGE: " . $e->getMessage() . " - TRACE: " . $e->getTraceAsString() . " - FILE: " . $e->getFile());

            return new JsonResponse(['message' => "Si è verificato un errore"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
