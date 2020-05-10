<?php

namespace lx\process;

/**
 * Class ProcessMap
 * @package lx\process
 */
class ProcessMap
{
    /** @var array */
    private array $map;

    /**
     * ProcessMap constructor.
     * @param array $map
     */
    public function __construct(array $map)
    {
        $this->map = [
            'name' => [],
            'key' => [],
        ];

        foreach ($map as $row) {
            $this->addRecord($row[0], $row[1], $row[2], $row[3], $row[4]);
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [];
        /** @var ProcessStatusData $statusData */
        foreach ($this->map['key'] as $statusData) {
            $result[] = $statusData->toArray();
        }

        return $result;
    }

    /**
     * @param string $name
     * @return integer
     */
    public function getMaxIndexForProcessName(string $name)
    {
        if (!array_key_exists($name, $this->map['name'])) {
            return 0;
        }

        return max(array_keys($this->map['name'][$name]));
    }

    /**
     * @param ProcessApplication $process
     */
    public function addProcess(ProcessApplication $process)
    {
        $this->addRecord(
            $process->getPid(),
            $process->getServiceName(),
            $process->getName(),
            $process->getIndex()
        );
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     */
    public function removeProcess($processName, $processIndex)
    {
        $key = $this->map['name'][$processName][$processIndex] ?? null;
        if (!$key) {
            return null;
        }

        unset($this->map['key'][$key]);
        unset($this->map['name'][$processName][$processIndex]);
        if (empty($this->map['name'][$processName])) {
            unset($this->map['name'][$processName]);
        }
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @return ProcessStatusData|null
     */
    public function getStatusData($processName, $processIndex)
    {
        $key = $this->map['name'][$processName][$processIndex] ?? null;
        if (!$key) {
            return null;
        }

        if (!array_key_exists($key, $this->map['key'])) {
            return null;
        }

        return $this->map['key'][$key];
    }

    /**
     * @return array
     */
    public function getStatusesData()
    {
        return $this->map['key'];
    }


    /*******************************************************************************************************************
     * PRIVATE
     ******************************************************************************************************************/

    /**
     * @param integer $pid
     * @param string $serviceName
     * @param string $name
     * @param integer $index
     * @param integer $status
     */
    private function addRecord($pid, $serviceName, $name, $index, $status = ProcessConst::PROCESS_STATUS_ACTIVE)
    {
        $key = $name . '_' . $index;
        $this->map['key'][$key] = new ProcessStatusData($pid, $serviceName, $name, $index, $status);

        if (!array_key_exists($name, $this->map['name'])) {
            $this->map['name'][$name] = [];
        }
        $this->map['name'][$name][$index] = $key;
    }
}
