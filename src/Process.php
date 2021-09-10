<?php

namespace lx\process;

use lx\Math;
use lx\process\interfaces\ProcessRepositoryInterface;

class Process
{
    private ProcessRepositoryInterface $repository;
    private int $pid;
    private string $serviceName;
    private string $name;
    private int $index;
    private int $statusInMap;
    private int $statusCurrent;

    public function __construct(
        ProcessRepositoryInterface $repository,
        int $pid,
        string $serviceName,
        string $name,
        int $index,
        int $status
    ) {
        $this->repository = $repository;
        $this->pid = $pid;
        $this->serviceName = $serviceName;
        $this->name = $name;
        $this->index = $index;
        $this->statusInMap = $status;
        $this->statusCurrent = $status;
    }

    public function getStatus(): int
    {
        return $this->statusCurrent;
    }

    public function isActive(): bool
    {
        return $this->statusCurrent == ProcessConst::PROCESS_STATUS_ACTIVE;
    }

    public function setStatus(int $status): void
    {
        $this->statusCurrent = $status;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function actualizeCurrentStatus(?array $currentPids = null): void
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

    public function delete(bool $renew = true): bool
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

    public function stop(bool $renew = true): bool
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

    public function rerun(bool $renew = true): bool
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
     */
    public function sendMessage($message): bool
    {
        $this->send([
            ProcessConst::MESSAGE_TYPE_COMMON,
            serialize($message),
        ]);

        return true;
    }

    /**
     * @param mixed $message
     */
    public function sendRequest($message): bool
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

    public function toArray(): array
    {
        return [
            $this->pid,
            $this->serviceName,
            $this->name,
            $this->index,
            $this->statusCurrent
        ];
    }

    public function toHashMap(): array
    {
        return [
            'serviceName' => $this->serviceName,
            'name' => $this->name,
            'index' => $this->index,
            'pid' => $this->pid,
            'status' => $this->statusCurrent,
        ];
    }

    private function sendDirective(int $code): void
    {
        $this->send([
            ProcessConst::MESSAGE_TYPE_SPECIAL,
            $code
        ]);
    }

    private function send(array $data): void
    {
        $this->repository->sendMessageToProcess(
            $this->getName(),
            $this->getIndex(),
            json_encode($data)
        );
    }
}
