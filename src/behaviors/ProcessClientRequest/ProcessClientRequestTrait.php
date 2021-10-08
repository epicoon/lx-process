<?php

namespace lx\process\behaviors\ProcessClientRequest;

use lx\process\Process;
use lx\process\ProcessSupervisor;

trait ProcessClientRequestTrait
{
    /**
     * @return mixed
     */
    private function callProcessAction(string $action, array $params = [])
    {
        $process = $this->getActiveProcess();
        if (!$process) {
            return false;
        }

        $response = $process->sendRequest(['action' => $action, 'params' => $params]);

        return $response;

    }

    private function getActiveProcess() : ?Process
    {
        $process = $this->getProcess();
        if ($process && $process->isActive()) {
            return $process;
        }

        return null;
    }
}
