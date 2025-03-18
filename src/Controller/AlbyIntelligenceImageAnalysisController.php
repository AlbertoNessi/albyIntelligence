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
        $sectionName = $request->request->get('sectionName');

        if (!$imageFile) {
            throw new Exception('No image provided');
        }

        $imageContent = file_get_contents($imageFile->getPathname());
        $base64Image = base64_encode($imageContent);
        $dataUrl = 'data:image/jpeg;base64,' . $base64Image;

        try {
            $messages = $this->aiPromptResponseService->generatePromptForImageAnalysis($prompt, $dataUrl, $sectionName);
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
            throw new Exception('Invalid CSRF token');
        }

        $prompt = $request->request->get('prompt', 'Describe the image');

        try {
            $assistant = $this->assistantAPIService->createAssistant();
        } catch (Exception $e) {
            $this->logger->error("Failed to create assistant: " . $e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
            return new JsonResponse(['message' => "Failed to create assistant"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $threadData = $this->assistantAPIService->createThread();
            $threadId = $threadData['id'] ?? null;
            
            if (!$threadId) {
                throw new Exception('Failed to create a new thread');
            }
        } catch (Exception $e) {
            $this->logger->error("Failed to create thread: " . $e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
            return new JsonResponse(['message' => "Failed to create thread"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
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

                try {
                    $this->assistantAPIService->addMessageToThread($threadId, $messages);
                } catch (Exception $e) {
                    $this->logger->error("Failed to add message to thread: " . $e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
                    return new JsonResponse(['message' => "Failed to add message to thread"], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Error processing image: " . $e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
            return new JsonResponse(['message' => "Error processing image"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $response = $this->assistantAPIService->runAssistant($assistant['id'], $threadId);
        } catch (Exception $e) {
            $this->logger->error("Failed to run assistant: " . $e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
            return new JsonResponse(['message' => "Failed to run assistant"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['response' => $response], 200);
    }
}
