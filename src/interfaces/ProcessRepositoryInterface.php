<?php

namespace lx\process\interfaces;

use lx\process\ProcessMap;
use lx\process\ProcessResponse;

interface ProcessRepositoryInterface
{
    /**
     * @return ProcessMap
     */
    public function getMap() : ProcessMap;

    public function renew();

    /**
     * @param string $processName
     * @param int $processIndex
     * @param string $message
     */
    public function sendMessageToProcess($processName, $processIndex, $message);

    /**
     * @param string $processName
     * @param int $processIndex
     * @param bool $clear
     * @return array
     */
    public function getProcessInputMessages($processName, $processIndex, $clear = false);

    /**
     * @param string $processName
     * @param int $processIndex
     * @param string $responseCode
     * @param mixed $message
     */
    public function sendResponseFromProcess($processName, $processIndex, $responseCode, $message);

    /**
     * @param string $processName
     * @param int $processIndex
     * @param string $requestCode
     * @return ProcessResponse
     */
    public function getProcessResponse($processName, $processIndex, $requestCode);
}
