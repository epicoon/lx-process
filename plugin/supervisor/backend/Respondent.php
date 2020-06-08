<?php

namespace lx\process\plugin\supervisor\backend;

use lx\process\ProcessSupervisor;

/**
 * Class Respondent
 * @package lx\process\plugin\supervisor\backend
 */
class Respondent extends \lx\Respondent {
    private $processSupervisor;

    /**
     * @return array
     */
	public function loadProcessesData()
	{
	    $ps = $this->getProcessSupervisor();
	    $map = $ps->getProcessesData();
		return $map;
	}

    /**
     * @param string $serviceName
     * @param string $processName
     * @return array
     */
	public function addProcess($serviceName, $processName)
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

    /**
     * @param string $processName
     * @param integer $processIndex
     * @return array
     */
    public function deleteProcess($processName, $processIndex)
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

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param string $message
     * @return array
     */
    public function sendMessage($processName, $processIndex, $message)
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

    /**
     * @param string $processName
     * @param integer $processIndex
     * @return array
     */
    public function rerunProcess($processName, $processIndex)
    {
        $ps = $this->getProcessSupervisor();
        $result = $ps->rerunProcess($processName, $processIndex);
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

    /**
     * @param string $processName
     * @param integer $processIndex
     * @return array
     */
    public function stopProcess($processName, $processIndex)
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


    /*******************************************************************************************************************
     * PRIVATE
     ******************************************************************************************************************/

    /**
     * @return ProcessSupervisor|mixed
     */
	private function getProcessSupervisor()
    {
        if (!isset($this->processSupervisor)) {
            $this->processSupervisor = $this->app->processSupervisor ?? new ProcessSupervisor();
        }

        return $this->processSupervisor;
    }
}
