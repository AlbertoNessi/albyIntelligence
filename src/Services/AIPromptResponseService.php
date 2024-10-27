<?php

namespace App\Services;

class AIPromptResponseService
{
    public function generateAIPromptResponse(string $userMessage, string $dataText): array
    {
        $promptMessage = "Based on the following data, answer the question in natural language \"{$userMessage}\"\n\n{$dataText}";

        $systemContent = <<<EOD
            You are a helpful assistant who helps the user search for information within a web app. Respond to this user request with the following results in ElasticSearch JSON. Translate these results into natural language and then display the result to the user.

            # Input data

            1. **User request**: {$userMessage}
            2. **ElasticSearch JSON results**:  {$dataText}

            # Steps

            1. **Parse the JSON Data**: Extract necessary fields and values from the ElasticSearch results.
            2. **Answer**: Answer with only the necessary information
            3. **Language and Tone**: Use user-friendly and approachable language, making sure technical terms are explained or simplified.

            # Output Format

            1. As briefly and concisely as possible.
            2. With natural language, not structured output
            3. If required by the context as a well-structured paragraph in natural language.
            4. Do not use arrays or objects in your response.

            # Notes

            - Focus on user-friendly explanations, avoiding technical jargon unless necessary.
            - Handle any missing or null data gracefully without interrupting the narrative flow.
            - Prioritize significant findings or data points that may interest the user.
            - Assuming the data is pre-processed to not include sensitive or irrelevant information.
        EOD;

        return [
            [
                'role' => 'system',
                'content' => $systemContent
            ],
            [
                'role' => 'user',
                'content' => $promptMessage
            ],
        ];
    }
}
