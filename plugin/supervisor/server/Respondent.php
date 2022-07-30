<?php

namespace lx\process\plugin\supervisor\server;

use lx;
use lx\process\ProcessSupervisor;
use lx\HttpResponseInterface;

class Respondent extends \lx\Respondent
{
    private $processSupervisor;

	public function loadProcessesData(): HttpResponseInterface
	{
	    $ps = $this->getProcessSupervisor();
	    $map = $ps->getProcessesData();
		return $this->prepareResponse($map);
	}

	public function addProcess(string $serviceName, string $processName): HttpResponseInterface
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return $this->prepareWarningResponse('Service doesn\'t exist');
        }

        $ps = $this->getProcessSupervisor();
        if (!$ps->checkServiceHasProcess($service, $processName)) {
            return $this->prepareWarningResponse('Service doesn\'t have this process');
        }

        $ps->runServiceProcess($service, $processName);
        return $this->prepareResponse('Ok');
    }

    public function deleteProcess(string $processName, int $processIndex): HttpResponseInterface
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->deleteProcess($processName, $processIndex);
        if (!$result) {
            return $this->prepareWarningResponse('Problem while process stopping');
        }

        return $this->prepareResponse('Ok');
    }

    public function sendMessage(string $processName, int $processIndex, string $message): HttpResponseInterface
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->sendMessageToProcess($processName, $processIndex, $message);
        if (!$result) {
            return $this->prepareWarningResponse('Problem with message sending');
        }

        return $this->prepareResponse('Ok');
    }

    public function rerunProcess(string $processName, int $processIndex): HttpResponseInterface
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->rerunProcess($processName, $processIndex);
        if (!$result) {
            return $this->prepareWarningResponse('Problem with process running');
        }

        return $this->prepareResponse('Ok');
    }

    public function stopProcess(string $processName, int $processIndex): HttpResponseInterface
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
