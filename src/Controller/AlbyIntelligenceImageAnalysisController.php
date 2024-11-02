<?php

namespace App\Controller;

use App\Services\AIPromptResponseService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Services\ChatGPTService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


class AlbyIntelligenceImageAnalysisController extends AbstractController
{
    private ChatGPTService $chatGPTService;
    private AIPromptResponseService $aiPromptResponseService;

    public function __construct(ChatGPTService $chatGPTService, AIPromptResponseService $aiPromptResponseService)
    {
        $this->chatGPTService = $chatGPTService;
        $this->aiPromptResponseService = $aiPromptResponseService;
    }

    #[Route('/analyze-image', name: 'analyzeImage_url', methods: ['POST'])]
    public function analyzeImage(Request $request): JsonResponse
    {
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

            return new JsonResponse([
                'code' => '200',
                'response' => $response
            ]);
        } catch (TransportExceptionInterface|Exception $e) {
            return new JsonResponse([
                'code' => $e->getCode(),
                'message' => [
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile()
                ]
            ]);
        }
    }
}
