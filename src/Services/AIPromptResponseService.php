<?php

namespace App\Services;

class AIPromptResponseService
{
    public function generateAIPromptResponse(string $userMessage, string $dataText): array
    {
        $systemContent = <<<EOD
            You are a helpful assistant who helps the user search for information within a web app. Respond to the user request with the following results in ElasticSearch JSON. Translate these results into natural language.

            # ElasticSearch JSON results
            ## Start of ElasticSearch JSON results
            $dataText
            ## End of ElasticSearch JSON results
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
}
