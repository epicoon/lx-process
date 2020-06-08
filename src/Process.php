<?php

namespace lx\process;

use lx\Math;
use lx\process\interfaces\ProcessRepositoryInterface;

/**
 * Class Process
 * @package lx\process
 */
class Process
{
    /** @var ProcessRepositoryInterface */
    private $repository;

    /** @var integer */
    private $pid;

    /** @var string */
    private $serviceName;

    /** @var string */
    private $name;

    /** @var integer */
    private $index;

    /** @var integer */
    private $statusInMap;

    /** @var integer */
    private $statusCurrent;

    /**
     * Process constructor.
     * @param integer $pid
     * @param string $serviceName
     * @param string $name
     * @param integer $index
     * @param integer $status
     */
    public function __construct(ProcessRepositoryInterface $repository, $pid, $serviceName, $name, $index, $status)
    {
        $this->repository = $repository;

        $this->pid = $pid;
        $this->serviceName = $serviceName;
        $this->name = $name;
        $this->index = $index;
        $this->statusInMap = $status;
        $this->statusCurrent = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->statusCurrent;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->statusCurrent == ProcessConst::PROCESS_STATUS_ACTIVE;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->statusCurrent = $status;
    }

    /**
     * @return string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return integer
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param integer $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @param array $currentPids
     */
    public function actualizeCurrentStatus($currentPids = null)
    {
        if ($currentPids === null) {
            $currentPids = ProcessHelper::getCurrentPids();
        }

        if (in_array($this->pid, $currentPids)) {
            $this->statusCurrent = ProcessConst::PROCESS_STATUS_ACTIVE;
        } else {
            if ($this->statusInMap == ProcessConst::PROCESS_STATUS_ACTIVE) {
                $this->statusCurrent = ProcessConst::PROCESS_STATUS_CRASHED;
            } else {
                $this->statusCurrent = $this->statusInMap;
            }
            $this->pid = 0;
        }
    }

    /**
     * @param bool $renew
     * @return  bool
     */
    public function delete($renew = true)
    {
        $this->actualizeCurrentStatus();
        if ($this->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
            $this->stop(false);
            if ($this->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
                return false;
            }
        }

        $this->repository->getMap()->removeProcess($this->getName(), $this->getIndex());
        if ($renew) {
            $this->repository->renew();
        }

        return true;
    }

    /**
     * @param bool $renew
     * @return  bool
     */
    public function stop($renew = true)
    {
        $this->actualizeCurrentStatus();
        if ($this->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
            $triesLimit = 10;
            $triesCounter = 0;
            $this->sendDirective(ProcessConst::DIRECTIVE_STOP);
            while ($this->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE && $triesCounter < $triesLimit) {
                sleep(1);
                $this->actualizeCurrentStatus();
                $triesCounter++;
            }

            if ($this->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
                return false;
            }
        }

        if ($renew) {
            $this->setStatus(ProcessConst::PROCESS_STATUS_CLOSED);
            $this->repository->renew();
        }

        return true;
    }

    /**
     * @param bool $renew
     * @return bool
     */
    public function rerun($renew = true)
    {
        $this->actualizeCurrentStatus();
        if ($this->getStatus() == ProcessConst::PROCESS_STATUS_ACTIVE) {
            return false;
        }

        $service = \lx::$app->getService($this->getServiceName());
        if (!$service) {
            return false;
        }

        $service->runProcess($this->getName(), $this->getIndex());
        return true;
    }

    /**
     * @param mixed $message
     * @return bool
     */
    public function sendMessage($message)
    {
        $this->send([
            ProcessConst::MESSAGE_TYPE_COMMON,
            serialize($message),
        ]);

        return true;
    }

    /**
     * @param mixed $message
     * @return bool
     */
    public function sendRequest($message)
    {
        $requestCode = Math::randHash();

        $this->send([
            ProcessConst::MESSAGE_TYPE_REQUEST,
            serialize($message),
            $requestCode
        ]);

        $triesLimit = 10;
        $triesCounter = 0;
        $response = new ProcessResponse();
        while ($response->isEmpty() && $triesCounter < $triesLimit) {
            sleep(1);
            $response = $this->repository->getProcessResponse($this->getName(), $this->getIndex(), $requestCode);
            $triesCounter++;
        }

        return $response->getData();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            $this->pid,
            $this->serviceName,
            $this->name,
            $this->index,
            $this->statusCurrent
        ];
    }

    /**
     * @return array
     */
    public function toHashMap()
    {
        return [
            'serviceName' => $this->serviceName,
            'name' => $this->name,
            'index' => $this->index,
            'pid' => $this->pid,
            'status' => $this->statusCurrent,
        ];
    }

    /**
     * @param integer $code
     */
    private function sendDirective($code)
    {
        $this->send([
            ProcessConst::MESSAGE_TYPE_SPECIAL,
            $code
        ]);
    }

    /**
     * @param array $data
     */
    private function send($data)
    {
        $this->repository->sendMessageToProcess(
            $this->getName(),
            $this->getIndex(),
            json_encode($data)
        );
    }
}
