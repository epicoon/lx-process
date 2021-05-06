<?php

namespace lx\process\interfaces;

use lx\process\ProcessMap;
use lx\process\ProcessResponse;

interface ProcessRepositoryInterface
{
    public function getMap(): ProcessMap;
    public function renew(): void;

    public function sendMessageToProcess(string $processName, int $processIndex, string $message): void;
    public function getProcessInputMessages(string $processName, int $processIndex, bool $clear = false): array;
    /**
     * @param mixed $message
     */
    public function sendResponseFromProcess(
        string $processName,
        int $processIndex,
        string $responseCode,
        $message
    ): void;
    public function getProcessResponse(string $processName, int $processIndex, string $requestCode): ProcessResponse;
}
