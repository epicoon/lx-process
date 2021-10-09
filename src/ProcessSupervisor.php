<?php

namespace lx\process;

use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\Math;
use lx\process\interfaces\ProcessRepositoryInterface;

class ProcessSupervisor implements FusionComponentInterface
{
    use FusionComponentTrait;

    protected ProcessRepositoryInterface $repository;

    public static function getDependenciesConfig(): array
    {
        return [
            'repository' => ProcessRepositoryInterface::class,
        ];
    }

    public function getRepository(): ProcessRepositoryInterface
    {
        return $this->repository;
    }

    public function register(ProcessApplication $processApp): void
    {
        $map = $this->repository->getMap();
        $maxIndex = $map->getMaxIndexForProcessName($processApp->getName());
        $newIndex = $maxIndex + 1;
        $processApp->setIndex($newIndex);
        $map->addProcess($processApp);
        $this->repository->renew();
    }

    public function reborn(ProcessApplication $processApp): void
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

    public function actualizeProcessStatuses(bool $renew = true): void
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

    public function getProcess(string $processName, int $processIndex): ?Process
    {
        $map = $this->repository->getMap();
        return $map->getProcess($processName, $processIndex);
    }

    public function getServiceProcesses(string $serviceName, ?string $processName = null): array
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

    public function getProcessesData(): array
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

    public function deleteProcess(string $processName, string $processIndex, bool $renew = true): bool
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->delete($renew);
    }

    public function stopProcess(string $processName, string $processIndex, bool $renew = true): bool
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->stop($renew);
    }

    public function rerunProcess(string $processName, int $processIndex, bool $renew = true): bool
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->rerun($renew);
    }

    /**
     * @param mixed $message
     */
    public function sendMessageToProcess(string $processName, int $processIndex, $message): bool
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->sendMessage($message);
    }

    /**
     * @param mixed $request
     */
    public function sendRequestToProcess(string $processName, int $processIndex, $request): bool
    {
        $process = $this->getProcess($processName, $processIndex);
        if (!$process) {
            return false;
        }

        return $process->sendRequest($request);
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
     * SERVICE PUBLIC
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param mixed $message
     */
    public function sendResponseFromProcessApplication(
        string $processName,
        int $processIndex,
        string $responseCode,
        $message
    ): void
    {
        $this->getRepository()->sendResponseFromProcess($processName, $processIndex, $responseCode, $message);
    }

    public function readMessagesForProcessApplication(
        string $processName,
        int $processIndex,
        bool $clear = false
    ): array
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
