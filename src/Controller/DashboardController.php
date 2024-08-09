<?php

namespace App\Controller;

use App\Services\ChatGPTService;
use App\Services\RequestHandlerService;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard_url')]
    public function dashboard(): Response
    {
        return $this->render('main/dashboard.html.twig', ['table_id' => '0']);
    }

    #[Route('/chatgpt_request', name: 'chatgpt_request_url', methods: ['POST'])]
    public function chatgptRequest(Request $request, ChatGPTService $chatGPTService, RequestHandlerService $requestHandlerService)
    {
        $parameters = $requestHandlerService->getParametersFromRequest($request);
        $prompt = [
            [
                'role' => 'system',
                'content' => "You are a useful assistant that allows the user to retrieve data about its web application from a semantic index. You will be provided with the user query and all the necessary data coming from the semantic index. Answer with JSON format",
            ],
            [
                'role' => 'user',
                'content' => $parameters['message']
            ],
        ];

        try {
            $response = $chatGPTService->sendRequest($prompt);

           /* $responseText = "No response from the API";
            if ($response) {
                $responseText = $response['choices'][0]['message']['content'];
            }*/

            return new JsonResponse($response);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (TransportExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[NoReturn] #[Route('/test', name: 'test_url')]
    public function testHttpClient(HttpClientInterface $client)
    {
        $response = $client->request('GET', 'https://httpbin.org/get');
        $content = $response->getContent();

        dd($content); // Debug and see the response content
    }

}
