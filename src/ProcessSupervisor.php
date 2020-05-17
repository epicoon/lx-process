<?php

namespace lx\process;

use lx\BaseObject;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ApplicationToolTrait;
use lx\Math;
use lx\process\interfaces\ProcessRepositoryInterface;

/**
 * Class ProcessSupervisor
 * @package lx
 */
class ProcessSupervisor extends BaseObject implements FusionComponentInterface
{
    use ApplicationToolTrait;
    use FusionComponentTrait;

    /** @var ProcessRepositoryInterface */
    protected ProcessRepositoryInterface $repository;

    /**
     * ProcessSupervisor constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * @return array
     */
    public static function getConfigProtocol()
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
     * @param ProcessApplication $process
     */
    public function register($process)
    {
        $map = $this->repository->getMap();
        $maxIndex = $map->getMaxIndexForProcessName($process->getName());
        $newIndex = $maxIndex + 1;
        $process->setIndex($newIndex);
        $map->addProcess($process);
        $this->repository->renew();
    }

    /**
     * @param ProcessApplication $process
     */
    public function reborn($process)
    {
        $map = $this->repository->getMap();
        $statusData = $map->getStatusData($process->getName(), $process->getIndex());
        if (!$statusData) {
            $this->register($process);
            return;
        }

        $statusData->setPid($process->getPid());
        $this->repository->renew();
    }

    /**
     * @param bool $renew
     */
    public function actualizeProcessStatuses($renew = true)
    {
        $currentPids = ProcessHelper::getCurrentPids();
        $map = $this->repository->getMap();
        $statusesData = $map->getStatusesData();
        /** @var ProcessStatusData $statusData */
        foreach ($statusesData as $statusData) {
            $statusData->actualizeCurrentStatus($currentPids);
        }

        if ($renew) {
            $this->repository->renew();
        }
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @return ProcessStatusData
     */
    public function getProcessStatus($processName, $processIndex)
    {
        $map = $this->repository->getMap();
        return $map->getStatusData($processName, $processIndex);
    }

    /**
     * @param string $serviceName
     * @param string $processName
     * @return array
     */
    public function getProcessStatusesForService($serviceName, $processName = null)
    {
        $map = $this->repository->getMap();
        $statusesData = $map->getStatusesData();

        $result = [];
        /** @var ProcessStatusData $statusData */
        foreach ($statusesData as $statusData) {
            if ($statusData->getServiceName() != $serviceName) {
                continue;
            }

            if ($processName && $statusData->getName() != $processName) {
                continue;
            }

            $result[] = $statusData->toHashMap();
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getProcessStatuses()
    {
        $this->actualizeProcessStatuses();

        $map = $this->repository->getMap();
        $statusesData = $map->getStatusesData();

        $result = [];
        /** @var ProcessStatusData $statusData */
        foreach ($statusesData as $statusData) {
            $result[] = $statusData->toHashMap();
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
        $map = $this->repository->getMap();
        $statusData = $map->getStatusData($processName, $processIndex);
        if (!$statusData) {
            return false;
        }

        $statusData->actualizeCurrentStatus();
        if ($statusData->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
            $this->stopProcess($processName, $processIndex, false);
            if ($statusData->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
                return false;
            }
        }

        $map->removeProcess($processName, $processIndex);
        if ($renew) {
            $this->repository->renew();
        }

        return true;
    }

    /**
     * @param string $processName
     * @param string $processIndex
     * @param bool $renew
     * @return  bool
     */
    public function stopProcess($processName, $processIndex, $renew = true)
    {
        $map = $this->repository->getMap();
        $statusData = $map->getStatusData($processName, $processIndex);
        if (!$statusData) {
            return false;
        }

        $statusData->actualizeCurrentStatus();
        if ($statusData->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
            $triesLimit = 10;
            $triesCounter = 0;
            $this->sendDirectiveToProcess($processName, $processIndex, ProcessConst::DIRECTIVE_STOP);
            while ($statusData->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE && $triesCounter < $triesLimit) {
                sleep(1);
                $statusData->actualizeCurrentStatus();
                $triesCounter++;
            }

            if ($statusData->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
                return false;
            }
        }

        if ($renew) {
            $statusData->setStatus(ProcessConst::PROCESS_STATUS_CLOSED);
            $this->repository->renew();
        }

        return true;
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param bool $renew
     * @return bool
     */
    public function rerunProcess($processName, $processIndex, $renew = true)
    {
        $map = $this->repository->getMap();
        $statusData = $map->getStatusData($processName, $processIndex);
        if (!$statusData) {
            return false;
        }

        $statusData->actualizeCurrentStatus();
        if ($statusData->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
            return false;
        }

        $service = $this->app->getService($statusData->getServiceName());
        if (!$service) {
            return false;
        }

        $service->runProcess($processName, $processIndex);
        return true;
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param integer $code
     */
    public function sendDirectiveToProcess($processName, $processIndex, $code)
    {
        $this->send($processName, $processIndex, [
            ProcessConst::MESSAGE_TYPE_SPECIAL,
            $code
        ]);
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param mixed $message
     * @return bool
     */
    public function sendMessageToProcess($processName, $processIndex, $message)
    {
        $this->send($processName, $processIndex, [
            ProcessConst::MESSAGE_TYPE_COMMON,
            serialize($message),
        ]);

        return true;
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param mixed $message
     * @return bool
     */
    public function sendRequestToProcess($processName, $processIndex, $message)
    {
        $requestCode = Math::randHash();

        $this->send($processName, $processIndex, [
            ProcessConst::MESSAGE_TYPE_REQUEST,
            serialize($message),
            $requestCode
        ]);

        $triesLimit = 10;
        $triesCounter = 0;
        $response = new ProcessResponse();
        while ($response->isEmpty() && $triesCounter < $triesLimit) {
            sleep(1);
            $response = $this->getRepository()->getProcessResponse($processName, $processIndex, $requestCode);
            $triesCounter++;
        }

        return $response->getData();
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param string $responseCode
     * @param mixed $message
     */
    public function sendResponseFromProcess($processName, $processIndex, $responseCode, $message)
    {
        $this->getRepository()->sendResponseFromProcess($processName, $processIndex, $responseCode, $message);
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param bool $clear
     * @return array
     */
    public function getProcessInputMessages($processName, $processIndex, $clear = false)
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


    /*******************************************************************************************************************
     * PRIVATE
     ******************************************************************************************************************/

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param array $data
     */
    private function send($processName, $processIndex, $data)
    {
        $this->repository->sendMessageToProcess($processName, $processIndex, json_encode($data));
    }
}
