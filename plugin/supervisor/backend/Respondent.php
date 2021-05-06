<?php

namespace lx\process\plugin\supervisor\backend;

use lx\process\ProcessSupervisor;

class Respondent extends \lx\Respondent {
    private $processSupervisor;

	public function loadProcessesData(): array
	{
	    $ps = $this->getProcessSupervisor();
	    $map = $ps->getProcessesData();
		return $map;
	}

	public function addProcess(string $serviceName, string $processName): array
    {
        $service = $this->app->getService($serviceName);
        if (!$service) {
            return [
                'success' => false,
                'message' => 'Service doesn\'t exist'
            ];
        }

        if (!$service->hasProcess($processName)) {
            return [
                'success' => false,
                'message' => 'Service doesn\'t have this process'
            ];
        }

        $service->runProcess($processName);

        return [
            'success' => true,
        ];
    }

    public function deleteProcess(string $processName, int $processIndex): array
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->deleteProcess($processName, $processIndex);
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Problem while process stopping',
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function sendMessage(string $processName, int $processIndex, string $message): array
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->sendMessageToProcess($processName, $processIndex, $message);
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Problem with message sending',
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function rerunProcess(string $processName, int $processIndex): array
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->rerunProcess($processName, $processIndex);
        if (!$result) {
            return $this->prepareErrorResponse('Problem with process running');
        }

        return [
            'success' => true,
        ];
    }

    public function stopProcess(string $processName, int $processIndex): array
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->stopProcess($processName, $processIndex);
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Problem with process running',
            ];
        }

        return [
            'success' => true,
        ];
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function getProcessSupervisor(): ?ProcessSupervisor
    {
        if (!isset($this->processSupervisor)) {
            $this->processSupervisor = $this->app->processSupervisor ?? new ProcessSupervisor();
        }

        return $this->processSupervisor;
    }
}
