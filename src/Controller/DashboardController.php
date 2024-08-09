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
    public function chatgptRequest(Request $request, ChatGPTService $chatGPTService, RequestHandlerService $requestHandlerService): JsonResponse
    {
        $parameters = $requestHandlerService->getParametersFromRequest($request);
        $prompt = [
            [
                'role' => 'system',
                'content' => "
                    You are an intelligent assistant integrated into a web application built with Symfony.
                    Your primary function is to help users retrieve and manage data from the following entities within the application:

                    - Messages: Handle all user communications stored within the system.
                    - Contacts: Manage and retrieve contact information stored within the application.
                    - Emails: Access and manage email records stored in the system.
                    - Events: Retrieve and manage events, including titles, dates, locations, and participants.
                    - Notes: Manage and retrieve user-created notes, including content, tags, and creation dates.
                    - Reminders: Manage reminders, including due dates, priorities, and associated tasks.
                    - Calendar: Handle calendar events, including integration with external calendars.
                    - Tasks: Manage tasks, including due dates, priorities, and statuses.
                    - Notifications: Provide details about system or app notifications, including unread notifications and associated actions.
                    - Locations: Retrieve and manage location-based data, including saved locations and recent places.
                    - Files and Documents: Handle files and documents stored within the system, including searching and retrieving files.
                    - Search History: Retrieve and manage the user's search history within the application for quick access to previous queries.

                    Users may ask you to perform specific queries related to these entities using a semantic index.
                    When responding to queries related to these entities, you must provide the response in JSON format.
                    The JSON should contain structured data that can be directly used by the application to display or further process the requested information.

                    Example JSON format:
                    {
                        \"result\": \"***the actual result***\"
                    }

                    In cases where the user's query falls outside the scope of these entities, generate a concise and informative natural language response. If you lack sufficient context or data to respond accurately, ask clarifying questions or gently suggest alternative queries. Always ensure the response is aligned with the user's intent and expectations.

                    Example formats:
                    - If the query is about an unknown entity or beyond the system's knowledge, use a response such as:
                        \"I'm currently only able to assist with Messages, Contacts, Emails, Events, Notes, and similar entities. Could you please refine your question?\"
                    - If the query requires external knowledge or context beyond the web application, provide an informative yet cautious response, ensuring no hallucination:
                        \"Based on the information available, I believe the best approach might be... However, please verify with additional sources.\"

                    Be concise, direct, and helpful, maintaining a professional tone. When possible, summarize complex information and present it in a digestible format. Avoid unnecessary details unless specifically requested by the user. Ensure every output is well-structured and actionable.

                    Remember, your priority is to assist with the Messages, Contacts, Emails, Events, Notes, Reminders, Calendar, Tasks, Notifications, Locations, Files, and Search History entities first, but you are capable of handling a wide range of general queries if required.
                    Ensure your responses are clear, concise, and helpful.",
            ],
            [
                'role' => 'user',
                'content' => $parameters['message']
            ],
        ];

        try {
            $response = $chatGPTService->sendRequest($prompt);

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
