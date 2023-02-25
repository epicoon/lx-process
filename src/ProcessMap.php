<?php

namespace lx\process;

use lx\process\interfaces\ProcessRepositoryInterface;
use DateTime;
use DateTimeZone;
use DateTimeImmutable;

class ProcessMap
{
    private ProcessRepositoryInterface $repository;
    private array $map;

    public function __construct(ProcessRepositoryInterface $repository, array $map)
    {
        $this->repository = $repository;

        $this->map = [
            'name' => [],
            'key' => [],
        ];

        foreach ($map as $row) {
            $dateInfo = $row[5];
            $date = DateTime::createFromImmutable(
                new DateTimeImmutable($dateInfo['date'] . ' ' . $dateInfo['timezone'])
            );
            $this->addRecord($row[0], $row[1], $row[2], $row[3], $date, $row[4]);
        }
    }

    public function toArray(): array
    {
        $result = [];
        /** @var Process $process */
        foreach ($this->map['key'] as $process) {
            $result[] = $process->toArray();
        }

        return $result;
    }

    public function getMaxIndexForProcessName(string $name): int
    {
        if (!array_key_exists($name, $this->map['name'])) {
            return 0;
        }

        return max(array_keys($this->map['name'][$name]));
    }

    public function addProcess(ProcessApplication $processApp): void
    {
        $this->addRecord(
            $processApp->getPid(),
            $processApp->getServiceName(),
            $processApp->getName(),
            $processApp->createdAt(),
            $processApp->getIndex()
        );
    }

    public function removeProcess(string $processName, int $processIndex): void
    {
        $key = $this->map['name'][$processName][$processIndex] ?? null;
        if (!$key) {
            return;
        }

        unset($this->map['key'][$key]);
        unset($this->map['name'][$processName][$processIndex]);
        if (empty($this->map['name'][$processName])) {
            unset($this->map['name'][$processName]);
        }
    }

    public function getProcess(string $processName, int $processIndex) : ?Process
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
     * @return array<Process>
     */
    public function getProcesses() : array
    {
        return $this->map['key'];
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function addRecord(
        int $pid,
        string $serviceName,
        string $name,
        int $index,
        DateTime $createdAt,
        int $status = ProcessConst::PROCESS_STATUS_ACTIVE
    ): void
    {
        $key = $name . '_' . $index;
        $this->map['key'][$key] = new Process(
            $this->repository,
            $pid,
            $serviceName,
            $name,
            $index,
            $status,
            $createdAt
        );

        if (!array_key_exists($name, $this->map['name'])) {
            $this->map['name'][$name] = [];
        }
        $this->map['name'][$name][$index] = $key;
    }
}
