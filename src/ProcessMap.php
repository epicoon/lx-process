<?php

namespace lx\process;

use lx\process\interfaces\ProcessRepositoryInterface;

/**
 * Class ProcessMap
 * @package lx\process
 */
class ProcessMap
{
    /** @var ProcessRepositoryInterface */
    private $repository;

    /** @var array */
    private array $map;

    /**
     * ProcessMap constructor.
     * @param ProcessRepositoryInterface $repository
     * @param array $map
     */
    public function __construct(ProcessRepositoryInterface $repository, array $map)
    {
        $this->repository = $repository;

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
        /** @var Process $process */
        foreach ($this->map['key'] as $process) {
            $result[] = $process->toArray();
        }

        return $result;
    }

    /**
     * @param string $name
     * @return int
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
     * @param int $processIndex
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
     * @param int $processIndex
     * @return Process|null
     */
    public function getProcess($processName, $processIndex) : ?Process
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
     * @return Process[]
     */
    public function getProcesses() : array
    {
        return $this->map['key'];
    }


    /*******************************************************************************************************************
     * PRIVATE
     ******************************************************************************************************************/

    /**
     * @param int $pid
     * @param string $serviceName
     * @param string $name
     * @param int $index
     * @param int $status
     */
    private function addRecord($pid, $serviceName, $name, $index, $status = ProcessConst::PROCESS_STATUS_ACTIVE)
    {
        $key = $name . '_' . $index;
        $this->map['key'][$key] = new Process(
            $this->repository,
            $pid,
            $serviceName,
            $name,
            $index,
            $status
        );

        if (!array_key_exists($name, $this->map['name'])) {
            $this->map['name'][$name] = [];
        }
        $this->map['name'][$name][$index] = $key;
    }
}
