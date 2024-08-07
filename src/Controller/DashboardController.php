<?php

namespace App\Controller;

use App\Services\ChatGPTService;
use App\Services\RequestHandlerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard_url')]
    public function dashboard() : Response
    {
        return $this->render('main/dashboard.html.twig', ['table_id' => '0']);
    }

    #[Route('/chatgpt_request', name: 'chatgpt_request_url', methods: ['POST'])]
    public function chatgptRequest(Request $request, ChatGPTService $chatGPTService, RequestHandlerService $requestHandlerService): JsonResponse
    {
        $parameters = $requestHandlerService->getParametersFromRequest($request->getContent());
        $messages = $parameters['messages'];

        try {
            $response = $chatGPTService->sendRequest($messages);
            return new JsonResponse(['content' => $response['choices'][0]['message']['content']], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
