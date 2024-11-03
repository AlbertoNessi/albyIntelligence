<?php

namespace App\Services;

class AIPromptResponseService
{
    public function generateAIPromptResponse(string $userMessage, string $dataText): array
    {
        $systemContent = <<<EOD
            # Context

            The user is using a web app that allow him/her to search for information about it.
            In the app the user can manage its contacts, emails, messages, reminders and so on.

            # Goal

            Answer the user question or request by looking inside the 'ElasticSearch JSON results'

            # Output Format

            Answer with a plain-text natural language phrase in a string.

            # Examples

            ### Example 1

            **User Prompt:** 'Show all contacts Alice has sent an email to'
            **Answer:** 'Bob Smith, Charlie Davis, Ethan Wilson'

            ### Example 2

            **User Prompt:** 'Who Alice sent the most emails to?'
            **Answer:** 'Alice sent the same amount of email to all the people she contacted'

            ### Example 3

            **User Prompt:** 'Show all the data of Alice'
            **Answer:** 'Alice has the following data: name = Alice, surname = Johnson, phone = 555-1234 and email = alice.johnson@example.com'

            # ElasticSearch JSON results

                    $dataText
        EOD;

        return [
            [
                'role' => 'system',
                'content' => $systemContent
            ],
            [
                'role' => 'user',
                'content' => $userMessage
            ],
        ];
    }

    public function generatePromptForElasticSearch($userMessage): array
    {
        $systemContent = <<<EOD
            # Context

            The user is using a web app that allow him/her to search for information about it.
            In the app the user can manage its contacts, emails, messages, reminders and so on.

            # Goal

            Translate the user's prompt into a natural language search query that will be used for the Symfony service to execute a search against the semantic index. Ensure the generated query maximizes its effectiveness without overly-specific filters, as other keys will be used for additional filtering.

            # Steps

            1. **Understand the User Request**: Analyze the user's prompt to understand the desired data, constraints, and overall intent. Identify the main keywords and relevant details to include in the search.

            2. **Form the Query**: Draft the query in a well-constructed natural language format that reflects the information the user is seeking.

            3. **Optimization and Precision**:
               - Maintain a balance between being descriptive and concise.
               - Avoid phrasing that overly restricts the scope of the search when the user hasn't explicitly specified.

            # Output Format

            Provide the resulting query as a plain-text natural language question or request.

            # Examples

            ### Example 1

            **User Prompt:** Find all documents about climate change since 2020
            **Generated Query:** Find documents related to climate change since 2020.

            ### Example 2

            **User Prompt:** Get records where author is John Smith with high priority
            **Generated Query:** Retrieve records written by John Smith that have high priority.

            ### Example 3

            **User Prompt:** Search for recent articles about machine learning in technology index
            **Generated Query:** Search for recent articles about machine learning in the technology index.

            # Notes

            - Maintain a natural language flow that is easily understandable while preserving the intent of the user's query.
            - Ensure the generated query remains suitably general, as additional filters will be handled separately. Use keywords thoughtfully to convey user intent clearly without unnecessary details.
            - Where specific fields or details are absent, default to a general outline of what is being searched.
        EOD;

        return [
            [
                'role' => 'system',
                'content' => $systemContent
            ],
            [
                'role' => 'user',
                'content' => $userMessage
            ],
        ];
    }

    public function generatePromptForImageAnalysis($userMessage, $imagePath): array
    {
        return [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $userMessage,
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $imagePath,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function generatePromptForConversation($userMessage, $imagePath): array
    {
        return [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $userMessage,
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $imagePath,
                        ],
                    ],
                ],
            ],
        ];
    }


}
