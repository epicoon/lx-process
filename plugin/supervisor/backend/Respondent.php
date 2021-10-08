<?php

namespace lx\process\plugin\supervisor\backend;

use lx;
use lx\process\ProcessSupervisor;
use lx\ResponseInterface;

class Respondent extends \lx\Respondent
{
    private $processSupervisor;

	public function loadProcessesData(): ResponseInterface
	{
	    $ps = $this->getProcessSupervisor();
	    $map = $ps->getProcessesData();
		return $this->prepareResponse($map);
	}

	public function addProcess(string $serviceName, string $processName): ResponseInterface
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return $this->prepareWarningResponse('Service doesn\'t exist');
        }

        if (!$service->hasProcess($processName)) {
            return $this->prepareWarningResponse('Service doesn\'t have this process');
        }

        $service->runProcess($processName);
        return $this->prepareResponse('Ok');
    }

    public function deleteProcess(string $processName, int $processIndex): ResponseInterface
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->deleteProcess($processName, $processIndex);
        if (!$result) {
            return $this->prepareWarningResponse('Problem while process stopping');
        }

        return $this->prepareResponse('Ok');
    }

    public function sendMessage(string $processName, int $processIndex, string $message): ResponseInterface
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->sendMessageToProcess($processName, $processIndex, $message);
        if (!$result) {
            return $this->prepareWarningResponse('Problem with message sending');
        }

        return $this->prepareResponse('Ok');
    }

    public function rerunProcess(string $processName, int $processIndex): ResponseInterface
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->rerunProcess($processName, $processIndex);
        if (!$result) {
            return $this->prepareWarningResponse('Problem with process running');
        }

        return $this->prepareResponse('Ok');
    }

    public function stopProcess(string $processName, int $processIndex): ResponseInterface
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->stopProcess($processName, $processIndex);
        if (!$result) {
            return $this->prepareWarningResponse('Problem with process running');
        }

        return $this->prepareResponse('Ok');
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function getProcessSupervisor(): ?ProcessSupervisor
    {
        if (!isset($this->processSupervisor)) {
            $this->processSupervisor = lx::$app->processSupervisor ?? new ProcessSupervisor();
        }

        return $this->processSupervisor;
    }
}
