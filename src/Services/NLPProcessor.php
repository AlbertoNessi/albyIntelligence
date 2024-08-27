<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class NLPProcessor
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws \JsonException
     */
    public function processText(string $text): array
    {
        $command = "python3 src/scripts/nlp_processor.py " . escapeshellarg($text);
        $output = shell_exec($command);

        if ($output === null) {
            $this->logger->error("Failed to execute command: $command");
            return [];
        }

        $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error("JSON decode error: " . json_last_error_msg());
            return [];
        }

        return $result;
    }
}
