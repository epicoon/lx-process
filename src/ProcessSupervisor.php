<?php

namespace lx\process;

use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ApplicationToolTrait;
use lx\Math;
use lx\ObjectTrait;
use lx\process\interfaces\ProcessRepositoryInterface;

/**
 * Class ProcessSupervisor
 * @package lx
 */
class ProcessSupervisor implements FusionComponentInterface
{
    use ObjectTrait;
    use ApplicationToolTrait;
    use FusionComponentTrait;

    /** @var ProcessRepositoryInterface */
    protected ProcessRepositoryInterface $repository;

    public static function getConfigProtocol(): array
    {
        return [
            'repository' => ProcessRepositoryInterface::class,
        ];
    }

    /**
     * @return ProcessRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param ProcessApplication $processApp
     */
    public function register($processApp)
    {
        $map = $this->repository->getMap();
        $maxIndex = $map->getMaxIndexForProcessName($processApp->getName());
        $newIndex = $maxIndex + 1;
        $processApp->setIndex($newIndex);
        $map->addProcess($processApp);
        $this->repository->renew();
    }

    /**
     * @param ProcessApplication $processApp
     */
    public function reborn($processApp)
    {
        $map = $this->repository->getMap();
        $process = $map->getProcess($processApp->getName(), $processApp->getIndex());
        if (!$process) {
            $this->register($processApp);
            return;
        }

        $process->setPid($processApp->getPid());
        $this->repository->renew();
    }

    /**
     * @param bool $renew
     */
    public function actualizeProcessStatuses($renew = true)
    {
        $currentPids = ProcessHelper::getCurrentPids();
        $map = $this->repository->getMap();
        $processes = $map->getProcesses();
        foreach ($processes as $process) {
            $process->actualizeCurrentStatus($currentPids);
        }

        if ($renew) {
            $this->repository->renew();
        }
    }

    /**
     * @param string $processName
     * @param int $processIndex
     * @return Process
     */
    public function getProcess($processName, $processIndex)
    {
        $map = $this->repository->getMap();
        return $map->getProcess($processName, $processIndex);
    }

    /**
     * @param string $serviceName
     * @param string $processName
     * @return array
     */
    public function getServiceProcesses($serviceName, $processName = null)
    {
        $map = $this->repository->getMap();
        $processes = $map->getProcesses();

        $result = [];
        foreach ($processes as $process) {
            if ($process->getServiceName() != $serviceName) {
                continue;
            }

            if ($processName && $process->getName() != $processName) {
                continue;
            }

            $result[] = $process->toHashMap();
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getProcessesData()
    {
        $this->actualizeProcessStatuses();

        $map = $this->repository->getMap();
        $processes = $map->getProcesses();

        $result = [];
        foreach ($processes as $process) {
            $result[] = $process->toHashMap();
        }

        return $result;
    }

    /**
     * @param string $processName
     * @param string $processIndex
     * @param bool $renew
     * @return  bool
     */
    public function deleteProcess($processName, $processIndex, $renew = true)
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->delete($renew);
    }

    /**
     * @param string $processName
     * @param string $processIndex
     * @param bool $renew
     * @return  bool
     */
    public function stopProcess($processName, $processIndex, $renew = true)
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->stop($renew);
    }

    /**
     * @param string $processName
     * @param int $processIndex
     * @param bool $renew
     * @return bool
     */
    public function rerunProcess($processName, $processIndex, $renew = true)
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->rerun($renew);
    }

    /**
     * @param string $processName
     * @param int $processIndex
     * @param mixed $message
     * @return bool
     */
    public function sendMessageToProcess($processName, $processIndex, $message)
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->sendMessage($message);
    }

    /**
     * @param string $processName
     * @param int $processIndex
     * @param mixed $request
     * @return bool
     */
    public function sendRequestToProcess($processName, $processIndex, $request)
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->sendRequest($request);
    }


    /*******************************************************************************************************************
     * SERVICE PUBLIC
     ******************************************************************************************************************/

    /**
     * @param string $processName
     * @param int $processIndex
     * @param string $responseCode
     * @param mixed $message
     */
    public function sendResponseFromProcessApplication($processName, $processIndex, $responseCode, $message)
    {
        $this->getRepository()->sendResponseFromProcess($processName, $processIndex, $responseCode, $message);
    }

    /**
     * @param string $processName
     * @param int $processIndex
     * @param bool $clear
     * @return array
     */
    public function readMessagesForProcessApplication($processName, $processIndex, $clear = false)
    {
        $messages = $this->repository->getProcessInputMessages($processName, $processIndex, $clear);
        $result = [];
        foreach ($messages as $row) {
            $message = json_decode($row, true);
            $messageArray = [
                'type' => $message[0],
                'data' => ($message[0] == ProcessConst::MESSAGE_TYPE_REQUEST
                    || $message[0] == ProcessConst::MESSAGE_TYPE_COMMON
                ) ? unserialize($message[1]) : $message[1],
            ];
            if (isset($message[2])) {
                $messageArray['meta'] = $message[2];
            }
            $result[] = $messageArray;
        }

        return $result;
    }
}
