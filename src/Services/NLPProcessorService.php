<?php
namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class NLPProcessorService
{
    private LoggerInterface $logger;
    private string $projectDir;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    public function processText(string $text): array
    {
        // Updated Python path
        $pythonPath = '/opt/venv/bin/python3'; // Use the correct path from Docker
        $scriptPath = $this->projectDir . '/src/scripts/nlp_processor.py';

        // Initialize the process with arguments as an array
        $process = new Process([$pythonPath, $scriptPath, $text]);

        /*$process->setTimeout(120);*/

        try {
            // Run the process
            $process->mustRun();

            // Get the output
            $output = $process->getOutput();
            $this->logger->info("Python script output: " . $output);

            // Decode JSON
            return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (ProcessFailedException $e) {
            // Log detailed error information
            $this->logger->error("Process failed: " . $e->getMessage());
            $this->logger->error("Error Output: " . $process->getErrorOutput());
            $this->logger->error("Command Output: " . $process->getOutput());
            return [];
        } catch (\JsonException $e) {
            // Log JSON decoding errors
            $this->logger->error("JSON decode error: " . $e->getMessage());
            $this->logger->error("Raw Output: " . $process->getOutput());
            return [];
        }
    }
}
