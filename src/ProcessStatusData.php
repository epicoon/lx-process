<?php

namespace lx\process;

/**
 * Class ProcessStatusData
 * @package lx\process
 */
class ProcessStatusData
{
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
     * ProcessStatusData constructor.
     * @param integer $pid
     * @param string $serviceName
     * @param string $name
     * @param integer $index
     * @param integer $status
     */
    public function __construct($pid, $serviceName, $name, $index, $status)
    {
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
}
