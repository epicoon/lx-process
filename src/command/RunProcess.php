<?php

namespace lx\process\command;

use lx;
use lx\CommandArgument;
use lx\NativeCommand;
use lx\process\ProcessSupervisor;

class RunProcess extends NativeCommand
{
    public function getName(): string
    {
        return 'process/run';
    }

    protected function defineArguments(): array
    {
        return [
            (CommandArgument::service())->setMandatory(),
            (new CommandArgument())
                ->setKeys(['process', 'p', 1])
                ->setType(CommandArgument::TYPE_STRING)
                ->setMandatory()
                ->setDescription('Process name'),
        ];
    }

    protected function process()
    {
        /** @var ProcessSupervisor $processSupervisor */
        $processSupervisor = lx::$app->processSupervisor;
        if (!$processSupervisor) {
            echo 'Your application must have component "processSupervisor"';
            return;
        }

        $serviceName = $this->params->get('service');
        $processName = $this->params->get('process');

        $service = lx::$app->getService($serviceName);
        if (!$service) {
            echo "Service $serviceName not found" . PHP_EOL;
            return;
        }
        
        if (!$processSupervisor->checkServiceHasProcess($service, $processName)) {
            echo "Service $serviceName does not have the process $processName" . PHP_EOL;
            return;
        }
        
        $processSupervisor->runServiceProcess($service, $processName);
        echo 'Done' . PHP_EOL;
    }
}
