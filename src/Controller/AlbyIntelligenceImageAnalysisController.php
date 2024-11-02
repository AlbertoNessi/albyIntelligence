<?php

namespace App\Controller;

use App\Services\AIPromptResponseService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Services\ChatGPTService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AlbyIntelligenceImageAnalysisController extends AbstractController
{
    private ChatGPTService $chatGPTService;
    private AIPromptResponseService $aiPromptResponseService;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private Loggerinterface $logger;

    public function __construct(ChatGPTService $chatGPTService, AIPromptResponseService $aiPromptResponseService, CsrfTokenManagerInterface $csrfTokenManager, LoggerInterface $logger)
    {
        $this->chatGPTService = $chatGPTService;
        $this->aiPromptResponseService = $aiPromptResponseService;
        $this->csrfTokenManager = $csrfTokenManager;
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
}
