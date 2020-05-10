<?php

namespace lx\process\interfaces;

use lx\process\ProcessMap;

interface ProcessRepositoryInterface
{
    /**
     * @return ProcessMap
     */
    public function getMap() : ProcessMap;

    public function renew();

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param string $message
     */
    public function sendMessageToProcess($processName, $processIndex, $message);

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param bool $clear
     * @return array
     */
    public function getProcessInputMessages($processName, $processIndex, $clear = false);
}
