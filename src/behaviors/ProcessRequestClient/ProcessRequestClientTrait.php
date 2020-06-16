<?php

namespace lx\process\behaviors\ProcessRequestClient;

use lx\process\Process;
use lx\process\ProcessSupervisor;

/**
 * Trait ProcessRequestClientTrait
 * @package lx\process\behaviors\ProcessRequestClient
 */
trait ProcessRequestClientTrait
{
    /**
     * @param $action
     * @param array $params
     * @return mixed
     */
    private function callProcessAction($action, $params = [])
    {
        $process = $this->getActiveProcess();
        if (!$process) {
            return false;
        }

        $response = $process->sendRequest(['action' => $action, 'params' => $params]);

        return $response;

    }

    /**
     * @return Process|null
     */
    private function getActiveProcess() : ?Process
    {
        $process = $this->getProcess();
        if ($process && $process->isActive()) {
            return $process;
        }

        return null;
    }
}
